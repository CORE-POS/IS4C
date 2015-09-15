function discountTypeFixup() {
    var bt_id = $('#batchType').val();
    var dt_id = $('#discType'+bt_id).val();
    if (dt_id == 0) {
        $('#newPriceHeader').html('New Price');
        $('#saleTools').hide();
        $('#priceChangeTools').show();
    } else {
        $('#newPriceHeader').html('Sale Price');
        $('#saleTools').show();
        $('#priceChangeTools').hide();
    }
}
function useSRPs() {
    $('tr.batchItem').each(function(){
        var srp = $(this).find('.itemSRP').val();
        $(this).find('.itemPrice').val(fixupPrice(srp));
    });
}
function reCalcSRPs() {
    var info = $('form').serialize(); 
    info += '&redoSRPs=1';
    $.ajax({
        type: 'post',
        dataType: 'json',
        data: info,
        success: function(resp) {
            for (var i=0; i<resp.length; i++) {
                var item = resp[i];
                $('tr.batchItem').each(function(){
                    var upc = $(this).find('.itemUPC').val(); 
                    if (upc == item.upc) {
                        $(this).find('.itemSRP').val(item.srp);

                        return false;
                    }
                });
            }
        }
    });
}
function discount(amt) {
    $('tr.batchItem').each(function(){
        var price = $(this).find('.currentPrice').val();
        price = price - amt;
        $(this).find('.itemPrice').val(fixupPrice(price));
    });
}
function markDown(amt) {
    if (Math.abs(amt) >= 1) amt = amt / 100;
    $('tr.batchItem').each(function(){
        var price = $(this).find('.currentPrice').val();
        price = price * (1 - amt);
        $(this).find('.itemPrice').val(fixupPrice(price));
    });
}
function markUp(amt) {
    markDown(-1 * amt);
}
function fixupPrice(val) {
    var bt_id = $('#batchType').val();
    var dt_id = $('#discType'+bt_id).val();
    val = Math.round(val*100);
    if (dt_id == 0) {
        while(lastDigit(val) != 5 && lastDigit(val) != 9)
            val++;
    } else {
        while(lastDigit(val) != 9)
            val++;
    }
    return val / 100;
}
function lastDigit(val) {
    return val - (10 * Math.floor(val/10));
}
function noEnter(e) {
    if (e.keyCode == 13) {
        $(this).trigger('change');
        return false;
    }
}
