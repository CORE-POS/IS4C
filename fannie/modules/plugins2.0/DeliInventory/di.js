var di = (function ($) {
    var mod = {};

    var openInput = false;
    var openVendor = false;
    var vendors = [];
    mod.setVendors = function(v) {
        vendors = v;
    }

    function vendorSelect(elem) {
        if (openVendor == elem) {
            return;
        }
        if (openInput) {
            swapOut(openInput);
        } else if (openVendor) {
            vendorSave(openVendor);
        }
        var current = $(elem).html();
        var opts = '<option value=""></option>';
        for (var i=0; i<vendors.length; i++) {
            opts += '<option value="' + vendors[i].id + '"'; 
            if (vendors[i].name == current) {
                opts += ' selected';
            }
            opts += '>' + vendors[i].name + '</option>';
        }
        var input = '<select class="form-control input-sm chosen">' + opts + '</select>';
        $(elem).html(input);
        openVendor = elem;
        $('select.chosen').chosen();
    }

    function vendorSave(elem) {
        var itemID = $(elem).closest('tr').attr('data-item-id');
        var vendorID = $(elem).find('select').val();
        var vendorName = $(elem).find('select option:selected').text();
        var dataStr = 'id=' + itemID + '&vendor=' + vendorID;
        $(elem).html(vendorName);
        openVendor = false;
        $.ajax({
            type: 'POST',
            data: dataStr
        });
    }

    function swapIn(elem) {
        var name = $(elem).attr('class').replace(' editable', '');
        var input = '<input name="' + name + '" class="form-control input-sm" value="' + $(elem).html() + '" />';
        $(elem).html(input);
        $(elem).find('input').focus();
    };

    function swapOut(elem) {
        var entry = $(elem).find('input').val();
        var dataStr = $(elem).find('input').serialize();
        var itemID = $(elem).closest('tr').attr('data-item-id');
        dataStr += '&id=' + itemID;
        $(elem).html(entry);
        reTotal(elem);
        openInput = false;
        $.ajax({
            type: 'POST',
            data: dataStr
        });
    }

    function reTotal(elem) {
        var row = $(elem).closest('tr');
        var caseSize = $(row).find('td.caseSize').html() * 1;
        var cases = $(row).find('td.cases').html() * 1;
        var fraction = $(row).find('td.fractions').html() * 1;
        var cost = $(row).find('td.cost').html().replace('$', '');
        var ttl = (cases * cost) + ((fraction / caseSize) * cost);
        $(row).find('td.total').html('$' + Math.floor(ttl*100) / 100);
    };

    function autoClose() {
        if (openInput) {
            swapOut(openInput);
        }
    }

    mod.editRow = function(elem) {
        if (openInput == elem) {
            return;
        } else if (openInput) {
            swapOut(openInput);
        } else if (openVendor) {
            vendorSave(openVendor);
        }
        openInput = elem;
        swapIn(openInput);
        setTimeout(autoClose, 10000);
    };

    mod.initRows = function() {
        $('td.editable').click(function() {
            mod.editRow(this);
        });
        $('td.vendor').click(function() {
            vendorSelect(this);
        });
    }

    return mod;

})(jQuery);
