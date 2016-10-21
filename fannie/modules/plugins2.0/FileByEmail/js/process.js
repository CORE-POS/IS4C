function processFile()
{
    var path = $('#savePath').val();
    var newname = $('#saveFilename').val();
    var current = $('#curName').val();

    if (newname == '') {
        showBootstrapAlert('#input-area', 'danger', 'Filename is required');
        return false;
    }

    $.ajax({
        type: 'post',
        dataType: 'json',
        data: 'path='+path+'&current='+current+'&new='+newname,
        success: function(resp) {
            if (resp.error) {
                showBootstrapAlert('#alert-area', 'danger', resp.error);
            } else if (resp.all_done) {
                $('#preview').attr('data', '');
                $('#curName').val('');
                showBootstrapAlert('#alert-area', 'warning', 'No more files');
                $('#saveFilename').val('');
            } else if (resp.next && resp.encoded) {
                $('#preview').attr('data', 'noauto/queue/'+resp.next);
                $('#curName').val(resp.encoded);
                $('#saveFilename').val('');
                $('#saveFilename').focus();
            } else {
                showBootstrapAlert('#alert-area', 'danger', 'Error: unknown response');
            }
        }
    });
}
