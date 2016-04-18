var skuMap = (function($) {

    var mod = {};

    mod.deleteRow = function(id, sku, plu, elem) {
        if (window.confirm('Delete entry for PLU #' + plu + '?')) {
            $.ajax({
                type: 'delete',
                data: 'id='+id+'&sku='+sku+'&plu='+plu,
                dataType: 'json'
            }).done(function (resp) {
                if (resp.error) {
                    window.alert('Error deleting PLU #' + plu);
                } else {
                    $(elem).closest('tr').remove();
                }
            }).fail(function (resp) {
                window.alert('Error deleting PLU #' + plu);
            });
        }

        return false;
    };

    return mod;

}(jQuery));
