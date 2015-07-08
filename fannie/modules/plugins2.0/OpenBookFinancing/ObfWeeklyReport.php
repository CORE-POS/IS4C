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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ObfWeeklyReport extends FannieReportPage
{
    protected $header = 'OBF: Weekly Report';
    protected $title = 'OBF: Weekly Report';

    public $page_set = 'Plugin :: Open Book Financing';
    public $report_set = 'Sales Reports';
    public $description = '[OBF Weekly Report] shows open book financing sales and labor data for a given week.';
    public $themed = true;

    protected $required_fields = array('weekID');

    protected $report_headers = array(
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Current Year', 'Last Year', '', '', '', '', '', '', ''),
    );

    public function report_description_content()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);
        
        $week = new ObfWeeksModel($dbc);
        $week->obfWeekID(FormLib::get('weekID'));
        $week->load();
        $start_ts = strtotime($week->startDate());
        $end_ts = mktime(0, 0, 0, date('n', $start_ts), date('j', $start_ts)+6, date('Y', $start_ts));

        return array('Week ' . date('F d, Y', $start_ts) . ' to ' . date('F d, Y', $end_ts));
    }

    public function fetch_report_data()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);
        
        $week = new ObfWeeksModel($dbc);
        $week->obfWeekID(FormLib::get('weekID'));
        $week->load();

        $colors = array(
            '#CDB49B',
            '#99C299',
            '#CDB49B',
            '#99C299',
            '#CDB49B',
            '#99C299',
            '#CDB49B',
            '#6685C2',
            '#FF4D4D',
            '#99C299',
            '#C299EB',
            '#FFB280',
            '#FFFF66',
        );
        
        $labor = new ObfLaborModel($dbc);
        $labor->obfWeekID($week->obfWeekID());
        
        /**
           Timestamps for the start and end of
           the current week
        */
        $start_ts = strtotime($week->startDate());
        $end_ts = mktime(0, 0, 0, date('n', $start_ts), date('j', $start_ts)+6, date('Y', $start_ts));

        /**
          Determine which month a given week falls in.
          If the first and last day of the week are not
          in the same month, choose whichever month
          4+ days of the week belong to
        */
        $month = false;
        $year = false;
        if (date('n', $start_ts) == date('n', $end_ts)) {
            $month = date('n', $start_ts);
            $year = date('Y', $start_ts);
        } else {
            $split = 0;
            for ($i=0; $i<7; $i++) {
                $ts = mktime(0, 0, 0, date('n', $start_ts), date('j', $start_ts)+$i, date('Y', $start_ts));
                if (date('n', $start_ts) == date('n', $ts)) {
                    $split++;
                }
            }
            if ($split >= 4) {
                $month = date('n', $start_ts);
                $year = date('Y', $start_ts);
            } else {
                $month = date('n', $end_ts);
                $year = date('Y', $end_ts);
            }
        }
        /**
          Use the entire month from the previous calendar year
          as the time period for year-over-year comparisons
        */
        $start_ly = mktime(0, 0, 0, $month, 1, $year-1);
        $end_ly = mktime(0, 0, 0, $month, date('t', $start_ly), $year-1);

        $future = $end_ts >= strtotime(date('Y-m-d')) ? true: false;

        /**
          Sales information is cached to avoid expensive
          aggregate queries
        */
        $sales = new ObfSalesCacheModel($dbc);
        $sales->obfWeekID($week->obfWeekID());
        $sales->actualSales(0, '>');
        $num_cached = $sales->find();
        if (count($num_cached) == 0) {
            $sales->reset();
            $sales->obfWeekID($week->obfWeekID());
            /**
              Lookup total sales for each category
              in a given date range
            */
            $salesQ = 'SELECT 
                        m.obfCategoryID as id,
                        m.superID,
                        SUM(t.total) AS sales
                       FROM __table__ AS t
                        INNER JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'superdepts AS s
                            ON t.department=s.dept_ID
                        INNER JOIN ObfCategorySuperDeptMap AS m
                            ON s.superID=m.superID
                        LEFT JOIN ObfCategories AS c
                            ON m.obfCategoryID=c.obfCategoryID
                       WHERE c.hasSales=1
                        AND t.tdate BETWEEN ? AND ?
                        AND t.trans_type IN (\'I\', \'D\')
                       GROUP BY m.obfCategoryID, m.superID';

            /**
              Lookup number of transactions 
              in a given date range
            */
            $transQ = 'SELECT 
                        YEAR(t.tdate) AS year,
                        MONTH(t.tdate) AS month,
                        DAY(t.tdate) AS day,
                        t.trans_num
                       FROM __table__ AS t
                        INNER JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'superdepts AS s
                            ON t.department=s.dept_ID
                        INNER JOIN ObfCategorySuperDeptMap AS m
                            ON s.superID=m.superID
                       WHERE 
                        t.tdate BETWEEN ? AND ?
                        AND t.trans_type IN (\'I\', \'D\')
                        AND t.upc <> \'RRR\'
                       GROUP BY 
                        YEAR(t.tdate),
                        MONTH(t.tdate),
                        DAY(t.tdate),
                        t.trans_num';

            /**
              Lookup tables for current week and
              year-over-year comparison
            */
            $dlog1 = DTransactionsModel::selectDlog(date('Y-m-d', $start_ts), date('Y-m-d', $end_ts));
            $dlog2 = DTransactionsModel::selectDlog(date('Y-m-d', $start_ly), date('Y-m-d', $end_ly));
            $args = array(date('Y-m-d 00:00:00', $start_ts), date('Y-m-d 23:59:59', $end_ts));

            /**
              Lookup number of transactions for the current
              week and save that information if the week
              is complete
            */
            $trans1Q = str_replace('__table__', $dlog1, $transQ);
            $transP = $dbc->prepare($trans1Q);
            $transR = $dbc->execute($transP, $args);
            if (!$future && $transR) {
                $sales->transactions($dbc->num_rows($transR));
            } else {
                $sales->transactions(0);
            }

            /**
              Lookup sales for the current week. Actual sales
              is zeroed out until the week is complete, but
              the records are saved as placeholders for later
            */
            $oneQ = str_replace('__table__', $dlog1, $salesQ);
            $oneP = $dbc->prepare($oneQ);
            $oneR = $dbc->execute($oneP, $args);
            while($w = $dbc->fetch_row($oneR)) {
                $sales->obfCategoryID($w['id']);
                $sales->superID($w['superID']);
                $sales->actualSales($w['sales']);
                if ($future) {
                    $sales->actualSales(0);
                }
                $sales->growthTarget($week->growthTarget());
                $sales->save();
            }
            
            /**
              Now lookup year-over-year info
              Since it examines a whole month rather than a single
              week, we'll take the average and then extend
              that out to seven days
            */
            $sales->reset();
            $sales->obfWeekID($week->obfWeekID());
            $args = array(date('Y-m-d 00:00:00', $start_ly), date('Y-m-d 23:59:59', $end_ly));
            $num_days = (float)date('t', $start_ly);

            /**
              Transactions last year, pro-rated
            */
            $trans2Q = str_replace('__table__', $dlog2, $transQ);
            $transP = $dbc->prepare($trans2Q);
            $transR = $dbc->execute($transP, $args);
            if ($transR) {
                $month_trans = $dbc->num_rows($transR);
                $avg_trans = ($month_trans / $num_days) * 7;
                $sales->lastYearTransactions($avg_trans);
            } else {
                $sales->lastYearTransactions(0);
            }

            /**
              Sales last year, pro-rated
            */
            $twoQ = str_replace('__table__', $dlog2, $salesQ);
            $twoP = $dbc->prepare($twoQ);
            $twoR = $dbc->execute($twoP, $args);
            while ($w = $dbc->fetch_row($twoR)) {
                $sales->obfCategoryID($w['id']);
                $sales->superID($w['superID']);
                $avg_sales = ($w['sales'] / $num_days) * 7;
                $sales->lastYearSales($avg_sales);
                if ($future) {
                    $sales->actualSales(0);
                    $sales->growthTarget($week->growthTarget());
                }
                $sales->save();
            }
        }

        // record set to return
        $data = array();                

        /**
          Information about sales
          - thisYear => sales for the current week
          - lastYear => sales for the same week last year
          - projected => planned sales for the current week
                         based on sales growth goals
          - trend => expected sales for the current week based
                     on recent history sales trends
          - quarterActual => actual sales for the quarter
          - quarterProjected => planned sales for the quarter based
                                on sales growth goals
          - quarterLaborSales => actual sales for the quarter as
                                 defined by labor measurements

          "Quarter" is not necessarily a calendar quarter. It's
          whatever period is currently defined for the "long-term"
          over under column. This period can be defined separately
          for sales and labor. A separate sales number is always
          tracked in concert with the long-term labor period so that
          the long-term sales per labor hour number makes sense.
        */
        $total_sales = new stdClass();
        $total_sales->thisYear = 0.0;
        $total_sales->lastYear = 0.0;
        $total_sales->projected = 0.0;
        $total_sales->trend = 0.0;
        $total_sales->quarterActual = 0.0;
        $total_sales->quarterProjected = 0.0;
        $total_sales->quarterLaborSales = 0.0;

        /**
          Information about number of transactions
          - thisYear => transactions for the current week
          - lastYear => transactions for the same week last year
          - quarterThisYear => transactions for the quarter
          - quarterLastYear => transactions for the same quarter
                               year-over-year
          
          See sales above for more info about "Quarters"
        */
        $total_trans = new stdClass();
        $total_trans->thisYear = 0;
        $total_trans->lastYear = 0;
        $total_trans->quarterThisYear = 0;
        $total_trans->quarterLastYear = 0;

        /**
          Information about labor hours
          - actual => actual hours for the current week
          - projected => planned hours for the current week
                         based on sales growth and SPLH goals
          - trend => expected hours for the current week based
                     on recent history sales trends and
                     SPH goals
          - quarterActual => actual hours for the quarter
          - quarterProjected => planned hours for the quarter
                                based on sales growth goals
          
          See sales above for more info about "Quarters"
        */
        $total_hours = new stdClass();
        $total_hours->actual = 0.0;
        $total_hours->projected = 0.0;
        $total_hours->trend = 0.0;
        $total_hours->quarterActual = 0.0;
        $total_hours->quarterProjected = 0.0;

        /**
          Information about wages. Fields are the same
          as hours.
        */
        $total_wages = new stdClass();
        $total_wages->actual = 0.0;
        $total_wages->projected = 0.0;
        $total_wages->trend = 0.0;
        $total_wages->quarterActual = 0.0;
        $total_wages->quarterProjected = 0.0;

        $qtd_sales_ou = 0;
        $qtd_hours_ou = 0;
        $qtd_wages_ou = 0;

        /**
          Look up sales for the week in a given category
        */
        $salesP = $dbc->prepare('SELECT s.actualSales,
                                    s.lastYearSales,
                                    s.growthTarget,
                                    n.super_name,
                                    s.superID,
                                    s.transactions,
                                    s.lastYearTransactions
                                 FROM ObfSalesCache AS s
                                    LEFT JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'superDeptNames
                                        AS n ON s.superID=n.superID
                                 WHERE s.obfWeekID=?
                                    AND s.obfCategoryID=?
                                 ORDER BY s.superID,n.super_name');

        /**
          Look up sales for the [sales] quarter in a given category
        */
        $quarterSalesP = $dbc->prepare('SELECT SUM(s.actualSales) AS actual,
                                            SUM(s.lastYearSales) AS lastYear,
                                            SUM(s.lastYearSales * (1+s.growthTarget)) AS plan,
                                            SUM(s.transactions) AS trans,
                                            SUM(s.lastYearTransactions) AS ly_trans
                                        FROM ObfSalesCache AS s
                                            INNER JOIN ObfWeeks AS w ON s.obfWeekID=w.obfWeekID
                                        WHERE w.obfQuarterID = ?
                                            AND s.obfCategoryID = ?
                                            AND s.superID=?
                                            AND w.endDate <= ?'); 

        /**
          Look up labor for the [labor] quarter in a given category
        */
        $quarterLaborP = $dbc->prepare('SELECT SUM(l.hours) AS hours,
                                            SUM(l.wages) AS wages,
                                            AVG(l.laborTarget) as laborTarget,
                                            AVG(l.averageWage) as averageWage,
                                            SUM(l.hoursTarget) as hoursTarget
                                        FROM ObfLabor AS l
                                            INNER JOIN ObfWeeks AS w ON l.obfWeekID=w.obfWeekID
                                        WHERE w.obfLaborQuarterID=?
                                            AND l.obfCategoryID=?
                                            AND w.endDate <= ?');

        /**
          Look up sales for the [labor] quarter in a given category

          Since the "quarter" can differ for long-term sales and
          long-term labor, this value is needed to calculate
          long-term SPLH correctly.
        */
        $quarterSplhP = $dbc->prepare('SELECT SUM(c.actualSales) AS actualSales,
                                            SUM(c.lastYearSales * (1+c.growthTarget)) AS planSales
                                        FROM ObfLabor AS l
                                            INNER JOIN ObfWeeks AS w ON l.obfWeekID=w.obfWeekID
                                            INNER JOIN ObfSalesCache AS c ON c.obfWeekID=l.obfWeekID
                                                AND c.obfCategoryID=l.obfCategoryID
                                        WHERE w.obfLaborQuarterID=?
                                            AND l.obfCategoryID=?
                                            AND w.endDate <= ?');
        /**
          Trends are based on the previous
          thirteen weeks that contain sales data. 
          First build a list of week IDs, then
          prepare statement to query a specific category
          of sales data.
        */
        $splhWeeks = '(';
        $splhWeekQ = '
            SELECT c.obfWeekID
            FROM ObfSalesCache AS c
                INNER JOIN ObfWeeks AS w ON c.obfWeekID=w.obfWeekID
            WHERE c.obfWeekID < ?
            GROUP BY c.obfWeekID
            HAVING SUM(c.actualSales) > 0
            ORDER BY MAX(w.endDate) DESC';
        $splhWeekQ = $dbc->add_select_limit($splhWeekQ, 13);
        $splhWeekP = $dbc->prepare($splhWeekQ);
        $splhWeekR = $dbc->execute($splhWeekP, array($week->obfWeekID()));
        while ($splhWeekW = $dbc->fetch_row($splhWeekR)) {
            $splhWeeks .= sprintf('%d,', $splhWeekW['obfWeekID']);
        }
        $splhWeeks = substr($splhWeeks, 0, strlen($splhWeeks)-1) . ')';
        $trendQ = '
            SELECT 
                actualSales,
                lastYearSales
            FROM ObfSalesCache AS c
            WHERE c.obfCategoryID = ?
                AND c.superID = ?
                AND c.actualSales > 0
                AND c.obfWeekID IN ' . $splhWeeks . '
            ORDER BY c.obfWeekID';
        $trendP = $dbc->prepare($trendQ);

        /**
          LOOP ONE
          Examine OBF Categories that have sales. These will include
          both sales and labor information
        */
        $categories = new ObfCategoriesModel($dbc);
        $categories->hasSales(1);
        foreach ($categories->find('name') as $category) {
            $data[] = array($category->name(), '', '', '', '', '', '', '', '', '',
                        'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                        'meta_background' => $colors[0],
                        'meta_foreground' => 'black',
            );
            $sum = array(0.0, 0.0);
            $dept_proj = 0.0;
            $dept_trend = 0;
            $salesR = $dbc->execute($salesP, array($week->obfWeekID(), $category->obfCategoryID()));
            $qtd_dept_plan = 0;
            $qtd_dept_sales = 0;
            $qtd_dept_ou = 0;
            /**
              Go through sales records for the category
            */
            while ($w = $dbc->fetch_row($salesR)) {
                $proj = ($w['lastYearSales'] * $w['growthTarget']) + $w['lastYearSales'];

                $trendR = $dbc->execute($trendP, array($category->obfCategoryID(), $w['superID']));
                $trend_data = array();
                $x = 0;
                while ($trendW = $dbc->fetchRow($trendR)) {
                    $trend_data[] = array($x, $trendW['actualSales']);
                    $x++;
                }
                $trend_data = $this->removeOutliers($trend_data);
                $exp = $this->exponentialFit($trend_data);
                $trend1 = exp($exp->a) * exp($exp->b * $x);

                $dept_trend += $trend1;
                $total_sales->trend += $trend1;

                $quarter = $dbc->execute($quarterSalesP, 
                    array($week->obfQuarterID(), $category->obfCategoryID(), $w['superID'], date('Y-m-d 00:00:00', $end_ts))
                );
                if ($dbc->num_rows($quarter) == 0) {
                    $quarter = array('actual'=>0, 'lastYear'=>0, 'plan'=>0, 'trans'=>0, 'ly_trans'=>0);
                } else {
                    $quarter = $dbc->fetch_row($quarter);
                }
                $qtd_dept_plan += $quarter['plan'];
                $qtd_dept_sales += $quarter['actual'];
                $total_trans->quarterThisYear = $quarter['trans'];
                $total_trans->quarterLastYear = $quarter['ly_trans'];

                $record = array(
                    $w['super_name'],
                    number_format($w['lastYearSales'], 0),
                    number_format($proj, 0),
                    number_format($proj, 0), // converts to % of sales
                    number_format($trend1, 0),
                    number_format($w['actualSales'], 0),
                    sprintf('%.2f%%', $this->percentGrowth($w['actualSales'], $w['lastYearSales'])),
                    number_format($w['actualSales'], 0), // converts to % of sales
                    number_format($w['actualSales'] - $proj, 0),
                    number_format($quarter['actual'] - $quarter['plan'], 0),
                    'meta' => FannieReportPage::META_COLOR,
                    'meta_background' => $colors[0],
                    'meta_foreground' => 'black',
                );
                $sum[0] += $w['actualSales'];
                $sum[1] += $w['lastYearSales'];
                $total_sales->thisYear += $w['actualSales'];
                $total_sales->lastYear += $w['lastYearSales'];
                if ($total_trans->thisYear == 0) {
                    $total_trans->thisYear = $w['transactions'];
                }
                if ($total_trans->lastYear == 0) {
                    $total_trans->lastYear = $w['lastYearTransactions'];
                }
                $total_sales->projected += $proj;
                $dept_proj += $proj;
                $total_sales->quarterProjected += $quarter['plan'];
                $total_sales->quarterActual += $quarter['actual'];
                $qtd_sales_ou += ($quarter['actual'] - $quarter['plan']);
                $qtd_dept_ou += ($quarter['actual'] - $quarter['plan']);
                $data[] = $record;
            }

            /** total sales for the category **/
            $record = array(
                'Total',
                number_format($sum[1], 0),
                number_format($dept_proj, 0),
                number_format($dept_proj, 0), // % of store sales re-written later
                number_format($dept_trend, 0),
                number_format($sum[0], 0),
                sprintf('%.2f%%', $this->percentGrowth($sum[0], $sum[1])),
                number_format($sum[0], 0),
                number_format($sum[0] - $dept_proj, 0),
                number_format($qtd_dept_ou, 0),
                'meta' => FannieReportPage::META_COLOR | FannieReportPage::META_BOLD,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $data[] = $record;

            /**
              Now labor values based on sales calculationsabove
            */
            $labor->obfCategoryID($category->obfCategoryID());
            $labor->load();
            // use SPLH instead of pre-allocated
            $proj_hours = $dept_proj / $category->salesPerLaborHourTarget();
            $trend_hours = $dept_trend / $category->salesPerLaborHourTarget();
            // approximate wage to convert hours into dollars
            $average_wage = 0;
            if ($labor->hours() != 0) {
                $average_wage = $labor->wages() / ((float)$labor->hours());
            }
            $proj_wages = $proj_hours * $average_wage;
            $trend_wages = $trend_hours * $average_wage;

            $quarter = $dbc->execute($quarterLaborP, 
                array($week->obfLaborQuarterID(), $labor->obfCategoryID(), date('Y-m-d 00:00:00', $end_ts))
            );
            if ($dbc->num_rows($quarter) == 0) {
                $quarter = array('hours'=>0, 'wages'=>0, 'laborTarget'=>0, 'hoursTarget'=>0, 'actualSales' => 0);
            } else {
                $quarter = $dbc->fetch_row($quarter);
            }
            $qt_splh = $dbc->execute($quarterSplhP,
                array($week->obfLaborQuarterID(), $labor->obfCategoryID(), date('Y-m-d 00:00:00', $end_ts))
            );
            if ($dbc->num_rows($qt_splh)) {
                $w = $dbc->fetch_row($qt_splh);
                $quarter['actualSales'] = $w['actualSales'];
                $quarter['planSales'] = $w['planSales'];
            }
            $qt_average_wage = $quarter['hours'] == 0 ? 0 : $quarter['wages'] / ((float)$quarter['hours']);
            $qt_proj_hours = $quarter['planSales'] / $category->salesPerLaborHourTarget();
            $qt_proj_labor = $qt_proj_hours * $qt_average_wage;
            $total_hours->quarterActual += $quarter['hours'];
            $total_hours->quarterProjected += $qt_proj_hours;
            $total_sales->quarterLaborSales += $quarter['actualSales'];

            $data[] = array(
                'Hours',
                '',
                number_format($proj_hours, 0),
                '',
                number_format($trend_hours, 0),
                number_format($labor->hours(), 0),
                sprintf('%.2f%%', $this->percentGrowth($labor->hours(), $proj_hours)),
                '',
                number_format($labor->hours() - $proj_hours, 0),
                number_format($quarter['hours'] - $qt_proj_hours, 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $total_hours->actual += $labor->hours();
            $qtd_hours_ou += ($quarter['hours'] - $qt_proj_hours);

            $data[] = array(
                'Wages',
                '',
                number_format($proj_wages, 0),
                '',
                number_format($trend_wages, 0),
                number_format($labor->wages(), 0),
                sprintf('%.2f%%', $this->percentGrowth($labor->wages(), $proj_wages)),
                '',
                number_format($labor->wages() - $proj_wages, 0),
                number_format($quarter['wages'] - $qt_proj_labor, 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $total_wages->actual += $labor->wages();
            $total_wages->quarterActual += $quarter['wages'];
            $qtd_wages_ou += ($quarter['wages'] - $qt_proj_labor);
            $total_wages->projected += $proj_wages;
            $total_hours->projected += $proj_hours;
            $total_wages->trend += $trend_wages;
            $total_hours->trend += $trend_hours;

            $data[] = array(
                '% of Sales',
                '',
                sprintf('%.2f%%', $proj_wages / $dept_proj * 100),
                '',
                sprintf('%.2f%%', $trend_wages / $dept_trend * 100),
                sprintf('%.2f%%', $sum[0] == 0 ? 0 : $labor->wages() / $sum[0] * 100),
                sprintf('%.2f%%', $this->percentGrowth(($sum[0] == 0 ? 0 : $labor->wages()/$sum[0]*100), ($proj_wages/$dept_proj*100))),
                '',
                '',
                '',
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $quarter_actual_sph = $quarter['hours'] == 0 ? 0 : ($qtd_dept_sales)/($quarter['hours']);
            $quarter_proj_sph = ($qtd_dept_plan)/($qt_proj_hours);
            $data[] = array(
                'Sales per Hour',
                '',
                number_format($dept_proj / $proj_hours, 2),
                '',
                number_format($dept_trend / $trend_hours, 2),
                number_format($labor->hours() == 0 ? 0 : $sum[0] / $labor->hours(), 2),
                sprintf('%.2f%%', $this->percentGrowth(($labor->hours() == 0 ? 0 : $sum[0]/$labor->hours()), $dept_proj/$proj_hours)),
                '',
                number_format(($labor->hours() == 0 ? 0 : $sum[0]/$labor->hours()) - ($dept_proj / $proj_hours), 2),
                number_format($quarter_actual_sph - $quarter_proj_sph, 2),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

            if (count($colors) > 1) {
                array_shift($colors);
            }
        }

        /**
          Now that total sales for the all categories have been calculated,
          go back and divide specific columns by total sales to get
          percentage of sales
        */
        for ($i=0; $i<count($data); $i++) {
            if (isset($data[$i][7]) && preg_match('/^[\d,]+$/', $data[$i][7])) {
                $amt = str_replace(',', '', $data[$i][7]);
                $percentage = ($total_sales->thisYear == 0) ? 0.00 : ((float)$amt) / ((float)$total_sales->thisYear);
                $data[$i][7] = number_format($percentage*100, 2) . '%';
            }
            if (isset($data[$i][3]) && preg_match('/^[\d,]+$/', $data[$i][3])) {
                $amt = str_replace(',', '', $data[$i][3]);
                $percentage = ((float)$amt) / ((float)$total_sales->projected);
                $data[$i][3] = number_format($percentage*100, 2) . '%';
            }
        }

        /**
          LOOP TWO
          Examine OBF Categories without sales. These will include
          only labor information
        */
        $cat = new ObfCategoriesModel($dbc);
        $cat->hasSales(0);
        foreach ($cat->find('name') as $c) {
            $data[] = array($c->name(), '', '', '', '', '', '', '', '', '',
                        'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                        'meta_background' => $colors[0],
                        'meta_foreground' => 'black',
            );
            $labor->obfCategoryID($c->obfCategoryID());
            $labor->load();

            $quarter = $dbc->execute($quarterLaborP, 
                array($week->obfLaborQuarterID(), $labor->obfCategoryID(), date('Y-m-d 00:00:00', $end_ts))
            );
            if ($dbc->num_rows($quarter) == 0) {
                $quarter = array('hours'=>0, 'wages'=>0, 'laborTarget'=>0, 'hoursTarget'=>0);
            } else {
                $quarter = $dbc->fetch_row($quarter);
            }
            $qt_average_wage = $quarter['hours'] == 0 ? 0 : $quarter['wages'] / ((float)$quarter['hours']);
            $qt_proj_hours = $total_sales->quarterProjected / $c->salesPerLaborHourTarget();
            $qt_proj_labor = $qt_proj_hours * $qt_average_wage;
            $total_hours->quarterActual += $quarter['hours'];
            $total_hours->quarterProjected += $qt_proj_hours;

            $average_wage = 0;
            if ($labor->hours() != 0) {
                $average_wage = $labor->wages() / ((float)$labor->hours());
            }
            // use SPLH instead of pre-allocated
            $proj_hours = $total_sales->projected / $c->salesPerLaborHourTarget();
            $proj_wages = $proj_hours * $average_wage;

            $trend_hours = $total_sales->trend / $c->salesPerLaborHourTarget();
            $trend_wages = $trend_hours * $average_wage;

            $data[] = array(
                'Hours',
                '',
                number_format($proj_hours, 0),
                '',
                number_format($trend_hours, 0),
                number_format($labor->hours(), 0),
                '',
                '',
                number_format($labor->hours() - $proj_hours, 0),
                number_format($quarter['hours'] - $qt_proj_hours, 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $qtd_hours_ou += ($quarter['hours'] - $qt_proj_hours);

            $data[] = array(
                'Wages',
                '',
                number_format($proj_wages, 0),
                '',
                number_format($trend_wages, 0),
                number_format($labor->wages(), 0),
                '',
                '',
                number_format($labor->wages() - $proj_wages, 0),
                number_format($quarter['wages'] - $qt_proj_labor, 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $total_wages->quarterActual += $quarter['wages'];
            $qtd_wages_ou += ($quarter['wages'] - $qt_proj_labor);

            $data[] = array(
                '% of Sales',
                '',
                sprintf('%.2f%%', $proj_wages / $total_sales->projected * 100),
                '',
                sprintf('%.2f%%', $trend_wages / $total_sales->trend * 100),
                sprintf('%.2f%%', $total_sales->thisYear == 0 ? 0.00 : $labor->wages() / $total_sales->thisYear * 100),
                '',
                '',
                '',
                '',
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $quarter_actual_sph = $quarter['hours'] == 0 ? 0 : ($total_sales->quarterActual)/($quarter['hours']);
            $quarter_proj_sph = ($total_sales->quarterProjected)/($qt_proj_hours);
            $data[] = array(
                'Sales per Hour',
                '',
                sprintf('%.2f', $total_sales->projected / $proj_hours),
                '',
                sprintf('%.2f', $total_sales->trend / $trend_hours),
                number_format($labor->hours() == 0 ? 0 : $total_sales->thisYear / $labor->hours(), 2),
                '',
                '',
                number_format(($labor->hours() == 0 ? 0 : $total_sales->thisYear/$labor->hours()) - ($total_sales->projected / $proj_hours), 2),
                number_format($quarter_actual_sph - $quarter_proj_sph, 2),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

            $total_hours->actual += $labor->hours();
            $total_wages->actual += $labor->wages();
            $total_wages->projected += $proj_wages;
            $total_hours->projected += $proj_hours;
            $total_wages->trend += $trend_wages;
            $total_hours->trend += $trend_hours;

            if (count($colors) > 1) {
                array_shift($colors);
            }
        }

        /**
           Storewide totals section
        */
        $data[] = array('Total Store', '', '', '', '', '', '', '', '', '',
                        'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                        'meta_background' => $colors[0],
                        'meta_foreground' => 'black',
        );
        $data[] = array(
            'Sales',
            number_format($total_sales->lastYear, 0),
            number_format($total_sales->projected, 0),
            '',
            number_format($total_sales->trend, 0),
            number_format($total_sales->thisYear, 0),
            sprintf('%.2f%%', $this->percentGrowth($total_sales->thisYear, $total_sales->lastYear)),
            '',
            number_format($total_sales->thisYear - $total_sales->projected, 0),
            number_format($qtd_sales_ou, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Hours',
            '',
            number_format($total_hours->projected, 0),
            '',
            number_format($total_hours->trend, 0),
            number_format($total_hours->actual, 0),
            '',
            '',
            number_format($total_hours->actual - $total_hours->projected, 0),
            number_format($qtd_hours_ou, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Wages',
            '',
            number_format($total_wages->projected, 0),
            '',
            number_format($total_wages->trend, 0),
            number_format($total_wages->actual, 0),
            '',
            '',
            number_format($total_wages->actual - $total_wages->projected, 0),
            number_format($qtd_wages_ou, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Wages as % of Sales',
            '',
            sprintf('%.2f%%', $total_wages->projected / $total_sales->projected * 100),
            '',
            sprintf('%.2f%%', $total_wages->trend / $total_sales->trend * 100),
            sprintf('%.2f%%', $total_sales->thisYear == 0 ? 0 : $total_wages->actual / $total_sales->thisYear * 100),
            '',
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $p_est = 0.32;
        $data[] = array(
            'Other Personnel Cost (est)',
            '',
            number_format($total_wages->projected * $p_est, 0),
            '',
            number_format($total_wages->trend * $p_est, 0),
            number_format($total_wages->actual * $p_est, 0),
            '',
            '',
            number_format(($total_wages->actual - $total_wages->projected) * $p_est, 0),
            number_format($qtd_wages_ou * $p_est, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $p_est += 1.0;
        $data[] = array(
            'Total Personnel Cost (est)',
            '',
            number_format($total_wages->projected * $p_est, 0),
            '',
            number_format($total_wages->trend * $p_est, 0),
            number_format($total_wages->actual * $p_est, 0),
            '',
            '',
            number_format(($total_wages->actual - $total_wages->projected) * $p_est, 0),
            number_format($qtd_wages_ou * $p_est, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $quarter_actual_sph = $total_hours->quarterActual == 0 ? 0 : ($total_sales->quarterActual)/($total_hours->quarterActual);
        $quarter_proj_sph = ($total_sales->quarterProjected)/($total_hours->quarterProjected);
        $data[] = array(
            'Sales per Hour',
            '',
            sprintf('%.2f', $total_sales->projected / $total_hours->projected),
            '',
            sprintf('%.2f', $total_sales->trend / $total_hours->trend),
            number_format($total_hours->actual == 0 ? 0 : $total_sales->thisYear / $total_hours->actual, 2),
            '',
            '',
            number_format(($total_hours->actual == 0 ? 0 : $total_sales->thisYear/$total_hours->actual) - ($total_sales->projected/$total_hours->projected), 2),
            number_format($quarter_actual_sph - $quarter_proj_sph, 2),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $proj_trans = $total_trans->lastYear * 1.05;
        $qtd_proj_trans = $total_trans->quarterLastYear * 1.05;
        $data[] = array(
            'Transactions',
            number_format($total_trans->lastYear),
            number_format($proj_trans),
            '',
            '',
            number_format($total_trans->thisYear),
            sprintf('%.2f%%', $this->percentGrowth($total_trans->thisYear, $total_trans->lastYear)),
            '',
            number_format($total_trans->thisYear - $proj_trans),
            number_format($total_trans->quarterThisYear - $qtd_proj_trans),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Average Basket',
            number_format($total_sales->lastYear / $total_trans->lastYear, 2),
            number_format($total_sales->projected / $proj_trans, 2),
            '',
            '',
            number_format($total_trans->thisYear == 0 ? 0 : $total_sales->thisYear / $total_trans->thisYear, 2),
            sprintf('%.2f%%', $this->percentGrowth($total_trans->thisYear == 0 ? 0 : $total_sales->thisYear/$total_trans->thisYear, $total_sales->lastYear/$total_trans->lastYear)),
            '',
            number_format(($total_trans->thisYear == 0 ? 0 : $total_sales->thisYear/$total_trans->thisYear) - ($total_sales->projected/$proj_trans), 2),
            number_format(($total_sales->quarterActual/$total_trans->quarterThisYear) - ($total_sales->quarterProjected/$qtd_proj_trans), 2),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        if (count($colors) > 1) {
            array_shift($colors);
        }

        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

        $stockP = $dbc->prepare('
            SELECT SUM(stockPurchase) AS ttl
            FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'stockpurchases
            WHERE tdate BETWEEN ? AND ?
                AND dept=992
                AND trans_num NOT LIKE \'1001-30-%\'
        ');

        $args1 = array(
            date('Y-07-01 00:00:00', $end_ts),
            date('Y-m-d 23:59:59', $end_ts),
        );
        if (date('n', $end_ts) < 7) {
            $args1[0] = (date('Y', $end_ts) - 1) . '-07-01 00:00:00';
        }

        $last_year = mktime(0, 0, 0, date('n',$end_ts), date('j',$end_ts), date('Y',$end_ts)-1);
        $args2 = array(
            date('Y-07-01 00:00:00', $last_year),
            date('Y-m-d 23:59:59', $last_year),
        );
        if (date('n', $last_year) < 7) {
            $args2[0] = (date('Y', $last_year) - 1) . '-07-01 00:00:00';
        }

        $args3 = array(
            date('Y-m-d 00:00:00', $start_ts),
            date('Y-m-d 23:59:59', $end_ts),
        );
        $args4 = array(
            date('Y-m-d 00:00:00', $start_ly),
            date('Y-m-d 23:59:59', $end_ly),
        );

        $current = $dbc->execute($stockP, $args1);
        $prior = $dbc->execute($stockP, $args2);
        $this_week = $dbc->execute($stockP, $args3);
        $last_week = $dbc->execute($stockP, $args4);
        if ($dbc->num_rows($current) > 0) {
            $current = $dbc->fetch_row($current);
            $current = $current['ttl'] / 20;
        } else {
            $current = 0;
        }
        if ($dbc->num_rows($prior) > 0) {
            $prior = $dbc->fetch_row($prior);
            $prior = $prior['ttl'] / 20;
        } else {
            $prior = 0;
        }
        if ($dbc->num_rows($this_week) > 0) {
            $this_week = $dbc->fetch_row($this_week);
            $this_week = $this_week['ttl'] / 20;
        } else {
            $this_week = 0;
        }
        if ($dbc->num_rows($last_week) > 0) {
            $last_week = $dbc->fetch_row($last_week);
            $last_week = $last_week['ttl'] / 20;
            $num_days = (float)date('t', $start_ly);
            $last_week = round(($last_week/$num_days) * 7);
        } else {
            $last_week = 0;
        }

        $data[] = array(
            'Ownership This Week',
            number_format($this_week, 0),
            number_format($last_week, 0),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );
        $data[] = array(
            'Ownership This Year',
            number_format($current, 0),
            number_format($prior, 0),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        return $data;
    }

    public function form_content()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">';
        $ret .= '<div class="form-group form-inline">
            <label>Week Starting</label>: 
            <select class="form-control" name="weekID">';
        $model = new ObfWeeksModel($dbc);
        foreach ($model->find('startDate', true) as $week) {
            $ret .= sprintf('<option value="%d">%s</option>',
                            $week->obfWeekID(),
                            date('M, d Y', strtotime($week->startDate()))
                            . ' - ' . date('M, d Y', strtotime($week->endDate()))
            );
        }
        $ret .= '</select>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="submit" class="btn btn-default">Get Report</button>';
        $ret .= '</div>';
        $ret .= '</form>';
        $ret .= '<p><button class="btn btn-default"
                onclick="location=\'ObfIndexPage.php\';return false;">Home</button>
                </p>';

        return $ret;
    }

    private function percentGrowth($a, $b)
    {
        if ($b == 0) {
            return 0.0;
        } else {
            return 100 * ($a - $b) / ((float)$b);
        }
    }

    private function leastSquare($points)
    {
        $avg_x = 0.0;
        $avg_y = 0.0;
        foreach ($points as $p) {
            $avg_x += $p[0];
            $avg_y += $p[1];
        }
        $avg_x /= (float)count($points);
        $avg_y /= (float)count($points);

        $numerator = 0.0;
        $denominator = 0.0;
        foreach ($points as $p) {
            $numerator += (($p[0] - $avg_x) * ($p[1] - $avg_y));
            $denominator += (($p[0] - $avg_x) * ($p[0] - $avg_x));
        }
        $slope = $numerator / $denominator;
        $y_intercept = $avg_y - ($slope * $avg_x);

        return array(
            'slope' => $slope,
            'y_intercept' => $y_intercept,
        );
    }

    private function exponentialFit($points)
    {
        $a_numerator = 
            (array_reduce($points, function($c,$p){ return $c + (pow($p[0],2)*$p[1]); })
            * array_reduce($points, function($c,$p){ return $c + ($p[1] * log($p[1])); })) 
            -
            (array_reduce($points, function($c,$p){ return $c + ($p[0]*$p[1]); })
            * array_reduce($points, function($c,$p){ return $c + ($p[0] * $p[1] * log($p[1])); })); 

        $a_denominator = 
            (array_reduce($points, function($c,$p) { return $c + $p[1]; })
            * array_reduce($points, function($c,$p) { return $c + (pow($p[0],2)*$p[1]); }))
            -
            pow(
                array_reduce($points, function($c,$p) { return $c + $p[0]*$p[1]; }),
                2);

        $a = $a_numerator / $a_denominator;

        $b_numerator = 
            (array_reduce($points, function($c,$p){ return $c + $p[1]; })
            * array_reduce($points, function($c,$p){ return $c + ($p[0] * $p[1] * log($p[1])); })) 
            -
            (array_reduce($points, function($c,$p){ return $c + ($p[0]*$p[1]); })
            * array_reduce($points, function($c,$p){ return $c + ($p[1] * log($p[1])); })); 
        $b_denominator = $a_denominator;

        $b = $b_numerator / $b_denominator;

        $ret = new stdClass();
        $ret->a = $a;
        $ret->b = $b;

        return $ret;
    }

    private function removeOutliers($arr)
    {
        $min_index = 0;
        $max_index = 0;
        for ($i=0; $i<count($arr); $i++) {
            if ($arr[$i][1] < $arr[$min_index][1]) {
                $min_index = $i;
            }
            if ($arr[$i][1] > $arr[$max_index][1]) {
                $max_index = $i;
            }
        }
        $ret = array();
        for ($i=0; $i<count($arr); $i++) {
            if ($i != $min_index && $i != $max_index) {
                $ret[] = $arr[$i];
            }
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec();
