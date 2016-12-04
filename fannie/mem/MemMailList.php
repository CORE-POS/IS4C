<?php 
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}

class MemMailList extends FannieReportPage
{
    protected $title = 'Mailing List';
    protected $header = 'Mailing List';

    protected $required_fields = array('type');
    protected $report_headers = array('Mem#', 'Last Name', 'First Name', 'Address', 'Address2', 'City', 'State', 'Zip', 'Phone', 'Email');
    public $description = '[Member Mailing List] lists contact information for selected customers.';

    protected function selectFrom()
    {
        return "
               SELECT CardNo, 
                  LastName, 
                  FirstName, 
                  street,
                  city,
                  state,
                  zip,
                  phone,
                  memType,
                  end_date,
                  email_1
              FROM custdata AS c
              LEFT JOIN meminfo AS m
              ON c.CardNo=m.card_no
              LEFT JOIN memDates AS d
              ON c.CardNo=d.card_no ";
    }

    protected function where($dbc, $type)
    {
         $ret = " WHERE ";

         switch ($type) {
             case 'Members':
             default:
                $ret .= "c.Type='PC'
                  AND (end_date > ".$dbc->now()." 
                    or end_date = '' 
                    or end_date is null
                    or end_date='1900-01-01 00:00:00'
                    or end_date='0000-00-00 00:00:00')
                  AND ads_OK = 1
                  AND personNum = 1
                  AND LastName <> 'NEW MEMBER'
                  AND LastName <> 'NEW WEB MEMBER'
                  order by m.card_no";
                break;
             case 'Members (All)':
                $ret .= "c.Type='PC'
                  AND (end_date > ".$dbc->now()." 
                    or end_date = '' 
                    or end_date is null
                    or end_date='1900-01-01 00:00:00'
                    or end_date='0000-00-00 00:00:00')
                  AND personNum = 1
                  AND LastName <> 'NEW MEMBER'
                  AND LastName <> 'NEW WEB MEMBER'
                  order by m.card_no";
                break;
             case 'Business':
                $ret .= "c.memType=2
                  AND personNum = 1
                  AND LastName <> 'NEW MEMBER'
                  order by m.card_no";
                break;
         }

         return $ret;
    }

    public function fetch_report_data()
    {
        try {
            $dbc = $this->connection;
            $dbc->selectDB($this->config->get('OP_DB'));

            $query = $this->selectFrom()
                . $this->where($dbc, $this->form->type);
        } catch (Exception $ex) {
            return array();
        }
        $result = $dbc->query($query);
        $data = array();

        while ($row = $dbc->fetch_row($result)) {
            list($street, $addr2) = array_pad(explode("\n", $row['street'], 2), 2, null);
            $data[] = array(
                $row['CardNo'],
                $row['LastName'],
                $row['FirstName'] === null ? '' : $row['FirstName'],
                $street,
                $addr2 === null ? '' : $addr2,
                $row['city'],
                $row['state'],
                $row['zip'],
                $row['phone'],
                $row['email_1'] === null ? '' : $row['email_1'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Mailing List Type</label>
        <select class="form-control" name="type">
            <option>Members</option>
            <option>Members (All)</option>
            <option>Business</option>
        </select>
    </div>
    <div class="form-group">
        <label>Format</label>
        <select name="excel" class="form-control">
            <option value="csv">Excel (CSV)</option> 
            <option value="csv">Excel (XLS)</option> 
            <option value="html">Web page</option>
        </select>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get List</button>
    </div>
</form>
HTML;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->form_content()));
        $form = new COREPOS\common\mvc\ValueContainer();
        foreach (array('Members', 'Members (All)', 'Business') as $type) {
            $form->type = $type;
            $this->setForm($form);
            $phpunit->assertInternalType('array', $this->fetch_report_data());
        }
    }
}

FannieDispatch::conditionalExec();

