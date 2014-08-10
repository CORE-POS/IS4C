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

function deptchange(){
	var dID = $('#deptselect').val();
	if (dID == 0){ 
		$('#infodiv').html("");
		return;
	}

	$.ajax({
		url: 'DepartmentEditor.php',
		type: 'POST',
		timeout: 5000,
		data: 'did='+dID+'&action=deptDisplay',
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			$('#infodiv').html(resp);
		}
	});
}

function deptSave(){
	var qs = "action=deptSave";
	var fields = $('.deptFields :input').serialize();
	if (!$('#deptdisc').is(':checked')) {
		fields += '&disc=0';
	}
	qs += '&'+fields;

	$.ajax({
		url: 'DepartmentEditor.php',
		type: 'POST',
		timeout: 5000,
		data: qs,
        dataType: 'json',
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
            if (resp.did && resp.msg) {
                alert(resp.msg);
                location = 'DepartmentEditor.php?did='+resp.did;
            } else {
                alert('Error saving department');
                console.log(resp);
            }
		}
	});
}
