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
				runParser(encodeURI(data.scans), SCALE_REL_PRE);
		}
		else if ($('#reginput').length != 0 && data.scans){
			// barcode scan input
			var v = $('#reginput').val();
            var url = document.URL;
            data.scans += ''; // convert to string
            // only add prefix when working on the main page
            // other pages that use scans (e.g., barcode as password)
            // may not be expecting this behavior
            // For efficiency, scale weight response include a UPC if there
            // is a pending item waiting for a weight. In this case the prefix
            // is not added. Filtering out scans while the scale is waiting
            // for a weight uses the prefix, so once the scale is ready
            // a UPC has to go through w/o prefix
            if (!data.scans && url.substring(url.length - 8) == 'pos2.php' && data.scans.substring(0, 3) != 'OXA') {
                data.scans = '0XA' + data.scans;
            }
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
