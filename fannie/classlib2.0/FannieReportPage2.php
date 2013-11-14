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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    * 26Apr13 Eric Lee
    * If this is extended for a multi_report_format page in not-sortable mode:
    * * Format the report in a single table
    * * Support an optional summary report at the end with
    *   * Different headers
    * Right-Align text in columns of numbers.
    * Use a .css
    * To do:
    * * Add summary to xls and csv outputs
    * * Remove the parts that do not override the base page.
*/

//include_once(dirname(__FILE__).'/FanniePage.php');
if (!class_exists('FannieReportPage')) {
    include_once(dirname(__FILE__).'/FannieReportPage.php');
}

/**
  @class FannieReportPage2
  Class for drawing screens
*/
class FannieReportPage2 extends FannieReportPage {

    public $required = True;

    public $description = "
    Base class for creating reports.
    Formats non-sortable multi-section report as single table.
    ";

    public function __construct() {
        parent::__construct();
    }

    /**
      Function for drawing page content.
      form_content and report_content are provided
      by default.
    protected $content_function = "form_content";
    */

    /**
      Define report headers. Headers are necessary if sorting is desired
    protected $report_headers = array();
    */

    /**
      End-of report summary(ies), with integrated headers. (optional)
        Two-dimensional, as for body data.
        Same number of columns as body data.
    */
    // 3New
    protected $summary_data = array();
    protected $summary_counter = 0;
    protected $number_of_summaries = 0;

    /**
      Define report format. Valid values are: html, xls, csv
    protected $report_format = 'html';
    */

    /**
      Enable caching of SQL data. Valid values are: none, day, month
    protected $report_cache = 'none';
    */

    /**
      Allow for reports that contain multiple separate tables of data
    protected $multi_report_mode = False;
    protected $multi_counter = 1;
    */
    // 1New
    protected $number_of_reports = 0;

    /**
      Option to enable/disable javascript sorting
    protected $sortable = True;
    */

    /**
      Which column to sort by default
    protected $sort_column = 0;
    */

    /**
      Sort direction. 0 is ascending, 1 is descending
    protected $sort_direction = 0;
    */

    /**
      Alignment of table cell text. Valid values are: '' 'left' 'right'
    */
    // 1New
    protected $cellTextAlign = '';

    /**
      Define the report display,
         all the reports and summaries on the page
      @return An HTML string (echoes, doesn't return)

      Generally this function is not overriden.

      This will first check the cache to see
      if data for this report has been saved.
        If not, it will look up the data by calling the
      fetch_report_data function.
        That function should be overriden.
        fetch_report_data can optionally return summary
        report data in separate array.

      Once the data is retrieved, this will call
      the calculate_footers function on the data. 
      Footers are not required, but it's useful for some
      final calculations. 

      Finally, the render_data function is called.
        Overriding that is not recommended.    
    Changed.
    */
    function report_content(){
        $data = array();
        $cached = $this->checkDataCache();
        if ($cached !== False){
            $data = unserialize(gzuncompress($cached));
            if ($data === False)
                $data = $this->fetch_report_data();
        }
        else {
            $data = $this->fetch_report_data();
            $this->freshenCache($data);
        }
        $output = '';
        if ($this->multi_report_mode && $this->report_format != 'xls'){
            $this->number_of_reports = count($data);
            $this->number_of_summaries = count($this->summary_data);
            foreach($data as $report_data){
                $footers = $this->calculate_footers($report_data);
                $output .= $this->render_data($report_data,
                        $this->report_headers,
                        $footers,
                        $this->report_format);
                if ($this->sortable)
                    $output .= '<br />';
            }
            // A summary or grand total of the report
            // First row (of each) tested to see if seems like headers.
            if ( !empty($this->summary_data) ) {
                foreach($this->summary_data as $report_data){
                    $this->summary_counter++;
                    $headers = array();
                    // Move $report_data[0] to headers if all non-numbers.
                    for($i=0;$i<count($report_data[0]);$i++){
                        if ( $report_data[0][$i] != '' && preg_match("/^[0-9., $%]+$/",$report_data[0][$i]) ) {
                            break;
                        }
                    }
                    if ( $i >= count($report_data[0]) )
                        $headers = array_shift($report_data);
                    $footers = array();
                    $output .= $this->render_data($report_data,
                            $headers,
                            $footers,
                            $this->report_format);
                    /* Better with CSS
                    if (False && $this->sortable)
                        $output .= '<br />';
                    */
                }
            }
            else {
                $output .= $this->render_data($report_data,
                        array(),
                        array(),
                        $this->report_format);
            }
        }
        elseif ($this->multi_report_mode && $this->report_format == 'xls'){
            /**
              For XLS ouput, re-assemble multiple reports into a single
              long dataset.
            */
            $xlsdata = array();
            foreach($data as $report_data){
                // Is the reference to $this->reports_headers() (i.e. function) an error?
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

    // report_content()
    }

    /**
      Format data for one report for display
      @param $data a two dimensional array of data
      @param $headers a header row (optional)
      @param $footers a column-totals row (optional)
      @param $format output format (html | xls | csv)
      @return formatted string
    Changed.
    */
    function render_data($data,$headers=array(),$footers=array(),$format='html'){
        global $FANNIE_URL,$FANNIE_ROOT;
        $ret = "";
        switch(strtolower($format)){
        case 'html':
            if ($this->multi_counter == 1){
                $this->add_css_file($FANNIE_URL.'src/jquery/themes/blue/style.css');
                $this->add_css_file($FANNIE_URL.'src/css/reports.css');
                if ( !$this->window_dressing )
                    $ret .= '<html><head></head><body>';
                $ret .= sprintf(
                '<a href="%s%sexcel=xls">Download Excel</a>',
                    $_SERVER['REQUEST_URI'],
                    (strstr($_SERVER['REQUEST_URI'],'?') === False ? '?' : '&'));
                $ret .= '    &nbsp;&nbsp;&nbsp;&nbsp ';
                $ret .= sprintf('<a href="%s%sexcel=csv">Download CSV</a>',
                    $_SERVER['REQUEST_URI'],
                    (strstr($_SERVER['REQUEST_URI'],'?') === False ? '?' : '&'));
                $ret .= '    &nbsp;&nbsp;&nbsp;&nbsp ';
                $ret .= '<a href="javascript:history:back();">Back</a>';
                foreach($this->report_description_content() as $line)
                    $ret .= (substr($line,0,1) == '<')?$line:'<br />'.$line;
            }
            $class = 'mySortableTable';
            if ($this->sortable)
                $class .= ' tablesorter fancytable';
            if ($this->sortable || $this->multi_counter == 1)
                $ret .= '<table class="'.$class.'" cellspacing="0" 
                    cellpadding="4" border="1">';
            break;
        case 'csv':
            foreach($this->report_description_content() as $line)
                $ret .= $this->csvLine(array($line));
        case 'xls':
            break;
        }

        if (!empty($headers)){
            switch(strtolower($format)){
            case 'html':
                $ret .= '<thead>';
                $ret .= $this->htmlLine($headers, True);
                $ret .= '</thead>';
                break;
            case 'csv':
                $ret .= $this->csvLine($headers);
                break;
            case 'xls':
                break;
            }
        }

        for ($i=0;$i<count($data);$i++){
            switch(strtolower($format)){
            case 'html':
                if ($i==0) {
                    if ($this->sortable || $this->multi_counter == 1) {
                        $ret .= '<tbody>';
                    }
                }
                $ret .= $this->htmlLine($data[$i]);
                if ($i==count($data)-1) {
                    if ( $this->sortable ||
                                !$this->multi_report_mode ||
                                ($this->multi_counter >= $this->number_of_reports &&
                                    $this->summary_counter == $this->number_of_summaries)
                        ) {
                        $ret .= '</tbody>';
                    }
                }
                break;
            case 'csv':
                $ret .= $this->csvLine($data[$i]);
                break;
            case 'xls':
                break;
            }
        }

        // For html these are supposed to be before <tbody>
        if (!empty($footers)){
            switch(strtolower($format)){
            case 'html':
                if ($this->sortable) {
                    $ret .= '<tfoot>';
                    $ret .= $this->htmlLine($footers, False, True);
                    $ret .= '</tfoot>';
                }
                else {
                    $ret .= $this->htmlLine($footers, False, True);
                    if ( $this->multi_counter < ($this->number_of_reports + count($this->summary_data)) ) {
                        $ret .= "<tr><td colspan='99'> &nbsp; </td></tr>";
                    }
                }
                break;
            case 'csv':
                $ret .= $this->csvLine($data[$i]);
                break;
            case 'xls':
                break;
            }
        }

        switch(strtolower($format)){
        case 'html':
            if ( $this->sortable ||
                    ($this->multi_counter == $this->number_of_reports && empty($this->summary_data)) ||
                    ($this->multi_counter >= $this->number_of_reports && $this->summary_counter == $this->number_of_summaries)
                    ) {
                $ret .= '</table>';
                }

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

    // render_data()
    }

    /**
      Turn array into HTML table row
      @param $row an array of data
      @param $header True means <th> tags, False means <td> tags
      @return HTML string

      Javascript sorting utility requires header rows to be <th> tags
    Changed.
    */
    function htmlLine($row, $header=False, $footer=False){
        global $FANNIE_URL;
        $ret = "<tr>";
        $tag = $header ? 'th' : 'td';
        for($i=0;$i<count($row);$i){
            $span = 1;
            while(isset($row[$i+$span]) && $row[$i+$span] === null && ($i+$span)<count($row)){
                $span++;
            }
            if ($header)
                $textAlign = '';
            else
                $textAlign = $this->cellTextAlign;
            if ($row[$i] === "" || $row[$i] === null) $row[$i] = '&nbsp;';
            elseif (is_numeric($row[$i]) && strlen($row[$i]) == 13){
                // auto-link UPCs to edit tool
                $textAlign = 'right';
                $row[$i] = sprintf('<a href="%sitem/itemMaint.php?upc=%s">%s</a>',
                    $FANNIE_URL,$row[$i],$row[$i]);
            }
            if ( $textAlign == '' && preg_match("/^[0-9., $%]+$/",$row[$i]) )
                $textAlign = 'right';
            $class = '';
            $class .= ($textAlign == 'right') ? " number" : '';
            $class .= ($footer) ? " footer" : '';
            $class = ($class) ? " class='$class'" : '';
            $ret .= '<'.$tag.$class.' colspan="'.$span.'">'.$row[$i].'</'.$tag.'>';
            $i += $span;
        }
        return $ret.'</tr>';
    // htmlLine()
    }

}

