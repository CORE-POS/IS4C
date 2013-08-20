var SCALE_REL_PRE = "";

function pollScale(rel_prefix){
	SCALE_REL_PRE = rel_prefix;
	$.ajax({url: SCALE_REL_PRE+'ajax-callbacks/ajax-poll-scale.php',
		type: 'post',
		cache: false,
		dataType: 'json',
		error: scalePollError,
		success: scalePollSuccess
	});
}

function scalePollError(e1,e2,e3){
	rePoll();
}

function scalePollSuccess(data){
	if (data){
		if (data.scale){
			$('#scaleBottom').html(data.scale);	
		}

		if (data.scans && data.scans.indexOf && data.scans.indexOf(':') !== -1){
			// data from the cc terminal
			// run directly; don't include user input
			if (typeof runParser == 'function')
				runParser(data.scans, SCALE_REL_PRE);
		}
		else if ($('#reginput').length != 0 && data.scans){
			// barcode scan input
			var v = $('#reginput').val();
			parseWrapper(v+data.scans);
			//return; // why is this here? scale needs to keep polling...
		}
	}
	rePoll();
}

function rePoll(){
	var timeout = 100;
	setTimeout("pollScale('"+SCALE_REL_PRE+"')",timeout);
}
