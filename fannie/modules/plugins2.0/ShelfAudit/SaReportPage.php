<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Based on example code from Wedge Community Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

/**
  @class SaScanningPage
*/
class SaReportPage extends FanniePage {

	protected $window_dressing = False;

	private $status = '';
	private $sql_actions = '';
	private $scans = array();

	function preprocess(){
		global $FANNIE_PLUGIN_SETTINGS,$FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ShelfAuditDB']);
		if (!is_object($dbc) || $dbc->connections[$FANNIE_PLUGIN_SETTINGS['ShelfAuditDB']] === False){
			$this->status = 'bad - cannot connect';
			return True;
		}
		if (FormLib::get_form_value('delete') == 'yes'){
			$query=$dbc->prepare_statement('delete from sa_inventory where id=?');
			$result=$dbc->exec_statement($query,array(FormLib::get_form_value('id')));
			if ($result) {
				$this->sql_actions='Deleted record.';
			} else {
				$this->sql_actions='Unable to delete record, please try again. <!-- '.$query.' -->';
			}
		} else if (FormLib::get_form_value('clear') == 'yes'){
			$query=$dbc->prepare_statement('update sa_inventory set clear=1;');
			$result=$dbc->exec_statement($query);
			if ($result) {
				$this->sql_actions='Cleared old scans.';
				header ("Location: SaReportPage.php");
				return False;
			} else {
				$this->sql_actions='Unable to clear old scans, try again. <!-- '.$query.' -->';
			}
		} else if ($_GET['change']=='yes') {
		}

		if (FormLib::get_form_value('view') == 'dept'){
			$order='d.dept_no,s.section,s.datetime';
		} else {
			$order='s.section,d.dept_no,s.datetime';
		}
	
		/* omitting wedge-specific temp tables Andy 29Mar2013
		$t=true;
		
		$q='START TRANSACTION';
		$r=mysql_query($q, $link);
		$t=&$r;
		
		$q='CREATE TEMPORARY TABLE `shelfaudit`.`tLastModified` (`upc` VARCHAR(13) NOT NULL, 
			`modified` DATETIME NOT NULL, KEY `upc_modified` (`upc`,`modified`)) 
			ENGINE = MYISAM';
		$r=mysql_query($q, $link);
		$t=&$r;
					
		$q='SELECT `upc`, `datetime` FROM `shelfaudit`.`hbc_inventory` WHERE CLEAR!=1';
		$r=mysql_query($q, $link);
		$t=&$r;
					
		$scans=array();
		
		while ($row=mysql_fetch_assoc($r)) {
			array_push($scans, array($row['upc'], $row['datetime']));
		}
			
		foreach ($scans as $scan) {
			$q='INSERT INTO `shelfaudit`.`tLastModified` 
				SELECT \''.$scan[0].'\', MAX(`modified`) 
				FROM `wedgepos`.`itemTableLog` WHERE `upc`=\''.$scan[0].'\'';
			$r=mysql_query($q, $link);
			$t=&$r;
		}
		*/
			
		$q= $dbc->prepare_statement('SELECT
			s.id,
			s.datetime,
			s.upc,
			s.quantity,
			s.section,
			p.description,
			d.dept_name,
			d.dept_no,

			CASE WHEN p.discounttype > 0 THEN p.special_price
			ELSE p.normal_price END AS retail,

			CASE WHEN p.discounttype = 2 THEN \'M\'
			ELSE \'\' END AS retailstatus

			FROM sa_inventory AS s LEFT JOIN '.
			$FANNIE_OP_DB.$dbc->sep().'products AS p
			ON s.upc=p.upc LEFT JOIN '.
			$FANNIE_OP_DB.$dbc->sep().'departments AS d
			ON p.department=d.dept_no
			WHERE clear!=1
			ORDER BY '.$order);
		$r=$dbc->exec_statement($q);
		if ($r) {
			$this->status = 'Good - Connected';
			$num_rows=$dbc->num_rows($r);
			if ($num_rows>0) {
				$this->scans=array();
				while($row = $dbc->fetch_row($r)){
					$this->scans[] = $row;
				}
			} else {
				$this->status = 'Good - No scans';
			}
		} else {
			$this->status = 'Bad - IT problem';
		}
		return True;
	}

	function css_content(){
		ob_start();
		?>
body {
 width: 768px;
 margin: auto;
 font-family: Helvetica, sans, Arial, sans-serif;
 background-color: #F9F9F9;
}

#bdiv {
	width: 768px;
	margin: auto;
	text-align: center;
}

body p,
body div {
 border: 1px solid #CfCfCf;
 background-color: #EFEFEF;
 line-height: 1.5;
 margin: 0px;
}

body table {
 font-size: small;
 text-align: center;
 border-collapse: collapse;
 width: 100%;
}

body table caption {
 font-family: sans-mono, Helvetica, sans, Arial, sans-serif;
 margin-top: 1em;
}

body table th {
 border-bottom: 2px solid #090909;
}

table tr:hover {
 background-color:#CFCFCF;
}

.right {
 text-align: right;
}
.small {
 font-size: smaller;
}
#col_a {
 width: 150px;
}
#col_b {
 width: 100px;
}
#col_c {
 width: 270px;
}
#col_d {
 width: 40px;
}
#col_e {
 width: 60px;
}
#col_f {
 width: 20px;
}
#col_g {
 width: 80px;
}
#col_h {
 width: 48px;
}
		<?php
		return ob_get_clean();
	}

	function body_content(){
		ob_start();
		?>
<html>
	<head>
	</head>
	<body>
		<div id="bdiv">
			<p><a href="#" onclick="window.open('SaScanningPage.php','scan','width=320, height=200, location=no, menubar=no, status=no, toolbar=no, scrollbars=no, resizable=no');">Enter a new scan</a></p>
			<p><?php echo($this->sql_actions); ?></p>
			<p><?php echo($this->status); ?></p>
			<p><a href="?view=dept">view by pos department</a> <a href="SaReportPage.php">view by scanned section</a></p>
		<?php
		if ($this->scans) {
			$clear = '<div><a href="SaReportPage.php?clear=yes">Clear Old</a></div>';
			print_r($clear);
		}
		
		$table = '';
		$view = FormLib::get_form_value('view','dept');
		$counter = ($view == 'dept') ? 'd' : 's';
		foreach($this->scans as $row) {
			
			if (!isset($counter_number)) {
				if ($counter=='d') { $counter_number=$row['dept_no']; }
				else { $counter_number=$row['section']; }
				
				$counter_total=$row['quantity']*$row['retail'];
				
				if ($counter=='d') { $caption=$row['dept_name'].' Department'; }
				else { $caption='Section #'.$row['section']; }
				
				$table .= '
		<table>
			<caption>'.$caption.'</caption>
			<thead>
				<tr>
					<th>Date+Time</th>
					<th>UPC</th>
					<th>Description</th>
					<th>Qty</th>
					<th>Each</th>
					<th>Sale</th>
					<th>Total</th>
					<th>Delete</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td id="col_a" class="small">'.$row['datetime'].'</td>
					<td id="col_b">'.$row['upc'].'</td>
					<td id="col_c">'.$row['item_desc'].'</td>
					<td id="col_d" class="right">'.$row['quantity'].'</td>
					<td id="col_e" class="right">'.money_format('%.2n', $row['retail']).'</td>
					<td id="col_f">'.(($row['retailstatus'])?$row['retailstatus']:'&nbsp;').'</td>
					<td id="col_g" class="right">'.money_format('%!.2n', ($row['quantity']*$row['retail'])).'</td>
					<td id="col_h"><a href="SaReportPage.php?delete=yes&id='.$row['id'].'"><img src="../../../images/cancel.png" border="0"/></a></td>
				</tr>';
			} else if ($counter_number!=$row['section'] && $counter_number!=$row['dept_no']) {
				if ($counter=='d') { $counter_number=$row['dept_no']; }
				else { $counter_number=$row['section']; }
				
				if ($counter=='d') { $caption=$row['dept_name'].' Department'; }
				else { $caption='Section #'.$row['section']; }
								
				$table .= '
			</tbody>
			<tfoot>
				<tr>
					<td colspan=6>&nbsp;</td>
					<td class="right">'.money_format('%.2n', $counter_total).'</td>
					<td>&nbsp;</td>
				</tr>
			</tfoot>
		</table>
		<table>
			<caption>'.$caption.'</caption>
			<thead>
				<tr>
					<th>Date+Time</th>
					<th>UPC</th>
					<th>Description</th>
					<th>Qty</th>
					<th>Each</th>
					<th>Sale</th>
					<th>Total</th>
					<th>Delete</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td id="col_a" class="small">'.$row['datetime'].'</td>
					<td id="col_b">'.$row['upc'].'</td>
					<td id="col_c">'.$row['item_desc'].'</td>
					<td id="col_d" class="right">'.$row['quantity'].'</td>
					<td id="col_e" class="right">'.money_format('%.2n', $row['retail']).'</td>
					<td id="col_f">'.(($row['retailstatus'])?$row['retailstatus']:'&nbsp;').'</td>
					<td id="col_g" class="right">'.money_format('%!.2n', ($row['quantity']*$row['retail'])).'</td>
					<td id="col_h"><a href="SaReportPage.php?delete=yes&id='.$row['id'].'"><img src="../../../images/cancel.png" border="0"/></a></td>
				</tr>';
				
				$counter_total=$row['quantity']*$row['retail'];
			} else {
				$counter_total+=$row['quantity']*$row['retail'];
				
				$table .= '
				<tr>
					<td id="col_a" class="small">'.$row['datetime'].'</td>
					<td id="col_b">'.$row['upc'].'</td>
					<td id="col_c">'.$row['item_desc'].'</td>
					<td id="col_d" class="right">'.$row['quantity'].'</td>
					<td id="col_e" class="right">'.money_format('%.2n', $row['retail']).'</td>
					<td id="col_f">'.(($row['retailstatus'])?$row['retailstatus']:'&nbsp;').'</td>
					<td id="col_g" class="right">'.money_format('%!.2n', ($row['quantity']*$row['retail'])).'</td>
					<td id="col_h"><a href="SaReportPage.php?delete=yes&id='.$row['id'].'"><img src="../../../images/cancel.png" border="0"/></a></td>
				</tr>';
			}
		}
	
		$table .= '
			</tbody>
			<tfoot>
				<tr>
					<td colspan=6>&nbsp;</td>
					<td class="right">'.money_format('%.2n', $counter_total).'</td>
					<td>&nbsp;</td>
				</tr>
			</tfoot>
		</table>
';
		if (!empty($table))
			print_r($table);
		?>
		</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new SaReportPage();
	$obj->draw_page();
}
