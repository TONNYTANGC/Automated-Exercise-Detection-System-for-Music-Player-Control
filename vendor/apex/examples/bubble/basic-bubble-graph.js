function generateData(baseval, count, yrange) {
	var i = 0;
	var series = [];
	while (i < count) {
		var x = Math.floor(Math.random() * (750 - 1 + 1)) + 1;;
		var y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;
		var z = Math.floor(Math.random() * (75 - 5 + 1)) + 5;
		series.push([x, y, z]);
		baseval += 86400000;
		i ++;
	}
	return series;
}

var options = {
	chart: {
		height: 220,
		type: 'bubble',
		toolbar: {
			show: false,
		},
	},
	dataLabels: {
		enabled: false
	},
	series: [{
		name: 'ETH',
		data: generateData(new Date('25 May 2019 GMT').getTime(), 20, {
			min: 10,
			max: 60
		})
	},{
		name: 'BTC',
		data: generateData(new Date('25 May 2019 GMT').getTime(), 20, {
			min: 10,
			max: 60
		})
	}],
	fill: {
		opacity: 0.8
	},
	xaxis: {
		tickAmount: 5,
		type: 'category',
	},
	yaxis: {
		max: 70
	},
	colors: ['#7da9fc', '#f3ae23', '#f9f9f9', '#f0474a', '#68bbb8'],
}

var chart = new ApexCharts(
	document.querySelector("#basic-bubble-graph"),
	options
);

chart.render();


