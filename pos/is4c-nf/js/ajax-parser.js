var CORE_JS_PREFIX = "";

function runParser(input_str,rel_prefix){
	CORE_JS_PREFIX = rel_prefix;
	$.ajax({
		url: CORE_JS_PREFIX+'ajax-callbacks/ajax-parser.php',
		type: 'GET',
		data: "input="+input_str,
		dataType: "json",
		cache: false,
		error: parserError,
		success: parserHandler
	});
}

function parserError(xml_ro,st,err){
}

function parserHandler(data,status_str,xml_ro){
	if (data.main_frame){
		location = data.main_frame;
		return;
	}
	else {
		if (data.output)
			$(data.target).html(data.output);
	}

	if (data.redraw_footer){
		/*
		$.ajax({
			url: CORE_JS_PREFIX+'ajax-callbacks/ajax-footer.php',
			type: 'GET',
			cache: false,
			success: function(data){
				$('#footer').html(data);
			}
		});
		*/
		$('#footer').html(data.redraw_footer);
	}

	if (data.scale){
		/*
		$.ajax({
			url: CORE_JS_PREFIX+'ajax-callbacks/ajax-scale.php',
			type: 'get',
			data: 'input='+data.scale,
			cache: false,
			success: function(res){
				$('#scaleBottom').html(res);
			}
		});
		*/
		$('#scaleBottom').html(data.scale);
	}

	if (data.term){
		$('#scaleIconBox').html(data.term);
	}

	if (data.receipt){
		$.ajax({
			url: CORE_JS_PREFIX+'ajax-callbacks/ajax-end.php',
			type: 'GET',
			data: 'receiptType='+data.receipt,
			dataType: 'json',
			cache: false,
			success: function(data){
				if (data.sync){
					ajaxTransactionSync(CORE_JS_PREFIX);
				}
			}
		});
	}

	if (data.retry){
		setTimeout("runParser('"+data.retry+"','"+CORE_JS_PREFIX+"');",150);
	}
}

function ajaxTransactionSync(rel_prefix){
	$.ajax({
		url: rel_prefix+'ajax-callbacks/ajax-transaction-sync.php',
		type: 'GET',
		cache: false,
		success: function(data){
		}
	});

}
