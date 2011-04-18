function pollScale(){
	$.ajax({url: '/ajax-callbacks/ajax-poll-scale.php',
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
		var URL = location.href;
		var pagename = URL.substring(URL.lastIndexOf('/') + 1);
		if (pagename == 'pos2.php' && data.scans){
			var v = $('#reginput').val();
			parseWrapper(v+data.scans);
			return;
		}
	}
	rePoll();
}

function rePoll(){
	var timeout = 100;
	setTimeout('pollScale()',timeout);
}
