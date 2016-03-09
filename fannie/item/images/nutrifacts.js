var nutriFacts = (function($) {
    var mod = {};

    var setByName = function(name, val) {
        if (typeof val !== 'undefined') {
            $('input[name='+name+']').val(val);
        }
    };

    mod.doLookup = function(e) {
        $.ajax({
            url: 'NutriFacts.php',
            data: 'id=' + $(this).val(),
            dataType: 'json',
        }).done(function(resp) {
            if (resp.description) {
                $('#item-name').html(resp.description);
            }
            setByName('serving_size', resp.servingSize);
            setByName('calories', resp.calories);
            setByName('fat_calories', resp.fatCalories);
            setByName('total_fat', resp.totalFat);
            setByName('sat_fat', resp.saturatedFat);
            setByName('trans_fat', resp.transFat);
            setByName('cholest', resp.cholesterol);
            setByName('sodium', resp.sodium);
            setByName('total_carb', resp.totalCarbs);
            setByName('fiber', resp.fiber);
            setByName('sugar', resp.sugar);
            setByName('protein', resp.protein);

            for (var i=0; i<resp.opts.length; i++) {
                $('input.nutrient-in')
                    .filter(function(){console.log(this.value);return this.value==='';})
                    .first().val(resp.opts[i].name);
                $('input.dv-in')
                    .filter(function(){console.log(this.value);return this.value==='';})
                    .first().val(resp.opts[i].percentDV);
            }
        });
    };

    return mod;
}(jQuery));

$(document).ready(function() {
    $('input[name=upc]').change(nutriFacts.doLookup);
});
