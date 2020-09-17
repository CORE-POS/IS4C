
var msi = (function ($) {

    var mod = {};

    function getItemRow(type, col) {

        var metatype = type.toLowerCase().replace(/ /g, '');

        var ret = '<tr><td><input type="hidden" name="col[]" value="' + col + '" />';
        ret += '<input type="hidden" name="itemID[]" value="0" />';
        ret += '<input type="hidden" name="type[]" value="' + metatype + '" />';
        ret += '<select name="align[]" class="form-control">';
        ret += '<option>Left</option>';
        ret += '<option>Center</option>';
        ret += '<option>Right</option>';
        ret += '</select></td>';
        ret += '<td class="form-inline">';

        switch (type) {
            case 'Priced Item':
                ret += '<label>Name</label>: <input type="text" class="form-control" name="text[]" /> ';
                ret += '<label>Price</label>: <input type="text" class="form-control" name="price[]" /> ';
                ret += '<input type="hidden" class="form-control" name="price2[]" />';
                ret += '<input type="hidden" class="form-control" name="option[]" />';
                ret += '<input type="hidden" class="form-control" name="extra[]" />';
                break;
            case 'Description':
                ret += '<label>Description</label>: <input type="text" class="form-control" name="text[]" />';
                ret += '<input type="hidden" class="form-control" name="price[]" />';
                ret += '<input type="hidden" class="form-control" name="price2[]" />';
                ret += '<input type="hidden" class="form-control" name="option[]" />';
                ret += '<input type="hidden" class="form-control" name="extra[]" />';
                break;
            case 'Divider':
                ret += '<input type="hidden" class="form-control" name="text[]" />';
                ret += '<input type="hidden" class="form-control" name="price[]" />';
                ret += '<input type="hidden" class="form-control" name="price2[]" />';
                ret += '<input type="hidden" class="form-control" name="option[]" />';
                ret += '<input type="hidden" class="form-control" name="extra[]" />';
                ret += '(Divider)';
            case 'Dual Priced Item':
                ret += '<label>Name</label>: <input type="text" class="form-control" name="text[]" /> ';
                ret += '<label>Price 1</label>: <input type="text" class="form-control" name="price[]" /> ';
                ret += '<label>Price 2</label>: <input type="text" class="form-control" name="price2[]" /> ';
                ret += '<input type="hidden" class="form-control" name="option[]" />';
                ret += '<input type="hidden" class="form-control" name="extra[]" />';
                break;
            case 'Header':
                ret += '<label>Header</label>: <input type="text" class="form-control" name="text[]" /> ';
                ret += '<input type="hidden" class="form-control" name="price[]" />';
                ret += '<input type="hidden" class="form-control" name="price2[]" />';
                ret += '<input type="hidden" class="form-control" name="option[]" />';
                ret += '<input type="hidden" class="form-control" name="extra[]" />';
                break;
            case 'Sandwich Step':
                ret += '<label>Step</label>: <input type="text" class="form-control" name="text[]" /> ';
                ret += '<label>Options</label>: <input type="text" class="form-control" name="option[]" /> ';
                ret += '<label>Extra</label>: <input type="text" class="form-control" name="extra[]" /> ';
                ret += '<input type="hidden" class="form-control" name="price[]" />';
                ret += '<input type="hidden" class="form-control" name="price2[]" />';
                break;
        }

        ret += '</td>';
        ret += '<td><a href="" onclick="msi.deleteItem(this); return false;" ><span class="glyphicon glyphicon-trash"</span></a>';
        ret += '</tr>';

        return ret;
    };

    mod.deleteItem = function(elem) {
        var itemID = $(elem).closest('tr').find('input[name="itemID[]"]').val();
        var deleteID = '<input type="hidden" name="del[]" value="' + itemID + '" />';
        console.log(deleteID);
        $('#menu-items-form').append(deleteID);
        $(elem).closest('tr').remove();
    };

    mod.addItem = function() {
        var type = $('#newItemType').val();
        var col = $('#newItemCol').val();
        var row = getItemRow(type, col);

        $('table#cols' + col).append(row);
    };

    mod.enableSorting = function() {
        $('table.cols-table').sortable({
            items: 'tr'
        });
    };

    return mod;

})(jQuery);

