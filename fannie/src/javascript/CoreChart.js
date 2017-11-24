
/**
  Wrapper for Chart.js so commonly used
  chart formats can be repeated easily
*/
var CoreChart = (function ($) {
    var mod = {};

    var colors = [
        "#3366cc",
        "#dc3912",
        "#ff9900",
        "#109618",
        "#990099",
        "#0099c6",
        "#dd4477",
        "#66aa00",
        "#b82e2e",
        "#316395",
        "#994499",
        "#22aa99",
        "#aaaa11",
        "#6633cc",
        "#e67300",
        "#8b0707",
        "#651067",
        "#329262",
        "#5574a6",
        "#3b3eac"
    ];

    var lineDataSets = function(lineData, lineLabels) {
        var datasets = [];
        for (var i=0; i<lineData.length; i++) {
            var set = {
                data: lineData[i],
                fill: false,
                label: lineLabels[i],
                backgroundColor: colors[i],
                pointBackgroundColor: colors[i],
                pointBorderColor: colors[i],
                borderColor: colors[i]
            };
            datasets.push(set);
        }

        return datasets;
    };

    mod.lineChart = function(elementID, xLabels, lineData, lineLabels) {
        var ctx = document.getElementById(elementID);
        var line = new Chart(ctx, {
            type: 'line',
            data: {
                labels: xLabels,
                datasets: lineDataSets(lineData, lineLabels)
            }
        });
    };

    return mod;

}(jQuery));

