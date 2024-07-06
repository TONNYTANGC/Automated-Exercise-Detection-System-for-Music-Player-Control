var options = {
	chart: {
		height: 310,
		type: 'radialBar',
	},
	plotOptions: {
		radialBar: {
			dataLabels: {
				name: {
					fontSize: '22px',
				},
				value: {
					fontSize: '16px',
				},
				total: {
					show: true,
					label: 'Overall',
					formatter: function (w) {
						// By default this function returns the average of all series. The below is just an example to show the use of custom formatter function
						return '98%'
					}
				}
			}
		}
	},
	series: [97, 85, 73, 35],
	labels: ['Grade A', 'Grade B', 'Grade C', 'Grade D'],
}

var chart = new ApexCharts(
	document.querySelector("#basic-radial-graph"),
	options
);
chart.render();