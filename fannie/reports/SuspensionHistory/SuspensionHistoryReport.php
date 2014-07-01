<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SuspensionHistoryReport extends FannieReportPage 
{
    public $description = '[Suspension History] lists when a membership was deactivated &amp; reactivated.';
    public $report_set = 'Membership';

    protected $title = "Fannie : Suspension History";
    protected $header = "Suspension History";
    protected $sort_direction = 1;
    protected $report_headers = array('Date', 'Reason', 'User');
    protected $required_fields = array('memNum');

    public function preprocess()
    {
        $this->card_no = FormLib::get('memNum','');

        return parent::preprocess();
    }

    public function report_description_content()
    {
        return array('History for account #'.$this->card_no);
    }


    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $q = $dbc->prepare_statement("select username,postdate,post,textStr
                from suspension_history AS s 
                LEFT JOIN reasoncodes AS r ON
                s.reasoncode & r.mask > 0
                WHERE s.cardno=? ORDER BY postdate DESC");
        $r = $dbc->exec_statement($q,array($this->card_no));
        $data = array();
        while($w = $dbc->fetch_row($r)){
            $record = array(
                $w['postdate'],
                (!empty($w['textStr']) ? $w['textStr'] : $w['post']),
                $w['username'],
            );
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        return '<form method="get" action="SuspensionHistoryReport.php">
            <b>Member #</b> <input type="text" name="memNum" value="" size="6" />
            <br /><br />
            <input type="submit" value="Get Report" />
            </form>';
    }
}

FannieDispatch::conditionalExec();

?>
