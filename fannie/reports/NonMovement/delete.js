var nonMovement = (function($) {
    var mod = {};
    mod.backgroundDelete = function(upc, description)
    {
        if (!window.confirm('Delete '+upc+' '+description)){
            return false;
        }

        $.ajax({
            url: 'NonMovementReport.php',
            data: 'deleteItem='+upc
        }).done(function() {
            $('#del'+upc).closest('tr').remove();
        });
    };

    mod.backgroundDeactivate = function(upc)
    {
        $.ajax({
            url: 'NonMovementReport.php',
            data: 'deactivate='+upc
        }).done(function() {
            $('#del'+upc).closest('tr').remove();
        });
    }

    return mod;

}(jQuery));

