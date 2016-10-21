
var productList = (function($) {
    var mod = {};

    var drawCheckbox = function(elem, cell, field) {
        var fs = elem.find('.' + cell + ':first').html();
        var content = "<input type=checkbox class=" + field + " "+((fs==='X')?'checked':'')+" />";
        elem.find('.' + cell + ':first').html(content);
    };

    var drawTextBox = function(elem, cell, field, size) {
        var brand = elem.find('.' + cell + ':first').html();
        var content = "<input type=text class=\"" + field + " form-control input-sm\" size="+size+" value=\""+brand+"\" />";   
        elem.find('.' + cell + ':first').html(content);
    };

    var drawKeyValSelect = function(elem, cell, field, obj) {
        var dept = elem.find('.' + cell + ':first').text();
        var content = "<select class=\"" + field + " form-control input-sm\"><optgroup style=\"font-size: 90%;\">";
        for (var i in obj) {
            if (obj.hasOwnProperty(i)) {
                content += "<option value=\""+i+"\" "+((dept==obj[i])?'selected':'')+">";
                content += obj[i]+"</option>";
            }
        }
        content += '</optgroup></select>';
        elem.find('.' + cell + ':first').html(content);
    };

    var drawTupleSelect = function(elem, cell, field, obj) {
        var tax = elem.find('.' + cell + ':first').html();
        console.log(field);
        var content = "<select class=\"" + field + " form-control input-sm\">";
        for (var ch in obj) {
            if (obj.hasOwnProperty(ch)) {
                var t_sel = (tax == ch) ? 'selected' : '';
                content += "<option value=\""+ch+":"+obj[ch][0]+"\" "+t_sel+">";
                content += obj[ch][1]+"</option>";
            }
        }
        elem.find('.' + cell + ':first').html(content);
    };

    mod.edit = function(elem) {
        var text = [{name:'brand',size:8}, {name:'desc',size:10}, {name:'cost',size:4}, {name:'price',size:4}];
        text.forEach(function(i) {
            drawTextBox(elem, 'td_'+i.name, 'in_'+i.name, i.size);
        });

        drawKeyValSelect(elem, 'td_dept', 'in_dept', deptObj);
        drawKeyValSelect(elem, 'td_supplier', 'in_supplier', vendorObj);

        var checks = ['fs', 'disc', 'wgt'];
        checks.forEach(function(i) {
            drawCheckbox(elem, 'td_'+i, 'in_'+i);
        });

        drawTupleSelect(elem, 'td_tax', 'in_tax', taxObj);
        drawTupleSelect(elem, 'td_local', 'in_local', taxObj);

        elem.find('.td_cmd:first .edit-link').hide();
        elem.find('.td_cmd:first .save-link').show();

        elem.find('input:text').keydown(function(event) {
            if (event.which === 13) {
                mod.save(elem);
            }
        });
        elem.find('.clickable input:text').click(function(event){
            // do nothing
            event.stopPropagation();
        });
        elem.find('.clickable select').click(function(event){
            // do nothing
            event.stopPropagation();
        });
    };

    var formToCell = function(elem, name, str) {
        var brand = elem.find('.in_' + name + ':first').val();
        elem.find('.td_' + name + ':first').html(brand);
        return str + '&' + name + '=' + encodeURIComponent(brand);
    };

    var checkBoxToCell = function(elem, name, str) {
        var fs = elem.find('.in_'+name+':first').is(':checked') ? 1 : 0;
        elem.find('.td_'+name+':first').html((fs===1)?'X':'-');
        return str + '&' + name + '=' + encodeURIComponent(fs);
    };

    mod.save = function(elem) {
        var upc = elem.find('.hidden_upc:first').val();
        var store_id = elem.find('.hidden_store_id:first').val();
        var dstr = 'ajax=save';

        mathField(elem.find('.in_cost:first').get(0));
        var cells = ['brand', 'desc', 'supplier', 'cost', 'price'];
        cells.forEach(function(i) {
            dstr = formToCell(elem, i, dstr);
        });

        var dept = elem.find('.in_dept:first').val();
        elem.find('.td_dept:first').html(deptObj[dept]);

        var tax = elem.find('.in_tax:first').val().split(':');
        elem.find('.td_tax:first').html(tax[0]);
        
        dstr = checkBoxToCell(elem, 'fs', dstr);
        dstr = checkBoxToCell(elem, 'disc', dstr);
        dstr = checkBoxToCell(elem, 'wgt', dstr);

        var local = elem.find('.in_local:first').val().split(':');
        elem.find('.td_local:first').html(local[0]);

        elem.find('.td_cmd:first .edit-link').show();
        elem.find('.td_cmd:first .save-link').hide();

        dstr += '&upc='+upc+'&dept='+dept+'&store_id='+store_id;
        dstr += '&tax='+tax[1]+'&local='+local[1];
        $.ajax({
            url: 'ProductListPage.php',
            data: dstr,
            type: 'post'
        });
    };

    mod.deleteCheck = function(upc,desc) {
        $.ajax({
            url: 'ProductListPage.php',
            data: 'ajax=deleteCheck&upc='+upc+'&desc='+desc,
            dataType: 'json',
            type: 'post'
        }).done(function(data) {
            if (data.alertBox && data.upc && data.enc_desc){
                if (window.confirm(data.alertBox)){
                    $.ajax({
                        url: 'ProductListPage.php',
                        data: 'ajax=doDelete&upc='+upc+'&desc='+data.enc_desc,
                        type: 'post'
                    }).done(function(){
                        $('#' + upc).remove();
                    });
                }
            } else {
                window.alert('Data error: cannot delete');
            }
        });
    };

    mod.enableEditing = function() {
        $('tr').each(function(){
            if ($(this).find('.hidden_upc').length !== 0) {
                $(this).find('.clickable').click(function() {
                    if ($(this).find(':input').length === 0) {
                        mod.edit($(this).closest('tr'));
                        $(this).find(':input').select();
                    }
                });
            }
        });
    };

    return mod;

}(jQuery));
