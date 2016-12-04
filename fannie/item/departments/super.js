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

var superDept = (function($) {
    var mod = {};

    var showAlert = function(type, msg) {
        var alertbox = '<div class="alert alert-' + type + '" role="alert">';
        alertbox += '<button type="button" class="close" data-dismiss="alert">';
        alertbox += '<span>&times;</span></button>';
        alertbox += msg + '</div>';
        $('#alertarea').append(alertbox);
    };

    var loadSelected = function(superID) {
        $.ajax({
            url: 'SuperDeptEditor.php',
            type: 'POST',
            timeout: 5000,
            data: 'sid='+superID+'&action=deptsInSuper'
        }).fail(function(){
            showAlert('danger', 'Unable to load department data');
        }).done(function(resp){
            $('#deptselect').html(resp);	
            if (resp.length == 0) {
                $('#deleteBtn').prop('disabled', false);
            } else {
                $('#deleteBtn').prop('disabled', true);
            }
        });
    };

    var loadNotSelected = function(superID) {
        $.ajax({
            url: 'SuperDeptEditor.php',
            type: 'POST',
            timeout: 5000,
            data: 'sid='+superID+'&action=deptsNotInSuper'
        }).fail(function(){
            showAlert('danger', 'Unable to load department data');
        }).done(function(resp){
            $('#deptselect2').html(resp);	
        });
    };

    var loadEmail = function(superID) {
        $.ajax({
            url: 'SuperDeptEditor.php',
            type: 'get',
            data: 'sid='+superID+'&action=superDeptEmail'
        }).done(function(resp) {
            $('#sd_email').val(resp);
        });
    };

    mod.superSelected = function(){
        var superID = $('#superselect').val();
        if (superID === '-1'){
            $('#namefield').show();
            $('#sd_email').val('');
            $('#newname').val('');
            $('#newname').focus();
        } else {
            $('#namefield').hide();
            var name = $('#superselect :selected').text();
            $('#newname').val(name);	
        }

        loadSelected(superID);
        loadNotSelected(superID);
        loadEmail(superID);
    };

    var shiftOptions = function(src, dest) {
        $(src+" option:selected").each(function(){  
            $(dest).append($(this).clone());  
            $(this).remove();  
        }); 
    };

    mod.addDepts = function(){
        shiftOptions('#deptselect2', '#deptselect');
        if ($('#deptselect option').length > 0) {
            $('#deleteBtn').prop('disabled', true);
        }
    };

    mod.remDepts = function(){
        shiftOptions('#deptselect', '#deptselect2');
        if ($('#deptselect option').length == 0) {
            $('#deleteBtn').prop('disabled', false);
        }
    };

    mod.saveData = function(){
        var name = $('#newname').val();
        var sID = $('#superselect').val();
        var depts = "";
        $("#deptselect option").each(function(){  
            depts += "&depts[]="+$(this).val();
        }); 

        var qs = "action=save&sid="+sID+"&name="+name+depts;
        qs += '&email='+$('#sd_email').val();

        $.ajax({
            url: 'SuperDeptEditor.php',
            type: 'POST',
            timeout: 5000,
            data: qs,
            dataType: 'json'
        }).fail(function(){
            showAlert('danger', 'Save failed!');
        }).done(function(resp){
            // reload the page so the form resets
            // when a new super department is created
            showAlert('success', 'Saved #' + resp.id + ' ' + resp.name);
            if (sID === '-1') {
                var newOpt = $('<option/>').val(resp.id).html(resp.name);
                $('#superselect').append(newOpt);
                $('#superselect').val(resp.id);
            }
        });
    };

    mod.deleteCurrent = function() {
        var sID = $('#superselect').val();
        $.ajax({
            url: 'SuperDeptEditor.php',
            type: 'post',
            data: 'action=delete&id='+sID,
            dataType: 'json'
        }).fail(function() {
            showAlert('danger', 'Delete failed!');
        }).success(function (resp) {
            if (resp.error) {
                showAlert('danger', resp.error);
            } else {
                window.location = 'SuperDeptEditor.php';
            }
        });
    };

    return mod;

}(jQuery));

