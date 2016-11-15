/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

var vendorEditor = (function($) {

    var mod = {};

    mod.vendorNew = function(){
        var content = "<b>New vendor name</b>: ";
        content += "<input class=\"form-control\" type=text id=\"newname\" />";
        content += "<p />";
        content += "<input type=submit value=\"Create vendor\" class=\"btn btn-default\" ";
        content += "onclick=\"vendorEditor.newvendor(); return false;\" />";
        $('#contentarea').html(content);
    };

    mod.saveDelivery = function() {
        var data = $('.delivery').serialize();
        var vid = $('#vendorselect').val();
        $.ajax({
            url: 'VendorIndexPage.php',
            data: 'delivery=1&vID='+vid+'&'+data,
            method: 'post',
            dataType: 'json'
        }).done(function(resp){
            if (resp.next && resp.nextNext) {
                $('#nextDelivery').html(resp.next);
                $('#nextNextDelivery').html(resp.nextNext);
            }
        });
    };

    mod.newvendor = function(){
        var name = $('#newname').val();
        $.ajax({
            url: 'VendorIndexPage.php',
            type: 'POST',
            timeout: 5000,
            data: 'name='+name+'&new=1'
        }).fail(function(){
            window.alert('Error loading XML document');
        }).done(function(){
            window.top.location='VendorIndexPage.php?vid='+resp;
        });
    };

    mod.saveVC = function(vendorID) {
        var dataStr = $('#vcForm').serialize() + '&vendorID=' + vendorID + '&info=1';

        $.ajax({
            url: 'VendorIndexPage.php',
            method: 'post',
            data: dataStr,
            dataType: 'json'
        }).done(function(resp){
            var elem = $('<div></div>');
            elem.addClass('alert');
            elem.addClass('alert-dismissable');
            if (resp.error) {
                elem.addClass('alert-danger');
            } else {
                elem.addClass('alert-success');
            }
            var btn = $('<button type="button" class="close" data-dismiss="alert"></button>');
            btn.append('<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>');
            elem.append(btn);
            elem.append(resp.msg);
            $('.form-alerts').append(elem);
        });
    };

    mod.saveShipping = function(s) {
        var dstr = 'id='+$('#vendorselect').val()+'&shipping='+s;
        $.ajax({
            url: 'VendorIndexPage.php',
            method: 'post',
            data: dstr,
            dataType: 'json'
        }).done(function(resp) {
            var elem = $('#vc-shipping');
            showBootstrapPopover(elem, 0, resp.error);
        });
    };

    mod.saveDiscountRate = function(s) {
        var dstr = 'id='+$('#vendorselect').val()+'&rate='+s;
        $.ajax({
            url: 'VendorIndexPage.php',
            method: 'post',
            data: dstr,
            dataType: 'json'
        }).done(function(resp) {
            var elem = $('#vc-discount');
            showBootstrapPopover(elem, 0, resp.error);
        });
    };

    mod.toggleActive = function(obj, vid) {
        var dstr = 'id=' + vid;
        if ($(obj).prop('checked')) {
            dstr += '&inactive=0';
        } else {
            dstr += '&inactive=1';
        }
        $.ajax({
            url: 'VendorIndexPage.php',
            method: 'post',
            data: dstr
        });
    };

    mod.saveAutoOrder = function(vid) {
        $.ajax({
            url: 'VendorIndexPage.php',
            method: 'post',
            data: 'id='+vid+'&'+$('.auto-order').serialize()
        }).done(function(resp) {
        });
    };

    return mod;

}(jQuery));

