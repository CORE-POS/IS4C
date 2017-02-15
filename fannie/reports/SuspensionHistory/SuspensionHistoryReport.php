<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SuspensionHistoryReport extends FannieReportPage 
{
    public $description = '[Suspension History] lists when a membership was deactivated &amp; reactivated.';
    public $report_set = 'Membership';
    public $themed = true;

    protected $title = "Fannie : Suspension History";
    protected $header = "Suspension History";
    protected $sort_direction = 1;
    protected $report_headers = array('Date', 'Reason', 'User');
    protected $required_fields = array('memNum');

    public function report_description_content()
    {
        return array('History for account #'.$this->form->memNum);
    }


    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $q = $dbc->prepare("select username,postdate,post,textStr
                from suspension_history AS s 
                LEFT JOIN reasoncodes AS r ON
                s.reasoncode & r.mask > 0
                WHERE s.cardno=? ORDER BY postdate DESC");
        $r = $dbc->execute($q,array($this->form->memNum));
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
        $this->add_onload_command('$(\'#memNum\').focus()');
        return '<form method="get" action="SuspensionHistoryReport.php">
            <label>Member #</label>
            <input type="text" name="memNum" required 
                class="form-control" id="memNum" />
            <p>
            <button type="submit" class="btn btn-default">Get Report</button> 
            </p>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            Lists all changes to a membership\'s
            active/inactive status.
            </p>';
    }
}

FannieDispatch::conditionalExec();

