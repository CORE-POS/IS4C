function loadReceipt(){
	var d = $('#rdate').val();
	var t = $('#rtrans_num').val();
	if (d == '' || t == '') return;
	$.ajax({
		url: 'ReverseTransPage.php?date='+d+'&trans='+t,
		type: 'get',
		success: function(data){
			$('#contentarea').html(data);	
		}
	});
}

function doVoid(dateStr, trans_num){
	var dataStr = 'date='+dateStr+'&trans='+trans_num;
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
