
var cwDash = (function ($) {

    var mod = {};

    mod.salesChart = function(json) {
        var ctx = document.getElementById('recentSales').getContext('2d');
        var line = new Chart(ctx, {
            type: 'line',
            data: {
                labels: json.labels,
                datasets: [{
                    label: "Sales",
                    data: json.points,
                    fill: false,
                    backgroundColor: "#3366cc",
                    borderColor: "#3366cc",
                    pointBackgroundColor: "#3366cc",
                    pointBorderColor: "#3366cc"
                }]
            },
            options: {
                animation: {
                    duration: 0
                },
                title : {
                    display: true,
                    text: "Daily Total Sales",
                }
            }
        });
    };

    mod.customersChart = function(json) {
        var ctx = document.getElementById('customerCount').getContext('2d');
        var line = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: json.labels,
                datasets: [
                    {
                        label: "Transactions",
                        data: json.total,
                        backgroundColor: "#3366cc",
                        borderColor: "#3366cc",
                        pointBackgroundColor: "#3366cc",
                        pointBorderColor: "#3366cc"
                    },
                    {
                        label: "Owners",
                        data: json.members,
                        backgroundColor: "#dc3912",
                        borderColor: "#dc3912",
                        pointBackgroundColor: "#dc3912",
                        pointBorderColor: "#dc3912"
                    }
                ]
            },
            options: {
                animation: {
                    duration: 0
                },
                title : {
                    display: true,
                    text: "Daily Transaction Counts",
                }
            }
        });
    };

    mod.pieChart = function(id, json) {
        var ctx = document.getElementById(id).getContext('2d');
        var pie = new Chart(ctx, {
            type: 'pie',
            data: {
                datasets: [{
                    data: json.data,
                    backgroundColor: ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499", "#22aa99", "#aaaa11", "#6633cc", "#e67300", "#8b0707", "#651067", "#329262", "#5574a6", "#3b3eac"]
                }],
                labels: json.labels
            },
            options: {
                animation: {
                    duration: 0
                },
                title: {
                    display: true,
                    text: json.title
                }
            }
        });
    };

    return mod;

}(jQuery));

