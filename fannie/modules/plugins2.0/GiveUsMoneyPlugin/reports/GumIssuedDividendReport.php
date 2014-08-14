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
class GumIssuedDividendReport extends FannieReportPage 
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report

    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    protected $required_fields = array('endDate');
    protected $report_headers = array('Account#', 'Equity Value', 'Days Held', 'Rate', 'Dividend', 'Check Number');

    public function preprocess()
    {
        $this->header = 'Issued Dividend Report';
        $this->title = 'Issued Dividend Report';

        return parent::preprocess();
    }

    public function report_description_content()
    {
        $dt = FormLib::get('endDate', date('Y-m-d'));
        return array('FY ending ' . $dt);
    }
    
    public function fetch_report_data()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $dt = FormLib::get('endDate', date('Y-m-d'));

        $dividends = new GumDividendsModel($dbc);
        $dividends->yearEndDate($dt);
        $map = new GumDividendPayoffMapModel($dbc);
        $check = new GumPayoffsModel($dbc);

        $data = array();
        foreach ($dividends->find('card_no') as $dividend) {
            $record = array(
                $dividend->card_no(),
                sprintf('%.2f', $dividend->equityAmount()),
                $dividend->daysHeld(),
                sprintf('%.2f%%', $dividend->dividendRate() *100),
                sprintf('%.2f', $dividend->dividendAmount()),
            );
            $checkID = false;
            $map->reset();
            $map->gumDividendID($dividend->gumDividendID());
            foreach ($map->find('gumPayoffID', true) as $obj) {
                $checkID = $obj->gumPayoffID();
            }
            if (!$checkID) {
                $record[] = '?';
            } else {
                $check->gumPayoffID($checkID);
                $check->load();
                $record[] = $check->checkNumber();
            }

            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">';
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
        $ret .= '<input type="submit" value="Get Report" />';
        $ret .= '</form>';

        $this->add_onload_command('$(\'#endDate\').datepicker();');

        return $ret;
    }

}

FannieDispatch::conditionalExec();

