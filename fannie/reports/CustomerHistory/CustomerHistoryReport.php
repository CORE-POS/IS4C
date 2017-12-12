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

class CustomerHistoryReport extends FannieReportPage 
{
    public $description = '[Customer History] lists changes made to a given customer account over time.';
    public $report_set = 'Operational Data';

    protected $title = "Fannie : Customer History";
    protected $header = "Customer History Report";
    protected $report_headers = array('Date & Time', 'Editor ID', 'Editor Name', 'First Name', 'Last Name', 'Primary', 'Discount', 'Phone', 'Alt. Phone', 'Email');
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
            return array(sprintf('<a href="AccountHistoryReport.php?id=%d">Account History</a>', $id)); 
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
                c.firstName,
                c.lastName,
                c.discount,
                c.accountHolder,
                c.phone,
                c.altPhone,
                c.email,
                u.name
            FROM " . FannieDB::fqn('UpdateCustomerLog', 'op') . " AS c
                LEFT JOIN " . FannieDB::fqn('Users', 'op') . " AS u ON c.userID=u.uid
            WHERE c.cardNo=?
            ORDER BY c.modified DESC, c.accountHolder DESC, c.lastName, c.firstName");
        $res = $dbc->execute($prep, array($id));
        $data = array();
        $last = false;
        while ($row = $dbc->fetchRow($res)) {
            $time = strtotime($row['modified']);
            if ($last !== false && $time !== false && $last - $time > 5) {
                $data[] = array('meta' => FannieReportPage::META_BLANK);
            }
            $last = $time;
            $data[] = array(
                $row['modified'],
                $row['userID'],
                $row['name'] !== null ? $row['name'] : 'n/a',
                $row['firstName'],
                $row['lastName'],
                $row['accountHolder'],
                $row['discount'] !== null ? $row['discount'] : '',
                $row['phone'] !== null ? $row['phone'] : '',
                $row['altPhone'] !== null ? $row['altPhone'] : '',
                $row['email'] !== null ? $row['email'] : '',
            );
        }

        return $data;
    }

    public function form_content()
    {
        return '
            <form method="get" action="CustomerHistoryReport.php">
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

