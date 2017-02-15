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
	var pID = $('#configselect').val();
	if (pID == 0){ 
		$('#infodiv').html("");
		return;
	}

    /* error: happens when compile or runtime error in url: */
	$.ajax({
		url: 'CoopCredConfigEditor.php',
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
	qs += "&configno="+$('#configno').val();

	qs += "&dummytender="+$('#dummytender').val();
	qs += "&dummydept="+$('#dummydept').val();
	qs += "&deptmin="+$('#deptmin').val();
	qs += "&deptmax="+$('#deptmax').val();
	qs += "&dummybanker="+$('#dummybanker').val();
	qs += "&bankermin="+$('#bankermin').val();
	qs += "&bankermax="+$('#bankermax').val();
	qs += "&membermin="+$('#membermin').val();
	qs += "&membermax="+$('#membermax').val();

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
		url: 'CoopCredConfigEditor.php',
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

