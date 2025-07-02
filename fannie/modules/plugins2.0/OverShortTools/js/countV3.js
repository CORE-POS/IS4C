function loader(){
	$('#display').html('');
	var date1 = $('#startDate').val();
	var date2 = $('#endDate').val();
    var store = $('select[name=store]').val();
    var type = $('select[name=type]').val();
	var args = 'action=loader&date1='+date1+'&date2='+date2+'&store='+store+'&type='+type;
	$.ajax({
		url: 'OverShortSafecountV3.php',
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
    var type = $('#savedType').val();

	var changeOrder = saveChangeOrder();
	var openSafeCount = saveOpenSafeCount();
	var buyAmount = saveRow('buyAmount');
	var atmAmount = saveAtmAmount('atmCount');
	var atmAmount2 = saveAtmAmount('atmCount2');
    var tillCount = saveTillCounts();
    var notes = saveNotes();

	var args = 'action=save&date1='+date1+'&date2='+date2+'&changeOrder='+changeOrder+'&openSafeCount='+openSafeCount+'&buyAmount='+buyAmount+'&atmAmount='+atmAmount+'&tillCount='+tillCount+'&notes='+notes+'&store='+store+'&type='+type+'&atmAmount2='+atmAmount2;

	$.ajax({
		url: 'OverShortSafecountV3.php',
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

function saveTillCounts() {
    var ret = '';
    $('.drop').each(function() {
        var rowID = this.id;
        var amt = Number(this.value);
        ret += rowID + ":" + amt + "|";
    });

    return ret;
}

function saveNotes() {
    var ret = '';
    $('.day-notes').each(function () {
        var noteID = this.id;
        var note = encodeURIComponent(this.value);
        ret += noteID + ':' + note + '|';
    });

    return ret;
}

function saveAtmAmount(idstr){
	var ret = '';
	if ($('#' + idstr).length !== 0) {
		ret += 'count:'+$('#' + idstr).val();
	} else {
		ret += 'count:0';
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

function updateChangeOrder(d){
	var newval = Number(document.getElementById('changeOrder'+d).value);
	
	var v = Number(document.getElementById('safeCount1'+d).value) + newval;
	document.getElementById('cashInTills'+d).innerHTML = Math.round(v*100)/100;

	resumInputs('changeOrder');
	resumRow('cashInTills');
    updateBuyAmount(d);

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
    updateBuyAmount(d);

    if (d == '20.00' || d == '50.00' || d == '100.00' || d == 'Junk') {
        var newttl = Number(document.getElementById('safeCount150.00').value);
        newttl += Number(document.getElementById('safeCount1100.00').value);
        newttl += Number(document.getElementById('safeCount120.00').value);
        newttl += Number(document.getElementById('safeCount1Junk').value);
        document.getElementById('extraPos').innerHTML = newttl;
        document.getElementById('dropExtra').dispatchEvent(new Event('change'));
    }

	updateAAVariance();
}

function updateAAVariance(){
	var accountable = Number($('#cashInTillsTotal').html());
	var actual = Number($('#safeCount2Total').html());

	var variance = actual - accountable;

	$('#actualTotal').html(Math.round(100*actual)/100);
	$('#accountableTotal').html(Math.round(100*accountable)/100);
	$('#aaVariance').html(Math.round(100*variance)/100);
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
		if (elem) {
			sum += Number(elem.innerHTML);
        }
	});
	$('#'+rowname+'Total').html(Math.round(sum*100) / 100);
}

function updateBuyAmount(d){
	if (d === 'Checks' || d === '100.00' || d === '50.00' || d === 'Junk')
		return;

	$('.denom').each(function(){
		var denom = $(this).val();
		if (denom === 'Checks' || denom === '100.00' || denom === '50.00' || denom === 'Junk') {
			// simulated "continue"
		} else {
			var val = Number(document.getElementById('par'+denom).innerHTML);

            if (denom === '20.00') {
                val -= Number(document.getElementById('changeOrder20.00').value);
                val -= Number(document.getElementById('atmCount').value);
                val -= Number(document.getElementById('atmCount2').value);
            } else {
                val -= Number(document.getElementById('cashInTills'+denom).innerHTML);
            }

			if (val < 0) val = 0;
			if (denom === '1.00') val = Math.round(val);

			document.getElementById('buyAmount'+denom).innerHTML = Math.round(val*100)/100;
		}
	});

	var i = 0;
	var v = Number(document.getElementById('buyAmount10.00').innerHTML);
	while (v % 50 != 0 && i < 5){
		v = v - 10;
		i = i+1;
	}
	document.getElementById('buyAmount10.00').innerHTML = v;

	i = 0;
	v = Number(document.getElementById('buyAmount5.00').innerHTML);
	while (v % 50 != 0 && i < 10){
		v = v - 5;
		i = i+1;
	}
	document.getElementById('buyAmount5.00').innerHTML = v;

	i = 0;
	v = Number(document.getElementById('buyAmount1.00').innerHTML);
	while (v % 50 != 0 && i < 50){
		v = v - 1;
		i = i+1;
	}
	document.getElementById('buyAmount1.00').innerHTML = v;

    var quarters = Number(document.getElementById('buyAmount0.25').innerHTML);
    while ((Math.round(quarters * 100)) % 1000 != 0 && (Math.round(quarters * 100)) % 25 == 0) {
        quarters += 0.25;
    }
    quarters = Math.round(quarters * 100) / 100;
    document.getElementById('buyAmount0.25').innerHTML = quarters;

    var dimes = Number(document.getElementById('buyAmount0.10').innerHTML);
    while ((Math.round(dimes * 100)) % 500 != 0 && (Math.round(dimes * 100)) % 10 == 0) {
        dimes += 0.10;
    }
    dimes = Math.round(dimes * 100) / 100;
    document.getElementById('buyAmount0.10').innerHTML = dimes;

    var nickels = Number(document.getElementById('buyAmount0.05').innerHTML);
    while ((Math.round(nickels * 100)) % 200 != 0 && (Math.round(nickels * 100)) % 5 == 0) {
        nickels += 0.05;
    }
    nickels = Math.round(nickels * 100) / 100;
    document.getElementById('buyAmount0.05').innerHTML = nickels;

    var pennies = Number(document.getElementById('buyAmount0.01').innerHTML);
    while ((Math.round(pennies * 100)) % 50 != 0) {
        pennies += 0.01;
    }
    pennies = Math.round(pennies * 100) / 100;
    document.getElementById('buyAmount0.01').innerHTML = pennies;

	resumRow('buyAmount');
}

function updateAtmAmounts(){
    updateBuyAmount('20.00');
}

function existingDates(dateStr)
{
    if (dateStr != '') {
        var dates = dateStr.split(' ');
        if (dates.length === 2) {
            $('#startDate').val(dates[0]);
            $('#endDate').val(dates[1]);
        } else {
            $('#startDate').val(dates[0]);
            $('#endDate').val(dates[0]);
        }
    }
}

function recalcDropVariance(e) {
    var elem = e.target;
    var cash = $(elem).closest('tr').find('.pos').html();
    var myself = elem.value;
    var diff = Math.round((myself - cash) * 100) / 100;
    $(elem).closest('tr').find('.var').html(diff);

    var sum = 0;
    $('.drop').each(function() {
        sum += Number($(this).val());
    });
    $('#dropTTL').html(sum);

    sum = 0;
    $('.var').each(function() {
        sum += Number($(this).html());
    });
    sum = Math.round(sum * 100) / 100;
    $('#dropVar').html(sum);
}

function crossCheck() {
    var ccID = $('#ccID').val();
    var args = 'action=crosscheck&ccID=' + ccID;
	$.ajax({
		url: 'OverShortSafecountV3.php',
		type: 'post',
		data: args,
		success: function(data){
			$('#ccAmt').html(data);
		}
	});
}

