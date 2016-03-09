function loader(){
	$('#display').html('');
	var date1 = $('#startDate').val();
	var date2 = $('#endDate').val();
    var store = $('select[name=store]').val();
	var args = 'action=loader&date1='+date1+'&date2='+date2+'&store='+store;
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
    var store = $('#savedStore').val();

	var changeOrder = saveChangeOrder();
	var openSafeCount = saveOpenSafeCount();
	var closeSafeCount = saveCloseSafeCount();
	var buyAmount = saveRow('buyAmount');
	var dropAmount = saveDropAmount();
	var depositAmount = saveRow('depositAmount');
	var atmAmount = saveAtmAmount();

	var args = 'action=save&date1='+date1+'&date2='+date2+'&changeOrder='+changeOrder+'&openSafeCount='+openSafeCount+'&closeSafeCount='+closeSafeCount+'&buyAmount='+buyAmount+'&dropAmount='+dropAmount+"&depositAmount="+depositAmount+'&atmAmount='+atmAmount+'&store='+store;

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
        var elem = document.getElementById(rowName+denom);
		if (elem) {
			ret += denom + ":"+ elem.innerHTML+"|";
        }
	});
	return ret;
}

function saveAtmAmount(){
	var ret = '';
	if ($('#atmFill').length !== 0) {
		ret += 'fill:'+$('#atmFill').val();
	} else {
		ret += 'fill:0';
    }
	if ($('#atmReject').length !== 0) {
		ret += '|reject:'+$('#atmReject').val();
	} else {
		ret += '|reject:0';
    }
	return ret;
}

function saveChangeOrder(){
	var ret = '';
	$('.denom').each(function(){
		var denom = $(this).val();
        var elem = document.getElementById('changeOrder'+denom);
		if (denom !== 'Checks' && elem) {
			ret += denom + ":"+ elem.value+"|";
        }
	});
	return ret;
}

function saveOpenSafeCount(){
	var ret = '';
	$('.denom').each(function(){
		var denom = $(this).val();
        var elem = document.getElementById('safeCount1'+denom);
		if (denom != 'Checks' && elem)
			ret += denom + ":"+ elem.value+"|";
	});
	return ret;
}

function saveDropAmount(){
	var ret = '';
	$('.denom').each(function(){
		var denom = $(this).val();
        var elem = document.getElementById('dropAmount'+denom);
		if (denom === 'Checks' || denom === '1.00') {
			ret += denom + ":"+ elem.innerHTML+"|";
		} else {
			ret += denom + ":"+ elem.value+"|";
        }
	});
	return ret;
}

function saveCloseSafeCount(){
	var ret = '';
	$('.denom').each(function(){
		var denom = $(this).val();
        var elem = document.getElementById('safeCount2'+denom);
		if (denom !== 'Checks' && denom !== 'Junk') {
			ret += denom + ":"+ elem.value+"|";
        }
	});
	return ret;
}

function updateChangeOrder(d){
	var newval = Number(document.getElementById('changeOrder'+d).value);
	
	var v = Number(document.getElementById('safeCount1'+d).value) + newval;
	document.getElementById('cashInTills'+d).innerHTML = Math.round(v*100)/100;

	resumInputs('changeOrder');
	resumRow('cashInTills');

	updateDepositAmount(d);

	updateAAVariance();
}

function updateOpenSafeCount(d){
	var newval = Number(document.getElementById('safeCount1'+d).value);
	
	var v = newval;
    var elem = document.getElementById('changeOrder'+d);
	if (elem) {
		v = Number(elem.value) + newval;
    }
	document.getElementById('cashInTills'+d).innerHTML = Math.round(v*100)/100;

	resumInputs('safeCount1');
	resumRow('cashInTills');

	updateDepositAmount(d);

	updateAAVariance();
}

function updateDropAmount(d){
	var ones = Number($('#dropAmountTotal').html());
	$('.denom').each(function(){
		var denom = $(this).val();
		if (denom === "1.00"){
		} else if (denom == "Checks") {
			ones -= Number($('#dropAmountChecks').html());
		} else {
			ones -= Number(document.getElementById('dropAmount'+denom).value);
        }
	});

	document.getElementById('dropAmount1.00').innerHTML = Math.round(ones*100)/100;

	updateDepositAmount(d);
}

function updateAAVariance(){
	var accountable = Number($('#cashInTillsTotal').html());
	accountable += Number($('#dropAmountTotal').html());
    accountable -= Number($('#fillTotal').html());
    accountable -= Number($('#depositAmountTotal').html());
	var actual = Number($('#safeCount2Total').html());

	var variance = actual - accountable;

	$('#actualTotal').html(Math.round(100*actual)/100);
	$('#accountableTotal').html(Math.round(100*accountable)/100);
	$('#aaVariance').html(Math.round(100*variance)/100);
}

function updateCloseSafeCount(d){
	var newval = Number(document.getElementById('safeCount2'+d).value);

	resumInputs('safeCount2');

	updateAAVariance();
}

function resumInputs(rowname){
	var sum = 0;

	$('.denom').each(function(){
		denom = $(this).val();
        var elem = document.getElementById(rowname+denom);
		if (elem) {
			sum += Number(elem.value);
        }
	});
    
	$('#'+rowname+'Total').html(Math.round(sum*100) / 100);
}

function resumRow(rowname){
	var sum = 0;
	$('.denom').each(function(){
		denom = $(this).val();
        var elem = document.getElementById(rowname+denom);
		if (rowname === "depositAmount" && denom === "Checks") {
            // would be "continue" in a loop
		} else if (elem) {
			sum += Number(elem.innerHTML);
        }
	});
	$('#'+rowname+'Total').html(Math.round(sum*100) / 100);
}

function updateDepositAmount(d){
    var val = 0;
    var count = 0;
	switch(d){
	case '10.00':
	case '5.00':
		val = Number(document.getElementById('cashInTills'+d).innerHTML);
		val += Number(document.getElementById('dropAmount'+d).value);
		val -= Number(document.getElementById('fill'+d).innerHTML);
		val -= Number(document.getElementById('#par'+d).innerHTML);
		if (val < 0) val = 0;
		document.getElementById('depositAmount'+d).innerHTML = Math.round(val*100)/100;
		updateBuyAmount(d);
		break;
	case '1.00':
		updateBuyAmount(d);
		break;
	case '20.00':
		val = Number(document.getElementById('cashInTills'+d).innerHTML);
		val += Number(document.getElementById('dropAmount'+d).value);
		val += Number($('#atmReject').val());	
		val -= Number($('#atmFill').val());
		document.getElementById('depositAmount'+d).innerHTML = Math.round(val*100)/100;
		break;
	case '50.00':
	case '100.00':
	case 'Junk':
		val = Number(document.getElementById('cashInTills'+d).innerHTML);
		val += Number(document.getElementById('dropAmount'+d).value);
		document.getElementById('depositAmount'+d).innerHTML = Math.round(val*100)/100;
		break;
	case '0.25':
		count = Math.floor(Number(document.getElementById('dropAmount'+d).value) / 10);
		val = Number(document.getElementById('dropAmount'+d).value) - (10*count);
		document.getElementById('depositAmount'+d).innerHTML = Math.round(val*100)/100;
		updateBuyAmount(d);
		break;
	case '0.10':
		count = Math.floor(Number(document.getElementById('dropAmount'+d).value) / 5);
		val = Number(document.getElementById('dropAmount'+d).value) - (5*count);
		document.getElementById('depositAmount'+d).innerHTML = Math.round(val*100)/100;
		updateBuyAmount(d);
		break;
	case '0.05':
		count = Math.floor(Number(document.getElementById('dropAmount'+d).value) / 2);
		val = Number(document.getElementById('dropAmount'+d).value) - (2*count);
		document.getElementById('depositAmount'+d).innerHTML = Math.round(val*100)/100;
		updateBuyAmount(d);
		break;
	case '0.01':
		count = Math.floor(Number(document.getElementById('dropAmount'+d).value) / 0.50);
		val = Number(document.getElementById('dropAmount'+d).value) - (0.50*count);
		document.getElementById('depositAmount'+d).innerHTML = Math.round(val*100)/100;
		updateBuyAmount(d);
		break;
	}

	resumRow('depositAmount');
}

function updateBuyAmount(d){
	if (d === 'Checks' || d === '100.00' || d === '50.00' || d === '20.00' || d === 'Junk')
		return;

	$('.denom').each(function(){
		var denom = $(this).val();
		if (denom === 'Checks' || denom === '100.00' || denom === '50.00' || denom === '20.00' || denom === 'Junk') {
			// simulated "continue"
		} else {
			var val = Number(document.getElementById('par'+denom).innerHTML);

			val -= Number(document.getElementById('cashInTills'+denom).innerHTML);
			if (denom === '1.00') {
				val -= Number(document.getElementById('dropAmount'+denom).innerHTML);
			} else {
				val -= Number(document.getElementById('dropAmount'+denom).value);
            }
			val += Number(document.getElementById('fill'+denom).innerHTML);
			val += Number(document.getElementById('depositAmount'+denom).innerHTML);

			if (val < 0) val = 0;
			if (denom === '1.00') val = Math.round(val);

			document.getElementById('buyAmount'+denom).innerHTML = Math.round(val*100)/100;
		}
	});
	var overage = 0;

	var i = 0;
	var v = Number(document.getElementById('buyAmount10.00').innerHTML);
	while (v % 50 != 0 && i < 5){
		v = v - 10;
		overage = overage + 10;
		i = i+1;
	}
	document.getElementById('buyAmount10.00').innerHTML = v;

	i = 0;
	v = Number(document.getElementById('buyAmount5.00').innerHTML);
	while (v % 50 != 0 && i < 10){
		v = v - 5;
		overage = overage + 5;
		i = i+1;
	}
	document.getElementById('buyAmount5.00').innerHTML = v;

	i = 0;
	v = Number(document.getElementById('buyAmount1.00').innerHTML);
	while (v % 50 != 0 && i < 50){
		v = v - 1;
		overage = overage + 1;
		i = i+1;
	}
	document.getElementById('buyAmount1.00').innerHTML = v;

	var overs = denom_overage(overage);
	if (overs[0] != 0){
		v = Number(document.getElementById('buyAmount0.25').innerHTML);
		document.getElementById('buyAmount0.25').innerHTML = v + overs[0];
	}
	if (overs[1] != 0){
		v = Number(document.getElementById('buyAmount0.10').innerHTML);
		document.getElementById('#buyAmount0.10').innerHTML = v + overs[1];
	}
	if (overs[2] != 0){
		v = Number(document.getElementById('buyAmount0.05').innerHTML);
		document.getElementById('buyAmount0.05').innerHTML = v + overs[2];
	}
	if (overs[3] != 0){
		v = Number(document.getElementById('buyAmount0.01').innerHTML);
		document.getElementById('buyAmount0.01').innerHTML = v + overs[3];
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
        if (dates.length === 2) {
            $('#startDate').val(dates[0]);
            $('#endDate').val(dates[1]);
        }
    }
}
