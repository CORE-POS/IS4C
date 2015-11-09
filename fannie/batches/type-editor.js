function saveBtField(action, val, bid, elem, orig)
{
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: action+'='+val+'&bid='+bid,
        dataType: 'json',
        success: function(data){
            showBootstrapPopover(elem, orig, data.error);
        }
    });

}
function saveDesc(val,bid){
    saveBtField('saveDesc', val, bid, $(this), this.defaultValue);
}
function saveType(val,bid){
    saveBtField('saveType', val, bid, $(this), this.defaultValue);
}
function saveDated(bid){
    var val = 0;
    if ($(this).prop('checked')) {
        val = 1;
    }
    saveBtField('saveDated', val, bid, $(this), this.defaultValue);
}
function saveSO(bid){
    var val = 0;
    if ($(this).prop('checked')) {
        val = 1;
    }
    saveBtField('saveSO', val, bid, $(this), this.defaultValue);
}
function saveUI(val,bid){
    saveBtField('saveUI', val, bid, $(this), this.defaultValue);
}
