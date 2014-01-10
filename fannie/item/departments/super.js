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
		$('#namespan').show();
        $('#sd_email').val('');
	}
	else {
		$('#namespan').hide();
	}

	var name = $('#superselect :selected').text();
	$('#newname').val(name);	

	$.ajax({
		url: 'SuperDeptEditor.php',
		type: 'POST',
		timeout: 5000,
		data: 'sid='+superID+'&action=deptsInSuper',
		error: function(e1,e2){
			alert('Error loading XML document');
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
		alert('Error loading XML document');
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
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			alert(resp);
            if (sID == -1) {
                top.location = 'SuperDeptEditor.php';
            }
			// reload the page so the form resets
			// when a new super department is created
		}
	});
}
