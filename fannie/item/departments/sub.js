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

function showSubsForDept(did){
	var d= 'did='+did+'&action=showSubsForDept';
	var name = $('#deptselect option:selected').text();
	$.ajax({
		url: 'SubDeptEditor.php',
		type: 'POST',
		timeout: 5000,
		data: d,
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			$('#subselect').html(resp);
			$('#subdiv').show();
			$('#formdiv').show();
			$('#subname').html('Subdepts in '+name);
		}
	});

}

function addSub(){
	var name = $('#newname').val();
	var did = $('#deptselect').val();
	var d = 'action=addSub&name='+name+'&did='+did;
	$.ajax({
		url: 'SubDeptEditor.php',
		type: 'POST',
		timeout: 5000,
		data: d,
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			$('#subselect').html(resp);
			$('#newname').val('');
		}
	});
}

function deleteSub(){
	var did = $('#deptselect').val();
	var d = 'action=deleteSub&did='+did;
	$('#subselect option:selected').each(function(){
		d += '&sid[]='+$(this).val();
	});
	$.ajax({
		url: 'SubDeptEditor.php',
		type: 'POST',
		timeout: 5000,
		data: d,
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			$('#subselect').html(resp);
		}
	});
}
