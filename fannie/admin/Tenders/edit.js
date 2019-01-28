var tenderEditor = (function($) {
    var mod = {};
    var ajax_url = 'TenderEditor.php';

    var _popover = function(data, elem, orig) {
        var timeout=1500;
        if (data === "") {
            data = 'Saved!';
        } else {
            elem.val(orig);
            timeout = 3000;
        }
        elem.popover({
            html: true,
            content: data,
            placement: 'auto bottom'
        });
        elem.popover('show');
        setTimeout(function(){elem.popover('destroy'); }, timeout);
    };

    var _saveField = function(f_name, val, t_id, ths) {
        var elem = $(ths);
        var orig = ths.defaultValue;
        $.ajax({
            type:'post',
            url: ajax_url,
            data: f_name+'='+val+'&id='+t_id
        }).done(function(data){
            _popover(data, elem, orig);
        });
    };

    mod.saveCode = function(val,t_id){
        _saveField('saveCode', val, t_id, this);
    };

    mod.saveName = function(val,t_id){
        _saveField('saveName', val, t_id, this);
    };

    mod.saveType = function(val,t_id){
        _saveField('saveType', val, t_id, this);
    };

    mod.saveCMsg = function(val,t_id){
        _saveField('saveCMsg', val, t_id, this);
    };

    mod.saveMin = function(val,t_id){
        _saveField('saveMin', val, t_id, this);
    };

    mod.saveMax = function(val,t_id){
        _saveField('saveMax', val, t_id, this);
    };

    mod.saveRLimit = function(val,t_id){
        _saveField('saveRLimit', val, t_id, this);
    };

    mod.saveSalesCode = function(val, t_id){
        _saveField('saveSalesCode', val, t_id, this);
    };

    mod.addTender = function(){
        $.ajax({
            type:'post',
            url: ajax_url,
            data:'newTender=yes'
        }).done(function(data){
            $('#mainDisplay').html(data);
        });
    };

    return mod;
}(jQuery));
