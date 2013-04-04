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

require('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

if (isset($_REQUEST['lookup'])){
	$m = $_REQUEST['month'];
	$y = $_REQUEST['year'];
	if (!is_numeric($y)){
		echo "Error: Invalid year";
		exit;
	}
	elseif(!is_numeric($m)){
		echo "Error: Invalid month";
		exit;
	}

	$ret = "<br /><form action=\"report.php\" method=\"post\">";
	$ret .= sprintf("<input type=hidden name=month value=%d />
			<input type=hidden name=year value=%d />",
			$m,$y);
	$ret .= "<table cellspacing=0 cellpadding=4 border=1>";
	$ret .= "<tr><th>&nbsp;</th><th>Batch</th><th>Start</th><th>End</th></tr>";
	$q = $dbc->prepare_statement("SELECT batchID,batchName,startDate,endDate FROM
		batches WHERE discounttype <> 0 AND (
		(year(startDate)=? and month(startDate)=?) OR
		(year(endDate)=? and month(endDate)=?)
		) ORDER BY startDate,batchType,batchName");
	$r = $dbc->exec_statement($q,array($y,$m,$y,$m));
	while($w = $dbc->fetch_row($r)){
		$start = array_shift(explode(' ',$w[2]));
		$end = array_shift(explode(' ',$w[3]));
		$ret .= sprintf("<tr><td><input type=checkbox name=ids[] value=%d /></td>
				<td>%s</td><td>%s</td><td>%s</td>
				<input type=hidden name=bnames[] value=\"%s\" /></tr>",
				$w[0],$w[1],$start,$end,$w[1]." (".$start." ".$end.")");
	}
	$ret .= "</table><br />
		<input type=submit value=\"Get Report\" />
		</form>";
	echo $ret;
	exit;
}

$page_title = "Fannie : Sale Performance";
$header = "Sale Performance";
include($FANNIE_ROOT.'src/header.html');
?>
<script type="text/javascript">
function lookupSales(){
	var dstr = "lookup=yes&year=";
	dstr += $('#syear').val();
	dstr += "&month="+$('#smonth :selected').val();
	$.ajax({url: 'index.php',
		method: 'get',
		cache: false,
		data: dstr,
		success: function(data){
			$('#result').html(data);
		}
	});
}
</script>

<div id="#myform">
<select id="smonth">
<?php for ($i=1;$i<=12;$i++) printf("<option value=%d>%s</option>",$i,date("F",mktime(0,0,0,$i,1,2000)));
?>
</select>

<input type="text" size="4" id="syear" value="<?php echo date("Y"); ?>" />

<input type="submit" value="Lookup Sales" onclick="lookupSales();" />
</div>
<div id="result"></div>

<?php
include($FANNIE_ROOT.'src/footer.html');
?>
