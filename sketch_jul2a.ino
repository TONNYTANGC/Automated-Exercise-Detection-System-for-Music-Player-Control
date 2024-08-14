#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <Wire.h>
#include "MAX30105.h"
#include "spo2_algorithm.h"
#include <LiquidCrystal_I2C.h>
#include <ArduinoJson.h>

MAX30105 particleSensor;

#define MAX_BRIGHTNESS 255
LiquidCrystal_I2C lcd(0x27, 16, 2);
uint32_t irBuffer[100];   // infrared LED sensor data
uint32_t redBuffer[100];  // red LED sensor data

int32_t bufferLength;   // data length
int32_t spo2;           // SPO2 value
int8_t validSPO2;       // indicator to show if the SPO2 calculation is valid
int32_t heartRate;      // heart rate value
int8_t validHeartRate;  // indicator to show if the heart rate calculation is valid

static double fbpmrate = 0.95;       // low pass filter coefficient for HRM in bpm
static uint32_t crosstime = 0;       // falling edge, zero crossing time in msec
static uint32_t crosstime_prev = 0;  // previous falling edge, zero crossing time in msec
static double bpm = 40.0;
static double ebpm = 40.0;
static double eir = 0.0;       // estimated lowpass filtered IR signal to find falling edge without notch
static double firrate = 0.85;  // IR filter coefficient to remove notch, should be smaller than frate
static double eir_prev = 0.0;

double frate = 0.95;
double avered = 0;
double aveir = 0;
double sumirrms = 0;
double sumredrms = 0;

byte pulseLED = 2;  // Onboard LED on ESP8266
byte readLED = 0;   // Onboard LED on ESP8266

unsigned long lastUpdate = 0;                // Last time the readings were updated
const unsigned long updateInterval = 10000;  // 10 seconds

// Network credentials
const char* ssid = "Tonny WiFi";
const char* password = "chiewlih82761401";

// Server IP and PHP script URL
const char* serverName = "http://172.20.10.3/SKIH3113_Final/post.php";
WiFiClient client;
// API key
String apiKeyValue = "tPmAT5Ab3j7F9";

// GPIO pin for relay
const int relayPin = D5;  // example GPIO pin number

#define FINGER_ON 50000                   // if ir signal is lower than this, it indicates your finger is not on the sensor
unsigned long fingerNotDetectedTime = 0;  // Time when finger is not detected
bool fingerDetected = true;               // Flag to check if the finger is detected

void setup() {
  Serial.begin(115200);  // initialize serial communication at 115200 bits per second:

  lcd.init();
  lcd.backlight();

  pinMode(pulseLED, OUTPUT);
  pinMode(readLED, OUTPUT);
  pinMode(relayPin, OUTPUT);
  digitalWrite(relayPin, LOW);  // Ensure relay is off initially

  // Initialize Wire library with custom I2C pins
  Wire.begin();  // SDA, SCL
  // Initialize sensor
  if (!particleSensor.begin(Wire, I2C_SPEED_FAST)) {  // Use default I2C port, 400kHz speed
    Serial.println(F("MAX30105 was not found. Please check wiring/power."));
    while (1)
      ;
  }
  lcd.setCursor(0, 0);
  lcd.print("   Heart Rate   ");
  lcd.setCursor(0, 1);
  lcd.print("     Monitor    ");
  delay(1000);

  // Resetting WiFi module
  WiFi.disconnect(true);
  delay(1000);

  WiFi.begin(ssid, password);
  Serial.println("Connecting");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("");
  Serial.print("Connected to WiFi network with IP Address: ");
  Serial.println(WiFi.localIP());

  byte ledBrightness = 60;  // Options: 0=Off to 255=50mA
  byte sampleAverage = 4;   // Options: 1, 2, 4, 8, 16, 32
  byte ledMode = 2;         // Options: 1 = Red only, 2 = Red + IR, 3 = Red + IR + Green
  byte sampleRate = 100;    // Options: 50, 100, 200, 400, 800, 1000, 1600, 3200
  int pulseWidth = 411;     // Options: 69, 118, 215, 411
  int adcRange = 4096;      // Options: 2048, 4096, 8192, 16384

  particleSensor.setup(ledBrightness, sampleAverage, ledMode, sampleRate, pulseWidth, adcRange);  // Configure sensor with these settings
}

void loop() {
  bufferLength = 100;  // buffer length of 100 stores 4 seconds of samples running at 25sps
  double fred, fir;
  int Ebpm = 60;
  unsigned long currentMillis = millis();
  if (currentMillis - lastUpdate >= updateInterval) {
    lastUpdate = currentMillis;

    // read the first 100 samples, and determine the signal range
    for (byte i = 0; i < bufferLength; i++) {
      while (particleSensor.available() == false)  // do we have new data?
        particleSensor.check();                    // Check the sensor for new data

      redBuffer[i] = particleSensor.getRed();
      irBuffer[i] = particleSensor.getIR();

      if (irBuffer[i] < FINGER_ON) {
        if (fingerDetected) {

          fingerNotDetectedTime = millis();  // Record the time when the finger is first not detected
          fingerDetected = false;
          lcd.clear();
          lcd.setCursor(0, 0);
          lcd.print(" !!Place your!!");
          lcd.setCursor(0, 1);
          lcd.print("finger in 5 sec");
        }
        if (millis() - fingerNotDetectedTime > 5000) {  // Check if finger has been not detected for 5 seconds
          lcd.clear();
          lcd.setCursor(0, 0);
          lcd.print("!!!System off!!!");
          delay(5000);
          lcd.clear();
          while (1)
            ;  // Stop the system
        }
      } else {
        fingerDetected = true;
        fingerNotDetectedTime = 0;  // Reset the time when the finger is detected again

        fred = (double)redBuffer[i];
        fir = (double)irBuffer[i];
        avered = avered * frate + (double)redBuffer[i] * (1.0 - frate);  // Average red level by low pass filter
        aveir = aveir * frate + (double)irBuffer[i] * (1.0 - frate);     // Average IR level by low pass filter
        sumredrms += (fred - avered) * (fred - avered);                  // Square sum of alternate component of red level
        sumirrms += (fir - aveir) * (fir - aveir);                       // Square sum of alternate component of IR level

        Ebpm = (int)HRM_estimator(fir, aveir);  // Ebpm is estimated BPM
        sumredrms = 0.0;
        sumirrms = 0.0;
        particleSensor.nextSample();  // We're finished with this sample so move to next sample

        Serial.print(F("red="));
        Serial.print(redBuffer[i], DEC);
        Serial.print(F(", ir="));
        Serial.println(irBuffer[i], DEC);

        Serial.print(F(", EHR="));
        Serial.print(Ebpm, DEC);
      }
    }

    // calculate heart rate and SpO2 after first 100 samples (first 4 seconds of samples)
    maxim_heart_rate_and_oxygen_saturation(irBuffer, bufferLength, redBuffer, &spo2, &validSPO2, &heartRate, &validHeartRate);

    // Get one more sample and calculate the values to store in the database
    while (particleSensor.available() == false)  // do we have new data?
      particleSensor.check();                    // Check the sensor for new data

    digitalWrite(readLED, !digitalRead(readLED));  // Blink onboard LED with every data read

    uint32_t newRed = particleSensor.getRed();
    uint32_t newIR = particleSensor.getIR();

    // Get Body Temperature
    float temperature = particleSensor.readTemperature() + 2;

    Serial.print(F("red="));
    Serial.print(newRed, DEC);
    Serial.print(F(", ir="));
    Serial.print(newIR, DEC);

    Serial.print(F(", HR="));
    Serial.print(heartRate, DEC);

    Serial.print(F(", HRvalid="));
    Serial.print(validHeartRate, DEC);

    Serial.print(F(", EHR="));
    Serial.print(Ebpm, DEC);

    Serial.print(F(", SPO2="));
    Serial.print(spo2, DEC);

    Serial.print(F(", SPO2Valid="));
    Serial.println(validSPO2, DEC);

    Serial.print(F(", Temp="));
    Serial.println(temperature, DEC);

    lcd.clear();  // Clear again after a delay
    lcd.setCursor(0, 0);
    lcd.print("HeartRate:");
    lcd.print(Ebpm, DEC);
    lcd.print(" BPM");

    lcd.setCursor(0, 1);
    lcd.print("SPO2:");
    lcd.print(spo2, DEC);
    lcd.print(" %");
    delay(1000);
    lcd.clear();

    lcd.setCursor(0, 0);
    lcd.print("Temp:");
    lcd.print(temperature, 1);
    lcd.print(" c");

    // Send HTTP POST request and get the response payload
    String payload = sendHttpPostRequest(String(Ebpm), String(spo2), String(temperature));

    // Parse the response payload and update relay state
    updateRelayState(payload);

    // Recalculate HR and SP02 with the new sample
    redBuffer[99] = newRed;
    irBuffer[99] = newIR;
    maxim_heart_rate_and_oxygen_saturation(irBuffer, bufferLength, redBuffer, &spo2, &validSPO2, &heartRate, &validHeartRate);
  }
}

double HRM_estimator(double fir, double aveir) {
  int CTdiff;

  // Heart Rate Monitor by finding falling edge
  eir = eir * firrate + fir * (1.0 - firrate);  // Estimated IR: low pass filtered IR signal

  if (((eir - aveir) * (eir_prev - aveir) < 0) && ((eir - aveir) < 0.0)) {  // Find zero cross at falling edge
    crosstime = millis();                                                   // System time in msec of falling edge

    CTdiff = crosstime - crosstime_prev;

    if ((CTdiff > 333) && (CTdiff < 1333)) {
      bpm = 60.0 * 1000.0 / (double)(crosstime - crosstime_prev);  // Get bpm
      ebpm = ebpm * fbpmrate + (1.0 - fbpmrate) * bpm;             // Estimated bpm by low pass filtered
    }
    crosstime_prev = crosstime;
  }
  eir_prev = eir;
  return (ebpm);
}

// Function to prepare and send HTTP POST request
String sendHttpPostRequest(String Ebpm, String spo2, String temperature) {
  String payload = "";

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(client, serverName);

    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    // Prepare your HTTP POST request data
    String httpRequestData = "api_key=" + apiKeyValue
                             + "&heartRate=" + Ebpm
                             + "&spo2=" + spo2
                             + "&temperature=" + temperature;
    Serial.print("httpRequestData: ");
    Serial.println(httpRequestData);

    // Send HTTP POST request
    int httpResponseCode = http.POST(httpRequestData);

    if (httpResponseCode > 0) {
      Serial.print("HTTP Response code: ");
      Serial.println(httpResponseCode);

      // Get the response payload
      payload = http.getString();
      Serial.println("Response payload: " + payload);
    } else {
      Serial.print("Error code: ");
      Serial.println(httpResponseCode);
      Serial.print("HTTPClient error: ");
      Serial.println(http.errorToString(httpResponseCode).c_str());
    }
    // Free resources
    http.end();
  } else {
    Serial.println("WiFi Disconnected");
  }

  return payload;
}

// Function to parse JSON response and update relay state
void updateRelayState(String payload) {
  StaticJsonDocument<200> doc;
  DeserializationError error = deserializeJson(doc, payload);

  if (error) {
    Serial.print(F("deserializeJson() failed: "));
    Serial.println(error.f_str());
    return;
  }

  const char* relayState = doc["relayState"];

  // Control the relay based on the server response
  if (strcmp(relayState, "ON") == 0) {
    digitalWrite(relayPin, HIGH);
    Serial.println("Relay turned ON");
  } else {
    digitalWrite(relayPin, LOW);
    Serial.println("Relay turned OFF");
  }
}