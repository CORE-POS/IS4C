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

function saveScript(vID){
	var scriptName = $('#vscript').val();

	if (vscript == ''){
		return;
	}

	$.ajax({
		url: 'ajax.php',
		type: 'POST',
		timeout: 1000,
		data: 'vid='+vID+'&script='+scriptName+'&action=saveScript',
		error: function(){
			alert('Error saving script name');
		},
		success: function(resp){
			alert('Saved as '+scriptName);
		}
	});
}

function vendorchange(){
	var vID = $('#vendorselect').val();

	if (vID == ''){
		$('#contentarea').html('');	
		return;
	}

	if (vID == 'new'){
		var content = "<b>New vendor name</b>: ";
		content += "<input type=text id=\"newname\" />";
		content += "<p />";
		content += "<input type=submit value=\"Create vendor\" ";
		content += "onclick=\"newvendor(); return false;\" />";
		$('#contentarea').html(content);
		return;
	}

	$.ajax({
		url: 'VendorIndexPage.php',
		type: 'POST',
		timeout: 1000,
		data: 'vid='+vID+'&action=vendorDisplay',
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			$('#contentarea').html(resp);
		}
	});
}

function newvendor(){
	var name = $('#newname').val();
	$.ajax({
		url: 'VendorIndexPage.php',
		type: 'POST',
		timeout: 1000,
		data: 'name='+name+'&action=newVendor',
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			top.location='VendorIndexPage.php?vid='+resp;
		}
	});
}
