var productList = (function($) {
    var mod = {};

    mod.edit = function(elem) {
        var brand = elem.find('.td_brand:first').html();
        var content = "<input type=text class=\"in_brand form-control input-sm\" size=8 value=\""+brand+"\" />";   
        elem.find('.td_brand:first').html(content);

        var desc = elem.find('.td_desc:first').html();
        var content = "<input type=text class=\"in_desc form-control input-sm\" size=10 value=\""+desc+"\" />";   
        elem.find('.td_desc:first').html(content);

        var dept = elem.find('.td_dept:first').text();
        var content = '<select class=\"in_dept form-control input-sm\"><optgroup style="font-size: 90%;">';
        for(dept_no in deptObj){
            content += "<option value=\""+dept_no+"\" "+((dept==deptObj[dept_no])?'selected':'')+">";
            content += deptObj[dept_no]+"</option>";
        }
        content += '</optgroup></select>';
        elem.find('.td_dept:first').html(content);

        var supplier = elem.find('.td_supplier:first').text();
        var content = '<select class=\"in_supplier form-control input-sm\"><optgroup style="font-size: 90%;">';
        for(var i in vendorObj){
            content += "<option "+((supplier==vendorObj[i])?'selected':'')+">";
            content += vendorObj[i]+"</option>";
        }
        content += '</optgroup></select>';
        elem.find('.td_supplier:first').html(content);

        var cost = elem.find('.td_cost:first').html();
        var content = "<input type=text class=\"in_cost form-control input-sm\" size=4 value=\""+cost+"\" />";    
        elem.find('.td_cost:first').html(content);

        var price = elem.find('.td_price:first').html();
        var content = "<input type=text class=\"in_price form-control input-sm\" size=4 value=\""+price+"\" />";  
        elem.find('.td_price:first').html(content);

        var tax = elem.find('.td_tax:first').html();
        var content = '<select class=\"in_tax form-control input-sm\">';
        for (ch in taxObj){
            var sel = (tax == ch) ? 'selected' : '';
            content += "<option value=\""+ch+":"+taxObj[ch][0]+"\" "+sel+">";
            content += taxObj[ch][1]+"</option>";
        }
        elem.find('.td_tax:first').html(content);

        var fs = elem.find('.td_fs:first').html();
        var content = "<input type=checkbox class=in_fs "+((fs=='X')?'checked':'')+" />";
        elem.find('.td_fs:first').html(content);

        var disc = elem.find('.td_disc:first').html();
        var content = "<input type=checkbox class=in_disc "+((disc=='X')?'checked':'')+" />";
        elem.find('.td_disc:first').html(content);

        var wgt = elem.find('.td_wgt:first').html();
        var content = "<input type=checkbox class=in_wgt "+((wgt=='X')?'checked':'')+" />";
        elem.find('.td_wgt:first').html(content);

        var local = elem.find('.td_local:first').html();
        var content = '<select class=\"in_local form-control input-sm\">';
        for (ch in localObj){
            var sel = (local == ch) ? 'selected' : '';
            content += "<option value=\""+ch+":"+localObj[ch][0]+"\" "+sel+">";
            content += localObj[ch][1]+"</option>";
        }
        elem.find('.td_local:first').html(content);

        elem.find('.td_cmd:first .edit-link').hide();
        elem.find('.td_cmd:first .save-link').show();

        elem.find('input:text').keydown(function(event) {
            if (event.which == 13) {
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

    mod.save = function(elem) {
        var upc = elem.find('.hidden_upc:first').val();
        var store_id = elem.find('.hidden_store_id:first').val();

        var brand = elem.find('.in_brand:first').val();
        elem.find('.td_brand:first').html(brand);

        var desc = elem.find('.in_desc:first').val();
        elem.find('.td_desc:first').html(desc);

        var dept = elem.find('.in_dept:first').val();
        elem.find('.td_dept:first').html(deptObj[dept]);

        var supplier = elem.find('.in_supplier:first').val();
        elem.find('.td_supplier:first').html(supplier);

        mathField(elem.find('.in_cost:first').get(0));
        var cost = elem.find('.in_cost:first').val();
        elem.find('.td_cost:first').html(cost);

        var price = elem.find('.in_price:first').val();
        elem.find('.td_price:first').html(price);

        var tax = elem.find('.in_tax:first').val().split(':');
        elem.find('.td_tax:first').html(tax[0]);
        
        var fs = elem.find('.in_fs:first').is(':checked') ? 1 : 0;
        elem.find('.td_fs:first').html((fs==1)?'X':'-');

        var disc = elem.find('.in_disc:first').is(':checked') ? 1 : 0;
        elem.find('.td_disc:first').html((disc==1)?'X':'-');

        var wgt = elem.find('.in_wgt:first').is(':checked') ? 1 : 0;
        elem.find('.td_wgt:first').html((wgt==1)?'X':'-');

        var local = elem.find('.in_local:first').val().split(':');
        elem.find('.td_local:first').html(local[0]);

        elem.find('.td_cmd:first .edit-link').show();
        elem.find('.td_cmd:first .save-link').hide();

        var dstr = 'ajax=save&upc='+upc+'&dept='+dept+'&price='+price+'&cost='+cost;
        dstr += '&tax='+tax[1]+'&fs='+fs+'&disc='+disc+'&wgt='+wgt+'&supplier='+supplier+'&local='+local[1];
        dstr += '&brand='+encodeURIComponent(brand);
        dstr += '&desc='+encodeURIComponent(desc);
        dstr += '&store_id='+store_id;
        $.ajax({
            url: 'ProductListPage.php',
            data: dstr,
            cache: false,
            type: 'post'
        });
    };

    mod.deleteCheck = function(upc,desc) {
        $.ajax({
            url: 'ProductListPage.php',
            data: 'ajax=deleteCheck&upc='+upc+'&desc='+desc,
            dataType: 'json',
            cache: false,
            type: 'post'
        }).done(function(data) {
            if (data.alertBox && data.upc && data.enc_desc){
                if (confirm(data.alertBox)){
                    $.ajax({
                        url: 'ProductListPage.php',
                        data: 'ajax=doDelete&upc='+upc+'&desc='+data.enc_desc,
                        cache: false,
                        type: 'post'
                    }).done(function(data){
                        $('#' + upc).remove();
                    });
                }
            } else {
                alert('Data error: cannot delete');
            }
        });
    };

    mod.enableEditing = function() {
        $('tr').each(function(){
            if ($(this).find('.hidden_upc').length != 0) {
                var upc = $(this).find('.hidden_upc').val();
                $(this).find('.clickable').click(function() {
                    if ($(this).find(':input').length == 0) {
                        mod.edit($(this).closest('tr'));
                        $(this).find(':input').select();
                    }
                });
            }
        });
    };

    return mod;

}(jQuery));
