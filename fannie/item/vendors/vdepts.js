var vDept = (function($) {
    var mod = {};

    var showError = function(msg) {
        showBootstrapAlert('#alert-area', 'danger', msg);
    };

    mod.deleteCat = function(num,name){
        var vid = $('#vendorID').val();
        if (window.confirm('Delete '+name+' ('+num+')?')){
            $.ajax({
                url: 'VendorDepartmentEditor.php',
                type: 'POST',
                dataType: 'json',
                timeout: 5000,
                data: 'deptID='+num+'&vid='+vid+'&action=deleteCat'
            }).fail(function(){
                showError('Network error deleting #' + num);
            }).done(function(resp){
                if (resp.error) {
                    showError(resp.error);
                } else {
                    $('#row-'+num).hide();
                }
            });
        }
    };

    mod.newdept = function(){
        var vid = $('#vendorID').val();
        var num = $('#newno').val();
        var name = $('#newname').val();

        $.ajax({
            url: 'VendorDepartmentEditor.php',
            type: 'POST',
            dataType: 'json',
            timeout: 5000,
            data: 'deptID='+num+'&vid='+vid+'&name='+name+'&action=createCat'
        }).fail(function(){
            showError('Network error creating department');
        }).done(function(resp){
            if (resp.error) {
                showError(resp.error);
            } else if (resp.row) {
                $('.table').append(resp.row);
                $('#newform').hide();
                $('#newform :input').each(function(){
                    $(this).val('');
                });
            } else {
                showError('Error: invalid response from server');
            }
        });
    };

    mod.save = function(did)
    {
        var name = $('#in'+did).val();
        var margin = $('#im'+did).val();
        var pos = $('#ip'+did).val();
        var vid = $('#vendorID').val();

        $('#nametd'+did).html(name);
        $('#margintd'+did).html(margin);
        $('#posdepttd'+did).html(pos);

        $('#button'+did+' .edit-link').show();
        $('#button'+did+' .save-link').hide();

        name = encodeURIComponent(name);
        $.ajax({
            url: 'VendorDepartmentEditor.php',
            type: 'POST',
            dataType: 'json',
            timeout: 5000,
            data: 'deptID='+did+'&vid='+vid+'&name='+name+'&margin='+margin+'&pos='+pos+'&action=updateCat'
        }).fail(function(){
            showError('Network error saving #' + did);
        }).done(function(resp){
            if (resp.error) {
                showError(resp.error);
            } else {
                showBootstrapAlert('#alert-area', 'success', 'Saved #' + did);
            }
        });
    };

    var inputTag = function(did, prefix, value) {
        var ret = '<input id="' + prefix + did + '" type="text" '
            + 'class="form-control save-' + did + '" '
            + 'value="' + value + '" />';

        return ret;
    };

    mod.edit = function(did)
    {
        var name = $('#nametd'+did).html();
        var margin = $('#margintd'+did).html();
        var pos = $('#posdepttd'+did).html();

        $('#nametd'+did).html(inputTag(did, 'in', name));
        $('#margintd'+did).html(inputTag(did, 'im', margin));
        $('#posdepttd'+did).html(inputTag(did, 'ip', pos));

        $('#button'+did+' .edit-link').hide();
        $('#button'+did+' .save-link').show();
        $('#im'+did).focus();
        $('.save-'+did).keydown(function(event) {
            if (event.which === 13) {
                mod.save(did);
            }
        });
    };

    return mod;

}(jQuery));
