var movementTableFilter = (function ($) {

    var mod = {};

    mod.from = '';
    mod.to = '';
    mod.upc = '';
    mod.brand = '';
    mod.desc = '';
    mod.store = '';
    mod.depts = [];
    mod.dept = '';
    mod.locs = [];
    mod.loc = '';
    mod.count_upcs = [];

    mod.filter_table = function(){
        this.setChangedValue();
        this.clickColumns();
        this.wipeUpdateCol();
        this.countUpcs();
        this.addSymbol();
    };

    mod.setDepts = function(){
        $('#mu-table tr').each(function(){
            var department = $(this).find('td:eq(4)').text();
            if ($.inArray(department, mod.depts) == -1) {
                mod.depts.push(department);
            }
            var loc = $(this).find('td:eq(9)').text();
            if ($.inArray(loc, mod.locs) == -1) {
                mod.locs.push(loc);
            }
        });
        this.depts.sort();
        this.locs.sort();
        $.each(this.depts, function(k,v) {
            if (v == '') {
                v = 'Department';
            }
            var option = '<option value="'+v+'">'+v+'</option>';
            $('#dept-filter').append(option);
        });
        $.each(this.locs, function(k,v) {
            if (v == '') {
                v = 'Floor Location';
            }
            var option = '<option value="'+v+'">'+v+'</option>';
            $('#loc-filter').append(option);
        });
    };

    mod.setFilter = function(){
        // show rows as filters are removed
        $('#mu-table tr').each(function(){
            $(this).show();
        });
        // hide rows as filters are applied
        $('#mu-table tr').each(function(){
            if (mod.upc != '') {
                var cur_upc = $(this).find('td:eq(1)').text();
                if (cur_upc.indexOf(mod.upc) <= -1) {
                    if (!$(this).parent('thead').is('thead')) {
                        $(this).hide();
                    }
                }
            }
            if (mod.brand != '') {
                var cur_brand = $(this).find('td:eq(2)').text();
                cur_brand = cur_brand.toLowerCase();
                if (cur_brand.indexOf(mod.brand) <= -1) {
                    if (!$(this).parent('thead').is('thead')) {
                        $(this).hide();
                    }
                }
            }
            if (mod.desc != '') {
                var cur_desc = $(this).find('td:eq(3)').text();
                cur_desc = cur_desc.toLowerCase();
                if (cur_desc.indexOf(mod.desc) <= -1) {
                    if (!$(this).parent('thead').is('thead')) {
                        $(this).hide();
                    }
                }
            }
            if (mod.store != '') {
                if (mod.store != 0) {
                    var cur_store = $(this).find('td:eq(5)').text();
                    if (cur_store.indexOf(mod.store) <= -1) {
                        if (!$(this).parent('thead').is('thead')) {
                            $(this).hide();
                        }
                    }
                } else {

                }
            }
            if (mod.from != '') {
                var cur_from = new Date($(this).find('td:eq(8)').text());
                if (cur_from <= mod.from) {
                    if (!$(this).parent('thead').is('thead')) {
                        $(this).hide();
                    }
                }

            }
            if (mod.to != '') {
                var cur_to = new Date($(this).find('td:eq(8)').text());
                if (cur_to >= mod.to) {
                    if (!$(this).parent('thead').is('thead')) {
                        $(this).hide();
                    }
                }

            }
            if (mod.dept != '' && mod.dept != 'Department') {
                var cur_dept = $(this).find('td:eq(4)').text();
                if (cur_dept.indexOf(mod.dept) <= -1) {
                    if (!$(this).parent('thead').is('thead')) {
                        $(this).hide();
                    }
                }
            }
            if (mod.loc != '' && mod.loc != 'Floor Location') {
                var cur_loc = $(this).find('td:eq(9)').text();
                if (cur_loc.indexOf(mod.loc) <= -1) {
                    if (!$(this).parent('thead').is('thead')) {
                        $(this).hide();
                    }
                }
            }
        });
    };

    mod.setChangedValue = function(){
        $('input').change(function(){
            var var_name = $(this).attr('data-var');
            switch(var_name) {
                case 'upc':
                    mod.upc = $(this).val();
                    break;
                case 'brand':
                    mod.brand = $(this).val();
                    mod.brand = mod.brand.toLowerCase();
                    break;
                case 'desc':
                    mod.desc = $(this).val();
                    mod.desc = mod.desc.toLowerCase();
                    break;
                case 'from':
                    mod.from = new Date($(this).val());
                    break;
                case 'to':
                    mod.to = new Date($(this).val());
                    break;
            }
            mod.setFilter();
        });
        $('select[name=store]').change(function(){
            mod.store = $(this).children('option:selected').val();
            mod.setFilter();
        });
        $('select[name=dept-filter]').change(function(){
            mod.dept = $(this).find('option:selected').text();;
            mod.setFilter();
        });
        $('select[name=loc-filter]').change(function(){
            mod.loc = $(this).find('option:selected').text();;
            mod.setFilter();
        });
        this.setDepts();
        $('select[name=store]').prop('selectedIndex', 0);
    };

    mod.clickColumns = function(){
        var columns = ['upc', 'brand', 'loc', 'dept'];
        $.each(columns, function(k,column) {
            $('td[data-column='+column+']').click(function(){
                var cur_text = $(this).text();
                $('#'+column+'-filter').val(cur_text)
                    .trigger('change');
            });
            $('td[data-column='+column+']').hover(function(){
                $(this).css('cursor', 'pointer');
            });
        });
    };

    mod.countUpcs = function(){
        $('td[data-column=upc]').each(function(){
            var upc = $(this).text();
            if (mod.count_upcs[upc] == undefined) {
                mod.count_upcs[upc] = 1;
            } else {
                mod.count_upcs[upc]++;
            }
            $('td:contains('+upc+')').prev().text(mod.count_upcs[upc]);
        });
        console.log(mod.count_upcs);
    };

    mod.wipeUpdateCol = function(){
        $('tr').each(function(){
            $(this).find('td:eq(0)').text(0);
        });
    };

    mod.addSymbol = function(){
        $('tr').each(function(){
            var number  = $(this).find('td:eq(7)').text();
            number = parseFloat(number);
            if (number > 0.01) {
                $(this).find('td:eq(7)').text('+'+number);
            }
        });
    };

    return mod;

}(jQuery));
