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
        $(this).find('.itemPrice').val(srp);
    });
}
function reCalcSRPs() {
    var info = $('form').serialize(); 
    info += '&redoSRPs=1';
    $.ajax({
        type: 'post',
        dataType: 'json',
        data: info
    }).done(function(resp) {
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
    });
}
function discount(amt) {
    $('tr.batchItem').each(function(){
        var price = $(this).find('.currentPrice').val();
        price = price - amt;
        $(this).find('.itemPrice').val(price);
    });
}
function markDown(amt) {
    if (Math.abs(amt) >= 1) amt = amt / 100;
    $('tr.batchItem').each(function(){
        var price = $(this).find('.currentPrice').val();
        price = price * (1 - amt);
        $(this).find('.itemPrice').val(price);
    });
}
function markUp(amt) {
    markDown(-1 * amt);
}
function noEnter(e) {
    if (e.keyCode == 13) {
        $(this).trigger('change');
        return false;
    }
}
