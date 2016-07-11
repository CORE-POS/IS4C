var salesToday = (function($) {
    var mod = {};

    mod.reloadGraph = function() {
        var url='SalesTodayReport.php?';
        url += 'super=' + $('select[name=dept]').val();
        url += '&store=' + $('select[name=store]').val();
        window.location=url;
    };

    mod.graphData = function() {
        var data = Array();
        var xmin = 24;
        var xmax = 0;
        var ymin = 999999999;
        var ymax = 0;

        $('.datarow').each(function(){
            var x = Number($(this).find('.x-data').html());
            var y = Number($(this).find('.y-data').html());
            if (x < xmin) {
                xmin = x;
            }
            if (x > xmax) {
                xmax = x;
            }
            if (y < ymin) {
                ymin = y;
            }
            if (y > ymax) {
                ymax = y;
            }
            data.push(Array(x, y));
        });

        singleline(data, Array(xmin, xmax), Array(ymin, ymax), '#chartDiv');
    };

    return mod;

}(jQuery));
