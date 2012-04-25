<?php

class SimpleReport extends FannieReport {

	public $description = "Report products in a department";
	protected $title = "Simple Report";
	protected $header = "Fannie :: Simple Report";

	function report_form(){
		ob_start();
		echo $this->form_tag();
		?>
		Enter department number:
		<input type="text" size="4" name="department" />
		<input type="submit" name="submit" value="Run Report" />
		</form>
		<?php
		return ob_get_clean();
	}

	function report_results(){
		$department = get_form_value('department', 0);
		$order = get_form_value('order', 'upc');
		$dir = get_form_value('dir', 'ASC');
		$excel = get_form_value('excel', False);

		$dbc = op_connect();
		$query = "SELECT upc, description, normal_price, modified
			FROM products WHERE department=$department
			ORDER BY $order $dir";
		$columns = array(
			'UPC' => array('col' => 'upc'),
			'Description' => array('col' => 'description'),
			'Price' => array('col' => 'normal_price',
					 'align' => 'right',
					 'format' => '%.2f'),
			'Last Modified' => array('col' => 'modified',
						 'date' => 'm/d/Y')
		);

		$report = get_sortable_table($dbc, $query, $columns,
				$this->module_url(), $order, $excel);	

		if ($excel){
			$this->download('report.xls', 'excel');
		}

		$dbc->close();
		return $report;
	}

	function preprocess(){
		if (isset($_REQUEST['submit'])){
			$this->mode = 'results';
			$this->window_dressing = False;
		}
	}
}

?>
