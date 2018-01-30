var dashboard = (function($) {

    var mod = {};

    mod.openGraph = function(barData) {
        var ctx = document.getElementById('openBar').getContext('2d');
        var bar = new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: barData.labels,
                datasets: [{
                    label: "Open Comments",
                    data: barData.bars,
                    backgroundColor: ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499", "#22aa99", "#aaaa11", "#6633cc", "#e67300", "#8b0707", "#651067", "#329262", "#5574a6", "#3b3eac"],
                    pointBackgroundColor: ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499", "#22aa99", "#aaaa11", "#6633cc", "#e67300", "#8b0707", "#651067", "#329262", "#5574a6", "#3b3eac"],
                    pointBordeColor: ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499", "#22aa99", "#aaaa11", "#6633cc", "#e67300", "#8b0707", "#651067", "#329262", "#5574a6", "#3b3eac"],
                    bordeColor: ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499", "#22aa99", "#aaaa11", "#6633cc", "#e67300", "#8b0707", "#651067", "#329262", "#5574a6", "#3b3eac"]
                }]
            },
            options: {
                animation: {
                    duration: 0
                },
                legend: {
                    labels: {
                        fontSize: 30,
                        boxWidth: 0
                    }
                },
                scales: {
                    xAxes: [{
                        ticks: {
                            beginAtZero: true,
                            fontSize: 40,
                            stepSize: 1
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            fontSize: 40
                        }
                    }]
                }
            }
        });
    };

    return mod;
}(jQuery));
