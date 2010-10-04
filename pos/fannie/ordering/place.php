<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
$page_title='Fannie - Place Special Order';
$header='Place Special Order';
include('../src/header.html');

require_once('../src/mysql_connect.php');

$notifies = array(
	'4'=>'Grocery',
	'2'=>'Cool/Frozen',
	'1'=>'Bulk',
	'5'=>'HBC',
	'8'=>'Meat',
	'9'=>'Gen Merch',
	'3'=>'Deli',
	'6'=>'Produce',
	'0'=>'Unsure'
);

?>
<script type="text/javascript">
$(document).ready(function(){
	$('#memRadio').attr('checked',true);

	$('#memRadio').click(function(){
		$('#memDisp').show();
		$('#nonMemDisp').hide();
		$('#orderFS').hide();
		$('#memNum').val('');
		$('#memNum').focus();
	});

	$('#nonMemRadio').click(function(){
		$('#memDisp').hide();
		$('#nonMemDisp').show();
		$('#orderFS').hide();
		$('#fn').val('');
		$('#ln').focus();
	});

	$('#ln').autocomplete({
		source: 'ajax-place.php',
		change: function(event,ui){
			$.ajax({url: 'ajax-place.php',
				data: 'ln-select='+$('#ln').val(),
				type: 'post',
				success: function(data,stat,xmlro){
					$('#fn').html(data);
					nonMemFnChange();
				},	
				error: function(xmlro,stat,errThrown){
					alert(errThrown);
				}
			});
		}
	});

	$('#memNum').change(function(){
		$.ajax({url: 'ajax-place.php',
			data: 'memNum='+$('#memNum').val(),
			type: 'post',
			success: function(data,stat,xmlro){
				$('#orderFS').show();
				$('#orderDiv').html(data);
			},
			error: function(xmlro,stat,errThrown){
				alert(errThrown);
			}
		});
	});

	/*
	$('#ln').change(function(){
		alert('regular change');
		$.ajax({url: 'ajax-place.php',
			data: 'ln-select='+$('#ln').val(),
			type: 'post',
			success: function(data,stat,xmlro){
				$('#fn').html(data);
				nonMemFnChange();
			},	
			error: function(xmlro,stat,errThrown){
				alert(errThrown);
			}
		});
	
	});
	*/

	$('#fn').change(nonMemFnChange);

	$('#orderform').submit(function(){
		var ret = true;
		$('.required').each(function(i,elem){
			if ($.trim( $(this).val() ) == ''){
				alert($(this).attr('title')+' is required');
				//$(this).focus();
				ret = false;
				return false;
			}
		});
		return ret;
	});
});

function nonMemFnChange(){
	$.ajax({url: 'ajax-place.php',
		data: 'ln-form='+$('#ln').val()+'&uid='+$('#fn').val(),
		type: 'post',
		success: function(data,stat,xmlro){
			$('#orderFS').show();
			$('#orderDiv').html(data);
		},
		error: function(xmlro,stat,errThrown){
			alert(errThrown);
		}
	});

}
</script>

<fieldset>
<input type="radio" id="memRadio" name="def" /><label for="memRadio">Member</label>
<input type="radio" id="nonMemRadio" name="def" /><label for="nonMemRadio">Non-Member</label>
<hr />
<div id="memDisp">
<b>Member Number</b>: <input type=text size=5 id="memNum" />
</div>
<div id="nonMemDisp" style="display:none;">
<b>Last name</b>: <input type="text" size="10" id="ln" />
&nbsp;&nbsp;
<b>First name</b>: <select id="fn"><option value=NEW>New customer</option></select>
</div>
</fieldset>
<fieldset id="orderFS" style="display:none;"><legend>Order Info</legend>
<form id="orderform" action="submitorder.php" method="post">
<div id="orderDiv"></div>
<b>Item to order</b>:<br />
<textarea class="required" title="Item to order" rows=3 cols=50 name=itemdesc></textarea>
<hr />
<b>Special Instructions / Notes</b>:<br />
<textarea rows=3 cols=50 name=notes></textarea>
<hr />
<b>Department</b>: <select name=super>
<?php foreach($notifies as $k=>$v) echo "<option value=$k>$v</option>"; ?>
</select>
&nbsp;&nbsp;&nbsp;
<input type="submit" value="Place Order" />
</form>
</fieldset>

<?php
include('../src/footer.html');
?>
