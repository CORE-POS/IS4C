function loadCashier(){
	var data = 'action=loadCashier';
    data += '&' + $('#osForm').serialize();

	$.ajax({
		url: 'OverShortCashierPage.php',
		data: data,
		success: function(response){
			$('#display').html(response);
			$('#countSCA').focus();
		}
	});

	$('#date').val('');
	$('#empno').val('');
}

function resumSheet(){
	var countTotal = 0;
	var osTotal = 0;
	var posTotal = 0;
	
	$('.tenderCode').each(function(){
		var c = 0;
		var code = $(this).val();
		if (code != 'CK')
			c = Number($('#count'+code).val());
		else
			c = Number($('#countCK').html());

		var p = Number($('#pos'+code).html());
		
		var os = Math.round( (c - p)*100 ) / 100;	
		if (code == 'CA'){
			var sca = Number($('#countSCA').val());
			posTotal += sca;
			os = Math.round( (c - p - sca)*100 ) / 100;
		}

		osTotal += os;
		countTotal += c;
		posTotal += p;
		
		$('#os'+code).html(os);
	});
	
	$('#posT').html(Math.round(posTotal*100)/100);
	$('#countT').html(Math.round(countTotal*100)/100);
	$('#osT').html(Math.round(osTotal*100)/100);
}

function resumChecks(){
	var checks = $('#checklisting').val();
	var tmp = checks.split("\n");
	var sum = 0;
	for (var i = 0; i < tmp.length; i++){
		sum += Number(tmp[i]);
	}

	$('#countCK').html(Math.round(sum*100)/100);
	resumSheet();
}

function save(){
	var tenders = saveTenders();
	var checks = saveChecks();
	var notes = escape($('#notes').val());
	var empno = $('#current_empno').val();
	var tdate = $('#current_date').val();
    var store = $('#current_store').val();

	var args = 'action=save&empno='+empno+'&date='+tdate+'&tenders='+tenders+'&checks='+checks+'&notes='+notes+'&store='+store;
	$.ajax({
		url: 'OverShortCashierPage.php',
		type: 'post',
		data: args,
		success: function(data){
			alert(data);
		}
	});
}

function saveTenders(){
	var ret = '';
	var sca = $('#countSCA').val();
	ret += "SCA:"+sca;
	$('.tenderCode').each(function(){
		var code = $(this).val();
		ret += "|"+code+":";
		if (code != 'CK')
			ret += $('#count'+code).val();
		else
			ret += $('#count'+code).html();
	});

	return ret;
}

function saveChecks(){
	var ret = '';
	var checks = $('#checklisting').val();
	var tmp = checks.split("\n")

	for (var i=0; i<tmp.length;i++){
		ret += tmp[i]+",";
	}

	ret = ret.substring(0,ret.length - 1);
	return ret;
}

function sumCashCounter()
{
    var entry = $('#cash-counter').val();
    var regexp = /[0-9\.]+/g;
    var numbers = entry.match(regexp);
    var sum = 0.0;
    for (var i=0; i<numbers.length; i++) {
        sum += Number(numbers[i]);
    }
    if (sum > 0) {
        $('#countCA').val(sum);
    }
}
