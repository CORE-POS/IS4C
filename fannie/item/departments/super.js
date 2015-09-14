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

function superSelected(){
	var superID = $('#superselect').val();
	if (superID == -1){
		$('#namefield').show();
        $('#sd_email').val('');
        $('#newname').val('');
        $('#newname').focus();
	} else {
		$('#namefield').hide();
        var name = $('#superselect :selected').text();
        $('#newname').val(name);	
	}

	$.ajax({
		url: 'SuperDeptEditor.php',
		type: 'POST',
		timeout: 5000,
		data: 'sid='+superID+'&action=deptsInSuper',
		error: function(e1,e2){
            showAlert('danger', 'Unable to load department data');
		},
		success: function(resp){
			$('#deptselect').html(resp);	
		}
	});

	$.ajax({
		url: 'SuperDeptEditor.php',
		type: 'POST',
		timeout: 5000,
		data: 'sid='+superID+'&action=deptsNotInSuper',
		error: function(){
            showAlert('danger', 'Unable to load department data');
		},
		success: function(resp){
			$('#deptselect2').html(resp);	
		}
	});

    $.ajax({
        url: 'SuperDeptEditor.php',
        type: 'get',
        data: 'sid='+superID+'&action=superDeptEmail',
        success: function(resp) {
            $('#sd_email').val(resp);
        }
    });
}

function addDepts(){
	$("#deptselect2 option:selected").each(function(){  
		$("#deptselect").append($(this).clone());  
		$(this).remove();  
	}); 
}

function remDepts(){
	$("#deptselect option:selected").each(function(){  
		$("#deptselect2").append($(this).clone());  
		$(this).remove();  
	}); 
}

function saveData(){
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
        dataType: 'json',
		error: function(){
            showAlert('danger', 'Save failed!');
		},
		success: function(resp){
			// reload the page so the form resets
			// when a new super department is created
            showAlert('success', 'Saved #' + resp.id + ' ' + resp.name);
            if (sID == -1) {
                var newOpt = $('<option/>').val(resp.id).html(resp.name);
                $('#superselect').append(newOpt);
                $('#superselect').val(resp.id);
            }
		}
	});
}

function showAlert(type, msg)
{
    var alertbox = '<div class="alert alert-' + type + '" role="alert">';
    alertbox += '<button type="button" class="close" data-dismiss="alert">';
    alertbox += '<span>&times;</span></button>';
    alertbox += msg + '</div>';
    $('#alertarea').append(alertbox);
}
