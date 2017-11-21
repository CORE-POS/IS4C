var salesToday = (function($) {
    var mod = {};

    mod.reloadGraph = function() {
        var url='SalesTodayReport.php?';
        url += 'super=' + $('select[name=dept]').val();
        url += '&store=' + $('select[name=store]').val();
        window.location=url;
    };

    mod.graphData = function() {
        var data = Array();
        var xmin = 24;
        var xmax = 0;
        var ymin = 999999999;
        var ymax = 0;

        $('.datarow').each(function(){
            var x = Number($(this).find('.x-data').html());
            var y = Number($(this).find('.y-data').html());
            if (x < xmin) {
                xmin = x;
            }
            if (x > xmax) {
                xmax = x;
            }
            if (y < ymin) {
                ymin = y;
            }
            if (y > ymax) {
                ymax = y;
            }
            data.push(Array(x, y));
        });

        singleline(data, Array(xmin, xmax), Array(ymin, ymax), '#chartDiv');
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
            }
        });
    };

    return mod;

}(jQuery));
