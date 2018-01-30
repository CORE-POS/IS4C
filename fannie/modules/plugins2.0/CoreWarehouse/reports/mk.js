
var mk = (function ($) {
    var mod = {};
    
    var lineGraph = function(elem, name, labels, data1, data2) {
        var ctx = document.getElementById(elem);
        var chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        data: data1, 
                        fill: false,
                        lineTension: 0,
                        label: name + ' Sales TY',
                        backgroundColor: "#3366cc",
                        pointBackgroundColor: "#3366cc",
                        pointBorderColor: "#3366cc",
                        borderColor: "#3366cc"
                    },
                    { 
                        data: data2, 
                        fill: false,
                        lineTension: 0,
                        label: name + ' Sales LY',
                        backgroundColor: "#dc3912",
                        pointBackgroundColor: "#dc3912",
                        pointBorderColor: "#dc3912",
                        borderColor: "#dc3912"
                    }
                ]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            callback: function(val, i, vals) {
                                return val.toLocaleString("en-US",{style:"currency", currency:"USD"});
                            }
                        }
                    }]
                }
            }
        });
    };

    var barGraph = function(labels, hillside, denfeld) {
        var ctx = document.getElementById('barGraph');
        var sets = [
            {
                label: "Denfeld Dollar Growth",
                data: denfeld,
                backgroundColor: "#3366cc",
                pointBackgroundColor: "#3366cc",
                pointBorderColor: "#3366cc",
                borderColor: "#3366cc"
            },
            {
                label: "Hillside Dollar Growth",
                data: hillside,
                backgroundColor: "#dc3912",
                pointBackgroundColor: "#dc3912",
                pointBorderColor: "#dc3912",
                borderColor: "#dc3912"
            }
        ];
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: sets
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            callback: function(val, i, vals) {
                                return val.toLocaleString("en-US",{style:"currency", currency:"USD"});
                            }
                        }
                    }]
                }
            }
        });
    };

    mod.drawLines = function(data) {
        lineGraph('denfeldLine', 'Denfeld', data.labels, data.denfeld.thisYear, data.denfeld.lastYear);
        lineGraph('hillsideLine', 'Hillside', data.labels, data.hillside.thisYear, data.hillside.lastYear);
        lineGraph('totalLine', 'Whole Org.', data.labels, data.ttl.thisYear, data.ttl.lastYear);
    };

    mod.drawBars = function(data) {
        barGraph(data.labels, data.hillside, data.denfeld);
    };

    return mod;

}(jQuery));

