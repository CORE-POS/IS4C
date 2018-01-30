
function showGraph(ofType) {
    $('#chartDiv').html('');
    var xLabels = $('thead th').toArray().map(x => x.innerHTML.trim()).filter(x => x != '&nbsp;');

    yPoints = [];
    yLabels = [];
    $('tbody tr').each(function() {
        if ($(this).find('.reportColumn0').text().indexOf(ofType) !== -1) {
            var ydata = Array();
            for (var i=0; i<xLabels.length; i++) {
                var y = Number($(this).find('.reportColumn'+(i+1)).html().replace(/%/, ''));
                ydata.push(y);
            }
            yPoints.push(ydata);
            yLabels.push($(this).find('.reportColumn0').text());
        }
    });
    CoreChart.lineChart('chartCanvas', xLabels, yPoints, yLabels);
};

function addOptions() {
    $('tbody .reportColumn0').each(function() {
        var opt = $('<option>').html($(this).text().trim());
        $('#grapher').append(opt);
    });
};
 
