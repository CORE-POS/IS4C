var marginTool = (function($) {
    var mod = {};

    mod.createBatch = function() {
        var cArray = Array();
        var pArray = Array();
        var uArray = Array();
        $('.itemrow').each(function(){
            cArray.push($(this).find('.currentprice').html());
            pArray.push($(this).find('.newprice').val());
            uArray.push($(this).find('.itemupc').val());
        });

        var changeUPC = Array();
        var changePrice = Array();
        for(var i=0; i<pArray.length; i++) {
            if (pArray[i] != cArray[i]) {
                changePrice.push(pArray[i]);
                changeUPC.push(uArray[i]);
            }
        }

        if (changePrice.length == 0) {
            window.alert('No prices have been changed!');
            return;
        }

        var prices = JSON.stringify(changePrice);
        var upcs = JSON.stringify(changeUPC);
        var tags = $('#shelftagSet').val();
        var batchName = $('#batchName').val();
        var dstr = 'newbatch='+batchName+'&tags='+tags+'&upcs='+upcs+'&newprices='+prices;
        $.ajax({
            url: 'MarginToolFromSearch.php',
            type: 'post',
            data: dstr
        }).done(function(resp) {
            window.location = resp;
        });
    };

    mod.reCalc = function(upc, price, cost, deptID, superID) {
        var newprice = Number(price);
        if (cost == 0 || isNaN(newprice)) {
            return false;
        }

        var curprice = Number($('#row'+upc).find('.currentprice').html());
        if (curprice == newprice) {
            $('#row'+upc).css('font-weight', 'normal');
            $('#row'+upc+' td').each(function() {
                $(this).css('background-color', '');
            });
        } else {
            $('#row'+upc).css('font-weight', 'bold');
            $('#row'+upc+' td').each(function() {
                $(this).css('background-color', '#ffc');
            });
        }

        var itemMargin = (price - cost) / price * 100;
        itemMargin = Math.round(itemMargin * 10000) / 10000;
        $('#margin'+upc).html(itemMargin+"%");

        // get all prices for items in the department
        // currently being displayed (and editable)
        var pArray = Array();
        var uArray = Array();
        $('.dept'+deptID).each(function(){
            pArray.push($(this).find('.newprice').val());
            uArray.push($(this).find('.itemupc').val());
        });
        var prices = JSON.stringify(pArray);
        var upcs = JSON.stringify(uArray);

        $.ajax({
            url: 'MarginToolFromSearch.php',
            type: 'post',
            data: 'upcs='+upcs+'&deptID='+deptID+'&newprices='+prices
        }).done(function(resp) {
            $('#dmargin'+deptID).html(resp+"%");
        });

        // get all prices for items in the superdepartment
        // currently being displayed (and editable)
        var pArray = Array();
        var uArray = Array();
        $('.super'+superID).each(function(){
            pArray.push($(this).find('.newprice').val());
            uArray.push($(this).find('.itemupc').val());
        });
        var prices = JSON.stringify(pArray);
        var upcs = JSON.stringify(uArray);

        $.ajax({
            url: 'MarginToolFromSearch.php',
            type: 'post',
            data: 'upcs='+upcs+'&superID='+superID+'&newprices='+prices
        }).done(function(resp) {
            $('#smargin'+superID).html(resp+"%");
        });
    };

    return mod;

}(jQuery));
