var options = {
	chart: {
		type: 'donut',
		height: 250,
	},
	legend: {
		position: "bottom",
		offsetY: 10,
	},
	dataLabels: {
		enabled: false
  },
	labels: ['Buy', 'Sell', 'Keep'],
	series: [20, 70, 30],
	stroke: {
		width: 0,
	},
	colors: ['#7da9fc', '#ffcd69', '#6dc0a1', '#f0474a', '#68bbb8'],
}
var chart = new ApexCharts(
	document.querySelector("#donut-volume-currency"),
	options
);
chart.render();