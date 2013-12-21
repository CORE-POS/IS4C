var first = true;
function cscallback(){
	if (first){
		first = false;
		return false;
	}

	var c = $('#cost').val();
	var d = $('#department').val();
	var u = $('#upc').val();
	var d = 'action=margin&upc='+u+'&dept='+d+'&cost='+c;

	$.ajax({
		url: 'ajax.php',
		type: 'POST',
		dataType: 'text/html',
		timeout: 5000,
		data: d,
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			resp = '<legend>Margin</legend>'+resp;
			$('#marginfs').html(resp);
		}
	});
}

function updateLC(the_likecode){
	if (the_likecode == "-1"){
		$('#lchidden').hide();
		return false;
	}

	var d = 'action=likecode&lc='+the_likecode;
	$.ajax({
		url: 'ajax.php',
		type: 'POST',
		dataType: 'text/html',
		timeout: 5000,
		data: d,
		error: function(){
		alert('Error loading XML document');
		},
		success: function(resp){
			$('#lctable').html(resp);
			$('#lchidden').show();
		}
	});
}
