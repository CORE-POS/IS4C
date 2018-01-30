var salesToday = (function($) {
    var mod = {};

    mod.reloadGraph = function() {
        var url='SalesTodayReport.php?';
        url += 'super=' + $('select[name=dept]').val();
        url += '&store=' + $('select[name=store]').val();
        window.location=url;
    };

    mod.chartData = function() {
        var labels = Array();
        $('.x-data').each(function (i, e) {
            labels.push(Number($(e).html()));
        });
        var dataPoints = Array();
        $('.y-data').each(function (i, e) {
            dataPoints.push(Number($(e).html()));
        });
        var ctx = document.getElementById('newChartCanvas');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: dataPoints,
                    fill: false,
                    label: 'Hourly Sales',
                    backgroundColor: "#3366cc",
                    pointBackgroundColor: "#3366cc",
                    pointBorderColor: "#3366cc",
                    borderColor: "#3366cc"
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            callback: function(val, i, vals) {
                                return val.toLocaleString("en-US",{style:"currency", currency:"USD"});
                            }
                        }
                    }],
                    xAxes: [{
                        ticks: {
                            callback: function(val, i, vals) {
                                if (val == 12) return '12pm';
                                return (val < 12) ? val+'am' : (val-12) + 'pm';
                            }
                        }
                    }]
                }
            }
        });
    };

    return mod;

}(jQuery));
