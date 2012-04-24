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

/**
  @class FannieReport
  Class for creating reports
*/
class FannieReport extends FanniePage {

	public $required = True;

	public $description = "
	Class for creating downloadable reports
	";

	private $downloadable = False;
	private $headers = array();

	/**
	  Send headers and remove extra HTML for download
	  @param $filename the file name
	  @param $type the file type. Currently allowed:
	   - excel
	*/
	function download($filename, $type){
		switch(strtolower($type)){
		case 'excel':
			$this->headers[] = "Content-Type: application/ms-excel";
			$this->headers[] = "Content-Disposition: attachment; filename=\"$filename\"";
		}
		$this->downloadable = True;
		$this->window_dressing = False;

		foreach($this->headers as $h)
			header($h);
	}

	function provides_functions(){
		return array(
			'get_sortable_table'
		);
	}

}

/**
  @file
  @brief Functions provided by FannieReport
*/

/**
  Create a sortable table from a query
  @param $dbc A connected SQLManager object
  @param $query the query
  @param $columns An array describing columns (see below)
  @param $url URL of the page
  @param $current current sort column
  @param $nolinks omit sorting links
  @return An HTML string

  $columns is a keyed array describing how the ouput
  should be displayed. Each key is a column header
  and each entry is an array of details including:
   - col <b>(required)</b> the column name in the query 
   - align <i>(optional)</i> align output left/right/center
   - format <i>(optional)</i> a printf style string for formatting
     displayed value
   - date <i>(optional)</i> a PHP date() style string for
     formatting displayed value
   - sort <i>(optional)</i> use a different column for sorting.
     If for instance a column contains text month names, sorting
     by a different column containing numeric months may make
     more sense
*/
function get_sortable_table($dbc, $query, $columns, $url, $current, $nolinks=False){

	$ret = '<table cellpadding="4" cellspacing="0" border="1">';
	$ret .= '<tr>';
	foreach($columns as $title => $c_info){
		if ($nolinks){
			// easy; just headers
			$ret .= '<th>'.$title.'</th>';
			continue;
		}

		$sort_col = (isset($c_info['sort'])) ? $c_info['sort'] : $c_info['col'];

		$url_args = empty($_POST) ? $_GET : $_POST;
		unset($url_args['m']);
		unset($url_args['order']);
		$dir = isset($url_args['dir']) ? strtoupper($url_args['dir']) : 'ASC';
		$otherdir = ($dir == "ASC") ? "DESC" : "ASC";

		$queryString = "";
		foreach($url_args as $key => $val)
			$queryString .= $key."=".$val."&";

		if (strstr($url,"?")) $url .= "&".$queryString;
		else $url .= "?".$queryString;

		$ret .= sprintf('<th><a href="%sorder=%s&dir=%s">%s</a></th>',
				$url, $sort_col,
				($sort_col == $current ? $otherdir : 'ASC'),
				$title
			);
	}
	$ret .= '</tr>';

	$result = $dbc->query($query);
	while($row = $dbc->fetch_row($result)){
		$ret .= '<tr>';
		foreach($columns as $c_info){
			$col = $c_info['col'];
			if (strstr($col,"."))
				$col = array_pop(explode(".",$col));
			$value = $row[$col];
			if (isset($c_info['format']))
				$value = sprintf($c_info['format'],$value);
			elseif(isset($c_info['date']))
				$value = date($c_info['date'],strtotime($value));
			$align = (isset($c_info['align']) ? 'align="'.$c_info['align'].'"' : '');
			
			$ret .= sprintf('<td %s>%s</td>',$align,$value);
		}
		$ret .= '</tr>';
	}
	$ret .= '</table>';

	return $ret;
}

?>
