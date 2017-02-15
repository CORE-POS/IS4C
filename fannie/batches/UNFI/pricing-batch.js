var vid = null;
var bid = null;
var sid = null;
var qid = null;
$(document).ready(function(){
    vid = $('#vendorID').val();
    bid = $('#batchID').val();
    sid = $('#superID').val();
    qid = $('#queueID').val();
});
function addToBatch(upc)
{
    var dstr = "upc="+upc+"&vendorID="+vid+"&queueID="+qid+"&batchID="+bid;
    var price = $('#row'+upc).find('.srp').html();
    $.ajax({
        url: 'batchAjax.php',
        data: dstr + '&action=batchAdd&price='+price
    }).done(function(data){
        $('#row'+upc).attr('class','selection');
        $('#row'+upc).find('.add-button').hide();
        $('#row'+upc).find('.remove-button').show();
    });
}
function removeFromBatch(upc)
{
    var dstr = "upc="+upc+"&vendorID="+vid+"&queueID="+qid+"&batchID="+bid;
    $.ajax({
        url: 'batchAjax.php',
        data: dstr + '&action=batchDel'
    }).done(function(data){
        if ($('tr#row'+upc+' input.varp:checked').length > 0)
            $('#row'+upc).attr('class','white');
        else if ($('tr#row'+upc+' td.price').html() < $('tr#row'+upc+' td.srp').html())
            $('#row'+upc).attr('class','red');
        else
            $('#row'+upc).attr('class','green');

        $('#row'+upc).find('.add-button').show();
        $('#row'+upc).find('.remove-button').hide();
    });
}
function toggleV(upc){
    var val = $('#row'+upc).find('.varp').prop('checked');
    if (val){
        $('#row'+upc).attr('class','white');
        $.ajax({
            url: 'batchAjax.php',
            data: 'action=addVarPricing&upc='+upc
        });
    }
    else {
        var m1 = $('#row'+upc).find('.cmargin').html();
        var m2 = $('#row'+upc).find('.dmargin').html();
        if (m1 >= m2)
            $('#row'+upc).attr('class','green');
        else
            $('#row'+upc).attr('class','red');
        $.ajax({
            url: 'batchAjax.php',
            data: 'action=delVarPricing&upc='+upc
        });
    }
}

function reprice(upc){
    if ($('#newprice'+upc).length > 0) return;

    var elem = $('#row'+upc).find('.srp');
    var srp = elem.html();

    var content = "<div class=\"form-inline input-group\"><span class=\"input-group-addon\">$</span>";
    content += "<input type=\"text\" id=\"newprice"+upc+"\" value=\""+srp+"\" class=\"form-control\" size=4 /></div>";
    var content2 = "<button type=\"button\" onclick=\"saveprice('"+upc+"');\" class=\"btn btn-default\">Save</button>";
    elem.html(content);
    $('#row'+upc).find('.dmargin').html(content2);
    $('#newprice'+upc).focus().select();
}

function saveprice(upc){
    var srp = parseFloat($('#newprice'+upc).val());
    var cost = parseFloat($('#row'+upc).find('.adj-cost').html());
    var newmargin = (srp - cost) / srp;
    newmargin *= 100;
    newmargin = Math.round(newmargin*100)/100;

    $('#row'+upc).find('.srp').html(srp);
    $('#row'+upc).find('.dmargin').html(newmargin+'%');

    var dstr = "upc="+upc+"&vendorID="+vid+"&queueID="+qid+"&batchID="+bid;
    $.ajax({
        url: 'batchAjax.php',
        data: dstr+'&action=newPrice&price='+srp+'&batchID='+bid,
        cache: false
    });
}

