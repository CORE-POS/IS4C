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
class GumEquityReport extends FannieReportPage 
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report

    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    protected $required_fields = array();
    protected $report_headers = array('Type', '# of Shares', 'Value');

    protected $sort_direction = 1;
    protected $multi_report_mode = true;

    public function preprocess()
    {
        $this->header = 'Active Loan Report';
        $this->title = 'Active Loan Report';

        return parent::preprocess();
    }
    
    public function fetch_report_data()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        // compound interest calculation is MySQL-specific
        $query = 'SELECT 
                    CASE WHEN shares > 0 THEN \'PURCHASE\' ELSE \'PAYOFF\' END as stype,
                    SUM(shares) AS totalS,
                    SUM(value) AS totalV
                  FROM GumEquityShares
                  GROUP BY CASE WHEN shares > 0 THEN \'PURCHASE\' ELSE \'PAYOFF\' END 
                  ORDER BY CASE WHEN shares > 0 THEN \'PURCHASE\' ELSE \'PAYOFF\' END DESC';
        $result = $dbc->query($query);

        $reports = array();
        $data = array();
        while($row = $dbc->fetch_row($result)) {
            $record = array(
               $row['stype'],
               sprintf('%d', $row['totalS']), 
               sprintf('%.2f', $row['totalV']), 
            );
            $data[] = $record;
        }
        $reports[] = $data;

        $data = array();
        $model = new GumEquitySharesModel($dbc);
        foreach ($model->find('tdate') as $obj) {
            $record = array(
                date('Y-m-d', strtotime($obj->tdate())),
                $obj->trans_num(),
                $obj->card_no(),
                $obj->shares(),
                sprintf('%.2f', $obj->value()),
            );
            $data[] = $record;
        }
        $reports[] = $data;

        return $reports;
    }

    private $first = true;
    public function assign_headers()
    {
        if ($this->first) {
            $this->first = false;
        } else {
            $this->report_headers = array('Date', 'Receipt', 'Owner', '# of Shares', 'Value');
        }
    }
    
    public function calculate_footers($data) {
        if (count($data[0]) == 3) {
            $sumS = 0;
            $sumV = 0;
            foreach($data as $row) {
                $sumS += $row[1];
                $sumV += $row[2];
            }

            return array('Balance', $sumS, sprintf('%.2f', $sumV));
        } else {
            $sumS = 0;
            $sumV = 0;
            foreach($data as $row) {
                $sumS += $row[3];
                $sumV += $row[4];
            }

            return array('Total', '', '', $sumS, sprintf('%.2f', $sumV));
        }
    }

    public function form_content()
    {
        return '<!-- no need -->';
    }
}

FannieDispatch::conditionalExec();

