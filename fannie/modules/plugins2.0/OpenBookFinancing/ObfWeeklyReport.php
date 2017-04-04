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
    public function preprocess()
    {
        if (!headers_sent()) {
            header('Location: ../OpenBookFinancingV2/ObfWeeklyReportV2.php');
        }
        return false;
    }

    protected $header = 'OBF: Weekly Report';
    protected $title = 'OBF: Weekly Report';

    public $page_set = 'Plugin :: Open Book Financing';
    public $report_set = 'Sales Reports';
    public $description = '[OBF Weekly Report] shows open book financing sales and labor data for a given week.';
    public $discoverable = false;

    protected $required_fields = array('weekID');
    protected $new_tablesorter = false;

    protected $report_headers = array(
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Current Year', 'Last Year', '', '', '', '', '', '', ''),
    );

    protected $class_lib = 'ObfLib';

    protected $colors = array(
        '#CDB49B',
        '#99C299',
        '#CDB49B',
        '#99C299',
        '#CDB49B',
        '#99C299',
        '#CDB49B',
        '#99C299',
        '#CDB49B',
        '#99C299',
        '#CDB49B',
        '#99C299',
        '#CDB49B',
        '#99C299',
        '#CDB49B',
        '#99C299',
    );

    public function report_description_content()
    {
        $class_lib = $this->class_lib;
        $dbc = $class_lib::getDB();
        
        $week = $class_lib::getWeek($dbc);
        $week->obfWeekID($this->form->weekID);
        $week->load();
        $start_ts = strtotime($week->startDate());
        $end_ts = mktime(0, 0, 0, date('n', $start_ts), date('j', $start_ts)+6, date('Y', $start_ts));

        $store = FormLib::get('store');
        $prev = $this->form->weekID - 1;
        $next = $this->form->weekID + 1;
        $other = $store == 1 ? 2 : 1;

        return array(
            '<div>Week ' . date('F d, Y', $start_ts) . ' to ' . date('F d, Y', $end_ts) . '</div>',
            "<div class=\"hidden-print\"><a href=\"?weekID={$prev}&store={$store}\">Prev Week</a> 
            | <a href=\"?weekID={$next}&store={$store}\">Next Week</a>
            | <a href=\"?weekID={$this->form->weekID}&store={$other}\">Other Store</a></div>",
        );
    }

    protected function initTotalSales()
    {
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

        return $total_sales;
    }

    protected function initTotalTrans()
    {
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

        return $total_trans;
    }

    protected function initTotalHours()
    {
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

        return $total_hours;
    }

    protected function initTotalWages()
    {
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

        return $total_wages;
    }

    protected function getAverageWage($labor)
    {
        $average_wage = 0;
        if ($labor->hours() != 0) {
            $average_wage = $labor->wages() / ((float)$labor->hours());
        }

        return $average_wage;
    }

    protected function projectHours($splhGoal, $dept_proj, $dept_trend)
    {
        $proj_hours = $dept_proj / $splhGoal;
        $trend_hours = $dept_trend / $splhGoal;

        return array($proj_hours, $trend_hours);
    }

    protected function projectWages($labor, $proj_hours, $trend_hours)
    {
        $average_wage = $this->getAverageWage($labor);
        $proj_wages = $proj_hours * $average_wage;
        $trend_wages = $trend_hours * $average_wage;

        return array($proj_wages, $trend_wages);
    }

    protected function rewritePercentageOfSales($data, $total_sales)
    {
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

        return $data;
    }

    protected function headerRow($header, $text='black')
    {
        return array($header, '', '', '', '', '', '', '', '', '',
                    'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                    'meta_background' => $this->colors[0],
                    'meta_foreground' => $text,
        );
    }

    public function fetch_report_data()
    {
        $class_lib = $this->class_lib;
        $dbc = $class_lib::getDB();
        
        $week = $class_lib::getWeek($dbc);
        $week->obfWeekID($this->form->weekID);
        $week->load();

        $labor = new ObfLaborModel($dbc);
        $labor->obfWeekID($week->obfWeekID());
        
        /**
           Timestamps for the start and end of
           the current week
        */
        $start_ts = strtotime($week->startDate());
        $end_ts = mktime(0, 0, 0, date('n', $start_ts), date('j', $start_ts)+6, date('Y', $start_ts));
        list($year, $month) = $this->findYearMonth($start_ts, $end_ts);

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
            $dateInfo = array(
                'start_ts' => $start_ts,
                'end_ts' => $end_ts,
                'start_ly' => $start_ly,
                'end_ly' => $end_ly,
            );
            $this->updateSalesCache($week, $num_cached, $dateInfo);
        }

        // record set to return
        $data = array();                

        $total_sales = $this->initTotalSales();
        $total_trans = $this->initTotalTrans();
        $total_hours = $this->initTotalHours();
        $total_wages = $this->initTotalWages();
        $qtd_sales_ou = 0;
        $qtd_hours_ou = 0;
        $qtd_wages_ou = 0;

        $this->prepareStatements($dbc);
        $this->prepTrendsStatement($dbc, $week);

        /**
          LOOP ONE
          Examine OBF Categories that have sales. These will include
          both sales and labor information
        */
        $categories = new ObfCategoriesModel($dbc);
        $categories->hasSales(1);
        foreach ($categories->find('name') as $category) {
            $data[] = $this->headerRow($category->name());
            $sum = array(0.0, 0.0);
            $dept_proj = 0.0;
            $dept_trend = 0;
            $salesR = $dbc->execute($this->salesP, array($week->obfWeekID(), $category->obfCategoryID()));
            $qtd_dept_plan = 0;
            $qtd_dept_sales = 0;
            $qtd_dept_ou = 0;
            /**
              Go through sales records for the category
            */
            while ($row = $dbc->fetch_row($salesR)) {
                $proj = ($row['lastYearSales'] * $row['growthTarget']) + $row['lastYearSales'];
                $trend1 = $this->calculateTrend($dbc, $category->obfCategoryID(), $row['superID']);
                $dept_trend += $trend1;
                $total_sales->trend += $trend1;

                $quarter = $dbc->getRow($this->quarterSalesP, 
                    array($week->obfQuarterID(), $category->obfCategoryID(), $row['superID'], date('Y-m-d 00:00:00', $end_ts))
                );
                if ($quarter === false) {
                    $quarter = array('actual'=>0, 'lastYear'=>0, 'plan'=>0, 'trans'=>0, 'ly_trans'=>0);
                }
                $qtd_dept_plan += $quarter['plan'];
                $qtd_dept_sales += $quarter['actual'];
                $total_trans->quarterThisYear = $quarter['trans'];
                $total_trans->quarterLastYear = $quarter['ly_trans'];

                $record = array(
                    $row['super_name'],
                    number_format($row['lastYearSales'], 0),
                    number_format($proj, 0),
                    number_format($proj, 0), // converts to % of sales
                    number_format($trend1, 0),
                    number_format($row['actualSales'], 0),
                    sprintf('%.2f%%', $this->percentGrowth($row['actualSales'], $row['lastYearSales'])),
                    number_format($row['actualSales'], 0), // converts to % of sales
                    number_format($row['actualSales'] - $proj, 0),
                    number_format($quarter['actual'] - $quarter['plan'], 0),
                    'meta' => FannieReportPage::META_COLOR,
                    'meta_background' => $this->colors[0],
                    'meta_foreground' => 'black',
                );
                $sum[0] += $row['actualSales'];
                $sum[1] += $row['lastYearSales'];
                $total_sales->thisYear += $row['actualSales'];
                $total_sales->lastYear += $row['lastYearSales'];
                if ($total_trans->thisYear == 0) {
                    $total_trans->thisYear = $row['transactions'];
                }
                if ($total_trans->lastYear == 0) {
                    $total_trans->lastYear = $row['lastYearTransactions'];
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
                'meta_background' => $this->colors[0],
                'meta_foreground' => 'black',
            );
            $data[] = $record;

            /**
              Now labor values based on sales calculationsabove
            */
            $labor->obfCategoryID($category->obfCategoryID());
            $labor->load();
            // use SPLH instead of pre-allocated
            list($proj_hours, $trend_hours) = $this->projectHours($category, $dept_proj, $dept_trend);
            // approximate wage to convert hours into dollars
            list($proj_wages, $trend_wages) = $this->projectWages($labor, $proj_hours, $trend_hours);

            $quarter = $dbc->getRow($this->quarterLaborP, 
                array($week->obfLaborQuarterID(), $labor->obfCategoryID(), date('Y-m-d 00:00:00', $end_ts))
            );
            if ($quarer === false) {
                $quarter = array('hours'=>0, 'wages'=>0, 'laborTarget'=>0, 'hoursTarget'=>0, 'actualSales' => 0);
            }
            $qt_splh = $dbc->getRow($this->quarterSplhP,
                array($week->obfLaborQuarterID(), $labor->obfCategoryID(), date('Y-m-d 00:00:00', $end_ts))
            );
            if ($qt_splh !== false) {
                $quarter['actualSales'] = $qt_splh['actualSales'];
                $quarter['planSales'] = $qt_splh['planSales'];
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
                'meta_background' => $this->colors[0],
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
                'meta_background' => $this->colors[0],
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
                'meta_background' => $this->colors[0],
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
                'meta_background' => $this->colors[0],
                'meta_foreground' => 'black',
            );

            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

            if (count($this->colors) > 1) {
                array_shift($this->colors);
            }
        }

        $data = $this->rewritePercentageOfSales($data, $total_sales);

        /**
          LOOP TWO
          Examine OBF Categories without sales. These will include
          only labor information
        */
        $cat = new ObfCategoriesModel($dbc);
        $cat->hasSales(0);
        foreach ($cat->find('name') as $c) {
            $data[] = $this->headerRow($c->name());
            $labor->obfCategoryID($c->obfCategoryID());
            $labor->load();

            $quarter = $dbc->getRow($this->quarterLaborP, 
                array($week->obfLaborQuarterID(), $labor->obfCategoryID(), date('Y-m-d 00:00:00', $end_ts))
            );
            if ($quarter === false) {
                $quarter = array('hours'=>0, 'wages'=>0, 'laborTarget'=>0, 'hoursTarget'=>0);
            }
            $qt_average_wage = $quarter['hours'] == 0 ? 0 : $quarter['wages'] / ((float)$quarter['hours']);
            $qt_proj_hours = $total_sales->quarterProjected / $c->salesPerLaborHourTarget();
            $qt_proj_labor = $qt_proj_hours * $qt_average_wage;
            $total_hours->quarterActual += $quarter['hours'];
            $total_hours->quarterProjected += $qt_proj_hours;

            list($proj_hours, $trend_hours) = $this->projectHours($c, $total_sales->projected, $total_sales->trend);
            list($proj_wages, $trend_wages) = $this->projectWages($labor, $proj_hours, $trend_hours);

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
                'meta_background' => $this->colors[0],
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
                'meta_background' => $this->colors[0],
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
                'meta_background' => $this->colors[0],
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
                'meta_background' => $this->colors[0],
                'meta_foreground' => 'black',
            );

            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

            $total_hours->actual += $labor->hours();
            $total_wages->actual += $labor->wages();
            $total_wages->projected += $proj_wages;
            $total_hours->projected += $proj_hours;
            $total_wages->trend += $trend_wages;
            $total_hours->trend += $trend_hours;

            if (count($this->colors) > 1) {
                array_shift($this->colors);
            }
        }

        /**
           Storewide totals section
        */
        $data[] = $this->headerRow('Total Store');

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
            'meta_background' => $this->colors[0],
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
            'meta_background' => $this->colors[0],
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
            'meta_background' => $this->colors[0],
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
            'meta_background' => $this->colors[0],
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
            'meta_background' => $this->colors[0],
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
            'meta_background' => $this->colors[0],
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
            'meta_background' => $this->colors[0],
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
            'meta_background' => $this->colors[0],
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
            'meta_background' => $this->colors[0],
            'meta_foreground' => 'black',
        );

        if (count($this->colors) > 1) {
            array_shift($this->colors);
        }

        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        $data[] = $this->ownershipThisWeek($dbc, $start_ts, $end_ts, $start_ly, $end_ly);
        $data[] = $this->ownershipThisYear($dbc, $end_ts);

        return $data;
    }

    public function form_content()
    {
        $class_lib = $this->class_lib;
        $dbc = $class_lib::getDB();

        $ret = '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="get">';
        $ret .= '<div class="form-group form-inline">
            <label>Week Starting</label>: 
            <select class="form-control" name="weekID">';
        $model = $class_lib::getWeek($dbc);
        foreach ($model->find('startDate', true) as $week) {
            $ret .= sprintf('<option value="%d">%s</option>',
                            $week->obfWeekID(),
                            date('M, d Y', strtotime($week->startDate()))
                            . ' - ' . date('M, d Y', strtotime($week->endDate()))
            );
        }
        $ret .= '</select>';
        $ret .= ' <label>Store</label>: ';
        $stores = FormLib::storePicker('store', false);
        $ret .= $stores['html'];
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="submit" class="btn btn-default">Get Report</button>';
        $ret .= '</div>';
        $ret .= '</form>';
        $ret .= '<p><button class="btn btn-default"
                onclick="location=\'index.php\';return false;">Home</button>
                </p>';

        return $ret;
    }

    protected function percentGrowth($a, $b)
    {
        return \COREPOS\Fannie\API\lib\Stats::percentGrowth($a, $b);
    }

    /**
      Determine which month a given week falls in.
      If the first and last day of the week are not
      in the same month, choose whichever month
      4+ days of the week belong to
    */
    protected function findYearMonth($start_ts, $end_ts)
    {
        $month = false;
        $year = false;
        if (date('n', $start_ts) == date('n', $end_ts)) {
            $month = date('n', $start_ts);
            $year = date('Y', $start_ts);
        } else {
            $split = 0;
            for ($i=0; $i<7; $i++) {
                $stamp = mktime(0, 0, 0, date('n', $start_ts), date('j', $start_ts)+$i, date('Y', $start_ts));
                if (date('n', $start_ts) == date('n', $stamp)) {
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

        return array($year, $month);
    }

    protected function updateSalesCache($week, $num_cached, $dateInfo)
    {
        $class_lib = $this->class_lib;
        $dbc = $class_lib::getDB();
        $sales = $class_lib::getCache($dbc);
        $sales->obfWeekID($week->obfWeekID());
        /**
          Lookup total sales for each category
          in a given date range
        */
        $salesQ = 'SELECT 
                    m.obfCategoryID as id,
                    c.storeID,
                    m.superID,
                    SUM(t.total) AS sales
                   FROM __table__ AS t
                    INNER JOIN ' . $this->config->get('OP_DB') . $dbc->sep() . 'superdepts AS s
                        ON t.department=s.dept_ID
                    INNER JOIN ObfCategorySuperDeptMap AS m
                        ON s.superID=m.superID
                    LEFT JOIN ObfCategories AS c
                        ON m.obfCategoryID=c.obfCategoryID AND t.store_id=c.storeID
                   WHERE c.hasSales=1
                    AND t.tdate BETWEEN ? AND ?
                    AND t.trans_type IN (\'I\', \'D\')
                   GROUP BY m.obfCategoryID, c.storeID, m.superID';
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
                    INNER JOIN ' . $this->config->get('OP_DB') . $dbc->sep() . 'superdepts AS s
                        ON t.department=s.dept_ID
                   WHERE 
                    s.superID IN (select superID FROM ObfCategorySuperDeptMap GROUP BY superID)
                    AND t.tdate BETWEEN ? AND ?
                    AND t.trans_type IN (\'I\', \'D\')
                    AND t.upc <> \'RRR\'
                    AND t.store_id=?
                   GROUP BY 
                    YEAR(t.tdate),
                    MONTH(t.tdate),
                    DAY(t.tdate),
                    t.trans_num';

        /**
          Lookup tables for current week and
          year-over-year comparison
        */
        $dlog1 = DTransactionsModel::selectDlog(date('Y-m-d', $dateInfo['start_ts']), date('Y-m-d', $dateInfo['end_ts']));
        $dlog2 = DTransactionsModel::selectDlog(date('Y-m-d', $dateInfo['start_ly']), date('Y-m-d', $dateInfo['end_ly']));
        $args = array(date('Y-m-d 00:00:00', $dateInfo['start_ts']), date('Y-m-d 23:59:59', $dateInfo['end_ts']));

        $future = $dateInfo['end_ts'] >= strtotime(date('Y-m-d')) ? true: false;

        /**
          Lookup number of transactions for the current
          week and save that information if the week
          is complete
        */
        $trans1Q = str_replace('__table__', $dlog1, $transQ);
        $transP = $dbc->prepare($trans1Q);
        $trans_info = array();
        foreach (array(1,2) as $storeID) {
            $tArgs = array_merge($args, array($storeID)); 
            $transR = $dbc->execute($transP, $tArgs);
            $trans_info[$storeID] = 0;
            if (!$future && $transR) {
                $trans_info[$storeID] = $dbc->numRows($transR);
            }
        }

        /**
          Lookup sales for the current week. Actual sales
          is zeroed out until the week is complete, but
          the records are saved as placeholders for later
        */
        $oneQ = str_replace('__table__', $dlog1, $salesQ);
        $oneP = $dbc->prepare($oneQ);
        $oneR = $dbc->execute($oneP, $args);
        while ($row = $dbc->fetch_row($oneR)) {
            $sales->transactions($trans_info[$row['storeID']]);
            $sales->obfCategoryID($row['id']);
            $sales->superID($row['superID']);
            $sales->actualSales($row['sales']);
            if ($future) {
                $sales->actualSales(0);
            }
            $labor = $class_lib::getLabor($dbc);
            $labor->obfWeekID($week->obfWeekID());
            $labor->obfCategoryID($row['id']);
            foreach ($labor->find() as $l) {
                $sales->growthTarget($l->growthTarget());
            }
            $sales->save();
        }

        if (count($num_cached[1]) == 0) {
            /**
              Now lookup year-over-year info
              Since it examines a whole month rather than a single
              week, we'll take the average and then extend
              that out to seven days
            */
            $sales->reset();
            $sales->obfWeekID($week->obfWeekID());
            $args = array(date('Y-m-d 00:00:00', $dateInfo['start_ly']), date('Y-m-d 23:59:59', $dateInfo['end_ly']));
            $num_days = (float)date('t', $dateInfo['start_ly']);

            /**
              Transactions last year, pro-rated
            */
            $trans2Q = str_replace('__table__', $dlog2, $transQ);
            $transP = $dbc->prepare($trans2Q);
            $trans_info = array();
            foreach (array(1,2) as $storeID) {
                $tArgs = array_merge($args, array($storeID)); 
                $transR = $dbc->execute($transP, $tArgs);
                $trans_info[$storeID] = 0;
                if ($transR) {
                    $tran_count = $dbc->numRows($transR);
                    $trans_info[$storeID] = $tran_count;
                    if (!isset($dateInfo['averageWeek']) || $dateInfo['averageWeek']) {
                        $avg_trans = ($tran_count / $num_days) * 7;
                        $trans_info[$storeID] = $avg_trans;
                    }
                }
            }

            /**
              Sales last year, pro-rated
            */
            $twoQ = str_replace('__table__', $dlog2, $salesQ);
            $twoP = $dbc->prepare($twoQ);
            $twoR = $dbc->execute($twoP, $args);
            while ($row = $dbc->fetch_row($twoR)) {
                $sales->lastYearTransactions($trans_info[$row['storeID']]);
                $sales->obfCategoryID($row['id']);
                $sales->superID($row['superID']);
                $sales->lastYearSales($row['sales']);
                if (!isset($dateInfo['averageWeek']) || $dateInfo['averageWeek']) {
                    $avg_sales = ($row['sales'] / $num_days) * 7;
                    $sales->lastYearSales($avg_sales);
                }
                if ($future) {
                    $sales->actualSales(0);
                    $labor = $class_lib::getLabor($dbc);
                    $labor->obfWeekID($week->obfWeekID());
                    $labor->obfCategoryID($row['id']);
                    foreach ($labor->find() as $l) {
                        $sales->growthTarget($l->growthTarget());
                    }
                }
                $sales->save();
            }
        }
    }

    protected $salesP = null;
    protected $quarterSalesP = null;
    protected $quarterLaborP = null;
    protected $quarterSplhP = null;
    protected $stockP = null;
    protected function prepareStatements($dbc)
    {
        /**
          Look up sales for the week in a given category
        */
        $this->salesP = $dbc->prepare('SELECT s.actualSales,
                                    s.lastYearSales,
                                    s.growthTarget,
                                    n.super_name,
                                    s.superID,
                                    s.transactions,
                                    s.lastYearTransactions
                                 FROM ObfSalesCache AS s
                                    LEFT JOIN ' . $this->config->get('OP_DB') . $dbc->sep() . 'superDeptNames
                                        AS n ON s.superID=n.superID
                                 WHERE s.obfWeekID=?
                                    AND s.obfCategoryID=?
                                 ORDER BY s.superID,n.super_name');

        /**
          Look up sales for the [sales] quarter in a given category
        */
        $this->quarterSalesP = $dbc->prepare('SELECT SUM(s.actualSales) AS actual,
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
        $this->quarterLaborP = $dbc->prepare('SELECT SUM(l.hours) AS hours,
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
        $this->quarterSplhP = $dbc->prepare('SELECT SUM(c.actualSales) AS actualSales,
                                            SUM(c.lastYearSales * (1+c.growthTarget)) AS planSales
                                        FROM ObfLabor AS l
                                            INNER JOIN ObfWeeks AS w ON l.obfWeekID=w.obfWeekID
                                            INNER JOIN ObfSalesCache AS c ON c.obfWeekID=l.obfWeekID
                                                AND c.obfCategoryID=l.obfCategoryID
                                        WHERE w.obfLaborQuarterID=?
                                            AND l.obfCategoryID=?
                                            AND w.endDate <= ?');

        $this->stockP = $dbc->prepare('
            SELECT SUM(stockPurchase) AS ttl
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'stockpurchases
            WHERE tdate BETWEEN ? AND ?
                AND dept=992
                AND trans_num NOT LIKE \'1001-30-%\'
        ');
    }

    protected $trendP = null;
    protected function prepTrendsStatement($dbc, $week)
    {
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
        $splhWeekQ = $dbc->addSelectLimit($splhWeekQ, 13);
        $splhWeekP = $dbc->prepare($splhWeekQ);
        $splhWeekR = $dbc->execute($splhWeekP, array($week->obfWeekID()));
        while ($splhWeekW = $dbc->fetch_row($splhWeekR)) {
            $splhWeeks .= sprintf('%d,', $splhWeekW['obfWeekID']);
        }
        if ($splhWeeks == '(') {
            $splhWeeks .= '-99999';
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
        $this->trendP = $dbc->prepare($trendQ);
    }

    protected function getStock($dbc, $args)
    {
        $stock = $dbc->getValue($this->stockP, $args);

        return $stock !== false ? $stock / 20 : 0;
    }

    protected function ownershipThisWeek($dbc, $start_ts, $end_ts, $start_ly, $end_ly, $average_week=true)
    {
        $args3 = array(
            date('Y-m-d 00:00:00', $start_ts),
            date('Y-m-d 23:59:59', $end_ts),
        );
        $this_week = $this->getStock($dbc, $args3);
        $args4 = array(
            date('Y-m-d 00:00:00', $start_ly),
            date('Y-m-d 23:59:59', $end_ly),
        );
        $last_week = $this->getStock($dbc, $args4);
        $days = date('t', $start_ly);
        if ($average_week) {
            $last_week = round(($last_week / $days) * 7);
        }

        return array(
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
            'meta_background' => $this->colors[0],
            'meta_foreground' => 'black',
        );
    }

    protected function ownershipThisYear($dbc, $end_ts)
    {
        $args1 = array(
            date('Y-07-01 00:00:00', $end_ts),
            date('Y-m-d 23:59:59', $end_ts),
        );
        if (date('n', $end_ts) < 7) {
            $args1[0] = (date('Y', $end_ts) - 1) . '-07-01 00:00:00';
        }
        $current = $this->getStock($dbc, $args1);

        $last_year = mktime(0, 0, 0, date('n',$end_ts), date('j',$end_ts), date('Y',$end_ts)-1);
        $args2 = array(
            date('Y-07-01 00:00:00', $last_year),
            date('Y-m-d 23:59:59', $last_year),
        );
        if (date('n', $last_year) < 7) {
            $args2[0] = (date('Y', $last_year) - 1) . '-07-01 00:00:00';
        }
        $prior = $this->getStock($dbc, $args2);

        return array(
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
            'meta_background' => $this->colors[0],
            'meta_foreground' => 'black',
        );
    }
    
    protected function calculateTrend($dbc, $categoryID, $superID)
    {
        $trendR = $dbc->execute($this->trendP, array($categoryID, $superID));
        $trend_data = array();
        $t_count = 0;
        while ($trendW = $dbc->fetchRow($trendR)) {
            $trend_data[] = array($t_count, $trendW['actualSales']);
            $t_count++;
        }
        $trend_data = \COREPOS\Fannie\API\lib\Stats::removeOutliers($trend_data);
        $exp = \COREPOS\Fannie\API\lib\Stats::exponentialFit($trend_data);
        $trend1 = exp($exp->a) * exp($exp->b * $t_count);

        return $trend1;
    }
}

FannieDispatch::conditionalExec();
