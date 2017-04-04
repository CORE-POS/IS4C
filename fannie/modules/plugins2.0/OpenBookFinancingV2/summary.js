
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
                        data: inp.all, 
                        fill: false,
                        label: 'Total',
                        hidden: hide,
                        backgroundColor: 'rgba(228,26,28,0.9)',
                        borderColor: 'rgba(228,26,28,0.4)'
                    },
                    { 
                        data: inp.hillside, 
                        fill: false,
                        label: 'Hillside',
                        hidden: hide,
                        backgroundColor: 'rgba(55,126,184,0.9)',
                        borderColor: 'rgba(55,126,184,0.4)'
                    },
                    { 
                        data: inp.denfeld, 
                        fill: false,
                        label: 'Denfeld',
                        hidden: hide,
                        backgroundColor: 'rgba(77,175,74,0.9)',
                        borderColor: 'rgba(77,175,74,0.4)',
                    },
                    { 
                        data: inp.hdeli, 
                        fill: false,
                        label: 'H. Deli',
                        hidden: hide,
                        backgroundColor: 'rgba(152,78,163,0.9)',
                        borderColor: 'rgba(152,78,163,0.4)'
                    },
                    { 
                        data: inp.ddeli, 
                        fill: false,
                        label: 'D. Deli',
                        hidden: hide,
                        backgroundColor: 'rgba(255,127,0,0.9)',
                        borderColor: 'rgba(255,127,0,0.4)'
                    },
                    { 
                        data: inp.hmerch, 
                        fill: false,
                        label: 'H. Merch',
                        hidden: hide,
                        backgroundColor: 'rgba(215,215,51,0.9)',
                        borderColor: 'rgba(255,255,51,0.4)'
                    },
                    { 
                        data: inp.dmerch, 
                        fill: false,
                        label: 'D. Merch',
                        hidden: hide,
                        backgroundColor: 'rgba(166,86,40,0.9)',
                        borderColor: 'rgba(166,86,40,0.4)'
                    },
                    { 
                        data: inp.hproduce, 
                        fill: false,
                        label: 'H. Produce',
                        hidden: hide,
                        backgroundColor: 'rgba(247,129,191,0.9)',
                        borderColor: 'rgba(247,129,191,0.4)'
                    },
                    { 
                        data: inp.dproduce, 
                        fill: false,
                        label: 'D. Produce',
                        hidden: hide,
                        backgroundColor: 'rgba(153,153,153,0.9)',
                        borderColor: 'rgba(153,153,153,0.4)'
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

        mod.renderChart(false);
    };

    return mod;

}());

