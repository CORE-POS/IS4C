<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class GumMailingList extends FannieReportPage 
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report

    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    protected $required_fields = array();
    protected $report_headers = array('Mem#', 'First Name', 'Last Name', 'Address', 'City', 'State', 'Zip', 'Phone', 'Email');

    public function preprocess()
    {
        $this->header = 'Loan and Equity Mailing List';
        $this->title = 'Loan and Equity Mailing List';

        return parent::preprocess();
    }
    
    public function fetch_report_data()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        // compound interest calculation is MySQL-specific
        $query = 'SELECT c.CardNo AS card_no,
                    c.FirstName, 
                    c.LastName,
                    m.street,
                    m.city,
                    m.state,
                    m.zip,
                    m.phone,
                    m.email_1
                  FROM ' . $FANNIE_OP_DB . $dbc->sep() . 'custdata AS c
                        LEFT JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'meminfo AS m ON c.CardNo=m.card_no
                  WHERE c.personNum=1
                    AND (
                        c.CardNo IN (SELECT card_no FROM GumLoanAccounts)
                        OR
                        c.CardNo IN (SELECT card_no FROM GumEquityShares)
                    )
                  ORDER BY c.CardNo';
        $result = $dbc->query($query);

        $data = array();
        while($row = $dbc->fetch_row($result)) {
            $record = array(
               $row['card_no'],
               $row['LastName'],
               $row['FirstName'],
               $row['street'],
               $row['city'],
               $row['state'],
               $row['zip'],
               $row['phone'],
               $row['email_1'],
            );
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        return '<!-- no need -->';
    }

}

FannieDispatch::conditionalExec();

