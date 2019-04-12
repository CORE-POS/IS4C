var di = (function ($) {
    var mod = {};

    var openInput = false;
    var openVendor = false;
    var openCategory = false;
    var autoCloseTimeout = false;
    var vendors = [];
    mod.setVendors = function(v) {
        vendors = v;
    }

    var categories = [];
    mod.setCategories = function(c) {
        categories = c;
    }

    mod.debug = function() {
        return openInput;
    }

    function vendorSelect(elem) {
        if (openVendor == elem) {
            return;
        }
        if (openInput) {
            swapOut(openInput);
        } else if (openVendor) {
            vendorSave(openVendor);
        } else if (openCategory) {
            categorySave(openCategory);
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
        }).error(function () {
            alert('Error! may have lost connection');
        });
    }

    function categorySelect(elem) {
        if (openCategory == elem) {
            return;
        }
        if (openInput) {
            swapOut(openInput);
        } else if (openVendor) {
            vendorSave(openVendor);
        } else if (openCategory) {
            categorySave(openCategory);
        }
        var current = $(elem).html();
        var opts = '<option value=""></option>';
        for (var i=0; i<categories.length; i++) {
            opts += '<option value="' + categories[i].id + '"'; 
            if (categories[i].name == current) {
                opts += ' selected';
            }
            opts += '>' + categories[i].name + '</option>';
        }
        var input = '<select class="form-control input-sm chosen">' + opts + '</select>';
        $(elem).html(input);
        openCategory = elem;
        $('select.chosen').chosen();
    }

    function categorySave(elem) {
        var itemID = $(elem).closest('tr').attr('data-item-id');
        var catID = $(elem).find('select').val();
        var catName = $(elem).find('select option:selected').text();
        var dataStr = 'id=' + itemID + '&catID=' + catID + '&category=' + encodeURIComponent(catName);;
        $(elem).html(catName);
        openCategory = false;
        $.ajax({
            type: 'POST',
            data: dataStr
        }).error(function () {
            alert('Error! may have lost connection');
        }).done(function () {
            var dest = $('table.inventory-table[data-cat-id*=' + catID + ']');
            if (dest.length > 0) {
                var row = $(elem).closest('tr').remove().clone();
                dest.append(row);
                $(row).find('td.editable').click(function() {
                    mod.editRow(this);
                });
                $(row).find('td.vendor').click(function() {
                    vendorSelect(this);
                });
                $(row).find('td.category').click(function () {
                    categorySelect(this);
                });
            }
        });
    }

    function keyNav(ev) {
        switch (ev.which) {
            case 9:
                ev.preventDefault();
                if (ev.shiftKey) {
                    var next = $(openInput).prev('td.editable').get(0);
                    if (!next) {
                        var next = $(openInput).closest('tr').prev('tr').find('td.cost').get(0);
                    }
                    swapOut(openInput);
                    if (next) {
                        mod.editRow(next);
                    }
                } else {
                    var next = $(openInput).next('td.editable').get(0);
                    if (!next) {
                        var next = $(openInput).closest('tr').next('tr').find('td.editable').get(0);
                    }
                    swapOut(openInput);
                    if (next) {
                        mod.editRow(next);
                    }
                }
                break;
            case 13:
                ev.preventDefault();
                var columnName = $(openInput).find('input').attr('name');
                var next = $(openInput).closest('tr').next('tr').find('td.' + columnName).get(0);
                swapOut(openInput);
                if (next) {
                    mod.editRow(next);
                }
                break;
        }
    }

    function swapIn(elem) {
        var name = $(elem).attr('class').replace(' editable', '');
        var input = '<input name="' + name + '" class="form-control input-sm" value="' + $(elem).html() + '" />';
        $(elem).html(input);
        $(elem).find('input').focus().select();
        $(elem).find('input').keydown(keyNav);
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
        }).error(function () {
            alert('Error! may have lost connection');
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
        } else if (openCategory) {
            categorySave(openCategory);
        }
        openInput = elem;
        swapIn(openInput);
        if (autoCloseTimeout) {
            clearTimeout(autoCloseTimeout);
        }
        autoCloseTimeout = setTimeout(autoClose, 10000);
    };

    mod.initRows = function() {
        $('td.editable').click(function() {
            mod.editRow(this);
        });
        $('td.vendor').click(function() {
            vendorSelect(this);
        });
        $('td.category').click(function () {
            categorySelect(this);
        });
        $('table.inventory-table').sortable({
            items: 'tr',
            connectWith: 'table.inventory-table',
            stop: function (ev, ui) {
                var catID = $(ui.item).closest('table').attr('data-cat-id');
                var dstr = 'catID=' + catID;
                $(ui.item).closest('table').find('tr').each(function() {
                    if ($(this).attr('data-item-id')) {
                        dstr += '&seq[]=' + $(this).attr('data-item-id');
                    }
                });
                $.ajax({
                    type: 'post',
                    data: dstr
                }).error(function () {
                    alert('Error! may have lost connection');
                });
            }
        });
    }

    mod.showSourcing = function() {
        $('.upc').show();
        $('.sku').show();
        $('.vendor').show();
        $('.trash').show();
        $('.cases').css({opacity: 1});
        $('.fractions').css({opacity: 1});
    };

    mod.hideSourcing = function() {
        $('.upc').hide();
        $('.sku').hide();
        $('.vendor').hide();
        $('.trash').hide();
        $('.cases').css({opacity: 0});
        $('.fractions').css({opacity: 0});
    };

    mod.attention = function(elem) {
        var row = $(elem).closest('tr');
        var itemID = row.attr('data-item-id');
        var dstr = 'id=' + itemID;
        if ($(elem).prop('checked')) {
            row.addClass('danger');
            dstr += '&flag=1';
        } else {
            row.removeClass('danger');
            dstr += '&flag=0';
        }
        $.ajax({
            type: 'post',
            data: dstr
        }).error(function () {
            alert('Error! may have lost connection');
        });
    };

    return mod;

})(jQuery);
