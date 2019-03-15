var estMargin = (function ($) {

    var mod = {};
    var all = {};
    var depts = {};
    var xLabels = {};

    function formatDateID(x) {
        return x.substring(0, 4) + '-' + x.substring(4, 6) + '-' + x.substring(6);
    }

    mod.init = function(a, d) {
        all = a;
        depts = d;
        xLabels = Object.keys(all).map(x => formatDateID(x));
        mod.reChart();
    };

    mod.reChart = function() {
        if ($('#marginGraph').length) {
            $('#marginGraph').remove();
        }
        var canvas = '<canvas id="marginGraph"></canvas>';
        $('#chartPara').append(canvas);
        var lineData = [];
        var lineLabels = [];
        var allData = Object.keys(all).map(x => ({ "x": formatDateID(x), "y": all[x].margin }));
        lineData.push(allData);
        lineLabels.push("Overall");
        $('.graphDept:checked').each(function () {
            var d = $(this).val();
            lineLabels.push(depts[d].name);
            var deptData = Object.keys(depts[d].perDay).map(x => ({ "x": formatDateID(x), "y": depts[d].perDay[x].margin }));
            lineData.push(deptData);
        });
        CoreChart.lineChart('marginGraph', xLabels, lineData, lineLabels);
    };

    return mod;

})(jQuery);
