
/**
  Wrapper for Chart.js so commonly used
  chart formats can be repeated easily
*/
var CoreChart = (function () {
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

    var getColorIndex = 0;
    mod.getColor = function() {
        var ret = colors[getColorIndex];
        getColorIndex = (getColorIndex + 1) % colors.length;
        return ret;
    };

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

    mod.pieChart = function(elementID, pieLabels, pieData) {
        var ctx = document.getElementById(elementID);
        var line = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: colors
                }]
            }
        });
    };

    mod.barChart = function(elementID, labels, dataSets) {
        var ctx = document.getElementById(elementID);
        dataSets = dataSets.map(function(obj, i) {
            if (!('backgroundColor' in obj)) {
                obj['backgroundColor'] = colors[i]; 
            }
            if (!('borderColor' in obj)) {
                obj['borderColor'] = colors[i]; 
            }
            return obj;
        });
        console.log(dataSets);
        var line = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: dataSets
            }
        });
    };

    return mod;

}());

