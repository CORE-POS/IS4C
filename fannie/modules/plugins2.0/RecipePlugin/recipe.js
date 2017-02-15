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

    var trapEnter = function(ev) {
        if (ev.which == 13) {
            ev.preventDefault();
            mod.addRow(this);
            return false;
        }
    };

    mod.addRow = function(elem) {
        var newID = Math.random().toString(16).slice(2);
        var newrow = '<tr id="tr' + newID+ '">';
        newrow += '<td><input type="hidden" class="edit-field" name="ingID[]" id="id_'+newID+'" value="id_'+newID+'" />';
        newrow += '<input id="inp_' + newID + '" type="text" class="form-control input-sm edit-field" name="amount[]" /></td>';
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
        $('#tr' + newID + ' .edit-field').keyup(trapEnter);
        $('#inp_' + newID).focus();
    };

    var saving = false;

    mod.save = function(id) {
        if (saving) {
            return;
        }
        saving = true;
        var data = 'id=' + id + '&' + $('.edit-field').serialize();
        $.ajax({
            url: 'RecipeEditor.php',
            data: data,
            dataType: 'json',
            type: 'post'
        }).done(function(resp) {
            // response:
            // real SQL IDs for values that were added since the most
            // recent save
            // [ { fakeID:foo, realID:bar }, { fakeID:foo2, realID:bar2 }, ... ]
            if (resp.length > 0) {
                for (var i=0; i<resp.length; i++) {
                    var entry = resp[i];
                    if (entry && entry.fakeID && entry.realID) {
                        $('#'+entry.fakeID).val(entry.realID);
                    }
                }
            }
            saving = false;
        }).fail(function() {
            saving = false;
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
            $('table .edit-field').keyup(trapEnter);
        });
    };

    mod.print = function(id) {
        window.open('RecipeViewer.php?id='+id, 'printRecipe', 'height=500,width=600');
    };


    mod.delRow = function(elem) {
        $(elem).closest('tr').remove();
    };

    return mod;

}(jQuery));
