var batchReport = (function ($) {

    var mod = {};

    var drawChart = function(yData, xLabels, yMin, yMax, name) {
        var ctx = $('#brCanvas');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: xLabels,
                datasets: [{ 
                    data: yData,
                    fill: false,
                    label: name + ' Sales',
                    backgroundColor: "rgba(0,35,200,0.9)",
                    borderColor: "rgba(0,35,200,0.7)"
                }],
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            min: yMin,
                            max: yMax
                        }
                    }]
                }
            }
        });
    };

    var getData = function(elem) {
        var upc = elem.siblings('.reportColumn0').find('a').html();
        var dstr = 'upc='+upc+'&date1='+$('#date1').val()+'&date2='+$('#date2').val();
        dstr += '&store='+$('select[name=store]').val();
        $.ajax({
            data: dstr,
            dataType: 'json'
        }).done(function (resp) {
            drawChart(resp.totals, resp.dates, resp.min, resp.max, resp.description);
        });
    };

    mod.init = function() {
        $('#primary-content').append('<canvas id="brCanvas"></canvas>');
        $('.mySortableTable td').click(function (ev) {
            var elem = $(this);
            getData(elem);
        });
    };

    return mod;

}(jQuery));
