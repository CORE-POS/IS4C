var batchEdit = (function ($) {
    var mod = {};

    var showFields = function(f_on, f_off) {
        $('.add-by-' + f_off.toLowerCase() + '-fields').hide();
        $('.add-by-' + f_off.toLowerCase() + '-fields :input').prop('disabled', true);
        $('.add-by-' + f_on.toLowerCase() + '-fields').show();
        $('.add-by-' + f_on.toLowerCase() + '-fields :input').prop('disabled', false);
        $('#addItem' + f_on.toUpperCase()).focus();
    };

    var colorName = function(resp) {
        if (resp.error) {
            return 'danger';
        } else {
            return 'success';
        }
    };

    var inputAreaAlert = function(type, msg) {
        showBootstrapAlert('#inputarea', type, msg);
    };

    mod.toggleUpcLcInput = function()
    {
        if ($('#addItemLikeCode').is(':checked')) {
            showFields('lc', 'upc');
        } else {
            showFields('upc', 'lc');
        }
    };

    mod.advanceToPrice = function()
    {
        var dataStr = $('#add-item-form').serialize();
        dataStr += '&id='+$('#batchID').val();
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: dataStr
        }).done(function(resp) {
            if (resp.error) {
                inputAreaAlert('danger', resp.msg);
                $(resp.field).focus().select();
            } else if (resp.content) {
                $('#inputarea').html(resp.content);
                $(resp.field).focus();
            }
        });
    };

    mod.addItemPrice = function(identifier)
    {
        var dataStr = $('#add-price-form').serialize();
        dataStr += '&id='+$('#batchID').val();  
        dataStr += '&upc='+identifier;
        $.ajax({
            type: 'post',
            data: dataStr,
            dataType: 'json'
        }).done(function(resp) {
            $('#inputarea').html(resp.input);
            $('#addItemUPC').focus();
            if (resp.added) {
                $('#displayarea').html(resp.display);
            }
            if (/^LC\d+$/.test(identifier)) {
                $('#addItemLikeCode').click();
            }
        });
    };

    mod.generateTags = function(batchID)
    {
        $.ajax({
            type: 'post',
            data: 'id='+batchID+'&autotag=1',
            dataType: 'json'
        }).done(function(resp) {
            inputAreaAlert('success', 'Generated ' + resp.tags + ' tags');
        });
    };

    mod.forceNow = function(batchID)
    {
        $('#progress-bar').show();
        $('#progress-bar .active').attr('title', 'Forcing Batch');
        $('#progress-bar .sr-only').attr('title', 'Forcing Batch');
        $.ajax({
            type: 'post',
            data: 'id='+batchID+'&force=1',
            dataType: 'json'
        }).fail(function() {
            $('#progress-bar').hide();
            inputAreaAlert('danger', 'Network Error forcing batch');
        }).done(function(resp) {
            inputAreaAlert(colorName(resp), resp.msg);
            $('#progress-bar').hide();
        });
    };

    mod.unsaleNow = function(batchID)
    {
        var r = confirm('Stop Sale: Are you sure?');
        if (r == true) {
            $.ajax({
                type: 'post',
                data: 'id='+batchID+'&unsale=1',
                dataType: 'json'
            }).done(function(resp) {
                inputAreaAlert(colorName(resp), resp.msg);
            });
        }
    };

    mod.cutItem = function(upc, batchID, userID, cutOp)
    {
        var dataStr = 'id=' + batchID + '&upc=' + upc + '&uid=' + userID + '&cut=' + cutOp;
        $.ajax({
            type: 'post',
            data: dataStr
        }).done(function() {
            if (cutOp) {
                $('#doCut'+upc).hide();
                $('#unCut'+upc).show();
            } else {
                $('#unCut'+upc).hide();
                $('#doCut'+upc).show();
            }
        });
    };
    
    mod.cutAll = function(batchID, userID) {
        var dataStr = 'id=' + batchID + '&uid=' + userID;
        var r = confirm('Cut all products from this batch?');
        if (r == true) {
            $.ajax({
                type: 'post',
                url: 'cutBatch.php',
                data: dataStr,
                dataType: 'json',
            }).done(function (resp) {
                if (resp.error) {
                    inputAreaAlert('danger', resp.error_msg);
                } else {
                    $('.cutLink').hide();
                    $('.unCutLink').show();
                }
            });
        }
    };

    mod.deleteUPC = function(id, upc)
    {
        var dataStr = '_method=delete&id='+id+'&upc='+upc;
        var clickedElem = $(this);
        $.ajax({
            type: 'post',
            data: dataStr,
            dataType: 'json'
        }).done(function(resp) {
            if (resp.error) {
                inputAreaAlert('danger', resp.msg);
            } else {
                clickedElem.closest('tr').hide();
                if (/^LC\d+$/.test(upc)) {
                    var lc = upc.substring(2, upc.length);
                    $('.lc-item-'+lc).hide();
                }
            }
        });
    };

    mod.editUpcPrice = function(upc)
    {
        $('#editable-text-'+upc).hide();
        $('#editable-fields-'+upc).show();
        $('#editLink'+upc+' .edit').hide();
        $('#editLink'+upc+' .save').show();
        $('#editable-fields-'+upc + ' input[name=price]').focus();
    };

    mod.saveUpcPrice = function(upc)
    {
        var dataStr = $('#editable-fields-'+upc+' :input').serialize();
        dataStr += '&id='+$('#batchID').val()+'&upc='+upc;

        $.ajax({
            type: 'post',
            data: dataStr,
            dataType: 'json'
        }).done(function(resp) {
            if (resp.error) {
                inputAreaAlert('warning', resp.msg);
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
        });
    };

    mod.editTransLimit = function()
    {
        var currentLimit = $('#currentLimit').html();
        var input = $('<input type="text" />').addClass('form-control').val(currentLimit);
        $('#currentLimit').html(input);
        input.focus();
        $('#edit-limit-link').hide();
        $('#save-limit-link').show();
    };

    mod.saveTransLimit = function(batchID)
    {
        var newLimit = $('#currentLimit :input').val();
        $('#edit-limit-link').show();
        $('#save-limit-link').hide();
        if (newLimit == 0) {
            newLimit = '';
        }
        $('#currentLimit').html(newLimit);
        if (newLimit === '') {
            $('#edit-limit-link a').html('Add Limit');
        } else {
            $('#edit-limit-link a').html('Edit Limit');
        }
        
        $.ajax({
            type: 'post',
            data: 'id='+batchID+'&limit='+newLimit
        });
    };

    mod.swapDiscountToQualifier = function(elem, upc)
    {
        var tr = $(elem).closest('tr');
        $.ajax({
            type: 'post',
            data: 'id='+$('#batchID').val()+'&upc='+upc+'&swap=1',
            dataType: 'json'
        }).done(function(resp) {
            if (resp.error) {
                inputAreaAlert('danger', resp.error);
            } else {
                $('#qualifier-table').append(tr);
                tr.find('.down-arrow').show();
                tr.find('.up-arrow').hide();
            }
        });
    };

    mod.swapQualifierToDiscount = function(elem, upc)
    {
        var tr = $(elem).closest('tr');
        $.ajax({
            type: 'post',
            data: 'id='+$('#batchID').val()+'&upc='+upc+'&swap=1',
            dataType: 'json'
        }).done(function(resp) {
            if (resp.error) {
                inputAreaAlert('danger', resp.error);
            } else {
                $('#discount-table').append(tr);
                tr.find('.down-arrow').hide();
                tr.find('.up-arrow').show();
            }
        });
    };

    mod.savePairedPricing = function(id)
    {
        var dataStr = $('#paired-fields :input').serialize();
        dataStr += '&id='+id;
        $.ajax({
            type: 'post',
            data: dataStr,
            dataType: 'json'
        }).done(function(resp) {
            if (resp.error) {
                inputAreaAlert('danger', resp.error);
            } else {
                inputAreaAlert('success', 'Saved Paired Sale Settings');
            }
        });
    };

    mod.trimPcBatch = function(id)
    {
        $.ajax({
            type: 'post',
            data: 'id='+id+'&trim=1',
            dataType: 'json'
        }).done(function(resp) {
            if (resp.error) {
                inputAreaAlert('danger', resp.error);
            } else {
                $('#displayarea').html(resp.display);
            }
        });

    };

    mod.toggleStore = function(storeID, batchID)
    {
        $.ajax({
            type: 'post',
            data: 'id='+batchID+'&storeID='+storeID,
            dataType: 'json'
        }).done(function(resp) {
            if (resp.error) {
                inputAreaAlert('danger', resp.error);
            }
        });
    };

    mod.updatePartial = function(batchID) {
        var dstr = $('.partialBatch').serialize() + '&partialID=' + batchID;
        $.ajax({
            type: 'post',
            data: dstr
        }).done(function() {
        });
    };

    mod.saveNotes = function(batchID) {
        var dstr = $('#batchNotes').serialize() + '&noteID=' + batchID;
        $.ajax({
            type: 'post',
            data: dstr
        }).done(function() {
        });
    };

    var noteToken = false;
    mod.noteTyped = function(batchID) {
        if (noteToken) {
            clearTimeout(noteToken);
        }
        noteToken = setTimeout(function() { mod.saveNotes(batchID); }, 2000);
    }

    return mod;

}(jQuery));

