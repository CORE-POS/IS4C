
var monthEnd = (function ($) {

    var mod = {};

    var data = {};

    var resumRow = function(denom) {
        var ttl = Number(document.getElementById('registers' + denom).value);
        if (document.getElementById('extra' + denom)) {
            ttl += Number(document.getElementById('extra' + denom).value);
        }
        ttl += Number(document.getElementById('safe' + denom).value);
        document.getElementById('total' + denom).value = ttl;
    };

    var resumAtm = function() {
        var ttl = Number($('#atmself').val());
        ttl += Number($('#atmsafe').val());
        $('#atmtotal').val(ttl);
    }

    var watchers = function() {
        $('input.registers').change(function () {
            var denom = this.id.replace('registers', '');
            data['registers'][denom] = Number(this.value);
            resumRow(denom);
        });
        $('input.extra').change(function () {
            var denom = this.id.replace('extra', '');
            data['change'][denom] = Number(this.value);
            resumRow(denom);
        });
        $('input.safe').change(function () {
            var denom = this.id.replace('safe', '');
            data['safe'][denom] = Number(this.value);
            resumRow(denom);
        });
        $('input.date-field').change(function () {
            var idx = Number(this.id.replace('date', '')) - 1;
            data['drops'][idx]['date'] = this.value;
        });
        $('input.drop-field').change(function () {
            var idx = Number(this.id.replace('drop', '')) - 1;
            data['drops'][idx]['count'] = this.value;
        });
        $('#atmself').change(function () {
            data['atm']['self'] = Number(this.value);
            resumAtm();
        });
        $('#atmsafe').change(function () {
            data['atm']['safe'] = Number(this.value);
            resumAtm();
        });
    };

    mod.getState = function() {
        return data;
    }

    mod.save = function() {
        var id = $('#month').val() + ':' + $('#year').val() + ':' + $('select[name=store]').val();
        var dstr = 'id=' + id + '&json=' + JSON.stringify(data);
        $.ajax({
            url: 'OsMonthEndPage.php',
            method: 'post',
            data: dstr
        }).done (function (resp) {
            alert('Save Complete');
        });
    };

    mod.getDate = function() {
        var dstr = $('#lookupForm').serialize();
        $.ajax({
            url: 'OsMonthEndPage.php',
            method: 'get',
            data: 'id=foo&' + dstr,
            dataType: 'json'
        }).done (function (resp) {
            data = resp;
            watchers();
            for (x in resp['registers']) {
                document.getElementById('registers'+x).value = resp['registers'][x];
            }
            for (x in resp['change']) {
                document.getElementById('extra'+x).value = resp['change'][x];
            }
            for (x in resp['safe']) {
                document.getElementById('safe'+x).value = resp['safe'][x];
                resumRow(x);
            }
            for (var i=0; i<resp['drops'].length; i++) {
                document.getElementById('date' + (i + 1)).value = resp['drops'][i]['date'];
                document.getElementById('drop' + (i + 1)).value = resp['drops'][i]['count'];
            }
            document.getElementById('atmself').value = resp['atm']['self'];
            document.getElementById('atmsafe').value = resp['atm']['safe'];
            resumAtm();
        });
    };

    return mod;

})(jQuery);
