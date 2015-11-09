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
var busy = false;

/* send action to this page 
   tack on more arguments as needed with '&' and '='
*/
function phpSend(action) {
    if (!busy){
	http.open('get', 'journal.php?action='+action);
	http.onreadystatechange = handleResponse;
	busy = true;
	http.send(null);
    }
    else
	setTimeout("phpSend('"+action+"')",50);
}

/* ajax callback function 
   by convention, results return [actionname]`[data]
   splitting on backtick separates, then switch on action name
   allows different actions to be handled differently
*/
function handleResponse() {
    if(http.readyState == 4){
	busy = false;
        var response = http.responseText;
        var array = response.split('`');
	switch(array[0]){
	case 'initDate':
	case 'reInit':
		document.getElementById('contentarea').innerHTML = array[1];
		resumSheet();
		break;
	case 'save':
		if (array[1] != '') alert(array[1]);
		break;
	default:
		alert(response);
	}
    }
}

function initDate(){
	var date = document.getElementById('init_date').value;
	document.getElementById('currentDate').value = date;
	document.getElementById('contentarea').innerHTML = "&nbsp;";
	phpSend("initDate&date="+date);
}

function resumSheet(){
	resumTenders();
	resumSales();
	resumOtherIncome();
	resumMisc();
	resumTotals();
}

function resumTenders(){
	var cash = Number(document.getElementById('inputCash').value);
	var check = Number(document.getElementById('inputCheck').value);
	var depTotal = Math.round((cash+check)*100) / 100;
	document.getElementById('depositTotal').innerHTML = depTotal;
	document.getElementById('jDepositTotal').innerHTML = depTotal;

	var ebt = Number(document.getElementById('inputEBT').value);
	var mc = Number(document.getElementById('inputCCMC').value);
	var visa = Number(document.getElementById('inputCCVisa').value);
	var disc1 = Number(document.getElementById('inputCCDisc1').value);
	var disc2 = Number(document.getElementById('inputCCDisc2').value);
	//document.getElementById('jCCMain').innerHTML = Math.round((ebt+mc+visa)*100) / 100;
	//document.getElementById('jCCDisc1').innerHTML = disc1;
	//document.getElementById('jCCDisc2').innerHTML = disc2;
	document.getElementById('totalFAPs').innerHTML = Math.round((mc+visa+disc1+disc2)*100) / 100;
	document.getElementById('totalCCEBT').innerHTML = Math.round((mc+visa+disc1+disc2+ebt)*100) / 100;
	document.getElementById('jTotalCCEBT').innerHTML = Math.round((mc+visa+disc1+disc2+ebt)*100) / 100;

	var rrr = Number(document.getElementById('inputRRR').value);
	var coupons = Number(document.getElementById('inputCoupons').value);
	var gc = Number(document.getElementById('inputGC').value);
	var tc = Number(document.getElementById('inputTC').value);
	var storecharge = Number(document.getElementById('inputStoreCharge').value);
	var instorecoup = Number(document.getElementById('inputInStoreCoup').value);
	document.getElementById('jRRR').innerHTML = rrr;
	document.getElementById('jCoupons').innerHTML = coupons;
	document.getElementById('jGC').innerHTML = gc;
	document.getElementById('jTC').innerHTML = tc;
	document.getElementById('jStoreCharge').innerHTML = storecharge;
	document.getElementById('jInStoreCoup').innerHTML = instorecoup;

	var tenderTotal = Math.round((cash+check+ebt+visa+mc+disc1+disc2+rrr+coupons+gc+tc+storecharge+instorecoup)*100) / 100;
	document.getElementById('tenderTotal').innerHTML = tenderTotal;	
}

function resumSales(){
	var pcodes = new Array(41201,41205,41300,41305,41310,41315,41400,41405,41407,41410,41415,41420,
				41425,41430,41435,41440,41500,41505,41510,41515,41520,41525,41530,
				41600,41605,41610,41640,41645,41700,41705);
	var pCodeTotal = 0;
	for (var i=0; i<pcodes.length; i++){
		var sales = Number(document.getElementById('inputPcode'+pcodes[i]).value);
		if (sales < 0){
			document.getElementById('jDebit'+pcodes[i]).innerHTML = -1*sales;
			document.getElementById('jCredit'+pcodes[i]).innerHTML = "&nbsp;";
		}
		else{
			document.getElementById('jDebit'+pcodes[i]).innerHTML = "&nbsp;";
			document.getElementById('jCredit'+pcodes[i]).innerHTML = sales;
		}
		pCodeTotal += sales;
	}
	
	document.getElementById('totalPcode').innerHTML = Math.round(pCodeTotal*100)/100;
	var totalPOS = Number(document.getElementById('totalPOS').innerHTML);
	document.getElementById('salesDiff').innerHTML = Math.round((totalPOS-pCodeTotal)*100) / 100;
}

function resumOtherIncome(){
	var gcSales = Number(document.getElementById('inputGCSales').value);
	if (gcSales < 0){
		document.getElementById('jDebitGCSales').innerHTML = gcSales*-1;
		document.getElementById('jCreditGCSales').innerHTML = "&nbsp;"; 
	}
	else{
		document.getElementById('jDebitGCSales').innerHTML = "&nbsp;"; 
		document.getElementById('jCreditGCSales').innerHTML = gcSales;
	}

	var tcSales = Number(document.getElementById('inputTCSales').value);
	if (tcSales < 0){
		document.getElementById('jDebitTCSales').innerHTML = tcSales*-1;
		document.getElementById('jCreditTCSales').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitTCSales').innerHTML = "&nbsp;";
		document.getElementById('jCreditTCSales').innerHTML = tcSales;
	}

	var miscPO = Number(document.getElementById('inputMiscPO').value);
	if (miscPO < 0){
		document.getElementById('jDebitMiscPO').innerHTML = miscPO*-1;
		document.getElementById('jCreditMiscPO').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitMiscPO').innerHTML = "&nbsp;";
		document.getElementById('jCreditMiscPO').innerHTML = miscPO;
	}

	var classA = Number(document.getElementById('inputClassA').value);
	if (classA < 0){
		document.getElementById('jDebitClassA').innerHTML = classA*-1;
		document.getElementById('jCreditClassA').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitClassA').innerHTML = "&nbsp;";
		document.getElementById('jCreditClassA').innerHTML = classA;
	}
	
	var classB = Number(document.getElementById('inputClassB').value);
	if (classB < 0){
		document.getElementById('jDebitClassB').innerHTML = classB*-1;
		document.getElementById('jCreditClassB').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitClassB').innerHTML = "&nbsp;";
		document.getElementById('jCreditClassB').innerHTML = classB;
	}

	document.getElementById('totalEquity').innerHTML = Math.round((classA+classB)*100) / 100;

	var ar = Number(document.getElementById('inputAR').value);
	if (ar < 0){
		document.getElementById('jDebitAR').innerHTML = ar*-1;
		document.getElementById('jCreditAR').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitAR').innerHTML = "&nbsp;";
		document.getElementById('jCreditAR').innerHTML = ar;
	}

	var discMem = Number(document.getElementById('inputMemDisc').value);
	if (discMem < 0){
		document.getElementById('jCreditDiscMem').innerHTML = discMem*-1;
		document.getElementById('jDebitDiscMem').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jCreditDiscMem').innerHTML = "&nbsp;";
		document.getElementById('jDebitDiscMem').innerHTML = discMem;
	}
	
	var discStaffMem = Number(document.getElementById('inputStaffMemDisc').value);
	if (discStaffMem < 0){
		document.getElementById('jCreditDiscStaffMem').innerHTML = discStaffMem*-1;
		document.getElementById('jDebitDiscStaffMem').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitDiscStaffMem').innerHTML = discStaffMem;
		document.getElementById('jCreditDiscStaffMem').innerHTML = "&nbsp;";
	}

	var discStaffNonMem = Number(document.getElementById('inputStaffNonMemDisc').value);
	if (discStaffNonMem < 0){
		document.getElementById('jCreditDiscStaffNonMem').innerHTML = discStaffNonMem*-1;
		document.getElementById('jDebitDiscStaffNonMem').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitDiscStaffNonMem').innerHTML = discStaffNonMem;
		document.getElementById('jCreditDiscStaffNonMem').innerHTML = "&nbsp;";
	}

	var mad = Number(document.getElementById('inputMAD').value);
	if (mad < 0){
		document.getElementById('jCreditMAD').innerHTML = mad*-1;
		document.getElementById('jDebitMAD').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitMAD').innerHTML = mad;
		document.getElementById('jCreditMAD').innerHTML = "&nbsp;";
	}

	document.getElementById('totalDisc').innerHTML = Math.round((discMem+discStaffMem+discStaffNonMem+mad)*100)/100;

	var tax = Number(document.getElementById('inputTax').value);
	if (tax < 0){
		document.getElementById('jDebitTax').innerHTML = tax*-1;
		document.getElementById('jCreditTax').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitTax').innerHTML = "&nbsp;";
		document.getElementById('jCreditTax').innerHTML = tax;
	}

	var it = Number(document.getElementById('inputITCorrections').value);
	if (it < 0){
		document.getElementById('jDebitITCorrections').innerHTML = it*-1;
		document.getElementById('jCreditITCorrections').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitITCorrections').innerHTML = "&nbsp;";
		document.getElementById('jCreditITCorrections').innerHTML = it;
	}

	var misc1 = Number(document.getElementById('inputMisc1').value);
	if (misc1 < 0){
		document.getElementById('jDebitMisc1').innerHTML = misc1*-1;
		document.getElementById('jCreditMisc1').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitMisc1').innerHTML = "&nbsp;";
		document.getElementById('jCreditMisc1').innerHTML = misc1;
	}

	var misc2 = Number(document.getElementById('inputMisc2').value);
	if (misc2 < 0){
		document.getElementById('jDebitMisc2').innerHTML = misc2*-1;
		document.getElementById('jCreditMisc2').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitMisc2').innerHTML = "&nbsp;";
		document.getElementById('jCreditMisc2').innerHTML = misc2;
	}

	var supplies = Number(document.getElementById('inputSupplies').value);
	if (supplies < 0){
		document.getElementById('jDebitSupplies').innerHTML = supplies*-1;
		document.getElementById('jCreditSupplies').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitSupplies').innerHTML = "&nbsp;";
		document.getElementById('jCreditSupplies').innerHTML = supplies;
	}

	var classIn = Number(document.getElementById('inputClass').value);
	if (classIn < 0){
		document.getElementById('jDebitClass').innerHTML = classIn*-1;
		document.getElementById('jCreditClass').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jCreditClass').innerHTML = classIn;
		document.getElementById('jDebitClass').innerHTML = "&nbsp;";
	}

	var found = Number(document.getElementById('inputFound').value);
	if (found < 0){
		document.getElementById('jDebitFound').innerHTML = found*-1;
		document.getElementById('jCreditFound').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jCreditFound').innerHTML = found;
		document.getElementById('jDebitFound').innerHTML = "&nbsp;";
	}

	var totes = Number(document.getElementById('inputTotes').value);
	if (totes < 0){
		document.getElementById('jDebitTotes').innerHTML = totes*-1;
		document.getElementById('jCreditTotes').innerHTML = "&nbsp;";
	}
	else{
		document.getElementById('jDebitTotes').innerHTML = "&nbsp;";
		document.getElementById('jCreditTotes').innerHTML = totes;
	}
}

function resumTotals(){
	var debitTotal = 0;
	var debits = document.getElementsByName('jDebit');
	for (var i = 0; i < debits.length; i++){
		var val = Number(debits.item(i).innerHTML);
		if (!isNaN(val))
			debitTotal += val;
	}
	document.getElementById('sheetSubDebit').innerHTML = Math.round(debitTotal*100)/100;

	var creditTotal = 0;
	var credits = document.getElementsByName('jCredit');
	for (var i = 0; i < credits.length; i++){
		var val = Number(credits.item(i).innerHTML);
		if (!isNaN(val))
			creditTotal += val;
	}
	document.getElementById('sheetSubCredit').innerHTML = Math.round(creditTotal*100)/100;

	var overshort = Math.round((debitTotal - creditTotal)*100) / 100;
	document.getElementById('overshort').innerHTML = overshort;

	if (overshort < 0){
		debitTotal += (-1*overshort);
		document.getElementById('debitOvershort').innerHTML = -1*overshort;
		document.getElementById('creditOvershort').innerHTML = '&nbsp;';
	}
	else{
		creditTotal += overshort;
		document.getElementById('creditOvershort').innerHTML = overshort;
		document.getElementById('debitOvershort').innerHTML = '&nbsp;';
	}
	var sheetDiff = Math.round((debitTotal-creditTotal)*100) / 100;

	document.getElementById('sheetDebit').innerHTML = Math.round(debitTotal*100)/100;
	document.getElementById('sheetCredit').innerHTML = Math.round(creditTotal*100)/100;
	document.getElementById('sheetDiff').innerHTML = sheetDiff;
}

function save(){
	var date = document.getElementById('currentDate').value;
	saveTenders(date);
	saveSales(date);
	saveOther(date);
	saveDiscount(date);
	saveMisc(date);
	saveTax(date);
}

function saveTenders(date){
	var cash = document.getElementById('inputCash').value;
	var check = document.getElementById('inputCheck').value;
	var ebt = document.getElementById('inputEBT').value;
	var mc = document.getElementById('inputCCMC').value;
	var visa = document.getElementById('inputCCVisa').value;
	var disc1 = document.getElementById('inputCCDisc1').value;
	var disc2 = document.getElementById('inputCCDisc2').value;
	var mad = document.getElementById('inputMAD').value;
	var rrr = document.getElementById('inputRRR').value;
	var coupons = document.getElementById('inputCoupons').value;
	var gc = document.getElementById('inputGC').value;
	var tc = document.getElementById('inputTC').value;
	var storecharge = document.getElementById('inputStoreCharge').value;
	var instorecoup = document.getElementById('inputInStoreCoup').value;

	var sendStr = "Cash"+":"+cash+";";
	sendStr += "Check"+":"+check+";";
	sendStr += "EBT"+":"+ebt+";";
	sendStr += "MC"+":"+mc+";";
	sendStr += "Visa"+":"+visa+";";
	sendStr += "Discover1"+":"+disc1+";";
	sendStr += "Discover2"+":"+disc2+";";
	sendStr += "MAD Coupon"+":"+mad+";";
	sendStr += "RRR Coupon"+":"+rrr+";";
	sendStr += "Coupons"+":"+coupons+";";
	sendStr += "Gift Card"+":"+gc+";";
	sendStr += "GIFT CERT"+":"+tc+";";
	sendStr += "InStore Charges"+":"+storecharge+";";
	sendStr += "InStoreCoupon"+":"+instorecoup;

	phpSend('save&date='+date+'&type=T&data='+sendStr);
}

function saveSales(date){
	var pcodes = new Array(41201,41205,41300,41305,41310,41315,41400,41405,41407,41410,41415,41420,
				41425,41430,41435,41440,41500,41505,41510,41515,41520,41525,41530,
				41600,41605,41610,41640,41645,41700,41705);
	var sendStr = "";
	for (var i = 0; i < pcodes.length; i++){
		var val = document.getElementById('inputPcode'+pcodes[i]).value;
		sendStr += pcodes[i]+":"+val;
		if (i != pcodes.length - 1) sendStr += ";";
	}

	phpSend('save&date='+date+'&type=P&data='+sendStr);
}

function saveOther(date){
	var gc = document.getElementById('inputGCSales').value;
	var tc = document.getElementById('inputTCSales').value;
	var miscPO = document.getElementById('inputMiscPO').value;
	var classA = document.getElementById('inputClassA').value;
	var classB = document.getElementById('inputClassB').value;
	var ar = document.getElementById('inputAR').value;
	var it = document.getElementById('inputITCorrections').value;
	var misc1 = document.getElementById('inputMisc1').value;
	var misc2 = document.getElementById('inputMisc2').value;
	var supplies = document.getElementById('inputSupplies').value;
	var classIn = document.getElementById('inputClass').value;
	var found = document.getElementById('inputFound').value;
	var totes = document.getElementById('inputTotes').value;

	var sendStr = "902"+":"+gc+";";
	sendStr += "900"+":"+tc+";";
	sendStr += "604"+":"+miscPO+";";
	sendStr += "992"+":"+classA+";";
	sendStr += "991"+":"+classB+";";
	sendStr += "990"+":"+ar+";";
	sendStr += "800"+":"+it+";";
	sendStr += "801"+":"+misc1+";";
	sendStr += "802"+":"+misc2+";";
	sendStr += "600"+":"+supplies+";";
	sendStr += "708"+":"+classIn+";";
	sendStr += "700"+":"+totes+";";
	sendStr += "FOUND"+":"+found;

	phpSend('save&date='+date+'&type=O&data='+sendStr);
}

function saveDiscount(date){
	var mem = document.getElementById('inputMemDisc').value;
	var staffMem = document.getElementById('inputStaffMemDisc').value;
	var staffNonMem = document.getElementById('inputStaffNonMemDisc').value;

	var sendStr = "Member"+":"+mem+";";
	sendStr += "Staff Member"+":"+staffMem+";";
	sendStr += "Staff NonMem"+":"+staffNonMem;

	phpSend('save&date='+date+'&type=D&data='+sendStr);
}

function saveTax(date){
	var tax = document.getElementById('inputTax').value;
	var sendStr = "tax"+":"+tax;
	phpSend('save&date='+date+'&type=X&data='+sendStr);
}

function reInit(){
	if (confirm("Are you sure you want to reload all inputs from the POS?")){
		var date = document.getElementById('currentDate').value;
		document.getElementById('contentarea').innerHTML = '';
		phpSend('reInit&date='+date);	
	}	
}

function csv(){
	alert("CSV save not enabled. Format not finalized");
}

function addMisc(){
	var num = document.getElementById('miscCount').value;
	var content = "<td>MiscReceipt</td><td><input onchange=\"resumMisc();resumTotals();\" type=text size=8 id=inputMisc"+num+" /></td>";
	content += "<td name=jDebit id=jDebitMisc"+num+" align=right>&nbsp;</td>";
	content += "<td name=jCredit id=jCreditMisc"+num+" align=right>&nbsp;</td>";
	content += "<td align=right><input type=text id=accountMisc"+num+" size=8 /></td>";

	num = Number(num);	
	
	var table = document.getElementById('thetable');
	var row = table.insertRow(87+num);
	row.innerHTML = content;
	document.getElementById('miscCount').value = num+1;
}

function saveMisc(date){
	var total = document.getElementById('miscCount').value;
	var sendStr = "";
	for (var i = 0; i < total; i++){
		var val = document.getElementById('inputMisc'+i).value;
		if (val != ''){
			var account = document.getElementById('accountMisc'+i).value;
			sendStr += account+":"+val+";";
		}	
	}
	sendStr = sendStr.substring(0,sendStr.length-1);
	phpSend('save&date='+date+'&type=M&data='+sendStr);
}

function resumMisc(){
	var total = document.getElementById('miscCount').value;
	for (var i = 0; i < total; i++){
		var val = Number(document.getElementById('inputMisc'+i).value);
		if (val < 0){
			document.getElementById('jDebitMisc'+i).innerHTML = -1*val;
			document.getelementById('jCreditMisc'+i).innerHTML = "&nbsp;";
		}
		else{
			document.getElementById('jDebitMisc'+i).innerHTML = "&nbsp;";
			document.getElementById('jCreditMisc'+i).innerHTML = val;
		}
	}
}
