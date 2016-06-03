var baseItem = (function() {
    var mod = {};

    mod.chainSubs = function(store_id) {
        chainSubDepartments(
            '../ws/',
            {
                super_id: '#super-dept'+store_id,
                dept_start: '#department'+store_id,
                dept_end: '#department'+store_id, 
                sub_start: '#subdept'+store_id,
                callback: function() {
                    $('#subdept'+store_id+' option:first').html('None').val(0);
                    $('#subdept'+store_id).trigger('chosen:updated');
                    $.ajax({
                        url: 'modules/ajax/BaseItemAjax.php',
                        data: 'dept_defaults='+$('#department'+store_id).val(),
                        dataType: 'json',
                        cache: false
                    }).done(function(data){
                        if (data.tax) {
                            $('#tax'+store_id).val(data.tax);
                        }
                        if (data.fs) {
                            $('#FS'+store_id).prop('checked',true);
                        } else {
                            $('#FS'+store_id).prop('checked', false);
                        }
                        if (data.wic) {
                            $('#wic'+store_id).prop('checked', true);
                        } else {
                            $('#wic'+store_id).prop('checked', false);
                        }
                        if (data.nodisc && !data.line) {
                            $('#discount-select'+store_id).val(0);
                        } else if (!data.nodisc && data.line) {
                            $('#discount-select'+store_id).val(1);
                        } else if (!data.nodisc && !data.line) {
                            $('#discount-select'+store_id).val(2);
                        } else {
                            $('#discount-select'+store_id).val(3);
                        }
                    });
                }
            }
        );
    };

    mod.vendorChanged = function(newVal) {
        $.ajax({
            url: 'modules/ajax/BaseItemAjax.php',
            data: 'vendorChanged='+newVal,
            dataType: 'json',
            cache: false
        }).done(function(resp) {
            if (!resp.error) {
                $('#local-origin-id').val(resp.localID);
                $('.tab-pane.active .product-case-size').prop('disabled', false);
                $('#product-sku-field').prop('disabled', false);
            } else {
                $('.tab-pane.active .product-case-size').prop('disabled', true);
                $('#product-sku-field').prop('disabled', true);
            }
        });
    };

    mod.addVendorDialog = function() {
        var v_dialog = $('#newVendorDialog').dialog({
            autoOpen: false,
            height: 300,
            width: 300,
            modal: true,
            buttons: {
                "Create Vendor" : addVendorCallback,
                "Cancel" : function() {
                    v_dialog.dialog("close");
                }
            },
            close: function() {
                $('#newVendorDialog :input').each(function(){
                    $(this).val('');
                });
                $('#newVendorAlert').html('');
            }
        });

        $('#newVendorDialog :input').keyup(function(e) {
            if (e.which == 13) {
                addVendorCallback();
            }
        });

        $('.newVendorButton').click(function(e){
            e.preventDefault();
            v_dialog.dialog("open"); 
        });

        function addVendorCallback() {
            var data = 'action=addVendor';
            data += '&' + $('#newVendorDialog :input').serialize();
            $.ajax({
                url: 'modules/ajax/BaseItemAjax.php',
                data: data,
                dataType: 'json'
            }).fail(function() {
                $('#newVendorAlert').html('Communication error');
            }).done(function(resp){
                if (resp.vendorID) {
                    v_dialog.dialog("close");
                    $('.vendor_field').each(function(){
                        var v_field = $(this);
                        if (v_field.hasClass('chosen-select')) {
                            var newopt = $('<option/>').attr('id', resp.vendorID).html(resp.vendorName);
                            v_field.append(newopt);
                        }
                        v_field.val(resp.vendorName);
                        if (v_field.hasClass('chosen-select')) {
                            v_field.trigger('chosen:updated');
                        }
                    });
                } else if (resp.error) {
                    $('#newVendorAlert').html(resp.error);
                } else {
                    $('#newVendorAlert').html('Invalid response');
                }
            });
        }
    };

    var syncNamesValues = function(selector, paircb) {
        var ret = {};
        $(selector).each(function(){
            if ($(this).attr('name').length > 0) {
                ret = paircb($(this), ret);
            }
        });

        return ret;
    };

    var syncLabels = function(selector, fieldcb, synced) {
        $(selector).each(function(){
            if ($(this).attr('name').length > 0) {
                var name = $(this).attr('name');
                if (name in synced && synced[name] === false) {
                    fieldcb($(this)).addClass('alert-warning');
                } else {
                    fieldcb($(this)).removeClass('alert-warning');
                }
            }
        });
    };

    mod.markUnSynced = function() {
        var store_id = $('.tab-pane.active .store-id:first').val();
        var current = syncNamesValues('#store-tab-'+store_id+' .syncable-input', function(obj, ret) {
            ret[obj.attr('name')] = obj.val();
            return ret;
        });
        var synced = {};
        $('.syncable-input').each(function(){
            if ($(this).attr('name').length > 0) {
                var name = $(this).attr('name');
                if (name in current && $(this).val() != current[name]) {
                    synced[name] = false;
                    $('#store-sync').prop('checked', false);
                } else {
                    synced[name] = true;
                }
            }
        });
        syncLabels('.syncable-input', function(obj){ return obj; }, synced);

        var checkboxes = syncNamesValues('#store-tab-'+store_id+' .syncable-checkbox', function(obj, ret) {
            ret[obj.attr('name')] = obj.prop('checked');
            return ret;
        });
        synced = {};
        $('.syncable-checkbox').each(function(){
            if ($(this).attr('name').length > 0) {
                var name = $(this).attr('name');
                if (name in checkboxes && $(this).prop('checked') != checkboxes[name]) {
                    synced[name] = false;
                    $('#store-sync').prop('checked', false);
                } else {
                    synced[name] = true;
                }
            }
        });
        syncLabels('.syncable-checkbox', function(obj){ return obj.closest('label'); }, synced);
    };

    mod.syncStoreTabs = function() {
        if ($('#store-sync').prop('checked') === false) {
            mod.markUnSynced();
            return true;
        }
        var store_id = $('.tab-pane.active .store-id:first').val();
        var current = syncNamesValues('#store-tab-'+store_id+' .syncable-input', function(obj, ret) {
            ret[obj.attr('name')] = obj.val();
            return ret;
        });
        $('.syncable-input').each(function(){
            if ($(this).attr('name').length > 0) {
                var name = $(this).attr('name');
                if (name in current) {
                    $(this).val(current[name]);
                    if ($(this).hasClass('chosen-select')) {
                        $(this).trigger('chosen:updated');
                    }
                }
            }
        });
        var checkboxes = syncNamesValues('#store-tab-'+store_id+' .syncable-checkbox', function(obj, ret) {
            ret[obj.attr('name')] = obj.prop('checked');
            return ret;
        });
        $('.syncable-checkbox').each(function(){
            if ($(this).attr('name').length > 0) {
                var name = $(this).attr('name');
                if (name in checkboxes) {
                    $(this).prop('checked', checkboxes[name]);
                }
            }
        });

        return true;
    };

    return mod;
}());
