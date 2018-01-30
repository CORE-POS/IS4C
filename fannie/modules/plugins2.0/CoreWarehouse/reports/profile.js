
var cwProfile = (function () {

    var mod = {};

    mod.renderChart = function(elem, label, data) {
        var ctx = document.getElementById(elem);
        console.log(data);

        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    { 
                        data: data.values,
                        fill: false,
                        label: 'Profile ' + label,
                        backgroundColor: "#3366cc",
                        pointBackgroundColor: "#3366cc",
                        pointBorderColor: "#3366cc",
                        borderColor: "#3366cc"
                    },
                    { 
                        data: data.avg,
                        fill: false,
                        label: 'Avg. ' + label,
                        backgroundColor: "#dc3912",
                        pointBackgroundColor: "#dc3912",
                        pointBorderColor: "#dc3912",
                        borderColor: "#dc3912"
                    },
                    { 
                        data: data.top,
                        fill: false,
                        label: 'Top ' + label,
                        backgroundColor: "#ff9900",
                        pointBackgroundColor: "#ff9900",
                        pointBorderColor: "#ff9900",
                        borderColor: "#ff9900"
                    }
                ]
            }
        });
    };

    mod.drawChart = function(spending, visits, baskets) {
        mod.renderChart('spendCanvas', 'Spending ($)', spending);
        mod.renderChart('visitCanvas', 'Visits', visits);
        mod.renderChart('basketCanvas', 'Baskets', baskets);
    };

    return mod;

}());

