<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
class GumDividendTaxReport extends FannieReportPage 
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report

    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    protected $required_fields = array('endDate');
    protected $report_headers = array('Mem#', 'Last Name', 'First Name', 'Address', 'Address', 'City', 'State', 'Zip', 'Amount', 'Partial SSN', 'SSN');

    public function preprocess()
    {
        $this->header = 'Dividend Tax Report';
        $this->title = 'Dividend Tax Report';

        return parent::preprocess();
    }

    public function report_description_content()
    {
        $end_date = FormLib::get('endDate', date('Y-m-d'));
        return array('FY ending ' . $end_date);
    }
    
    public function fetch_report_data()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $end_date = FormLib::get('endDate', date('Y-m-d'));
        $key = FormLib::get('key');
        $privkey = openssl_pkey_get_private($key);

        $prep = $dbc->prepare('
            SELECT d.card_no,
                d.dividendAmount,
                c.LastName,
                c.FirstName,
                m.street,
                m.city,
                m.state,
                m.zip,
                CASE WHEN t.maskedTaxIdentifier IS NULL THEN \'n/a\' ELSE t.maskedTaxIdentifier END AS maskedTaxIdentifier,
                t.encryptedTaxIdentifier
            FROM GumDividends AS d
                LEFT JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'custdata AS c ON c.CardNo=d.card_no AND c.personNum=1
                LEFT JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'meminfo AS m ON m.card_no=d.card_no
                LEFT JOIN GumTaxIdentifiers AS t ON d.card_no=t.card_no
            WHERE yearEndDate = ?');
        $res = $dbc->execute($prep, array($end_date));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $record = array(
                $row['card_no'],
                $row['LastName'],
                $row['FirstName'],
            );
            list($addr1, $addr2) = explode("\n", $row['street'], 2);
            $record[] = $addr1;
            $record[] = ($addr2==null) ? '' : $addr2;
            $record[] = $row['city'];
            $record[] = $row['state'];
            $record[] = $row['zip'];
            $record[] = sprintf('%.2f', $row['dividendAmount']);
            $record[] = 'XXX-XX-' . $row['maskedTaxIdentifier'];
            $record[] = $this->unmask($row['encryptedTaxIdentifier'], $privkey);
            $data[] = $record;
        }

        return $data;
    }

    private function unmask($val, $privkey)
    {
        if (!$privkey) {
            return 'No key';
        } elseif ($val !== 'n/a') {
            $try = openssl_private_decrypt($val, $decrypted, $privkey);
            return $try ? $decrypted : openssl_error_string();
        } else {
            return 'n/a';
        }
    }

    public function report_content()
    {
        if (FormLib::get('excel') == '1099') {
            $data = $this->fetch_report_data();
            $this->reportToPdf($data);
        } else {
            return parent::report_content();
        }
    }

    private function reportToPdf($data)
    {
        $pdf = new FPDF('P', 'mm', 'Letter');
        $bridge = GumLib::getSetting('posLayer');
        $year = date('Y', strtotime(FormLib::get('endDate')));
        $count = 0;
        foreach ($data as $row) {
            if ($count % 2 == 0) {
                $pdf->addPage();
            }
            $custdata = $bridge::getCustdata($row[0]);
            $meminfo = $bridge::getMeminfo($row[0]);
            $ssn = ($row[10] == 'No key') ? $row[9] : $row[10];
            $amount = array(1 => $row[8]);
            $form = new GumTaxDividendFormTemplate($custdata, $meminfo, $ssn, $tax_year, $amount);
            $form->renderAsPDF($pdf, 15 + (($count%2)*150));
            $count++;
        }
        $pdf->Output('taxform.pdf', 'I');
    }

    public function form_content()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $ret = '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post">';
        $ret .= 'FY ending: ';
        $ret .= '<select name="endDate">';
        $years = $dbc->query('SELECT yearEndDate
                              FROM GumDividends
                              GROUP BY yearEndDate
                              ORDER BY yearEndDate DESC');
        while ($row = $dbc->fetch_row($years)) {
            $ret .= sprintf('<option>%s</option>',
                        date('Y-m-d', strtotime($row['yearEndDate'])));
        }
        $ret .= '</select>';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<select name="excel">
            <option value="">Web page</option>
            <option value="csv">CSV</option>
            <option value="xls">Excel</option>
            <option value="1099">1099</option>
            </select>';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" value="Get Report" />';
        $ret .= '<hr />';
        $ret .= '<strong>Enter Key to View Current Value</strong><br />';
        $ret .= '<textarea id="keyarea" name="key" rows="10" cols="30"></textarea>';
        $ret .= '</form>';

        $this->add_onload_command('$(\'#endDate\').datepicker();');

        return $ret;
    }

}

FannieDispatch::conditionalExec();

