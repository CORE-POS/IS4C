function toggleUpcLcInput()
{
    if ($('#addItemLikeCode').is(':checked')) {
        $('.add-by-upc-fields').hide();
        $('.add-by-upc-fields :input').prop('disabled', true);
        $('.add-by-lc-fields').show();
        $('.add-by-lc-fields :input').prop('disabled', false);
        $('#addItemLC').focus();
    } else {
        $('.add-by-lc-fields').hide();
        $('.add-by-lc-fields :input').prop('disabled', true);
        $('.add-by-upc-fields').show();
        $('.add-by-upc-fields :input').prop('disabled', false);
        $('#addItemUPC').focus();
    }
}

function advanceToPrice()
{
    var dataStr = $('#add-item-form').serialize();
    dataStr += '&id='+$('#batchID').val();
    $.ajax({
        type: 'post',
        dataType: 'json',
        data: dataStr,
        success: function(resp) {
            if (resp.error) {
                showBootstrapAlert('#inputarea', 'danger', resp.msg);
                $(resp.field).focus().select();
            } else if (resp.content) {
                $('#inputarea').html(resp.content);
                $(resp.field).focus();
            }
        }
    });
}

function addItemPrice(identifier)
{
    var dataStr = $('#add-price-form').serialize();
    dataStr += '&id='+$('#batchID').val();  
    dataStr += '&upc='+identifier;
    $.ajax({
        type: 'post',
        data: dataStr,
        dataType: 'json',
        success: function(resp) {
            $('#inputarea').html(resp.input);
            $('#addItemUPC').focus();
            if (resp.added) {
                $('#displayarea').html(resp.display);
            }
            if (/^LC\d+$/.test(identifier)) {
                $('#addItemLikeCode').click();
            }
        }
    });
}

function generateTags(batchID)
{
    $.ajax({
        type: 'post',
        data: 'id='+batchID+'&autotag=1',
        dataType: 'json',
        success: function(resp) {
            showBootstrapAlert('#inputarea', 'success', 'Generated ' + resp.tags + ' tags');
        }
    });
}

function forceNow(batchID)
{
    $('#progress-bar').show();
    $('#progress-bar .active').attr('title', 'Forcing Batch');
    $('#progress-bar .sr-only').attr('title', 'Forcing Batch');
    $.ajax({
        type: 'post',
        data: 'id='+batchID+'&force=1',
        dataType: 'json',
        error: function() {
            $('#progress-bar').hide();
            showBootstrapAlert('#inputarea', 'danger', 'Network Error forcing batch');
        },
        success: function(resp) {
            var type = 'success';
            if (resp.error) {
                type = 'danger';
            }
            showBootstrapAlert('#inputarea', type, resp.msg);
            $('#progress-bar').hide();
        }
    });
}

function unsaleNow(batchID)
{
    $.ajax({
        type: 'post',
        data: 'id='+batchID+'&unsale=1',
        dataType: 'json',
        success: function(resp) {
            var type = 'success';
            if (resp.error) {
                type = 'danger';
            }
            showBootstrapAlert('#inputarea', type, resp.msg);
        }
    });
}

function cutItem(upc, batchID, userID, cutOp)
{
    var dataStr = 'id=' + batchID + '&upc=' + upc + '&uid=' + userID + '&cut=' + cutOp;
    $.ajax({
        type: 'post',
        data: dataStr,
        success: function(resp)
        {
            if (cutOp) {
                $('#doCut'+upc).hide();
                $('#unCut'+upc).show();
            } else {
                $('#unCut'+upc).hide();
                $('#doCut'+upc).show();
            }
        }
    });
}

function deleteUPC(id, upc)
{
    var dataStr = '_method=delete&id='+id+'&upc='+upc;
    var clickedElem = $(this);
    $.ajax({
        type: 'post',
        data: dataStr,
        dataType: 'json',
        success: function(resp) {
            if (resp.error) {
                showBootstrapAlert('#inputarea', 'danger', resp.msg);
            } else {
                clickedElem.closest('tr').hide();
                if (/^LC\d+$/.test(upc)) {
                    var lc = upc.substring(2, upc.length);
                    $('.lc-item-'+lc).hide();
                }
            }
        }
    });
}

function editUpcPrice(upc)
{
    $('#editable-text-'+upc).hide();
    $('#editable-fields-'+upc).show();
    $('#editLink'+upc+' .edit').hide();
    $('#editLink'+upc+' .save').show();
    $('#editable-fields-'+upc + ' input[name=price]').focus();
}

function saveUpcPrice(upc)
{
    var dataStr = $('#editable-fields-'+upc+' :input').serialize();
    dataStr += '&id='+$('#batchID').val()+'&upc='+upc;

    $.ajax({
        type: 'post',
        data: dataStr,
        dataType: 'json',
        success: function(resp) {
            if (resp.error) {
                showBootstrapAlert('#inputarea', 'warning', resp.msg);
            } else {
                $('#editLink'+upc+' .edit').show();
                $('#editLink'+upc+' .save').hide();
                $('#item-qty-'+upc).html(resp.qty + ' for ');
                $('#sale-price-'+upc).html(resp.price);
                $('#editable-fields-'+upc+' input[name=qty]').val(resp.qty);
                $('#editable-fields-'+upc+' input[name=price]').val(resp.price);
                $('#editable-fields-'+upc).hide();
                $('#editable-text-'+upc).show();
                if (resp.qty > 1) {
                    $('#item-qty-'+upc).show();
                } else {
                    $('#item-qty-'+upc).hide();
                }
            }
        }
    });
}

function editTransLimit()
{
    var currentLimit = $('#currentLimit').html();
    var input = $('<input type="text" />').addClass('form-control').val(currentLimit);
    $('#currentLimit').html(input);
    input.focus();
    $('#edit-limit-link').hide();
    $('#save-limit-link').show();
}

function saveTransLimit(batchID)
{
    var newLimit = $('#currentLimit :input').val();
    $('#edit-limit-link').show();
    $('#save-limit-link').hide();
    if (newLimit == 0) {
        newLimit = '';
    }
    $('#currentLimit').html(newLimit);
    if (newLimit == '') {
        $('#edit-limit-link a').html('Add Limit');
    } else {
        $('#edit-limit-link a').html('Edit Limit');
    }
    
    $.ajax({
        type: 'post',
        data: 'id='+batchID+'&limit='+newLimit,
        success: function(resp) {
        }
    });
}

function swapDiscountToQualifier(elem, upc)
{
    var tr = $(elem).closest('tr');
    $.ajax({
        type: 'post',
        data: 'id='+$('#batchID').val()+'&upc='+upc+'&swap=1',
        dataType: 'json',
        success: function(resp) {
            if (resp.error) {
                showBootstrapAlert('#inputarea', 'danger', resp.error);
            } else {
                $('#qualifier-table').append(tr);
                tr.find('.down-arrow').show();
                tr.find('.up-arrow').hide();
            }
        }
    });
}

function swapQualifierToDiscount(elem, upc)
{
    var tr = $(elem).closest('tr');
    $.ajax({
        type: 'post',
        data: 'id='+$('#batchID').val()+'&upc='+upc+'&swap=1',
        dataType: 'json',
        success: function(resp) {
            if (resp.error) {
                showBootstrapAlert('#inputarea', 'danger', resp.error);
            } else {
                $('#discount-table').append(tr);
                tr.find('.down-arrow').hide();
                tr.find('.up-arrow').show();
            }
        }
    });
}

function savePairedPricing(id)
{
    var dataStr = $('#paired-fields :input').serialize();
    console.log(dataStr);
    dataStr += '&id='+id;
    $.ajax({
        type: 'post',
        data: dataStr,
        dataType: 'json',
        success: function(resp) {
            if (resp.error) {
                showBootstrapAlert('#inputarea', 'danger', resp.error);
            } else {
                showBootstrapAlert('#inputarea', 'success', 'Saved Paired Sale Settings');
            }
        }
    });
}

function printSigns()
{
    var myform = $('<form action="../../admin/labels/SignFromSearch.php" method="post"/>');
    $('.batch-hidden-upc').each(function() {
        var upc_in = $('<input name="u[]" />').val($(this).val());
        myform.append(upc_in);
    });
    if ($('#batch-discount-type').val() == 0) {
        var new_retail = $('<input name="item_mode" />').val(1);
        myform.append(new_retail);
    } else if ($('#batch-future-mode').val() == 1) {
        var new_sale = $('<input name="item_mode" />').val(3);
        myform.append(new_sale);
    } else if ($('#batch-future-mode').val() == 0) {
        var current_sale = $('<input name="item_mode" />').val(2);
        myform.append(current_sale);
    }
    $(document.body).append(myform);
    myform.submit();
}

