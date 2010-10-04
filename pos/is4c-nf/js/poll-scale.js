function pollScale(drop_scans){
	drop_scans = typeof(drop_scans) != 'undefined' ? drop_scans : false;
	
	$.ajax({url: '/ajax-callbacks/ajax-poll-scale.php',
		type: 'post',
		cache: false,
		dataType: 'json',
		error: function(){
			rePoll(drop_scans,50);
		},
		success: function(data){
			if (data){
				if (data.scale){
					$('#scaleBottom').html(data.scale);	
				}
				if (!drop_scans && data.scans){
					for (var i=0; i<data.scans.length; i++){
						var v = $('#reginput').val();
						$('#reginput').val(v+data.scans[i]);
						submitWrapper();
					}
				}
			}
			rePoll(drop_scans,50);
		}
	});
}

function rePoll(drop_scans,timeout){
	if (drop_scans)
		setTimeout('pollScale(true)',timeout);
	else
		setTimeout('pollScale(false)',timeout);
}
