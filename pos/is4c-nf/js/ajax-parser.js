function runParser(input_str){
	$.ajax({
		url: '/ajax-callbacks/ajax-parser.php',
		type: 'GET',
		data: "input="+input_str,
		dataType: "json",
		cache: false,
		error: function(xml_ro,st,err){
			alert(st); alert(xml_ro.status);
		},
		success: parserHandler
		});
}

function parserHandler(data,status_str,xml_ro){
	if (!data.main_frame){
		if (data.output)
			$(data.target).html(data.output);
	}
	else {
		location = data.main_frame;
	}

	if (data.redraw_footer){
		$.ajax({
			url: '/ajax-callbacks/ajax-footer.php',
			type: 'GET',
			cache: false,
			success: function(data){
				$('#footer').html(data);
			}
		});
	}

	if (data.receipt){
		$.ajax({
			url: '/ajax-callbacks/ajax-end.php',
			type: 'GET',
			data: 'receiptType='+data.receipt,
			cache: false,
			success: function(data){
			}
		});
	}

	if (data.scale){
		$.ajax({
			url: '/ajax-callbacks/ajax-scale.php',
			type: 'get',
			data: 'input='+data.scale,
			cache: false,
			success: function(res){
				$('#scaleBottom').html(res);
			}
		});
	}

	if (data.retry){
		setTimeout("runParser('"+data.retry+"');",700);
	}
}
