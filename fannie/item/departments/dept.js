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
	qs += "&new="+$('#isNew').val();
	qs += "&did="+$('#deptno').val();
	qs += "&name="+$('#deptname').val();
	qs += "&tax="+$('#depttax').val();
	if ($('#deptfs').is(':checked'))
		qs += "&fs=1";
	else
		qs += "&fs=0";
	if ($('#deptdisc').is(':checked'))
		qs += "&disc=1";
	else
		qs += "&disc=0";
	qs += "&min="+$('#deptmin').val();
	qs += "&max="+$('#deptmax').val();
	qs += "&margin="+$('#deptmargin').val();
	qs += "&pcode="+$('#deptsalescode').val();

	$.ajax({
		url: 'DepartmentEditor.php',
		type: 'POST',
		timeout: 5000,
		data: qs,
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			alert(resp);
		}
	});
}
