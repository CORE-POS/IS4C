<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * Show total sales by hour for today from dlog.
 * Offer dropdown of superdepartments and, on-select, display the same report for
 *  that superdept only.
 * This page extends FanniePage because it is simpler than most reports
 *  and would be encumbered by the FannieReportPage structure.
*/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class SalesTodayReport extends FanniePage {

	protected $selected;
	protected $name = "";
	protected $supers;

	function preprocess(){
		global $dbc;
		$this->selected = (isset($_GET['super']))?$_GET['super']:-1;

		/* Populate an array of superdepartments from which to
		 *  select for filtering this report in the next run
		 *  and if a superdepartment was chosen for this run
		 *  get its name.
		*/
		$superP = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts ORDER BY super_name");
		$superR = $dbc->exec_statement($superP);
		$this->supers = array();
		$this->supers[-1] = "All";
		while($row = $dbc->fetch_row($superR)){
			$this->supers[$row[0]] = $row[1];
			if ($this->selected == $row[0])
				$this->name = $row[1];
		}

		$this->title = "Fannie : Today's $this->name Sales";
		$this->header = "Today's $this->name Sales";

		$this->has_menus(True);

		return True;

	// preprocess()
	}

	function body_content(){
		global $dbc, $FANNIE_TRANS_DB;

		$today = date("Y-m-d");

		$query1="SELECT ".$dbc->hour('tdate').", 
				sum(total)as Sales
			FROM ".$FANNIE_TRANS_DB.$dbc->sep()."dlog AS d left join MasterSuperDepts AS t
				ON d.department = t.dept_ID
			WHERE ".$dbc->datediff('tdate',$dbc->now())."=0
				AND (trans_type ='I' OR trans_type = 'D' or trans_type='M')
				AND (t.superID > 0 or t.superID IS NULL)
			GROUP BY ".$dbc->hour('tdate')."
			ORDER BY ".$dbc->hour('tdate');
		$args = array();
		if ($this->selected != -1){
			$query1="SELECT ".$dbc->hour('tdate').", 
					sum(total)as Sales,
					sum(case when t.superID=? then total else 0 end) as prodSales
				FROM ".$FANNIE_TRANS_DB.$dbc->sep()."dlog AS d left join MasterSuperDepts AS t
					ON d.department = t.dept_ID
				WHERE ".$dbc->datediff('tdate',$dbc->now())."=0
					AND (trans_type ='I' OR trans_type = 'D' or trans_type='M')
					AND t.superID > 0
				GROUP BY ".$dbc->hour('tdate')."
				ORDER BY ".$dbc->hour('tdate');
			$args = array($this->selected);
		}

		$prep = $dbc->prepare_statement($query1);
		$result = $dbc->exec_statement($query1,$args);

		echo "<div align=\"center\"><h1>Today's <span style=\"color:green;\">$this->name</span> Sales!</h1>";
		echo "<table cellpadding=4 cellspacing=2 border=0>";
		echo "<tr><td><b>Hour</b></td><td><b>Sales</b></td></tr>";
		$sum = 0;
		$sum2 = 0;
		while($row=$dbc->fetch_row($result)){
			printf("<tr><td>%d</td><td style='text-align:right;'>%.2f</td><td style='%s'>%.2f%%</td></tr>",
				$row[0],
				($this->selected==-1)?$row[1]:$row[2],
				($this->selected==-1)?'display:none;':'text-align:right;',	
				($this->selected==-1)?0.00:$row[2]/$row[1]*100);
			$sum += $row[1];
			if($this->selected != -1) $sum2 += $row[2];
		}
		echo "<tr><th width=60px style='text-align:left;'>Total</th><td style='text-align:right;'>";
		if ($this->selected != -1)
			echo number_format($sum2,2)."</td><td>".round($sum2/$sum*100,2)."%";
		else
			echo number_format($sum,2);
		echo "</td></tr></table>";

		echo "<p>Also available: <select onchange=\"top.location='SalesTodayReport.php?super='+this.value;\">";
		foreach($this->supers as $k=>$v){
			echo "<option value=$k";
			if ($k == $this->selected)
				echo " selected";
			echo ">$v</option>";
		}
		echo "</select></p></div>";

	// body_content()
	}

// SalesTodayReport
}

// This construct lets the rest of the file be included for extension.
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])){
	$obj = new SalesTodayReport();
	$obj->draw_page();
}
?>
