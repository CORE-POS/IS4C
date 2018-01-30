
var obfSummary = (function () {
    var mod = {};

    var inp = {};

    mod.renderChart = function(hide) {
        var ctx = document.getElementById('obfCanvas');

        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: inp.labels,
                datasets: [
                    { 
                        data: inp.sales, 
                        fill: false,
                        label: 'Current Sales',
                        hidden: hide,
                        backgroundColor: "#3366cc",
                        pointBackgroundColor: "#3366cc",
                        pointBorderColor: "#3366cc",
                        borderColor: "#3366cc"
                    },
                    { 
                        data: inp.lySales, 
                        fill: false,
                        label: 'Last Year Sales',
                        hidden: hide,
                        backgroundColor: "#dc3912",
                        pointBackgroundColor: "#dc3912",
                        pointBorderColor: "#dc3912",
                        borderColor: "#dc3912"
                    }
                ]
            }
        });

        var ctx2 = document.getElementById('obfHoursCanvas');

        var myChart = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: inp.labels,
                datasets: [
                    { 
                        data: inp.hours, 
                        fill: false,
                        label: 'Current Hours',
                        hidden: hide,
                        backgroundColor: "#3366cc",
                        pointBackgroundColor: "#3366cc",
                        pointBorderColor: "#3366cc",
                        borderColor: "#3366cc"
                    },
                    { 
                        data: inp.lyHours, 
                        fill: false,
                        label: 'Last Year Hours',
                        hidden: hide,
                        backgroundColor: "#dc3912",
                        pointBackgroundColor: "#dc3912",
                        pointBorderColor: "#dc3912",
                        borderColor: "#dc3912"
                    }
                ]
            }
        });

        var ctx3 = document.getElementById('obfSplhCanvas');

        var myChart = new Chart(ctx3, {
            type: 'line',
            data: {
                labels: inp.labels,
                datasets: [
                    { 
                        data: inp.splh, 
                        fill: false,
                        label: 'Current SPLH',
                        hidden: hide,
                        backgroundColor: "#3366cc",
                        pointBackgroundColor: "#3366cc",
                        pointBorderColor: "#3366cc",
                        borderColor: "#3366cc"
                    },
                    { 
                        data: inp.lySplh, 
                        fill: false,
                        label: 'Last Year SPLH',
                        hidden: hide,
                        backgroundColor: "#dc3912",
                        pointBackgroundColor: "#dc3912",
                        pointBorderColor: "#dc3912",
                        borderColor: "#dc3912"
                    }
                ]
            }
        });
    };

    mod.drawChart = function(jsonStr) {
        inp = JSON.parse(jsonStr);

        /* behavior on remembering hidden-ness is a little flakey
        var para = document.createElement("div");
        para.align = 'center';
        para.style.marginBottom = '10px';
        var links = '<a href="" onclick="obfSummary.renderChart(true); return false;">Hide All</a>';
        links += '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        links += '<a href="" onclick="obfSummary.renderChart(false); return false;">Show All</a>';
        para.innerHTML = links;
        document.body.append(para);
        */

        var canvas = document.createElement("canvas");
        canvas.id = "obfCanvas";
        document.body.appendChild(canvas);

        var canvas2 = document.createElement("canvas");
        canvas2.id = "obfHoursCanvas";
        document.body.appendChild(canvas2);

        var canvas3 = document.createElement("canvas");
        canvas3.id = "obfSplhCanvas";
        document.body.appendChild(canvas3);

        mod.renderChart(false);
    };

    return mod;

}());

