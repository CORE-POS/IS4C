function pollScale(drop_scans){
	$.ajax({url: '/ajax-callbacks/ajax-poll-scale.php',
		type: 'post',
		cache: false,
		dataType: 'json',
		error: function(e1,e2,e3){
			rePoll(drop_scans);
		},
		success: function(data){
			if (data){
				if (data.scale){
					$('#scaleBottom').html(data.scale);	
				}
				var URL = location.href;
				var pagename = URL.substring(URL.lastIndexOf('/') + 1);
				if (pagename == 'pos2.php' && data.scans){
					var v = $('#reginput').val();
					parseWrapper(v+data.scans);
					$('#reginput').val('');
				}
			}
			rePoll(drop_scans);
		}
	});
}

function rePoll(drop_scans){
	var timeout = 25;
	if (drop_scans)
		setTimeout('pollScale(true)',timeout);
	else
		setTimeout('pollScale(false)',timeout);
}
