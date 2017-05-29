<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class LikeCodeList extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie : Like Code List";
    protected $header = "Like Code List";

    protected $required_fields = array('lc1');

    public $description = '[Like Code List] shows all the items in a set of likecodes';
    public $report_set = 'Operational Data';
    protected $report_headers = array('LC#', 'Like Code Name', 'Item UPC', 'Item Description', 'Last Sold');
    protected $no_sort_but_style = true;
    protected $sortable = false;

    /**
      Lots of options on this report.
    */
    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $start = $this->form->lc1;
        $end = FormLib::get('lc2', false);
        if ($end === false) {
            $end = $start;
        }

        $prep = $dbc->prepare("
            SELECT u.likeCode,
                l.likeCodeDesc,
                p.upc,
                MAX(p.description) AS descript,
                MAX(p.last_sold) AS ls
            FROM upcLike AS u
                INNER JOIN likeCodes AS l ON l.likeCode=u.likeCode
                INNER JOIN products AS p ON p.upc=u.upc
            WHERE u.likeCode BETWEEN ? AND ?
            GROUP BY u.likeCode,
                l.likeCodeDesc,
                p.upc
            ORDER BY u.likeCode");
        $res = $dbc->execute($prep, array($start, $end));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['likeCode'],
                $row['likeCodeDesc'],
                $row['upc'],
                $row['descript'],
                ($row['ls']===null ? 'n/a' : $row['ls']),
            );
        }

        return $data;
    }

    public function form_content()
    {
        $this->addScript($this->config->get('URL') . 'src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile($this->config->get('URL') . 'src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('.chosen-select').chosen();\n");

        $model = new LikeCodesModel($this->connection);
        $opts = $model->toOptions();

        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Starting Like Code</label>
        <select name="lc1" class="form-control chosen-select">
            {$opts}
        </select>
    </div>
    <div class="form-group">
        <label>Ending Like Code</label>
        <select name="lc2" class="form-control chosen-select">
            {$opts}
        </select>
    </div>
    <div class="form-group">
        <button class="btn btn-submit btn-core">Get List</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

