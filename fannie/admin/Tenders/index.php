<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'auth/login.php');
include('ajax.php');

if (!validateUserQuiet('tenders')){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}admin/Tenders/");
	exit;
}

$page_title = "Fannie : Tenders";
$header = "Tenders";
include($FANNIE_ROOT.'src/header.html');

?>
<script type="text/javascript">
function saveCode(val,t_id){
	$.ajax({url:'ajax.php',
		cache:false,
		dataType:'post',
		data: 'saveCode='+val+'&id='+t_id,
		success: function(data){
			if (data != "")
				alert(data);
		}	
	});
}
function saveName(val,t_id){
	$.ajax({url:'ajax.php',
		cache:false,
		dataType:'post',
		data: 'saveName='+val+'&id='+t_id,
		success: function(data){
			if (data != "")
				alert(data);
		}	
	});
}
function saveType(val,t_id){
	$.ajax({url:'ajax.php',
		cache:false,
		dataType:'post',
		data: 'saveType='+val+'&id='+t_id,
		success: function(data){
			if (data != "")
				alert(data);
		}	
	});
}
function saveCMsg(val,t_id){
	$.ajax({url:'ajax.php',
		cache:false,
		dataType:'post',
		data: 'saveCMsg='+val+'&id='+t_id,
		success: function(data){
			if (data != "")
				alert(data);
		}	
	});
}
function saveMin(val,t_id){
	$.ajax({url:'ajax.php',
		cache:false,
		dataType:'post',
		data: 'saveMin='+val+'&id='+t_id,
		success: function(data){
			if (data != "")
				alert(data);
		}	
	});
}
function saveMax(val,t_id){
	$.ajax({url:'ajax.php',
		cache:false,
		dataType:'post',
		data: 'saveMax='+val+'&id='+t_id,
		success: function(data){
			if (data != "")
				alert(data);
		}	
	});
}
function saveRLimit(val,t_id){
	$.ajax({url:'ajax.php',
		cache:false,
		dataType:'post',
		data: 'saveRLimit='+val+'&id='+t_id,
		success: function(data){
			if (data != "")
				alert(data);
		}	
	});
}
function addTender(){
	$.ajax({url:'ajax.php',
		cache: false,
		dataType:'post',
		data:'newTender=yes',
		success: function(data){
			$('#mainDisplay').html(data);
		}
	});
}
</script>
<?php

echo '<div id="mainDisplay">';
echo getTenderTable();
echo '</div>';

include($FANNIE_ROOT.'src/footer.html');
?>
