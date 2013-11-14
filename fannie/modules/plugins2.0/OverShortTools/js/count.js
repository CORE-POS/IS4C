function loader(){
	$('#display').html('');
	var date1 = $('#startDate').val();
	var date2 = $('#endDate').val();
	var args = 'action=loader&date1='+date1+'&date2='+date2;
	$.ajax({
		url: 'OverShortSafecountPage.php',
		data: args,
		success: function(data){
			$('#display').html(data);
		}
	});
}

function save(){
	var date1 = $('#startDate').val();
	var date2 = $('#endDate').val();

	var depositAmount = saveRow('changeOrder');
	var openSafeCount = saveOpenSafeCount();
	var closeSafeCount = saveCloseSafeCount();
	var depositAmount = saveRow('buyAmount');
	var dropAmount = saveDropAmount();
	var depositAmount = saveRow('depositAmount');
	var atmAmount = saveAtmAmount();

	var args = 'action=save&date1='+date1+'&date2='+date2+'&changeOrder='+changeOrder+'&openSafeCount='+openSafeCount+'&closeSafeCount='+closeSafeCount+'&buyAmount='+buyAmount+'&dropAmount='+dropAmount+"&depositAmount="+depositAmount+'&atmAmount='+atmAmount;

	$.ajax({
		url: 'OverShortSafecountPage.php',
		type: 'post',
		data: args,
		success: function(data){
			alert(data);
		}
	});
}

function saveRow(rowName){
	var ret = '';
	$('.denom').each(function(){
		var denom = $(this).val();
		if ($('#'+rowName+denom).length != 0)
			ret += denom + ":"+ $('#'+rowName+denom).html()+"|";
	});
	return ret;
}

function saveAtmAmount(){
	var ret = '';
	if ($('#atmFill').length != 0)
		ret += 'fill:'+$('#atmFill').val();
	else
		ret += 'fill:0';
	if ($('#atmReject').length != 0)
		ret += '|reject:'+$('#atmReject').val();
	else
		ret += '|reject:0';
	return ret;
}

function saveOpenSafeCount(){
	var ret = '';
	$('.denom').each(function(){
		var denom = $(this).val();
		if (denom != 'Checks' && $('#safeCount1'+denom).length != 0)
			ret += denom + ":"+ $('#safeCount1'+denom).val()+"|";
	});
	return ret;
}

function saveDropAmount(){
	var ret = '';
	$('.denom').each(function(){
		var denom = $(this).val();
		if (denoms[i] == 'Checks' || denoms[i] == '1.00')
			ret += denom + ":"+ $('#dropAmount'+denom).html()+"|";
		else
			ret += denom + ":"+ $('#dropAmount'+denom).val()+"|";
	});
	return ret;
}

function saveCloseSafeCount(){
	var ret = '';
	$('.denom').each(function(){
		var denom = $(this).val();
		if (denom != 'Checks' && denom != 'Junk')
			ret += denom + ":"+ $('#safeCount2'+denom).val()+"|";
	});
	return ret;
}

function updateChangeOrder(d){
	var newval = Number($('#changeOrder'+d).val());
	
	var v = Number($('#safeCount1'+d).val()) + newval;
	$('#cashInTills'+d).html(Math.round(v*100)/100);

	resumInputs('changeOrder');
	resumRow('cashInTills');

	updateDepositAmount(d);

	updateAAVariance();
}

function updateOpenSafeCount(d){
	var newval = Number($('#safeCount1'+d).val());
	
	var v = newval;
	if ($('#changeOrder'+d).length != 0)
		v = Number($('#changeOrder'+d).val()) + newval;
	$('#cashInTills'+d).html(Math.round(v*100)/100);

	resumInputs('safeCount1');
	resumRow('cashInTills');

	updateDepositAmount(d);

	updateAAVariance();
}

function updateDropAmount(d){
	var ones = Number($('#dropAmountTotal').html());
	$('.denom').each(function(){
		var denom = $(this).val();
		if (denom == "1.00"){}
		else if (denom == "Checks")
			ones -= Number($('#dropAmountChecks').html());
		else
			ones -= Number($('#dropAmount'+denom).val());
	});

	$('#dropAmount1.00').html(Math.round(ones*100)/100);

	updateDepositAmount(d);
}

function updateAAVariance(){
	var accountable = Number($('#cashInTillsTotal').html())
		   + Number($('#dropAmountTotal').html());
		   - Number($('#fillTotal').html());
		   - Number($('#depositAmountTotal').html());
	var actual = Number($('#safeCount2Total').html());

	var variance = actual - accountable;

	$('#actualTotal').html(Math.round(100*actual)/100);
	$('#accountableTotal').html(Math.round(100*accountable)/100);
	$('#aaVariance').html(Math.round(100*variance)/100);
}

function updateCloseSafeCount(d){
	var newval = Number($('#safeCount2'+d).val());

	resumInputs('safeCount2');

	updateAAVariance();
}

function resumInputs(rowname){
	var sum = 0;
	$('.denom').each(function(){
		denom = $(this).val();
		if ($('#'+rowname+denom).length != 0)
			sum += Number($('#'+rowname+denom).val());
	});
	$('#'+rowname+'Total').html(Math.round(sum*100) / 100);
}

function resumRow(rowname){
	var sum = 0;
	$('.denom').each(function(){
		denom = $(this).val();
		if (rowname == "depositAmount" && denom == "Checks")
			{} // would be "continue" in a loop
		else if ($('#'+rowname+denom).length != 0)
			sum += Number($('#'+rowname+denom).html());
	});
	$('#'+rowname+'Total').html(Math.round(sum*100) / 100);
}

function updateDepositAmount(d){
	switch(d){
	case '10.00':
	case '5.00':
		var val = Number($('#cashInTills'+d).html());
		val += Number($('#dropAmount'+d).val());
		val -= Number($('#fill'+d).html());
		val -= Number($('#par'+d).html());
		if (val < 0) val = 0;
		$('#depositAmount'+d).html(Math.round(val*100)/100);
		updateBuyAmount(d);
		break;
	case '1.00':
		updateBuyAmount(d);
		break;
	case '20.00':
		var val = Number($('#cashInTills'+d).html);
		val += Number($('#dropAmount'+d).val());
		val += Number($('#atmReject').val());	
		val -= Number($('#atmFill').val());
		$('#depositAmount'+d).html(Math.round(val*100)/100);
		break;
	case '50.00':
	case '100.00':
	case 'Junk':
		var val = Number($('#cashInTills'+d).html());
		val += Number($('#dropAmount'+d).val());
		$('#depositAmount'+d).html(Math.round(val*100)/100);
		break;
	case '0.25':
		var count = Math.floor(Number($('#dropAmount'+d).val()) / 10);
		var val = Number($('#dropAmount'+d).val()) - (10*count);
		$('#depositAmount'+d).html(Math.round(val*100)/100);
		updateBuyAmount(d);
		break;
	case '0.10':
		var count = Math.floor(Number($('#dropAmount'+d).val()) / 5);
		var val = Number($('#dropAmount'+d).val()) - (5*count);
		$('#depositAmount'+d).html(Math.round(val*100)/100);
		updateBuyAmount(d);
		break;
	case '0.05':
		var count = Math.floor(Number($('#dropAmount'+d).val()) / 2);
		var val = Number($('#dropAmount'+d).val()) - (2*count);
		$('#depositAmount'+d).html(Math.round(val*100)/100);
		updateBuyAmount(d);
		break;
	case '0.01':
		var count = Math.floor(Number($('#dropAmount'+d).val()) / 0.50);
		var val = Number($('#dropAmount'+d).val()) - (0.50*count);
		$('#depositAmount'+d).html(Math.round(val*100)/100);
		updateBuyAmount(d);
		break;
	}

	resumRow('depositAmount');
}

function updateBuyAmount(d){
	if (d == 'Checks' || d == '100.00' || d == '50.00' || d == '20.00' || d == 'Junk')
		return;

	$('.denom').each(function(){
		var denom = $(this).val();
		if (denom == 'Checks' || denom == '100.00' || denom == '50.00' || denom == '20.00' || denom == 'Junk')
			{} // simulated "continue"
		else {
			var val = Number($('#par'+denom).html());

			val -= Number($('#cashInTills'+denom).html());
			if (denom == '1.00')
				val -= Number($('#dropAmount'+denom).html());
			else
				val -= Number($('#dropAmount'+denom).val());
			val += Number($('#fill'+denom).html());
			val += Number($('#depositAmount'+denom).html());

			if (val < 0) val = 0;
			if (denom == '1.00') val = Math.round(val);

			$('#buyAmount'+denom).html(Math.round(val*100)/100);
		}
	});
	var overage = 0;

	var i = 0;
	var v = Number($('#buyAmount10.00').html());
	while (v % 50 != 0 && i < 5){
		v = v - 10;
		overage = overage + 10;
		i = i+1;
	}
	$('#buyAmount10.00').html(v);

	var i = 0;
	var v = Number($('#buyAmount5.00').html());
	while (v % 50 != 0 && i < 10){
		v = v - 5;
		overage = overage + 5;
		i = i+1;
	}
	$('#buyAmount5.00').html(v);

	var i = 0;
	var v = Number($('#buyAmount1.00').html());
	while (v % 50 != 0 && i < 50){
		v = v - 1;
		overage = overage + 1;
		i = i+1;
	}
	$('#buyAmount1.00').html(v);

	var overs = denom_overage(overage);
	if (overs[0] != 0){
		var v = Number($('#buyAmount0.25').html());
		$('#buyAmount0.25').html(v + overs[0]);
	}
	if (overs[1] != 0){
		var v = Number($('#buyAmount0.10').html());
		$('#buyAmount0.10').html(v + overs[1]);
	}
	if (overs[2] != 0){
		var v = Number($('#buyAmount0.05').html());
		$('#buyAmount0.05').html(v + overs[2]);
	}
	if (overs[3] != 0){
		var v = Number($('#buyAmount0.01').html());
		$('#buyAmount0.01').html(v + overs[3]);
	}

	resumRow('buyAmount');
}

function updateAtmAmounts(){
	updateDepositAmount('20.00');
}

function denom_overage(overage){
	var ret = Array(0,0,0,0);

	ret[0] = Math.floor(overage / 10.0)*10;
	overage = overage % 10;
	ret[1] = Math.floor(overage / 5.0)*5;
	overage = overage % 5;
	ret[2] = Math.floor(overage / 2.0)*2;
	overage = overage % 2;
	ret[3] = Math.floor(overage / 0.50)*0.50;

	return ret;
}

function existingDates(dateStr)
{
    if (dateStr != '') {
        var dates = dateStr.split(' ');
        if (dates.length == 2) {
            $('#startDate').val(dates[0]);
            $('#endDate').val(dates[1]);
        }
    }
}
