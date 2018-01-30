
var stChart = (function() {
    var mod = {};

    var _fontSize = 12;

    mod.setFontSize = function(f) {
        _fontSize = f;
    }

    mod.lineChart = function(elemID, labels, points, dayName) {
        var ctx = document.getElementById(elemID).getContext('2d');
        var line = new Chart(ctx, {
            type: 'line',
            responsive: false,
            data: {
                datasets: [
                    {
                        data: points.today,
                        fill: false,
                        label: 'Sales Today',
                        backgroundColor: "#3366cc",
                        pointBackgroundColor: "#3366cc",
                        pointBorderColor: "#3366cc",
                        borderColor: "#3366cc"
                    },
                    {
                        data: points.avg,
                        fill: false,
                        label: 'Avg. ' + dayName,
                        backgroundColor: "#dc3912",
                        pointBackgroundColor: "#dc3912",
                        pointBorderColor: "#dc3912",
                        borderColor: "#dc3912"
                    },
                    {
                        data: points.lastWeek,
                        fill: false,
                        label: 'Last ' + dayName,
                        backgroundColor: "#ff9900",
                        pointBackgroundColor: "#ff9900",
                        pointBorderColor: "#ff9900",
                        borderColor: "#ff9900"
                    }
                ],
                labels: labels
            },
            options: {
                animation: {
                    duration: 0
                },
                legend: {
                    labels: {
                        fontSize: _fontSize
                    }
                },
                scales: {
                    xAxes: [{
                        ticks: {
                            fontSize: _fontSize,
                            callback: function(val, i, vals) {
                                if (val == 12) return '12pm';
                                return (val < 12) ? val+'am' : (val-12) + 'pm';
                            }
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            fontSize: _fontSize,
                            callback: function(val, i, vals) {
                                return val.toLocaleString("en-US",{style:"currency", currency:"USD"});
                            }
                        }
                    }]
                }
            }
        });
    };

    return mod;

})();
