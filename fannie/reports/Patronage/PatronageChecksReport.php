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

class PatronageChecksReport extends FannieReportPage 
{
    public $description = '[Patronage Checks] reports how many checks have been cashed for a given year.';
    public $report_set = 'Membership :: Patronage';
    public $themed = true;

    protected $header = "Patronage Checks Report";
    protected $title = "Fannie : Patronage Checks Report";

    protected $required_fields = array('fy');
    protected $report_headers = array('Date', 'Cashed Elsewhere', 'Cashed Here', 'Total');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $fy = $this->form->fy;
        $date1 = FormLib::get('date1');
        $date2 = FormLib::get('date2');
        
        $args = array($fy);
        $query = '
            SELECT cashed_here,
                ' . $dbc->dateymd('cashed_date') . ' AS tdate,
                COUNT(*) AS num
            FROM patronage AS p
            WHERE p.FY = ?';
        if (!empty($date1) && !empty($date2)) {
            $query .= ' AND p.cashed_date BETWEEN ? AND ? ';
            $args[] = $date1 . ' 00:00:00';
            $args[] = $date2 . ' 23:59:59';
        }
        $query .= '
            GROUP BY ' . $dbc->dateymd('cashed_date') . ', cashed_here
            ORDER BY ' . $dbc->dateymd('cashed_date');
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($w = $dbc->fetch_row($res)) {
            if (!isset($data[$w['tdate']])) {
                $data[$w['tdate']] = array(0, 0);
            }
            $data[$w['tdate']][$w['cashed_here']] = $w['num'];
        }
        $real_data = array();
        foreach ($data as $date => $counts) {
            $real_data[] = array(
                date('Y-m-d', strtotime($date)),
                $counts[0],
                $counts[1],
                $counts[0]+$counts[1],
            );
        }

        return $real_data;
    }

    public function calculate_footers($data)
    {
        $sums = array(0, 0, 0);
        foreach ($data as $row) {
            $sums[0] += $row[1];
            $sums[1] += $row[2];
            $sums[2] += $row[3];
        }

        return array_merge(array('Totals'), $sums);
    }

    public function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">
            <div class="col-sm-5">
                <div class="form-group">
                    <label>FY</label>
                    <select name="fy" class="form-control">';
        $q = '
            SELECT FY
            FROM patronage
            GROUP BY FY
            ORDER BY FY DESC';
        $r = $dbc->query($q);
        while ($w = $dbc->fetchRow($r)) {
            $ret .= sprintf('<option>%s</option>', $w['FY']);
        }
        $ret .= '
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="text" name="date1" class="form-control date-field" id="date1" />
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="text" name="date2" class="form-control date-field" id="date2" />
                </div>
            </div>
            <div class="col-sm-5">
                ' . FormLib::dateRangePicker() . '
                <p>
                    <button type="submit" class="btn btn-default">Get Report</button>
                </p>
            </div>
        </form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            List information about patronage checks
            cashed at the store or elsewhere over a given
            date range.
            </p>';
    }
}

FannieDispatch::conditionalExec();

