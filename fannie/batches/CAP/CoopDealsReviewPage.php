<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

include("../../config.php");
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class CoopDealsReviewPage extends FanniePage {
	protected $title = "Fannie - CAP sales";
	protected $header = "Review Data";
	
	private $mode = 'form';

	function preprocess(){
		if (FormLib::get_form_value('start') !== '')
			$this->mode = 'results';
		return True;
	}

	function body_content(){
		if ($this->mode == 'form')
			return $this->form_content();
		elseif($this->mode == 'results')
			return $this->results_content();
	}

	function results_content(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$start = FormLib::get_form_value('start',date('Y-m-d'));
		$end = FormLib::get_form_value('end',date('Y-m-d'));
		$b_start = FormLib::get_form_value('bstart',date('Y-m-d'));
		$b_end = FormLib::get_form_value('bend',date('Y-m-d'));
		$naming = FormLib::get_form_value('naming','');
		$upcs = FormLib::get_form_value('upc',array());
		$prices = FormLib::get_form_value('price',array());
		$names = FormLib::get_form_value('batch',array());
		$batchIDs = array();

		$batchP = $dbc->prepare_statement('INSERT INTO batches (batchName, batchType,
				discountType, priority, startDate, endDate) VALUES 
				(?, ?, ?, 0, ?, ?)');
		$listP = $dbc->prepare_statement('INSERT INTO batchList (upc, batchID, salePrice, active)
				VALUES (?, ?, ?, 0)');
		$list = new BatchListModel($dbc);
		$list->active(0);
		$list->pricemethod(0);
		$list->quantity(0);

		for($i=0;$i<count($upcs);$i++){
			if(!isset($batchIDs[$names[$i]])){
				$args = array($names[$i].' '.$naming,1,1);
				if (substr($names[$i],-2) == " A"){
					$args[] = $start;
					$args[] = $end;
				}
				elseif (substr($names[$i],-2) == " B"){
					$args[] = $b_start;
					$args[] = $b_end;
				}
				else{
					$args[] = $start;
					$args[] = $b_end;
				}
	
				$dbc->exec_statement($batchP,$args);
				$bID = $dbc->insert_id();
				$batchIDs[$names[$i]] = $bID;
			}
			$id = $batchIDs[$names[$i]];

			$list->upc($upcs[$i]);
			$list->batchID($id);
			$list->salePrice(sprintf("%.2f",$prices[$i]));
			$list->save();
		}

		$ret = "New sales batches have been created!<p />";
		$ret .= "<a href=\"../newbatch/\">View batches</a>";	
		return $ret;
	}

	
	function form_content(){
		global $FANNIE_OP_DB, $FANNIE_URL;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$query = $dbc->prepare_statement("SELECT t.upc,p.description,t.price,
			CASE WHEN s.super_name IS NULL THEN 'sale' ELSE s.super_name END as batch,
			t.abtpr as subbatch
			FROM tempCapPrices as t
			INNER JOIN products AS p
			on t.upc = p.upc LEFT JOIN
			MasterSuperDepts AS s
			ON p.department=s.dept_ID
			ORDER BY s.super_name,t.upc");
		$result = $dbc->exec_statement($query);

		$ret = "<form action=CoopDealsReviewPage.php method=post>
		<table cellpadding=4 cellspacing=0 border=1>
		<tr><th>UPC</th><th>Desc</th><th>Sale Price</th><th>Batch</th></tr>";
		while($row = $dbc->fetch_row($result)){
			$ret .= sprintf("<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%s Co-op Deals %s</tr>",
				$row[0],$row[1],$row[2],$row[3],$row[4]);
			$ret .= sprintf("<input type=hidden name=upc[] value=\"%s\" />
				<input type=hidden name=price[] value=\"%s\" />
				<input type=hidden name=batch[] value=\"%s Co-op Deals %s\" />",
				$row[0],$row[2],$row[3],$row[4]);
		}
		$ret .= "</table><p />
		<table cellpadding=4 cellspacing=0><tr>
		<td><b>A Start</b></td><td><input type=text name=start onclick=\"showCalendarControl(this);\" /></td>
		</tr><tr>
		<td><b>A End</b></td><td><input type=text name=end onclick=\"showCalendarControl(this);\" /></td>
		</tr><tr>
		<td><b>B Start</b></td><td><input type=text name=bstart onclick=\"showCalendarControl(this);\" /></td>
		</tr><tr>
		<td><b>B End</b></td><td><input type=text name=bend onclick=\"showCalendarControl(this);\" /></td>
		</tr><tr>
		<td><b>Month</b></td><td><input type=text name=naming /></td>
		</tr></table>
		<input type=submit value=\"Create Batch(es)\" />
		</form>";

		$this->add_script($FANNIE_URL.'src/CalendarControl.js');

		return $ret;
	}
}

FannieDispatch::conditionalExec(false);

