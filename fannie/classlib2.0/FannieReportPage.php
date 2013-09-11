<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

include_once(dirname(__FILE__).'/FanniePage.php');

/**
  @class FanniePage
  Class for drawing screens
*/
class FannieReportPage extends FanniePage {

	public $required = True;

	public $description = "
	Base class for creating reports.
	";

	public function __construct() {
		// To set authentication.
		parent::__construct();
	}

	/**
	  Function for drawing page content.
	  form_content and report_content are provided
	  by default.
	*/
	protected $content_function = "form_content";

	/**
	  Define report headers. Headers are necessary if sorting is desired
	*/
	protected $report_headers = array();

	/**
	  Define report format. Valid values are: html, xls, csv
	*/
	protected $report_format = 'html';

	/**
	  Enable caching of SQL data. Valid values are: none, day, month
	*/
	protected $report_cache = 'none';

	/**
	  Allow for reports that contain multiple separate tables of data
	*/
	protected $multi_report_mode = False;
	protected $multi_counter = 1;

	/**
	  Option to enable/disable javascript sorting
	*/
	protected $sortable = True;

	/**
	  Which column to sort by default
	*/
	protected $sort_column = 0;

	/**
	  Sort direction. 0 is ascending, 1 is descending
	*/
	protected $sort_direction = 0;

	/**
	  Handle pre-display tasks such as input processing
	  @return
	   - True if the page should be displayed
	   - False to stop here

	  Typically in a report this checks for posted data
	  and decides between showing a data entry form
	  or the report results.  
	*/
	function preprocess(){
		return True;
	}
	
	/**
	  Define the data input form
	  @return An HTML string
	*/
	function form_content(){
	
	}

	/**
	  Define the report display
	  @return An HTML string

	  Generally this function is not overriden.

	  This will first check the cache to see
	  if data for this report has been saved. If not,
	  it will look up the data by calling the
	  fetch_report_data function. That function should
	  be overriden.

	  Once the data is retrieved, this will call
	  the calculate_footers function on the data. 
	  Footers are not required, but it's useful for some
	  final calculations. 

	  Finally, the render_data function is called. Overriding
	  that is not recommended.	
	*/
	function report_content(){
		$data = array();
		$cached = $this->check_data_cache();
		if ($cached !== False){
			$data = unserialize(gzuncompress($cached));
			if ($data === False)
				$data = $this->fetch_report_data();
		}
		else {
			$data = $this->fetch_report_data();
			$this->freshen_cache($data);
		}
		$output = '';
		if ($this->multi_report_mode && $this->report_format != 'xls'){
			foreach($data as $report_data){
				$footers = $this->calculate_footers($report_data);
				$output .= $this->render_data($report_data,$this->report_headers,
						$footers,$this->report_format);
				$output .= '<br />';
			}
		}
		elseif ($this->multi_report_mode && $this->report_format == 'xls'){
			/**
			  For XLS ouput, re-assemble multiple reports into a single
			  long dataset.
			*/
			$xlsdata = array();
			foreach($data as $report_data){
				if (!empty($this->report_headers)) $xlsdata[] = $this->report_headers();
				foreach($report_data as $line) $xlsdata[] = $line;
				$footers = $this->calculate_footers($report_data);
				if (!empty($footers)) $xlsdata[] = $footers;
				$xlsdata[] = array('');
			}
			$output = $this->render_data($xlsdata,array(),array(),'xls');
		}
		else {
			$footers = $this->calculate_footers($data);
			$output = $this->render_data($data,$this->report_headers,
					$footers,$this->report_format);
		}
		echo $output;
	}

	/**
	  Calculate a footer row
	  @param $data an two-dimensional array of data
	  @return array of footer values

	  Principally, footers are separate from data
	  so they can be marked in such in HTML rendering
	  and stay at the bottom when data sorting changes.

	  This function may also be used to set values
	  for headers or default sorting. On more elaborate reports,
	  the number of columns may vary depending on what options
	  are selected. This function is always called so those values
	  will be set reliably even if caching is enabled.
	*/
	function calculate_footers($data){
		return array();
	}

	/**
	  Look for cached SQL data
	
	  Data is stored in the archive database, reportDataCache table.

	  The key column is an MD5 hash of the current URL (minus the excel
	  parameter, if present). This means your forms should use type GET
	  if caching is enabled.

	  The data is stored as a serialized, gzcompressed string.
	*/
	function check_data_cache(){
		global $FANNIE_ARCHIVE_DB;
		$dbc = FannieDB::get($FANNIE_ARCHIVE_DB);
		if ($this->report_cache != 'day' && $this->report_cache != 'month')
			return False;
		$table = $FANNIE_ARCHIVE_DB.$dbc->sep()."reportDataCache";
		$hash = $_SERVER['REQUEST_URI'];
		$hash = str_replace("&excel=xls","",$hash);
		$hash = str_replace("&excel=csv","",$hash);
		$hash = md5($hash);
		$query = $dbc->prepare_statement("SELECT report_data FROM $table WHERE
			hash_key=? AND expires >= ".$dbc->now());
		$result = $dbc->exec_statement($query,array($hash));
		if ($dbc->num_rows($result) > 0){
			$ret = $dbc->fetch_row($result);
			return $ret[0];
		}
		else
			return False;
	}

	/**
	  Store SQL data in the cache
	  @param $data the data
	  @return True or False based on success

	  See check_data_cache for details
	*/
	function freshen_cache($data){
		global $FANNIE_ARCHIVE_DB;
		$dbc = FannieDB::get($FANNIE_ARCHIVE_DB);
		if ($this->report_cache != 'day' && $this->report_cache != 'month')
			return False;
		$table = $FANNIE_ARCHIVE_DB.$dbc->sep()."reportDataCache";
		$hash = $_SERVER['REQUEST_URI'];
		$hash = str_replace("&excel=xls","",$hash);
		$hash = str_replace("&excel=csv","",$hash);
		$hash = md5($hash);
		$expires = '';
		if ($this->report_cache == 'day')
			$expires = date('Y-m-d',mktime(0,0,0,date('n'),date('j')+1,date('Y')));
		elseif ($this->report_cache == 'month')
			$expires = date('Y-m-d',mktime(0,0,0,date('n')+1,date('j'),date('Y')));

		$delQ = $dbc->prepare_statement("DELETE FROM $table WHERE hash_key=?");
		$dbc->exec_statement($delQ,array($hash));
		$upQ = $dbc->prepare_statement("INSERT INTO $table (hash_key, report_data, expires)
			VALUES (?,?,?)");
		$dbc->exec_statement($upQ, array($hash, gzcompress(serialize($data)), $expires));
		return True;
	}

	/**
	  Extra, non-tabular information prepended to
	  reports
	  @return array of strings
	*/
	function report_description_content(){
		return array();
	}

	/**
	  Get the report data
	  @return a two dimensional array

	  Actual SQL queries go here!

	  If using multi_report_mode, this should
	  return an array of two dimensional arrays
	  where each two dimensional arrays contains
	  a report's data.
	*/
	function fetch_report_data(){

	}

	/**
	  Format data for display
	  @param $data a two dimensional array of data
	  @param $headers a header row (optional)
	  @param $format output format (html | xls | csv)
	  @return formatted string
	*/
	function render_data($data,$headers=array(),$footers=array(),$format='html'){
		global $FANNIE_URL,$FANNIE_ROOT;
		$ret = "";
		switch(strtolower($format)){
		case 'html':
			if ($this->multi_counter == 1){
				$this->add_css_file($FANNIE_URL.'src/jquery/themes/blue/style.css');
				$ret .= sprintf('<html><head></head><body>
					<a href="%s%sexcel=xls">Download Excel</a>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="%s%sexcel=csv">Download CSV</a>',
					$_SERVER['REQUEST_URI'],
					(strstr($_SERVER['REQUEST_URI'],'?') ===False ? '?' : '&'),
					$_SERVER['REQUEST_URI'],
					(strstr($_SERVER['REQUEST_URI'],'?') ===False ? '?' : '&')
				);
				foreach($this->report_description_content() as $line)
					$ret .= '<br />'.$line;
			}
			$class = 'mySortableTable';
			if ($this->sortable) $class .= ' tablesorter';
			$ret .= '<table class="'.$class.'" cellspacing="0" 
				cellpadding="4" border="1">';
			break;
		case 'csv':
			foreach($this->report_description_content() as $line)
				$ret .= $this->csv_line(array($line));
		case 'xls':
			break;
		}

		if (!empty($headers)){
			switch(strtolower($format)){
			case 'html':
				$ret .= '<thead>';
				$ret .= $this->html_line($headers, True);
				$ret .= '</thead>';
				break;
			case 'csv':
				$ret .= $this->csv_line($headers);
				break;
			case 'xls':
				break;
			}
		}

		for ($i=0;$i<count($data);$i++){
			switch(strtolower($format)){
			case 'html':
				if ($i==0) $ret .= '<tbody>';
				$ret .= $this->html_line($data[$i]);
				if ($i==count($data)-1) $ret .= '</tbody>';
				break;
			case 'csv':
				$ret .= $this->csv_line($data[$i]);
				break;
			case 'xls':
				break;
			}
		}

		if (!empty($footers)){
			switch(strtolower($format)){
			case 'html':
				$ret .= '<tfoot>';
				$ret .= $this->html_line($footers, True);
				$ret .= '</tfoot>';
				break;
			case 'csv':
				$ret .= $this->csv_line($data[$i]);
				break;
			case 'xls':
				break;
			}
		}

		switch(strtolower($format)){
		case 'html':
			$ret .= '</table></body></html>';
			$this->add_script($FANNIE_URL.'src/jquery/js/jquery.js');
			$this->add_script($FANNIE_URL.'src/jquery/jquery.tablesorter.js');
			$sort = sprintf('[[%d,%d]]',$this->sort_column,$this->sort_direction);
			if ($this->sortable)
				$this->add_onload_command("\$('.mySortableTable').tablesorter({sortList: $sort, widgets: ['zebra']});");
			break;
		case 'csv':
			header('Content-Type: application/ms-excel');
			header('Content-Disposition: attachment; filename="'.$this->header.'.csv"');
			break;
		case 'xls':
			$xlsdata = $data;
			if (!empty($headers)) array_unshift($xlsdata,$headers);
			if (!empty($footers)) array_push($xlsdata,$footers);
			foreach($this->report_description_content() as $line)
				array_unshift($xlsdata,array($line));
			if (!function_exists('ArrayToXls'))
				include_once($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
			$ret = ArrayToXls($xlsdata);
			header('Content-Type: application/ms-excel');
			header('Content-Disposition: attachment; filename="'.$this->header.'.xls"');
			break;
		}

		$this->multi_counter++;
		return $ret;
	}

	/**
	   Convert keyed array to numerical
	   indexes and maintain order
	*/
	function dekey_array($arr){
		$ret = array();
		foreach($arr as $outer_key => $row){
			$record = array();
			foreach($row as $key => $val)
				$record[] = $val;
			$ret[] = $record;
		}
		return $ret;
	}

	/**
	  Turn array into HTML table row
	  @param $row an array of data
	  @param $header True means <th> tags, False means <td> tags
	  @return HTML string

	  Javascript sorting utility requires header rows to be <th> tags
	*/
	function html_line($row, $header=False){
		global $FANNIE_URL;
		$ret = "<tr>";
		$tag = $header ? 'th' : 'td';
		for($i=0;$i<count($row);$i){
			$span = 1;
			while(isset($row[$i+$span]) && $row[$i+$span] === null && ($i+$span)<count($row)){
				$span++;
			}
			if ($row[$i] === "" || $row[$i] === null) $row[$i] = '&nbsp;';
			elseif (is_numeric($row[$i]) && strlen($row[$i]) == 13){
				// auto-link UPCs to edit tool
				$row[$i] = sprintf('<a href="%sitem/itemMaint.php?upc=%s">%s</a>',
					$FANNIE_URL,$row[$i],$row[$i]);
			}
			$ret .= '<'.$tag.' colspan="'.$span.'">'.$row[$i].'</'.$tag.'>';
			$i += $span;
		}
		return $ret.'</tr>';;
	}

	/**
	  Turn array into CSV line
	  @param $row an array of data
	  @return CSV string
	*/
	function csv_line($row){
		$ret = "";
		foreach($row as $item){
			$item = str_replace('"','',$item);
			$ret .= '"'.$item.'",';
		}
		$ret = substr($ret,0,strlen($ret)-1)."\r\n";
		return $ret;
	}

	/**
	  Check for input and display the page
	*/
	function draw_page(){
		if (!$this->check_auth() && $this->must_authenticate){
			$this->login_redirect();
		}
		elseif ($this->preprocess()){
			
			if ($this->window_dressing)
				echo $this->get_header();

			$fn = $this->content_function;
			echo $this->$fn();

			if ($this->window_dressing)
				echo $this->get_footer();

			foreach($this->scripts as $s_url => $s_type){
				printf('<script type="%s" src="%s"></script>',
					$s_type, $s_url);
				echo "\n";
			}
			
			$js_content = $this->javascript_content();
			if (!empty($js_content) || !empty($this->onload_commands)){
				echo '<script type="text/javascript">';
				echo $js_content;
				echo "\n\$(document).ready(function(){\n";
				foreach($this->onload_commands as $oc)
					echo $oc."\n";
				echo "});\n";
				echo '</script>';
			}

			$page_css = $this->css_content();
			if (!empty($page_css)){
				echo '<style type="text/css">';
				echo $page_css;
				echo '</style>';
			}

			foreach($this->css_files as $css_url){
				printf('<link rel="stylesheet" type="text/css" href="%s">',
					$css_url);
				echo "\n";
			}

			if ($this->window_dressing)
				echo '</body></html>';
		}

	// draw_page()
	}
}

?>
