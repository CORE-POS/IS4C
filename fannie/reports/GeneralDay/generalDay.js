
var generalDay = (function ($) {

    var mod = {};

    mod.tenders = function() {
        var labels = $('#reportTable1 td.reportColumn0').toArray()
            .map(x => x.innerHTML.trim());
        var vals = $('#reportTable1 td.reportColumn2').toArray()
            .map(x => -1 * Number(x.innerHTML.trim()));
        var zipped = labels.map((e, i) => [e, vals[i]])
            .filter(x => x[1] > 0);
        var ttl = zipped.reduce((acc, i) => acc + i[1], 0);
        labels = zipped.map(x => x[0]);
        vals = zipped.map(x => x[1] / ttl * 100);

        $('#reportTable1').after('<div class="col-sm-6 col-sm-offset-3"><canvas id="tenderChart"></canvas></div>');
        CoreChart.pieChart('tenderChart', labels, vals);
    };

    mod.sales = function() {
        var labels = $('#reportTable2 td.reportColumn0').toArray()
            .map(x => x.innerHTML.trim());
        var vals = $('#reportTable2 td.reportColumn2').toArray()
            .map(x => Number(x.innerHTML.trim()));
        var zipped = labels.map((e, i) => [e, vals[i]])
            .filter(x => x[1] > 0);
        labels = zipped.map(x => x[0]);
        vals = zipped.map(x => x[1]);

        $('#reportTable2').after('<div class="col-sm-6 col-sm-offset-3"><canvas id="salesChart"></canvas></div>');
        CoreChart.pieChart('salesChart', labels, vals);

    };

    return mod;

}(jQuery));

