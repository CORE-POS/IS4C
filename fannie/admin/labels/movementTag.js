var movementTableFilter = (function ($) {

    var mod = {};

    mod.from = '';
    mod.to = '';
    mod.upc = '';
    mod.brand = '';
    mod.desc = '';
    mod.store = '';
    mod.col_nums = {
        'upc':1,
        'from':8,
        'to':8,
        'brand':2,
        'description':3
    };

    mod.hello = function(){
        alert('hi');
    };

    mod.filter_table = function(){
        this.setChangedValue();
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
        $('select').change(function(){
            mod.store = $(this).children('option:selected').val();
            mod.setFilter();
        });
    };

    return mod;

}(jQuery));
