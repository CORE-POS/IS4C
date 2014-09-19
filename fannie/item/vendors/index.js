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
		timeout: 5000,
		data: 'vid='+vID+'&action=vendorDisplay',
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			$('#contentarea').html(resp);
            $('.delivery').change(saveDelivery);
		}
	});
}

function saveDelivery()
{
    var data = $('.delivery').serialize();
	var vid = $('#vendorselect').val();
    $.ajax({
        url: 'VendorIndexPage.php',
        data: 'action=saveDelivery&vID='+vid+'&'+data,
        method: 'post',
        dataType: 'json',
        success: function(resp){
            if (resp.next && resp.nextNext) {
                $('#nextDelivery').html(resp.next);
                $('#nextNextDelivery').html(resp.nextNext);
            }
        }
    });
}

function newvendor(){
	var name = $('#newname').val();
	$.ajax({
		url: 'VendorIndexPage.php',
		type: 'POST',
		timeout: 5000,
		data: 'name='+name+'&action=newVendor',
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			top.location='VendorIndexPage.php?vid='+resp;
		}
	});
}

function editSaveVC(vendorID){
	if ($('#vcPhoneEdit').length == 0)
		editVC(vendorID);
	else
		saveVC(vendorID);
}

function editVC(vendorID){
	var phone = $('#vcPhone').html();
	$('#vcPhone').html('<input type="text" id="vcPhoneEdit" value="'+phone+'" />');

	var fax = $('#vcFax').html();
	$('#vcFax').html('<input type="text" id="vcFaxEdit" value="'+fax+'" />');

	var email = $('#vcEmail').html();
	$('#vcEmail').html('<input type="text" id="vcEmailEdit" value="'+email+'" />');

	var web = $('#vcWebsite').html();
	$('#vcWebsite').html('<input type="text" id="vcWebsiteEdit" value="'+web+'" />');

	var notes = $('#vcNotes').html();
	$('#vcNotes').html('<br /><textarea rows="5" cols="35" id="vcNotesEdit">'+notes+'</textarea>');

	$('#vcEditSave').html('Save Contact Info');
	$('#vcPhoneEdit').focus();
}

function saveVC(vendorID){
	var dataStr = 'vendorID='+vendorID;
	dataStr += '&phone='+$('#vcPhoneEdit').val();
	dataStr += '&fax='+$('#vcFaxEdit').val();
	dataStr += '&email='+$('#vcEmailEdit').val();
	dataStr += '&website='+$('#vcWebsiteEdit').val();
	dataStr += '&notes='+$('#vcNotesEdit').val();
	dataStr += '&action=saveContactInfo';

	$.ajax({
		url: 'VendorIndexPage.php',
		method: 'post',
		data: dataStr,
		success: function(resp){
			$('#contentarea').html(resp);
		}
	});
}
