var first = true;
function cscallback(){
	if (first){
		first = false;
		return false;
	}

	var cost = $('#cost').val();
	var dept = $('#department').val();
	var upc = $('#upc').val();
	var d = 'action=margin&upc='+upc+'&dept='+dept+'&cost='+cost;

	$.ajax({
		url: 'ajax.php',
		data: d
    }).fail(function(){
        window.alert('Error loading XML document');
    }).done(function(resp){
        resp = '<legend>Margin</legend>'+resp;
        $('#marginfs').html(resp);
	});
}

function updateLC(the_likecode){
	if (the_likecode === "-1"){
		$('#lchidden').hide();
		return false;
	}

	var d = 'action=likecode&lc='+the_likecode;
	$.ajax({
		url: 'ajax.php',
		data: d
    }).fail(function(){
		window.alert('Error loading XML document');
    }).done(function(resp){
        $('#lctable').html(resp);
        $('#lchidden').show();
	});
}
