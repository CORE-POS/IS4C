<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of CORE-POS.

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

if (!class_exists('FanniePage')) {
    include_once(dirname(__FILE__).'/FanniePage.php');
}

/**
  @class FanniePage
  Class for drawing screens
*/
class FannieReportPage extends FanniePage 
{

    public $required = True;

    /**
      Description of the report
    */
    public $description = "
    Base class for creating reports.
    ";

    public $page_set = 'Reports';

    /**
      Assign report to a "set" of reports
    */
    public $report_set = '';

    public $discoverable = true;

    /*
    */
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
      If fields are present in the request, the
      form has been submitted and report can be
      displayed
    */
    protected $required_fields = array();

    /**
      Define report headers. Headers are necessary if sorting is desired
    */
    protected $report_headers = array();

    /**
      Define report format. Valid values are: html, xls, csv, txt
    */
    protected $report_format = 'html';

    /**
      Enable caching of SQL data. Valid values are: none, day, month
    */
    protected $report_cache = 'none';

    /**
      Allow for reports that contain multiple separate tables of data.
      If all the reports are the same width, using META_BLANK and/or
      META_REPEAT_HEADERS may be preferrable.  
    */
    protected $multi_report_mode = False;
    protected $multi_counter = 1;
    protected $header_index = 0;

    /**
      Option to enable/disable javascript sorting
    */
    protected $sortable = true;

    /**
      Apply CSS to table but not sorting JS.
      May become default behavior if it does
      not mess up current unsorted reports
    */
    protected $no_sort_but_style = false;

    /**
      Which column to sort by default
    */
    protected $sort_column = 0;

    /**
      Sort direction. 0 is ascending, 1 is descending
    */
    protected $sort_direction = 0;

    /**
      false => use tablesorter v. 2.0.5b
      true  => use tablesorter v. 2.22.1 (bootstrap compatible)
    */
    protected $new_tablesorter = false;


    /**
      Disable inclusion of jquery. In rare cases the report 
      content may be embedded in a page that already included
      jquery and multiple copies included can cause problems.
    */
    protected $no_jquery = false;

    /**
      Column containing chart labels.
    */
    protected $chart_label_column = 0;

    /**
      Column(s) containing chart data values.
      An empty array signifies means every column
      except the label contains data.
    */
    protected $chart_data_columns = array();

    protected $form;

    /** 
        Assign meta constant(s) to a row's "meta" field
        for special behavior.

        Bold is self-explanatory. Blank will insert a blank
        line and repeat headers will repeat the report_headers.
        The latter two will terminate the current <tbody> and
        start a new one. This breaks the report into separately
        sortable chunks.
      */ 
    const META_BOLD         = 1;
    const META_BLANK        = 2;
    const META_REPEAT_HEADERS    = 4;
    const META_CHART_DATA    = 8;
    const META_COLOR    = 16;

    protected function hasAllFields($fields)
    {
        foreach($fields as $field) {
            if (FormLib::get($field, '') === '') {
                return false;
            }
        }

        return true;
    }

    /**
      Handle pre-display tasks such as input processing
      @return
       - True if the page should be displayed
       - False to stop here

      The default version will check required_fields
      to determine whether the form_content or
      report_content method should be called. It
      also the value of "excel" for the request and
      sets necessary output options.
    */
    public function preprocess()
    {
        if ($this->hasAllFields($this->required_fields)) {
            $this->form = new \COREPOS\common\mvc\FormValueContainer();
            $this->content_function = 'report_content'; 
            if ($this->config->get('WINDOW_DRESSING')) {
                $this->has_menus(true);
                $this->new_tablesorter = true;
            } else {
                $this->has_menus(false);
            }
            $this->formatCheck();
        } else {
            $this->content_function = 'form_content'; 
        }

        return true;
    }
    
    /**
      Define the data input form
      @return An HTML string
    */
    public function form_content()
    {
    
    }

    protected function hasWarehouseSource()
    {
        $plugins = $this->config->get('PLUGIN_LIST');
        if (FormLib::get('data-source') === 'CoreWarehouse' && is_array($plugins) && in_array('CoreWarehouse', $plugins)) {
            $reflector = new ReflectionClass($this);
            $source = \COREPOS\Fannie\Plugin\CoreWarehouse\CwReportDataSource::getDataSource($reflector->getName());
            return $source !== false ? $source : false;
        }

        return false;
    }

    function bodyContent()
    {
        $plugins = $this->config->get('PLUGIN_LIST');
        if (($source=$this->hasWarehouseSource()) !== false) {
            $source_select = '
                <div class="col-sm-12" id="data-source-fields">
                    <div class="form-group">
                        <label>Data Source</label> <select name="data-source" class="form-control" id="select-data-source">
                            <option>Standard</option>
                            <option>CoreWarehouse</option>
                        </select>
                    </div>';
            $source_select = array_reduce($source->additionalFields($reflector->getName()),
                function ($carry, $field) {
                    return $carry . '<div class="form-group">' . $field->toHTML() . '</div>';
                },
                $source_select
            );
            $source_select .= '</div>';
            $source_select = preg_replace('/\s\s+/', '', $source_select);
            $this->addOnloadCommand("\$('#primary-content form').prepend('$source_select');\n");
            $this->addOnloadCommand("\$('#primary-content .cw-field').attr('disabled', true);\n");
            $this->addOnloadCommand("\$('#select-data-source').change(function(){ if ($(this).val()=='CoreWarehouse') { $('#primary-content .cw-field').attr('disabled', false); } else { $('#primary-content .cw-field').attr('disabled', true); } });\n");
        }
        return $this->form_content();
    }

    protected function getData()
    {
        $data = array();
        $cached = $this->checkDataCache();
        if ($cached !== false) {
            $data = $cached;
        } else {
            /**
              Use CoreWarehouse data source if requested & available
              Fail back to FannieReportPage::fetch_report_data()
              if a data source cannot be found or data source fails
              to handle the request
            */
            if (($source=$this->hasWarehouseSource()) !== false && FormLib::get('data-source') === 'CoreWarehouse') {
                $data = $source->fetchReportData($reflector->getName(), $this->config, $this->connection);
                if ($data === false) {
                    $data = $this->fetch_report_data();
                }
            } else {
                $data = $this->fetch_report_data();
            }
            $this->freshenCache($data);
        }

        return $data;
    }

    protected function multiContent($data)
    {
        $output = '';
        if ($this->report_format != 'xls') {
            foreach($data as $report_data) {
                $this->assign_headers();
                // calculate_footers() here because it can affect headers.
                $footers = $this->calculate_footers($report_data);
                $headers = $this->report_headers;
                $this->header_index = 0;
                $output .= $this->render_data($report_data,$headers,
                            $footers,$this->report_format);
                if ($this->report_format == 'html') {
                    $output .= '<br />';
                } elseif ($this->report_format == 'csv' || $this->report_format == 'txt') {
                    $output .= "\r\n";
                }
            }
        } else {
            /**
              For XLS multi-report ouput, re-assemble the reports into a single
              long array of rows (dataset).
            */
            $xlsdata = array();
            foreach($data as $report_data) {
                $this->assign_headers();
                // calculate_footers() here because it can affect headers.
                $footers = $this->calculate_footers($report_data);
                $this->header_index = 0;
                if (!empty($this->report_headers)) {
                    $headers1 = $this->select_headers(true);
                    $xlsdata[] = $headers1;
                }
                $report_data = $this->xlsMeta($report_data);
                $xlsdata = array_reduce($report_data, 
                    function($carry, $line) {
                        $carry[] = $line;
                        return $carry;
                    },
                    $xlsdata
                );
                if (!empty($footers)) {
                    // A single footer row
                    if (!is_array($footers[0])) {
                        $xlsdata[] = $footers;
                    // More than one footer row
                    } else {
                        $xlsdata = array_reduce($footers, 
                            function($carry, $footer) {
                                $carry[] = $footer;
                                return $carry;
                            },
                            $xlsdata
                        );
                    }
                }
                $xlsdata[] = array('');
                $this->multi_counter++;
            }
            $output = $this->render_data($xlsdata,array(),array(),'xls');
        }

        return $output;
    }

    protected function singleContent($data)
    {
        // NOT multi_report_mode
        $this->assign_headers();
        $footers = $this->calculate_footers($data);
        /* $data may contain REPEAT_HEADERS calls
         * If the 2nd+ headers should be different then report_headers
         *  has two dimensions.
         */
        return $this->render_data($data,$this->report_headers,
                $footers,$this->report_format);
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
    function report_content()
    {
        $data = $this->getData();
        $output = '';
        if ($this->multi_report_mode) {
            $output = $this->multiContent($data);
        } else {
            $output = $this->singleContent($data);
        }

        return $output;
    }

    /**
      Displays both form_content and report_content
      @return html string
    */
    public function both_content()
    {
        $ret = '';
        if ($this->report_format == 'html') {
            $ret .= $this->form_content();
            $ret .= '<hr />';
        }
        
        $ret .= $this->report_content();
        
        return $ret;
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
    public function calculate_footers($data)
    {
        return array();
    }

    /**
     * Assign new values to $report_headers,
     *  which is intially assigned in the report,
     *  usually for 2nd+ reports in multi_report_mode.
     */
    public function assign_headers()
    {
    }

    /**
      * Return a single-dimension array of headers (column-heads).
      @param
      @return array of header values
      *
      * Allow for but not require different headers on each report.
      * Input may be one- or two-dimensional.
      *  If the latter, index is header_index.
      *  If headers[x] doesn't exist use the last one that does exist
      *   or empty if none exists.
    */
    public function select_headers($incrIndex=False) 
    {
        $headers = array();
        $hIndex = $this->header_index;
        if (is_array($this->report_headers[0])) {
            if (isset($this->report_headers[$hIndex])) {
                $headers = $this->report_headers[$hIndex];
            } else {
                $hIndex = (count($this->report_headers) - 1);
                if ($hIndex >= 0) {
                    $headers = $this->report_headers[$hIndex];
                }
            }
        } else {
            $headers = $this->report_headers;
        }

        if ($incrIndex) {
            $this->header_index++;
        }

        return $headers;
    }

    /**
      Apply formatting to data. This method can be used to
      add markup to records - e.g., links to other content.

      @param $data two-dimensional array of report data
      @return two-dimensional array of report data
    */
    protected function format($data)
    {
        return $data;
    }

    private function getCacheKey()
    {
        $reflector = new ReflectionClass($this);
        $qstr = filter_input(INPUT_SERVER, 'QUERY_STRING');
        $qstr = str_replace('&excel=xls', '', $qstr);
        $qstr = str_replace('&excel=xlsx', '', $qstr);
        $qstr = str_replace('&excel=csv', '', $qstr);
        $qstr = str_replace('&excel=txt', '', $qstr);
        $qstr = str_replace('?excel=xls', '', $qstr);
        $qstr = str_replace('?excel=xlsx', '', $qstr);
        $qstr = str_replace('?excel=csv', '', $qstr);
        $qstr = str_replace('?excel=txt', '', $qstr);

        return $reflector->getName() . $qstr;
    }

    /**
      Look for cached SQL data
    
      Data is stored in the archive database, reportDataCache table.

      The key column is an MD5 hash of the current URL (minus the excel
      parameter, if present). This means your forms should use type GET
      if caching is enabled.

      The data is stored as a serialized, gzcompressed string.
    */
    protected function checkDataCache()
    {
        if (isset($_GET['no-cache']) && $_GET['no-cache'] === '1') {
            return false;
        }

        $data = COREPOS\Fannie\API\data\DataCache::getFile
            ($this->report_cache, 
            $this->getCacheKey()
        );

        return $data ? unserialize(gzuncompress($data)) : false;
    }

    /**
      Store SQL data in the cache
      @param $data the data
      @return True or False based on success

      See checkDataCache for details
    */
    protected function freshenCache($data)
    {
        return COREPOS\Fannie\API\data\DataCache::putFile(
            $this->report_cache,
            gzcompress(serialize($data)),
            $this->getCacheKey()
        );
    }

    /**
      Extra, non-tabular information prepended to
      reports
      @return array of strings
    */
    public function report_description_content()
    {
        return array();
    }

    /**
      Standard lines to include above report data
      @param $datefields [array] names of one or two date fields
        in the GET/POST data. The fields "date", "date1", and
        "date2" are detected automatically.
      @return array of description lines
    */
    protected function defaultDescriptionContent($rowcount, $datefields=array())
    {
        $ret = array();
        $ret[] = '<div class="hidden-print">' . $this->header . '</div>';
        $ret[] = '<div class="hidden-print">' . _('Report generated') . ' ' . date('l, F j, Y g:iA') . '</div>';
        $ret[] = '<div class="hidden-print">Returned ' . $rowcount . ' rows</div>';
        $dt1 = false;
        $dt2 = false;
        if (count($datefields) == 1) {
            $dt1 = strtotime(FormLib::get($datefields[0])); 
        } elseif (count($datefields) == 2) {
            $dt1 = strtotime(FormLib::get($datefields[0])); 
            $dt2 = strtotime(FormLib::get($datefields[1])); 
        } elseif (FormLib::get('date') !== '') {
            $dt1 = strtotime(FormLib::get('date'));
        } elseif (FormLib::get('date1') !== '' && FormLib::get('date2') !== '') {
            $dt1 = strtotime(FormLib::get('date1'));
            $dt2 = strtotime(FormLib::get('date2'));
        }
        if ($dt1 && $dt2) {
            $ret[] = _('From') . ' ' 
                . date('l, F j, Y', $dt1) 
                . ' ' . _('to') . ' ' 
                . date('l, F j, Y', $dt2);
        } elseif ($dt1 && !$dt2) {
            $ret[] = _('For') . ' ' . date('l, F j, Y', $dt1);
        }

        return $ret;
    }

    /**
      Extra, non-tabular information appended to
      reports
      @return array of strings
    */
    public function report_end_content()
    {
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
    public function fetch_report_data()
    {
        return array(
            array('2000-01-01', '1-1-1', '1234567890123', 'Sample Data'),
        );
    }

    /**
      Format data for display
      @param $data a two dimensional array of data
      @param $headers a header row (optional)
      @param $format output format (html | xls | csv | txt)
      @return formatted string
    */
    public function render_data($data,$headers=array(),$footers=array(),$format='html')
    {
        $url = $this->config->get('URL');
        $ret = "";
        switch(strtolower($format)) {
            case 'html':
                if ($this->multi_counter == 1) {
                    if (!$this->new_tablesorter) {
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            // windows has trouble with symlinks
                            $this->add_css_file($url . 'src/javascript/tablesorter-2.0.5b/themes/blue/style.css');
                            $this->add_css_file($url . 'src/css/print.css');
                        } else {
                            $this->add_css_file($url . 'src/javascript/tablesorter/themes/blue/style.css');
                            $this->add_css_file($url . 'src/css/print.css');
                        }
                    } else {
                        $this->add_css_file($url . 'src/javascript/tablesorter-2.22.1/css/theme.bootstrap.css');
                    }
                    if (!$this->window_dressing) {
                        $ret .= '<!DOCTYPE html><html><head>' .
                        '<meta http-equiv="Content-Type" ' .
                            'content="text/html; charset=iso-8859-1">' .
                        '</head><body>';
                        $ret .= '<script type="text/javascript">
                            function highlightCell(e)
                            {
                                if ($(this).css(\'background-color\') != \'rgb(255, 255, 0)\') {
                                    $(this).css(\'background-color\',\'yellow\');
                                } else {
                                    $(this).css(\'background-color\',\'\');
                                }
                            }
                            </script>';
                    }
                    $ret .= '<div id="pre-report-content">';
                    if (empty($_POST)) {
                        $uri = filter_input(INPUT_SERVER, 'REQUEST_URI');
                        $ret .= '<div class="hidden-print">';
                        if (\COREPOS\Fannie\API\data\DataConvert::excelSupport()) {
                            $ret .= sprintf('<a href="%s%sexcel=xls">Download Excel</a>
                                &nbsp;&nbsp;&nbsp;&nbsp;',
                                $uri,
                                (strstr($uri, '?') === false ? '?' : '&')
                            );
                        }
                        $json = FormLib::queryStringtoJSON(filter_input(INPUT_SERVER, 'QUERY_STRING'));
                        $ret .= sprintf('<a href="%s%sexcel=csv">Download CSV</a>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <a href="%s%sexcel=txt">Download TXT</a>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <a href="?json=%s">Back</a></div>',
                            $uri,
                            (strstr($uri, '?') === false ? '?' : '&'),
                            $uri,
                            (strstr($uri, '?') === false ? '?' : '&'),
                            base64_encode($json)
                        );
                    } else {
                        $ret .= '<form id="downloadForm" method="post">';
                        foreach ($_POST as $key => $val) {
                            if (is_array($val)) {
                                foreach ($val as $v) {
                                    $ret .= "<input type=\"hidden\" name=\"{$key}[]\" value=\"{$v}\" />";
                                }
                            } else {
                                $ret .= "<input type=\"hidden\" name=\"{$key}\" value=\"{$val}\" />";
                            }
                        }
                        $ret .= '<input type="hidden" name="excel" id="excelType" /></form>';
                        if (\COREPOS\Fannie\API\data\DataConvert::excelSupport()) {
                            $ret .= '<a href="" onclick="$(\'#excelType\').val(\'xls\');$(\'#downloadForm\').submit(); return false;">Download Excel</a>
                                &nbsp;&nbsp;&nbsp;&nbsp;';
                        }
                        $ret .= '<a href="" onclick="$(\'#excelType\').val(\'csv\');$(\'#downloadForm\').submit(); return false;">Download CSV</a>';
                        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                        $ret .= '<a href="" onclick="$(\'#excelType\').val(\'txt\');$(\'#downloadForm\').submit(); return false;">Download TXT</a>';
                    }
                    $ret = array_reduce($this->defaultDescriptionContent(count($data)),
                        function ($carry, $line) {
                            return $carry . (substr($line,0,1)=='<'?'':'<br />').$line;
                        },
                        $ret
                    );
                    $ret = array_reduce($this->report_description_content(),
                        function ($carry, $line) {
                            return $carry . (substr($line,0,1)=='<'?'':'<br />').$line;
                        },
                        $ret
                    );
                    $ret .= '</div>';
                }
                if ($this->sortable || $this->no_sort_but_style) {
                    $ret .= '<table class="mySortableTable tablesorter tablesorter-bootstrap">';
                } else {
                    $ret .= '<table class="mySortableTable" cellspacing="0" 
                        cellpadding="4" border="1">' . "\n";
                }
                break;
            case 'csv':
            case 'txt':
                $sep = strtolower($format) == 'txt' ? "\t" : ',';
                foreach ($this->defaultDescriptionContent(count($data)) as $line) {
                    $ret .= $this->csvLine(array(strip_tags($line)), $sep);
                }
                foreach ($this->report_description_content() as $line) {
                    $ret .= $this->csvLine(array(strip_tags($line)), $sep);
                }
            case 'xls':
                break;
        }

        if (!empty($headers)) {
            $headers1 = $this->select_headers(False);
            if (!$this->multi_report_mode && strtolower($format) != 'xls') {
                $this->header_index++;
            }
            switch(strtolower($format)) {
                case 'html':
                    $ret .= '<thead>' . "\n";
                    $ret .= $this->htmlLine($headers1, True);
                    $ret .= '</thead>' . "\n";
                    break;
                case 'csv':
                    $ret .= $this->csvLine($headers1);
                    break;
                case 'txt':
                    $ret .= $this->csvLine($headers1, "\t");
                    break;
                case 'xls':
                    break;
            }
        }

        for ($i=0;$i<count($data);$i++) {
            switch(strtolower($format)) {
                case 'html':
                    if ($i==0) {
                        $ret .= "<tbody>\n";
                    }
                    $ret .= $this->htmlLine($data[$i]);
                    if ($i==count($data)-1) {
                        $ret .= "</tbody>\n";
                    }
                    break;
                case 'csv':
                    $ret .= $this->csvLine($data[$i]);
                    break;
                case 'txt':
                    $ret .= $this->csvLine($data[$i], "\t");
                    break;
                case 'xls':
                    break;
            }
        }

        if (!empty($footers)) {
            switch(strtolower($format)) {
                case 'html':
                    $ret .= '<tfoot>';
                    // A single footer row
                    if (!is_array($footers[0])) {
                        $ret .= $this->htmlLine($footers, True);
                    // More than one footer row
                    } else {
                        foreach ($footers as $footer) {
                            $ret .= $this->htmlLine($footer, True);
                        }
                    }
                    $ret .= '</tfoot>';
                    break;
                case 'csv':
                case 'txt':
                    $sep = strtolower($format) == 'txt' ? "\t" : ',';
                    // A single footer row
                    if (!is_array($footers[0])) {
                        $ret .= $this->csvLine($footers, $sep);
                    // More than one footer row
                    } else {
                        foreach ($footers as $footer) {
                            $ret .= $this->csvLine($footer, $sep);
                        }
                    }
                    break;
                case 'xls':
                    break;
            }
        }

        switch(strtolower($format)) {
            case 'html':
                $ret .= '</table>';
                $ret = array_reduce($this->report_end_content(),
                    function ($carry, $line) {
                        return $carry . (substr($line,0,1)=='<'?'':'<br />').$line;
                    },
                    $ret
                );
                if (!$this->no_jquery && !$this->new_tablesorter) {
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        // windows has trouble with symlinks
                        $this->add_script($url . 'src/javascript/jquery-1.11.1/jquery-1.11.1.min.js');
                    } else {
                        $this->add_script($url . 'src/javascript/jquery.js');
                    }
                }
                if (!$this->new_tablesorter) {
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        // windows has trouble with symlinks
                        $this->add_script($url . 'src/javascript/tablesorter-2.0.5b/jquery.tablesorter.js');
                    } else {
                        $this->add_script($url . 'src/javascript/tablesorter/jquery.tablesorter.js');
                    }
                } else {
                    $this->add_script($url . 'src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.js');
                    $this->add_script($url . 'src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.widgets.js');
                }
                $this->addScript($url . 'src/javascript/jquery.floatThead.min.js');
                $sort = sprintf('[[%d,%d]]',$this->sort_column,$this->sort_direction);
                if ($this->sortable) {
                    if (!$this->new_tablesorter) {
                        $this->add_onload_command("\$('.mySortableTable').tablesorter({sortList: $sort, widgets: ['zebra']});");
                    } else {
                        $this->add_onload_command("\$.tablesorter.themes.bootstrap['active'] = 'info';");
                        $this->add_onload_command("\$.tablesorter.themes.bootstrap['table'] += ' tablesorter-core table-condensed small';");
                        $this->add_onload_command("\$.tablesorter.themes.bootstrap['header'] += ' table-condensed small';");
                        $this->add_onload_command("\$('.mySortableTable').tablesorter({sortList: $sort, theme:'bootstrap', headerTemplate: '{content} {icon}', widgets: ['uitheme','zebra']});");
                    }
                    $this->addOnloadCommand("\$('.mySortableTable').floatThead();\n");
                } elseif ($this->new_tablesorter) {
                    /**
                      New bootstrap-themed tablesorter requires more setup to style correctly
                      even when sorting isn't used. Simply including a CSS file is not sufficient.
                    */
                    $this->add_onload_command("\$.tablesorter.themes.bootstrap['active'] = 'info';");
                    $this->add_onload_command("\$.tablesorter.themes.bootstrap['table'] += ' tablesorter-core table-condensed small';");
                    $this->add_onload_command("\$.tablesorter.themes.bootstrap['header'] += ' table-condensed small';");
                    $this->add_onload_command("\$('.mySortableTable thead th').data('sorter', false);\n");
                    $this->add_onload_command("\$('.mySortableTable').tablesorter({theme:'bootstrap', headerTemplate: '{content} {icon}', widgets: ['uitheme','zebra']});");
                }
                break;
            case 'csv':
                if (!headers_sent()) {
                    header('Content-Type: application/ms-excel');
                    header('Content-Disposition: attachment; filename="'.$this->header.'.csv"');
                }
                foreach($this->report_end_content() as $line) {
                    $ret .= $this->csvLine(array(strip_tags($line)));
                }
                break;
            case 'txt':
                if (!headers_sent()) {
                    header('Content-Type: application/ms-excel');
                    header('Content-Disposition: attachment; filename="'.$this->header.'.txt"');
                }
                foreach($this->report_end_content() as $line) {
                    $ret .= $this->csvLine(array(strip_tags($line)), "\t");
                }
                break;
            case 'xls':
                // headers empty in multi-report-mode
                if (!empty($headers)) {
                    $headers1 = $this->select_headers(True);
                    array_unshift($data,$headers1);
                }
                if (!$this->multi_report_mode) {
                    $data = $this->xlsMeta($data);
                }
                for ($i=0;$i<count($data);$i++) {
                    for ($j=0;$j<count($data[$i]);$j++) {
                        if (isset($data[$i][$j])) {
                            $data[$i][$j] = $this->excelFormat($data[$i][$j]);
                        }
                    }
                }
                $xlsdata = $data;
                // footers empty in multi-report-mode
                if (!empty($footers)) {
                    // A single footer row
                    if (!is_array($footers[0])) {
                        array_push($xlsdata,$footers);
                    // More than one footer row
                    } else {
                        $xlsdata = array_reduce($footers, 
                            function($carry, $footer) {
                                $carry[] = $footer;
                                return $carry;
                            },
                            $xlsdata
                        );
                    }
                }
                $xlsdata = array_reduce($this->report_end_content(), 
                    function($carry, $line) {
                        $carry[] = strip_tags($line);
                        return $carry;
                    },
                    $xlsdata
                );
                $xlsdata = array_merge(array_reduce($this->report_description_content(), 
                    function($carry, $line) {
                        $carry[] = array(strip_tags($line));
                        return $carry;
                    },
                    array()
                ),$xlsdata); // prepend
                $xlsdata = array_merge(array_reduce($this->defaultDescriptionContent(count($data)), 
                    function($carry, $line) {
                        $carry[] = array(strip_tags($line));
                        return $carry;
                    },
                    array() 
                ), $xlsdata); // prepend
                $ext = \COREPOS\Fannie\API\data\DataConvert::excelFileExtension();
                $ret = \COREPOS\Fannie\API\data\DataConvert::arrayToExcel($xlsdata);
                header('Content-Type: application/ms-excel');
                header('Content-Disposition: attachment; filename="'
                    . $this->header . '.' . $ext . '"');
                break;
        }

        $this->multi_counter++;
        $this->header_index++;
        return $ret;
    }

    /**
       Convert keyed array to numerical
       indexes and maintain order
    */
    public function dekey_array($arr)
    {
        $ret = array();
        foreach($arr as $outer_key => $row) {
            $record = array();
            foreach($row as $key => $val) {
                $record[] = $val;
            }
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
    public function htmlLine($row, $header=False)
    {
        $url = $this->config->get('URL');
        $meta = 0;
        if (isset($row['meta'])) {
            $meta = $row['meta'];
            unset($row['meta']);
        }

        $ret = "\t<tr";
        if (($meta & self::META_CHART_DATA) != 0) {
            $ret .= ' class="d3ChartData"';
        }
        $ret .= ">\n";

        $tag = $header ? 'th' : 'td';

        if (($meta & self::META_BOLD) != 0) {
            $tag = 'th';
        }
        if (($meta & self::META_BLANK) != 0) {
            $ret = "</tbody>\n<tbody>\n\t<tr>\n";
            $header1 = $this->select_headers(False);
            // just using headers as a column count
            $row = array_map(function ($item) { return null; }, $header1);
        }
        $color_styles = '';
        if (($meta & self::META_COLOR) != 0) {
            if (isset($row['meta_background'])) {
                $color_styles .= 'background-color:' . $row['meta_background'] . ';';
                unset($row['meta_background']);
            }
            if (isset($row['meta_foreground'])) {
                $color_styles .= 'color:' . $row['meta_foreground'] . ';';
                unset($row['meta_foreground']);
            }
        }
        if (($meta & self::META_REPEAT_HEADERS) != 0) {
            $ret = "</tbody>\n<tbody>\n\t<tr>\n";
            $tag = 'th';
            $row = array();
            $header1 = $this->select_headers(true);
            $row = array_filter($header1, function ($item) { return true; });
        }

        $date = false;
        $trans = false;
        /* After removing HTML, the cell will be seen as a number
         *  and aligned right if it matches this pattern:
         * Optional leading $, optionally with space(s) after
         * Optional - sign
         * A digit
         * Possibly more decimal points, commas or digits
         * Optionally trailing %, optionally with space(s) before
         */
        $numberPattern = '/^(\$ *)?(-)?(\d)([.,\d]*)( *%)?$/';
        for($i=0;$i<count($row);$i) {
            $span = 1;
            while(array_key_exists($i+$span,$row) && $row[$i+$span] === null && ($i+$span)<count($row)) {
                $span++;
            }
            $styles = $color_styles;
            if ($row[$i] === "" || $row[$i] === null) {
                $row[$i] = '&nbsp;';
            } elseif (is_numeric($row[$i]) && strlen($row[$i]) == 13) {
                // auto-link UPCs to edit tool
                $row[$i] = \COREPOS\Fannie\API\lib\FannieUI::itemEditorLink($row[$i]);
            } else if (!$header && !$date && preg_match('/^\d\d\d\d-\d\d-\d\d$/', $row[$i])) {
                // cell contains a date column
                $date = $row[$i];
            } elseif (!$header && !$date && preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $row[$i])) {
                // cell contains a date column
                list($date, $time) = explode(' ', $row[$i]);
            } else if ($date && !$trans && preg_match('/^\d+-\d+-\d+$/', $row[$i])) {
                // row contains a trans_num column & a date column
                // auto-link to reprint receipt
                $trans = $row[$i];
                $row[$i] = sprintf('<a href="%sadmin/LookupReceipt/RenderReceiptPage.php?date=%s&amp;receipt=%s"
                                       target="_rp_%s_%s">%s</a>',
                                    $url, $date, $row[$i],
                                    $date, $row[$i], $row[$i]);
            } else {
                if (preg_match($numberPattern, strip_tags($row[$i]))) {
                    $styles .= 'text-align:right;';
                }
            }

            $class = 'class="reportColumn'.$i;
            if (($meta & self::META_CHART_DATA) != 0) {
                if ($i == $this->chart_label_column) {
                    $class .= ' d3Label ';
                } else if (is_array($this->chart_data_columns) && 
                    (count($this->chart_data_columns) == 0 ||
                    in_array($i, $this->chart_data_columns))
                ) {
                    $class .= ' d3Data ';
                }
            }
            if ($header) {
                $class .= ' thead ';
            }
            $class .= '"';

            $ret .= "\t\t<" . $tag . ' ' . $class . ' style="' . $styles . '" colspan="' . $span . '">' . "\n"
                . "\t\t\t" . $row[$i] . "\n"
                . "\t\t</" . $tag . ">\n";
            $i += $span;
        }
        $ret .= "\t</tr>\n";
        if (($meta & self::META_REPEAT_HEADERS) != 0) {
            $ret .= "</tbody>\n<tbody>\n";
        } elseif (($meta & self::META_BLANK) != 0) {
            $ret .= "</tbody>\n";
        }

        return $ret;

    }

    /**
      Turn array into CSV line
      @param $row an array of data
      @return CSV string
    */
    public function csvLine($row, $separator=',')
    {
        $meta = 0;
        if (isset($row['meta'])) {
            $meta = $row['meta'];
            unset($row['meta']);
        }
        if (($meta & self::META_BLANK) != 0) {
            $header1 = $this->select_headers(False);
            // just using headers as a column count
            $row = array_map(function ($val) { return null; }, $header1);
        }
        if (($meta & self::META_REPEAT_HEADERS) != 0) {
            $header1 = $this->select_headers(True);
            $row = array_map(function ($val) { return strip_tags($val); }, $header1);
        }
        $ret = "";
        foreach($row as $item) {
            $item = $this->excelFormat($item);
            $ret .= '"'.$item.'"' . $separator;
        }
        $ret = substr($ret,0,strlen($ret)-1)."\r\n";

        return $ret;
    }

    /**
     * Remove formatting from cell contents for Excel formats
    */
    public function excelFormat($item, $style='')
    {
        if ($style == '' && strpos('csv|xls',$this->report_format) !== False) {
            $style = $this->report_format;
        }
        $item = strip_tags($item);
        if ($style == 'csv' || $style == 'txt') {
            $item = str_replace('"','',$item);
        }
        // '$ 12.39' -> '12.39' or '$ -12.39' -> '-12.39'
        $item = preg_replace('/^\$ *(\d|-)/',"$1",$item);
        // '12.39 %' -> '12.39'
        // should this divide by 100 when stripping the % sign?
        $item = preg_replace("/(\d) *%$/","$1",$item);
        // 1,000 -> 1000
        $item = preg_replace("/(\d),(\d\d\d)/","$1$2",$item);
        return $item;
    }

    /**
      Apply meta rules to XLS data
    */
    public function xlsMeta($data)
    {
        $fixup = array();
        foreach($data as $row) {
            $meta = 0;
            if (isset($row['meta'])) {
                $meta = $row['meta'];
                unset($row['meta']);
            }
            if (($meta & self::META_BLANK) != 0) {
                $row = array();
                $header1 = $this->select_headers(False);
                // just using headers as a column count
                $row = array_map(function($i){ return null; }, $header1);
            }
            if (($meta & self::META_REPEAT_HEADERS) != 0) {
                $header1 = $this->select_headers(True);
                $row = array_map(function($i){ return strip_tags($i); }, $header1);
            }
            $fixup[] = $row;
        }

        return $fixup;
    }

    /**
      Helper: check default export args
    */
    protected function formatCheck()
    {
        if (FormLib::get('excel') === 'xls') {
            $this->report_format = 'xls';
            $this->window_dressing = false;
            /**
              Verify whether Excel support is available. If it is not,
              fall back to CSV output. Should probably
              generate some kind of log message or notification.
            */
            if (!\COREPOS\Fannie\API\data\DataConvert::excelSupport()) {
                $this->report_format = 'csv';
            }
        } elseif (FormLib::get('excel') === 'csv') {
            $this->report_format = 'csv';
            $this->window_dressing = false;
        } elseif (FormLib::get('excel') === 'txt') {
            $this->report_format = 'txt';
            $this->window_dressing = false;
        }
    }

    /**
      Set the form value container
      @param [ValueContainer] $f
      Accepts a generic ValueContainer instead of a FormValueContainer
      so that unit tests can inject preset values 
    */
    public function setForm(COREPOS\common\mvc\ValueContainer $f)
    {
        $this->form = $f;
    }

    public function requiredFields()
    {
        return $this->required_fields;
    }

    /**
      Check for input and display the page
    */
    function drawPage()
    {
        if (!($this->config instanceof FannieConfig)) {
            $this->config = FannieConfig::factory();
        }

        if (!$this->checkAuth() && $this->must_authenticate) {
            $this->loginRedirect();
        } elseif ($this->preprocess()) {

            /** Use FanniePage::drawPage for the plain old html
                version of the page
            */
            if ($this->content_function == 'form_content') {
                if (FormLib::get('json') !== '') {
                    $this->addOnloadCommand(FormLib::fieldJSONtoJavascript(base64_decode(FormLib::get('json'))));
                }
                return parent::drawPage();
            }

            /**
              Global setting overrides default behavior
              to force the menu to appear.
              Unlike normal pages, the override is only applied
              when the output format is HTML.
            */
            if (($this->config->get('WINDOW_DRESSING') || $this->new_tablesorter) && $this->report_format == 'html') {
                $this->window_dressing = true;
            }
            
            if ($this->window_dressing) {
                echo $this->getHeader();
            }

            if ($this->readinessCheck() !== false) {
                $func = $this->content_function;
                echo $this->$func();
            } else {
                echo $this->errorContent();
            }

            if ($this->window_dressing) {
                $footer = $this->getFooter();
                $footer = str_ireplace('</html>','',$footer);
                $footer = str_ireplace('</body>','',$footer);
                echo $footer;
            }

            if ($this->report_format == 'html') {
                foreach($this->scripts as $s_url => $s_type) {
                    printf('<script type="%s" src="%s"></script>',
                        $s_type, $s_url);
                    echo "\n";
                }
                
                $js_content = $this->javascriptContent();
                if (!empty($js_content) || !empty($this->onload_commands)) {
                    echo '<script type="text/javascript">';
                    echo $js_content;
                    echo "\n\$(document).ready(function(){\n";
                    foreach ($this->onload_commands as $oc) {
                        if (strstr($oc, 'standardFieldMarkup()')) {
                            continue;
                        }
                        echo $oc."\n";
                    }
                    echo "});\n";
                    echo '</script>';
                }

                $page_css = $this->cssContent();
                if (!empty($page_css)) {
                    echo '<style type="text/css">';
                    echo $page_css;
                    echo '</style>';
                }

                echo array_reduce($this->css_files,
                    function ($carry, $css_url) {
                        return $carry . sprintf('<link rel="stylesheet" type="text/css" href="%s">' . "\n",
                                        $css_url);
                    },
                    ''
                );
            }

            if ($this->window_dressing || $this->report_format == 'html') {
                echo '</body></html>';
            }
        }

    // drawPage()
    }

    function draw_page()
    {
        $this->drawPage();
    }

    public function baseUnitTest($phpunit)
    {
        $this->bodyContent();
        foreach (array('html', 'csv', 'txt') as $format) {
            $this->report_format = $format;
            $phpunit->assertNotEquals(0, strlen($this->report_content()));
        }
    }

}

