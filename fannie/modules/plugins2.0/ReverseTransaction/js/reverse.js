function loadReceipt(){
	var d = $('#rdate').val();
	var t = $('#rtrans_num').val();
    var s = $('select[name=store]').val();
	if (d == '' || t == '' || s == '') return;
	$.ajax({
		url: 'ReverseTransPage.php?date='+d+'&trans='+t+'&store='+s,
		type: 'get',
		success: function(data){
			$('#contentarea').html(data);	
		}
	});
}

function doVoid(dateStr, trans_num, store){
	var dataStr = 'date='+dateStr+'&trans='+trans_num+'&store='+store;
	$.ajax({
		url: 'ReverseTransPage.php',
		type: 'post',
		data: dataStr,
		success: function(resp){
			alert('Reversed!');
			$('#contentarea').html(resp);
		}
	});
}
