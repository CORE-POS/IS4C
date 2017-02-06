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

    mod.addRow = function(elem) {
        var newID = "inp_" + Math.random().toString(16).slice(2);
        var newrow = '<tr>';
        newrow += '<td><input type="hidden" name="ingID[]" value="0" /><input id="' + newID + '" type="text" class="form-control input-sm edit-field" name="amount[]" /></td>';
        newrow += '<td><input type="text" class="form-control input-sm edit-field" name="unit[]" /></td>';
        newrow += '<td><input type="text" class="form-control input-sm edit-field" name="name[]" /></td>';
        newrow += '<td><input type="text" class="form-control input-sm edit-field" name="notes[]" /></td>';
        newrow += '<td><a class="btn btn-success btn-xs" href="" onclick="recipe.addRow(this); return false;">';
        newrow += '<span class="glyphicon glyphicon-plus"></span></a>';
        newrow += ' <a class="btn btn-danger btn-xs" href="" onclick="recipe.delRow(this); return false;">';
        newrow += '<span class="glyphicon glyphicon-minus"></span></a>';
        newrow += '</td>';
        newrow += '</tr>';
        $(elem).closest('tr').after(newrow);
        $('#' + newID).focus();
    };

    mod.delRow = function(elem) {
        $(elem).closest('tr').remove();
    };

    return mod;

}(jQuery));
