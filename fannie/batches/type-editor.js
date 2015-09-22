function saveDesc(val,bid){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveDesc='+val+'&bid='+bid,
        dataType: 'json',
        success: function(data){
            showBootstrapPopover(elem, orig, data.error);
        }
    });
}
function saveType(val,bid){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveType='+val+'&bid='+bid,
        dataType: 'json',
        success: function(data){
            showBootstrapPopover(elem, orig, data.error);
        }
    });
}
function saveDated(bid){
    var elem = $(this);
    var val = 0;
    if ($(this).prop('checked')) {
        val = 1;
    }
    var orig = this.defaultValue;
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveDated='+val+'&bid='+bid,
        dataType: 'json',
        success: function(data){
            showBootstrapPopover(elem, orig, data.error);
        }
    });
}
function saveSO(bid){
    var elem = $(this);
    var val = 0;
    if ($(this).prop('checked')) {
        val = 1;
    }
    var orig = this.defaultValue;
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveSO='+val+'&bid='+bid,
        dataType: 'json',
        success: function(data){
            showBootstrapPopover(elem, orig, data.error);
        }
    });
}
function saveUI(val,bid){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveUI='+val+'&bid='+bid,
        dataType: 'json',
        success: function(data){
            showBootstrapPopover(elem, orig, data.error);
        }
    });
}
