/* ajax request */
function createRequestObject() {
    var ro;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
        ro = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
        ro = new XMLHttpRequest();
    }
    return ro;
}

/* global request object */
var http = createRequestObject();

/* send action to this page 
   tack on more arguments as needed with '&' and '='
*/
function phpSend(action) {
    http.open('get', 'count.php?action='+action);
    http.onreadystatechange = handleResponse;
    http.send(null);
}

/* ajax callback function 
   by convention, results return [actionname]`[data]
   splitting on backtick separates, then switch on action name
   allows different actions to be handled differently
*/
function handleResponse() {
    if(http.readyState == 4){
        var response = http.responseText;
        var array = response.split('`');
	switch(array[0]){
	case 'loader':
		document.getElementById('display').innerHTML=array[1];
		break;
	case 'save':
		alert(array[1]);
		break;
	default:
		alert('ERROR:'+response);
	}
    }
}

function loader(){
	document.getElementById('display').innerHTML='';
	var date1 = document.getElementById('startDate').value;	
	var date2 = document.getElementById('endDate').value;	
	phpSend('loader&date1='+date1+'&date2='+date2);
}

function save(){
	var date1 = document.getElementById('savedDate1').value;
	var date2 = document.getElementById('savedDate2').value;

	var changeOrder = saveChangeOrder();
	var openSafeCount = saveOpenSafeCount();
	var closeSafeCount = saveCloseSafeCount();
	var buyAmount = saveBuyAmount();
	var dropAmount = saveDropAmount();
	var depositAmount = saveDepositAmount();
	var atmAmount = saveAtmAmount();

	phpSend('save&date1='+date1+'&date2='+date2+'&changeOrder='+changeOrder+'&openSafeCount='+openSafeCount+'&closeSafeCount='+closeSafeCount+'&buyAmount='+buyAmount+'&dropAmount='+dropAmount+"&depositAmount="+depositAmount+'&atmAmount='+atmAmount);
}

var denoms = Array('0.01','0.05','0.10','0.25','Junk','1.00','5.00','10.00','20.00','50.00','100.00','Checks');

function saveDepositAmount(){
	var ret = '';
	for(var i=0; i<denoms.length;i++){
		if (document.getElementById('depositAmount'+denoms[i]))
			ret += denoms[i]+":"+document.getElementById('depositAmount'+denoms[i]).innerHTML;
		if (i < denoms.length-1)
			ret += "|";
	}
	return ret;
}

function saveAtmAmount(){
	var ret = '';
	if (document.getElementById('atmFill'))
		ret += 'fill:'+document.getElementById('atmFill').value;
	else
		ret += 'fill:0';
	if (document.getElementById('atmReject'))
		ret += '|reject:'+document.getElementById('atmReject').value;
	else
		ret += '|reject:0';
	return ret;
}

function saveBuyAmount(){
	var ret = '';
	for(var i=0; i<denoms.length;i++){
		if (document.getElementById('buyAmount'+denoms[i]))
			ret += denoms[i]+":"+document.getElementById('buyAmount'+denoms[i]).innerHTML;
		if (i < denoms.length-1)
			ret += "|";
	}
	return ret;
}

function saveChangeOrder(){
	var ret = '';
	for(var i=0; i<denoms.length;i++){
		if (document.getElementById('changeOrder'+denoms[i]))
			ret += denoms[i]+":"+document.getElementById('changeOrder'+denoms[i]).value;
		if (i < denoms.length-1) ret += "|";
	}
	return ret;
}

function saveOpenSafeCount(){
	var ret = '';
	for(var i=0; i<denoms.length;i++){
		if (denoms[i] != 'Checks')
			ret += denoms[i]+":"+document.getElementById('safeCount1'+denoms[i]).value;
		if (i < denoms.length-1) ret += "|";
	}
	return ret;
}

function saveDropAmount(){
	var ret = '';
	for (var i=0; i<denoms.length;i++){
		if (denoms[i] == 'Checks' || denoms[i] == '1.00')
			ret += denoms[i]+":"+document.getElementById('dropAmount'+denoms[i]).innerHTML;
		else
			ret += denoms[i]+":"+document.getElementById('dropAmount'+denoms[i]).value;
		if (i < denoms.length-1) ret += "|";
	}
	return ret;
}

function saveCloseSafeCount(){
	var ret = '';
	for(var i=0; i<denoms.length;i++){
		if (denoms[i] != 'Checks' && denoms[i] != 'Junk')
			ret += denoms[i]+":"+document.getElementById('safeCount2'+denoms[i]).value;
		if (i < denoms.length-1) ret += "|";
	}
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
	if (document.getElementById('changeOrder'+d))
		v = Number(document.getElementById('changeOrder'+d).value) + newval;
	document.getElementById('cashInTills'+d).innerHTML = Math.round(v*100)/100;

	resumInputs('safeCount1');
	resumRow('cashInTills');

	updateDepositAmount(d);

	updateAAVariance();
}

function updateDropAmount(d){
	var ones = Number(document.getElementById('dropAmountTotal').innerHTML);
	for (var i=0; i < denoms.length; i++){
		if (denoms[i] == "1.00"){}
		else if (denoms[i] == "Checks")
			ones -= Number(document.getElementById('dropAmountChecks').innerHTML);
		else
			ones -= Number(document.getElementById('dropAmount'+denoms[i]).value);
	}

	document.getElementById('dropAmount1.00').innerHTML = Math.round(ones*100)/100;

	updateDepositAmount(d);
}

function updateAAVariance(){
	var accountable = Number(document.getElementById('cashInTillsTotal').innerHTML)
		   + Number(document.getElementById('dropAmountTotal').innerHTML);
		   - Number(document.getElementById('fillTotal').innerHTML);
		   - Number(document.getElementById('depositAmountTotal').innerHTML);
	var actual = Number(document.getElementById('safeCount2Total').innerHTML);

	var variance = actual - accountable;

	document.getElementById('actualTotal').innerHTML = Math.round(100*actual)/100;
	document.getElementById('accountableTotal').innerHTML = Math.round(100*accountable)/100;
	document.getElementById('aaVariance').innerHTML = Math.round(100*variance)/100;
}

function updateCloseSafeCount(d){
	var newval = Number(document.getElementById('safeCount2'+d).value);

	resumInputs('safeCount2');

	updateAAVariance();
}

function resumInputs(rowname){
	var sum = 0;
	for(var i=0; i < denoms.length; i++){
		if (document.getElementById(rowname+denoms[i]))
			sum += Number(document.getElementById(rowname+denoms[i]).value);
	}
	document.getElementById(rowname+'Total').innerHTML = Math.round(sum*100) / 100;
}

function resumRow(rowname){
	var sum = 0;
	for(var i=0; i < denoms.length; i++){
		if (rowname == "depositAmount" && denoms[i] == "Checks")
			continue;
		if (document.getElementById(rowname+denoms[i]))
			sum += Number(document.getElementById(rowname+denoms[i]).innerHTML);
	}
	document.getElementById(rowname+'Total').innerHTML = Math.round(sum*100) / 100;
}

function updateDepositAmount(d){
	switch(d){
	case '10.00':
	case '5.00':
		var val = Number(document.getElementById('cashInTills'+d).innerHTML);
		val += Number(document.getElementById('dropAmount'+d).value);
		val -= Number(document.getElementById('fill'+d).innerHTML);
		val -= Number(document.getElementById('par'+d).innerHTML);
		if (val < 0) val = 0;
		document.getElementById('depositAmount'+d).innerHTML=Math.round(val*100)/100;
		updateBuyAmount(d);
		break;
	case '1.00':
		updateBuyAmount(d);
		break;
	case '20.00':
		var val = Number(document.getElementById('cashInTills'+d).innerHTML);
		val += Number(document.getElementById('dropAmount'+d).value);
		val += Number(document.getElementById('atmReject').value);	
		val -= Number(document.getElementById('atmFill').value);	
		document.getElementById('depositAmount'+d).innerHTML=Math.round(val*100)/100;
		break;
	case '50.00':
	case '100.00':
	case 'Junk':
		var val = Number(document.getElementById('cashInTills'+d).innerHTML);
		val += Number(document.getElementById('dropAmount'+d).value);
		document.getElementById('depositAmount'+d).innerHTML=Math.round(val*100)/100;
		break;
	case '0.25':
		var count = Math.floor(Number(document.getElementById('dropAmount'+d).value) / 10);
		var val = Number(document.getElementById('dropAmount'+d).value) - (10*count);
		document.getElementById('depositAmount'+d).innerHTML=Math.round(val*100)/100;
		updateBuyAmount(d);
		break;
	case '0.10':
		var count = Math.floor(Number(document.getElementById('dropAmount'+d).value) / 5);
		var val = Number(document.getElementById('dropAmount'+d).value) - (5*count);
		document.getElementById('depositAmount'+d).innerHTML=Math.round(val*100)/100;
		updateBuyAmount(d);
		break;
	case '0.05':
		var count = Math.floor(Number(document.getElementById('dropAmount'+d).value) / 2);
		var val = Number(document.getElementById('dropAmount'+d).value) - (2*count);
		document.getElementById('depositAmount'+d).innerHTML=Math.round(val*100)/100;
		updateBuyAmount(d);
		break;
	case '0.01':
		var count = Math.floor(Number(document.getElementById('dropAmount'+d).value) / 0.50);
		var val = Number(document.getElementById('dropAmount'+d).value) - (0.50*count);
		document.getElementById('depositAmount'+d).innerHTML=Math.round(val*100)/100;
		updateBuyAmount(d);
		break;
	}

	resumRow('depositAmount');
}

function updateBuyAmount(d){
	if (d == 'Checks' || d == '100.00' || d == '50.00' || d == '20.00' || d == 'Junk')
		return;

	for(var i=0; i < denoms.length; i++){
		if (denoms[i] == 'Checks' || denoms[i] == '100.00' || denoms[i] == '50.00' || denoms[i] == '20.00' || denoms[i] == 'Junk')
			continue;

		var val = Number(document.getElementById('par'+denoms[i]).innerHTML);

		val -= Number(document.getElementById('cashInTills'+denoms[i]).innerHTML);
		if (denoms[i] == '1.00')
			val -= Number(document.getElementById('dropAmount'+denoms[i]).innerHTML);
		else
			val -= Number(document.getElementById('dropAmount'+denoms[i]).value);
		val += Number(document.getElementById('fill'+denoms[i]).innerHTML);
		val += Number(document.getElementById('depositAmount'+denoms[i]).innerHTML);

		if (val < 0) val = 0;
		if (denoms[i] == '1.00') val = Math.round(val);

		document.getElementById('buyAmount'+denoms[i]).innerHTML = Math.round(val*100)/100;
	}
	var overage = 0;

	var i = 0;
	var v = Number(document.getElementById('buyAmount10.00').innerHTML);
	while (v % 50 != 0 && i < 5){
		v = v - 10;
		overage = overage + 10;
		i = i+1;
	}
	document.getElementById('buyAmount10.00').innerHTML = v;

	var i = 0;
	var v = Number(document.getElementById('buyAmount5.00').innerHTML);
	while (v % 50 != 0 && i < 10){
		v = v - 5;
		overage = overage + 5;
		i = i+1;
	}
	document.getElementById('buyAmount5.00').innerHTML = v;

	var i = 0;
	var v = Number(document.getElementById('buyAmount1.00').innerHTML);
	while (v % 50 != 0 && i < 50){
		v = v - 1;
		overage = overage + 1;
		i = i+1;
	}
	document.getElementById('buyAmount1.00').innerHTML = v;

	var overs = denom_overage(overage);
	if (overs[0] != 0){
		var v = Number(document.getElementById('buyAmount0.25').innerHTML);
		document.getElementById('buyAmount0.25').innerHTML = v + overs[0];
	}
	if (overs[1] != 0){
		var v = Number(document.getElementById('buyAmount0.10').innerHTML);
		document.getElementById('buyAmount0.10').innerHTML = v + overs[1];
	}
	if (overs[2] != 0){
		var v = Number(document.getElementById('buyAmount0.05').innerHTML);
		document.getElementById('buyAmount0.05').innerHTML = v + overs[2];
	}
	if (overs[3] != 0){
		var v = Number(document.getElementById('buyAmount0.01').innerHTML);
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
