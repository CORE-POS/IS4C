var recipe = (function ($) {
    var mod = {};

    mod.show = function(id) {
        $.ajax({
            url: 'RecipeViewer.php',
            data: 'id='+id,
            type: 'get',
        }).done(function (resp) {
            $('#recipeContent').html(resp);
            window.scrollTo(0,0);
        });
    };

    mod.save = function(id) {
        console.log('saving');
        var data = 'id=' + id + '&' + $('.edit-field').serialize();
        console.log(data);
        $.ajax({
            url: 'RecipeEditor.php',
            data: data,
            type: 'post'
        }).done(function() {
        });
    }

    mod.edit = function(id) {
        $.ajax({
            url: 'RecipeEditor.php',
            data: 'id='+id,
            type: 'get',
        }).done(function (resp) {
            $('#recipeContent').html(resp);
            window.scrollTo(0,0);
            $('.edit-field').change(function() {
                mod.save(id);
            });
        });
    };

    mod.print = function(id) {
        window.open('RecipeViewer.php?id='+id, 'printRecipe', 'height=500,width=600');
    };

    return mod;

}(jQuery));
