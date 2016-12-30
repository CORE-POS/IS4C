var CORE_JS_PREFIX = "";

function runParser(input_str,rel_prefix){
	CORE_JS_PREFIX = rel_prefix;
	$.ajax({
		url: CORE_JS_PREFIX+'ajax/AjaxParser.php',
		type: 'GET',
		data: "input="+input_str,
		dataType: "json",
		cache: false
	}).done(parserHandler).fail(parserError);
}

function parserError(xhr, statusText, err)
{
    errorLog.show(xhr, statusText, err);
}

function customerWindowHtml(selector, content)
{
    CustomerDisplay.updateCustomerDisplay(selector, content);
}

function parserHandler(data)
{
	if (data.main_frame){
		window.location = data.main_frame;
		return;
	}

    if (data.output) {
        $(data.target).html(data.output);
        customerWindowHtml(data.target, data.output);
	}

	if (data.redraw_footer){
		$('#footer').html(data.redraw_footer);
        customerWindowHtml('#footer', data.redraw_footer);
	}

	if (data.scale){
		$('#scaleBottom').html(data.scale);
        customerWindowHtml('#scaleBottom', data.scale);
	}

	if (data.term){
		$('#scaleIconBox').html(data.term);
        customerWindowHtml('#scaleIconBox', data.term);
	}

	if (data.receipt){
		$.ajax({
			url: CORE_JS_PREFIX+'ajax/AjaxEnd.php',
			type: 'GET',
			data: 'receiptType='+data.receipt+'&ref='+data.trans_num,
			dataType: 'json',
			cache: false
		}).done(function(data) {
            if (data.error) {
                var icon = $('#receipticon').attr('src');
                var newicon = icon.replace(/(.*graphics)\/.*/, "$1/deadreceipt.gif");
                $('#receipticon').attr('src', newicon);
            }
        }).fail(function() {
            var icon = $('#receipticon').attr('src');
            var newicon = icon.replace(/(.*graphics)\/.*/, "$1/deadreceipt.gif");
            $('#receipticon').attr('src', newicon);
        });
	}

	if (data.retry){
		setTimeout(function() { runParser(encodeURI(data.retry), CORE_JS_PREFIX); },150);
	}
}

