<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class ProductMovement extends FannieReport {

	public $description = "
	Report product sales by UPC.
	";

	protected $header = "Fannie : Product Movement";
	protected $title = "Product Movement";

	function preprocess(){
		if (isset($_REQUEST['submit'])){
			$this->mode = 'results';
			$this->window_dressing = False;
		}
		return True;
	}

	function report_results(){
		$start_date = get_form_value('date1',date('Y-m-d'));
		$end_date = get_form_value('date2',date('Y-m-d'));
		$upc = get_form_value('upc','');
		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
		$order = get_form_value('order','date_string');
		$dir = get_form_value('dir','ASC');
		$excel = get_form_value('excel',False);
		
		$dlog = select_dlog($start_date,$end_date);

		$dbc = op_connect();
		$query = "select ".$dbc->dateymd('tdate')." as date_string,
			  t.upc,p.description,
			       sum(case when t.trans_status in ('M') then t.itemqtty 
				   else t.quantity end) 
			  as qty,
			  sum(t.total) as total from
			  $dlog as t left join products as p on t.upc = p.upc 
			  WHERE t.upc = '$upc' AND
			  tdate BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
			  GROUP BY date_string,t.upc,p.description
			  order by $order $dir";

		$columns = array(
			'Date' => array('col'=>'date_string','date'=>'m/d/Y'),
			'UPC' => array('col'=>'t.upc'),
			'Description' => array('col' => 'p.description'),
			'Qty' => array('col'=>'qty','align'=>'right','format'=>'%.2f'),
			'Sales' => array('col'=>'total','align'=>'right','format'=>'%.2f')
		);

		$ret = "Report summed by date on<br />";
		$ret .= date("F d, Y")."<br />";
		$ret .= "From $start_date to $end_date<br />";
		
		$ret .= get_sortable_table($dbc, $query, $columns, $this->module_url(), $order, $excel);

		/**
		  Use download method to set headers
		  Use conversion method to change HTML into CSV
		*/
		if ($excel){
			$this->download('productMovement.csv','excel');
			$arr = html_to_array($ret);
			$csv = array_to_csv($arr);
			$ret = $csv;
		}

		$dbc->close();
		return $ret;
	}

	function report_form(){
		global $FANNIE_URL;
		$this->add_script($FANNIE_URL.'src/CalendarControl.js');

		ob_start();
		echo $this->form_tag('get');
		?>
		<table border="0" cellspacing="0" cellpadding="5">
		<tr> 
			<td>
				<p><b>UPC</b></p>
				<p><b>Excel</b></p>
			</td>
			<td>
				<p>
				<input type=text name=upc id=upc  />
				</p>
				<p>
				<input type=checkbox name=excel id=excel /> 
				</p>
			</td>
			<td>
				<p><b>Date Start</b> </p>
				<p><b>End</b></p>
			</td>
		        <td>
				<p>
				<input type=text size=25 name=date1 
					onfocus="this.value='';showCalendarControl(this);">
				</p>
				<p>
				<input type=text size=25 name=date2 onfocus="this.value='';showCalendarControl(this);">
				</p>
			</td>
		</tr>
		<tr>
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		</table>
		</form>
		<?php
		return ob_get_clean();
	}
}
