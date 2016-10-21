var batchTypeEditor = (function($) {
    var mod = {};
    var saveBtField = function(action, val, bid, elem, orig) {
        $.ajax({
            url: 'BatchTypeEditor.php',
            type: 'post',
            data: action+'='+val+'&bid='+bid,
            dataType: 'json'
        }).done(function(data){
            showBootstrapPopover(elem, orig, data.error);
        });

    };

    var checkedVal = function(elem) {
        var val = 0;
        if ($(elem).prop('checked')) {
            val = 1;
        }
        return val;
    }

    mod.saveDesc = function(val,bid){
        saveBtField('saveDesc', val, bid, $(this), this.defaultValue);
    };

    mod.saveType = function(val,bid){
        saveBtField('saveType', val, bid, $(this), this.defaultValue);
    };

    mod.saveDated = function(bid){
        var val = checkedVal(this);
        saveBtField('saveDated', val, bid, $(this), this.defaultValue);
    };

    mod.saveSO = function(bid){
        var val = checkedVal(this);
        saveBtField('saveSO', val, bid, $(this), this.defaultValue);
    };

    mod.savePartial = function(bid){
        var val = checkedVal(this);
        saveBtField('savePartial', val, bid, $(this), this.defaultValue);
    };

    mod.saveUI = function(val,bid){
        saveBtField('saveUI', val, bid, $(this), this.defaultValue);
    };

    return mod;

}(jQuery));

