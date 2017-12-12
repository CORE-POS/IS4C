<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class AccountHistoryReport extends FannieReportPage 
{
    public $description = '[Account History] lists changes made to a given customer account over time.';
    public $report_set = 'Operational Data';

    protected $title = "Fannie : Account History";
    protected $header = "Account History Report";
    protected $report_headers = array('Date & Time', 'Editor ID', 'Editor Name', 'Status', 'Type', 'Start', 'End', 'Address', 'Address', 'City', 'State', 'Zip', 'UPC');
    protected $required_fields = array('id');

    protected $sort_direction = 1;

    public function report_description_content()
    {
        if ($this->report_format == 'html') {
            try {
                $id = $this->form->id;
            } catch (Exception $ex) {
                return array();
            }
            return array(sprintf('<a href="CustomerHistoryReport.php?id=%d">Names on Account History</a>', $id)); 
        }
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        try {
            $id = $this->form->id;
        } catch (Exception $ex) {
            return array();
        }

        $prep = $dbc->prepare("
            SELECT c.modified,
                c.userID,
                u.name,
                c.memberStatus,
                c.customerTypeID,
                c.startDate,
                c.endDate,
                c.addressFirstLine,
                c.addressSecondLine,
                c.city,
                c.state,
                c.zip,
                c.idCardUPC
            FROM " . FannieDB::fqn('UpdateAccountLog', 'op') . " AS c
                LEFT JOIN " . FannieDB::fqn('Users', 'op') . " AS u ON c.userID=u.uid
            WHERE c.cardNo=?");
        $res = $dbc->execute($prep, array($id));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['modified'],
                $row['userID'],
                $row['name'] !== null ? $row['name'] : 'n/a',
                $row['memberStatus'],
                $row['customerTypeID'],
                $row['startDate'] !== null ? $row['startDate'] : '',
                $row['endDate'] !== null ? $row['endDate'] : '',
                $row['addressFirstLine'],
                $row['addressSecondLine'],
                $row['city'],
                $row['state'],
                $row['zip'],
                $row['idCardUPC'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        return '
            <form method="get" action="AccountHistoryReport.php">
            <div class="form-group">
                <label>Account #</label>
                <input type="text" name="id" class="form-control" required />
            </div>
            <p>
                <button type="submit" class="btn btn-default">Get Report</button>
            </p>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            List audit log of changes to a given customer.
            </p>';
    }
}

FannieDispatch::conditionalExec();

