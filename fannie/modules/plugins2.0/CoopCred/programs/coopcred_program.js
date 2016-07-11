/*******************************************************************************

    Copyright 2009 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

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

function programChange(){
	var pID = $('#programselect').val();
	if (pID == 0){ 
		$('#infodiv').html("");
		return;
	}

    /* error: happens when compile or runtime error in url: */
	$.ajax({
		url: 'CoopCredProgramEditor.php',
		type: 'POST',
		timeout: 5000,
		data: 'pid='+pID+'&action=programDisplay',
		error: function(){
		alert('Change: Error loading XML document');
		},
		success: function(resp){
			$('#infodiv').html(resp);
		}
	});
}

function programSave(){
    /* Compose the string of parameters for the POST request
     * as in: editor.php?action=progSave&active=$('#acitve')[...]
     * $('#active') is the jQuery function to get the value of the
     *  input with id="active".
     * action is the same as <form action="">
     *  accessible with FormLib::get_form_value('action');
     * The program named in url: of the qQuery $.ajax() function
     *  gets the 'action' as it would from a <form>
     *  and passes it to:
     *   $this->ajax_response(FormLib::get_form_value('action'));
     */
	var qs = "action=programSave";
	qs += "&isnew="+$('#isnew').val();
	qs += "&progno="+$('#progno').val();
	qs += "&progname="+$('#progname').val();
	if ($('#isactive').is(':checked'))
		qs += "&isactive=1";
	else
		qs += "&isactive=0";
	qs += "&startdate="+$('#startdate').val();
	qs += "&enddate="+$('#enddate').val();

	qs += "&tendertype="+$('#tendertype').val();
	qs += "&tendername="+$('#tendername').val();
	qs += "&tenderkeycap="+$('#tenderkeycap').val();
	qs += "&inputtender="+$('#inputtender').val();

	qs += "&paymentid="+$('#paymentid').val();
	qs += "&paymentname="+$('#paymentname').val();
	qs += "&paymentkeycap="+$('#paymentkeycap').val();
	qs += "&bankid="+$('#bankid').val();

	if ($('#creditok').is(':checked'))
		qs += "&creditok=1";
	else
		qs += "&creditok=0";
	qs += "&maxcredit="+$('#maxcredit').val();
	if ($('#inputok').is(':checked'))
		qs += "&inputok=1";
	else
		qs += "&inputok=0";
	if ($('#transferok').is(':checked'))
		qs += "&transferok=1";
	else
		qs += "&transferok=0";
	qs += "&origtender="+$('#origtender').val();
	qs += "&origdepartment="+$('#origdepartment').val();

    /*
     * The output of url:, which is a PHP echo, is in resp,
     *  which is displayed in an alert() popup before returning control
     *  to the initial caller.
     * error: happens when compile or runtime error in url:
     *         it is not from url: returning false.
     *        The function in url: that handles/receives this
     *         returns nothing.
     * success: resp is the output of url:
     */
	$.ajax({
		url: 'CoopCredProgramEditor.php',
		type: 'POST',
		timeout: 5000,
		data: qs,
		error: function(){
		alert('Save: Error loading XML document');
		},
		success: function(resp){
			alert(resp);
		}
	});
}

