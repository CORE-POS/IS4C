function getXData() {
    var xdata = Array();
    $('thead th').each(function() {
        var txt = $(this).text().trim();
        if (txt) {
            xdata.push(new Date(txt));
        }
    });

    return xdata;
};

function showGraph(ofType) {
    $('#chartDiv').html('');

    var xdata = getXData();
    var xmin = xdata[0];
    var xmax = xdata[xdata.length-1];
    var ymin = 999999999;
    var ymax = -1*ymin;

    var first = true;
    $('tbody tr').each(function() {
        if ($(this).find('.reportColumn0').text().indexOf(ofType) !== -1) {
            var ydata = Array();
            for (var i=0; i<xdata.length; i++) {
                var y = Number($(this).find('.reportColumn'+(i+1)).html().replace(/%/, ''));
                ydata.push(y);
                if (y > ymax) {
                    ymax = y;
                }
                if (y < ymin) {
                    ymin = y;
                }
            }
            console.log(ydata);

            var data = Array();
            for (var i=0; i < xdata.length; i++) {
                data.push(Array(xdata[i], ydata[i]));
            }

            if (first) {
                singleline(data, Array(xmin, xmax), Array(ymin, ymax), '#chartDiv');
                first = false;
            } else {
                addsingleline(data, Array(xmin, xmax), Array(ymin, ymax), '#chartDiv', 'steelblue');
            }
        }
    });
};

function addOptions() {
    $('tbody .reportColumn0').each(function() {
        var opt = $('<option>').html($(this).text().trim());
        $('#grapher').append(opt);
    });
};
 
