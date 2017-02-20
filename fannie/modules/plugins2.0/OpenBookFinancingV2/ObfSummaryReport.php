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

class ObfSummaryReport extends ObfWeeklyReport
{
    protected $sortable = false;
    protected $no_sort_but_style = true;
    protected $header = 'OBF Summary';
    protected $title = 'OBF Summary';

    protected $report_headers = array(
        array('', 'Last Year', 'Plan Goal', 'Trend', 'Actual', '% Growth', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', 'Trend', 'Actual', '% Growth', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', 'Trend', 'Actual', '% Growth', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', 'Trend', 'Actual', '% Growth', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', 'Trend', 'Actual', '% Growth', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', 'Trend', 'Actual', '% Growth', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Current Year', 'Last Year', '', '', '', '', '', '', ''),
    );

    protected $class_lib = 'ObfLibV2';

    protected $PLAN_SALES = array(
        '1,6' => 48125.67,      // Hillside Produce
        '2,10' => 11037.90,     // Hillside Deli
        '2,11' => 30002.96,
        '2,16' => 12231.91,
        '3,1' => 24806.33,      // Hillside Grocery
        '3,4' => 61459.93,
        '3,5' => 23038.55,
        '3,7' => 98.48,
        '3,8' => 17579.95,
        '3,9' => 3313.16,
        '3,13' => 14085.38,
        '3,17' => 25413.22,
        '7,6' => 16406.47,      // Denfeld Produce
        '8,10' => 4049.92,      // Denfeld Deli
        '8,11' => 12211.43,
        '8,16' => 4768.70,
        '9,1' => 8281.40,       // Denfeld Grocery
        '9,4' => 24726.33,
        '9,5' => 9070.20,
        '9,7' => 45.52,
        '9,8' => 5975.21,
        '9,9' => 1310.53,
        '9,13' => 4589.22,
        '9,17' => 8823.08,
    );

    private $laborPercent = array(
        1 => 8.31,
        2 => 22.41,
        3 => 4.96,
        4 => 2.70,
        6 => 0.42,
        5 => 2.62,
        7 => 12.17,
        8 => 27.17,
        9 => 6.66,
        10 => 4.43,
        11 => 0.53,
    );

    public function preprocess()
    {
        return FannieReportPage::preprocess();
    }

    public function fetch_report_data()
    {
        $class_lib = $this->class_lib;
        $dbc = $class_lib::getDB();
        
        $week = $class_lib::getWeek($dbc);
        $week->obfWeekID($this->form->weekID);
        $week->load();

        $labor = new ObfLaborModelV2($dbc);
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
        $sales = $class_lib::getCache($dbc);
        $sales->obfWeekID($week->obfWeekID());
        $sales->actualSales(0, '>');
        $num_cached = $sales->find();
        $sales->reset();
        $sales->obfWeekID($week->obfWeekID());
        $sales->lastYearSales(0, '>');
        $ly_cached = $sales->find();
        if (count($num_cached) == 0 || count($ly_cached) == 0) {
            $dateInfo = array(
                'start_ts' => $start_ts,
                'end_ts' => $end_ts,
                'start_ly' => $start_ly,
                'end_ly' => $end_ly,
            );
            $this->updateSalesCache($week, array($num_cached, $ly_cached), $dateInfo);
        }

        // record set to return
        $data = array();                
        $org = array(
            'sales' => 0,
            'hours' => 0,
            'lastYear' => 0,
            'projSales' => 0,
            'projHours' => 0,
            'trendSales' => 0,
            'wages' => 0,
        );

        $this->prepareStatements($dbc);
        $this->prepTrendsStatement($dbc, $week);

        foreach (array(1,2) as $store) {

            $total_sales = $this->initTotalSales();
            $total_trans = $this->initTotalTrans();
            $total_hours = $this->initTotalHours();
            $total_wages = $this->initTotalWages();
            $qtd_sales_ou = 0;
            $qtd_hours_ou = 0;


            /**
              LOOP ONE
              Examine OBF Categories that have sales. These will include
              both sales and labor information
            */
            $categories = new ObfCategoriesModelV2($dbc);
            $categories->hasSales(1);
            $categories->storeID($store);
            foreach ($categories->find('name') as $category) {
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
                    $projID = $category->obfCategoryID() . ',' . $row['superID'];
                    $proj = $this->PLAN_SALES[$projID];
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
                }

                /** total sales for the category **/
                $record = array(
                    $category->name() . ' Sales',
                    number_format($sum[1], 0),
                    number_format($dept_proj, 0),
                    number_format($dept_trend, 0),
                    number_format($sum[0], 0),
                    sprintf('%.2f%%', $this->percentGrowth($sum[0], $sum[1])),
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
                list($proj_hours, $trend_hours) = $this->projectHours($labor->splhTarget(), $dept_proj, $dept_trend);
                // approximate wage to convert hours into dollars
                list($proj_wages, $trend_wages) = $this->projectWages($labor, $proj_hours, $trend_hours);

                $quarter = $dbc->getRow($this->quarterLaborP, 
                    array($week->obfLaborQuarterID(), $labor->obfCategoryID(), date('Y-m-d 00:00:00', $end_ts))
                );
                if ($quarter === false) {
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

                $total_hours->projected += $proj_hours;
                $total_hours->trend += $trend_hours;

                $total_hours->actual += $labor->hours();
                $qtd_hours_ou += ($quarter['hours'] - $qt_proj_hours);

                $quarter_actual_sph = $quarter['hours'] == 0 ? 0 : ($qtd_dept_sales)/($quarter['hours']);
                $quarter_proj_sph = ($qt_proj_hours == 0) ? 0 : ($qtd_dept_plan)/($qt_proj_hours);
                $data[] = array(
                    $category->name() . ' SPLH',
                    '',
                    number_format($dept_proj / $proj_hours, 2),
                    number_format($dept_trend / $trend_hours, 2),
                    number_format($labor->hours() == 0 ? 0 : $sum[0] / $labor->hours(), 2),
                    sprintf('%.2f%%', $this->percentGrowth(($labor->hours() == 0 ? 0 : $sum[0]/$labor->hours()), $dept_proj/$proj_hours)),
                    number_format(($labor->hours() == 0 ? 0 : $sum[0]/$labor->hours()) - ($dept_proj / $proj_hours), 2),
                    number_format($quarter_actual_sph - $quarter_proj_sph, 2),
                    'meta' => FannieReportPage::META_COLOR,
                    'meta_background' => $this->colors[0],
                    'meta_foreground' => 'black',
                );

                $data[] = array(
                    'Labor % of Sales',
                    '',
                    sprintF('%.2f%%', $this->laborPercent[$category->obfCategoryID()]),
                    '',
                    sprintf('%.2f%%', $labor->wages() / $sum[0] * 100),
                    '',
                    '',
                    '',
                    'meta' => FannieReportPage::META_COLOR,
                    'meta_background' => $this->colors[0],
                    'meta_foreground' => 'black',
                );

                $total_wages->actual += $labor->wages();

                if (count($this->colors) > 1) {
                    array_shift($this->colors);
                }
            }

            /**
              LOOP TWO
              Examine OBF Categories without sales. These will include
              only labor information
            */
            $cat = new ObfCategoriesModelV2($dbc);
            $cat->hasSales(0);
            $cat->storeID($store);
            $cat->name('Admin', '<>');
            foreach ($cat->find('name') as $c) {
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

                list($proj_hours, $trend_hours) = $this->projectHours($labor->splhTarget(), $total_sales->projected, $total_sales->trend);
                list($proj_wages, $trend_wages) = $this->projectWages($labor, $proj_hours, $trend_hours);

                $qtd_hours_ou += ($quarter['hours'] - $qt_proj_hours);

                $quarter_actual_sph = $quarter['hours'] == 0 ? 0 : ($total_sales->quarterActual)/($quarter['hours']);
                $quarter_proj_sph = $qt_proj_hours == 0 ? 0 : ($total_sales->quarterProjected)/($qt_proj_hours);
                $data[] = array(
                    $c->name() . ' SPLH',
                    '',
                    sprintf('%.2f', $total_sales->projected / $proj_hours),
                    sprintf('%.2f', $total_sales->trend / $trend_hours),
                    number_format($labor->hours() == 0 ? 0 : $total_sales->thisYear / $labor->hours(), 2),
                    '',
                    number_format(($labor->hours() == 0 ? 0 : $total_sales->thisYear/$labor->hours()) - ($total_sales->projected / $proj_hours), 2),
                    number_format($quarter_actual_sph - $quarter_proj_sph, 2),
                    'meta' => FannieReportPage::META_COLOR,
                    'meta_background' => $this->colors[0],
                    'meta_foreground' => 'black',
                );

                $data[] = array(
                    'Labor % of Sales',
                    '',
                    sprintf('%.2f%%', $this->laborPercent[$c->obfCategoryID()]),
                    '',
                    sprintf('%.2f%%', $labor->wages() / $total_sales->thisYear * 100),
                    '',
                    '',
                    '',
                    'meta' => FannieReportPage::META_COLOR,
                    'meta_background' => $this->colors[0],
                    'meta_foreground' => 'black',
                );

                $total_hours->actual += $labor->hours();
                $total_hours->projected += $proj_hours;
                $total_hours->trend += $trend_hours;
                $total_wages->actual += $labor->wages();

                if (count($this->colors) > 1) {
                    array_shift($this->colors);
                }
            }

            /**
               Storewide totals section
            */
            $data[] = array(
                sprintf('<a href="ObfWeeklyReportV2.php?weekID=%d&store=%d">Total Store Sales</a>', $this->form->weekID, $store),
                number_format($total_sales->lastYear, 0),
                number_format($total_sales->projected, 0),
                number_format($total_sales->trend, 0),
                number_format($total_sales->thisYear, 0),
                sprintf('%.2f%%', $this->percentGrowth($total_sales->thisYear, $total_sales->lastYear)),
                number_format($total_sales->thisYear - $total_sales->projected, 0),
                number_format($qtd_sales_ou, 0),
                'meta' => FannieReportPage::META_COLOR | FannieReportPage::META_BOLD,
                'meta_background' => $this->colors[0],
                'meta_foreground' => 'black',
            );

            $quarter_actual_sph = $total_hours->quarterActual == 0 ? 0 : ($total_sales->quarterActual)/($total_hours->quarterActual);
            $quarter_proj_sph = $total_hours->quarterProjected == 0 ? 0 : ($total_sales->quarterProjected)/($total_hours->quarterProjected);
            $data[] = array(
                'SPLH',
                '',
                sprintf('%.2f', $total_sales->projected / $total_hours->projected),
                sprintf('%.2f', $total_sales->trend / $total_hours->trend),
                number_format($total_hours->actual == 0 ? 0 : $total_sales->thisYear / $total_hours->actual, 2),
                '',
                number_format(($total_hours->actual == 0 ? 0 : $total_sales->thisYear/$total_hours->actual) - ($total_sales->projected/$total_hours->projected), 2),
                number_format($quarter_actual_sph - $quarter_proj_sph, 2),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $this->colors[0],
                'meta_foreground' => 'black',
            );

            $data[] = array(
                'Labor % of Sales',
                '',
                '',
                '',
                sprintf('%.2f%%', ($total_wages->actual / $total_sales->thisYear) * 100),
                '',
                '',
                '',
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $this->colors[0],
                'meta_foreground' => 'black',
            );

            $org['sales'] += $total_sales->thisYear;
            $org['projSales'] += $total_sales->projected;
            $org['lastYear'] += $total_sales->lastYear;
            $org['trendSales'] += $total_sales->trend;
            $org['hours'] += $total_hours->actual;
            $org['projHours'] += $total_hours->projected;
            $org['wages'] += $total_wages->actual;

            if (count($this->colors) > 1) {
                array_shift($this->colors);
            }

            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        }

        $cat = new ObfCategoriesModelV2($dbc);
        $cat->hasSales(0);
        $cat->name('Admin');
        foreach ($cat->find('name') as $c) {
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

            list($proj_hours, $trend_hours) = $this->projectHours($labor->splhTarget(), $org['projSales'], $org['trendSales']);
            list($proj_wages, $trend_wages) = $this->projectWages($labor, $proj_hours, $trend_hours);

            $qtd_hours_ou += ($quarter['hours'] - $qt_proj_hours);

            $quarter_actual_sph = $quarter['hours'] == 0 ? 0 : ($total_sales->quarterActual)/($quarter['hours']);
            $quarter_proj_sph = $qt_proj_hours == 0 ? 0 : ($total_sales->quarterProjected)/($qt_proj_hours);
            $data[] = array(
                'Admin SPLH',
                '',
                sprintf('%.2f', $org['projSales'] / $proj_hours),
                sprintf('%.2f', $org['trendSales'] / $proj_hours),
                number_format($labor->hours() == 0 ? 0 : ($org['sales']) / $labor->hours(), 2),
                '',
                number_format(($labor->hours() == 0 ? 0 : ($org['sales']/$labor->hours()) - ($org['projSales'] / $proj_hours)), 2),
                '',//number_format($quarter_actual_sph - $quarter_proj_sph, 2),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $this->colors[0],
                'meta_foreground' => 'black',
            );

            $data[] = array(
                'Labor % of Sales',
                '',
                '',
                '',
                sprintf('%.2f%%', $labor->wages() / ($org['sales']) * 100),
                '',
                '',
                '',
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $this->colors[0],
                'meta_foreground' => 'black',
            );

            $org['hours'] += $labor->hours();
            $org['projHours'] += $proj_hours;
            $org['wages'] += $labor->wages();

            if (count($this->colors) > 1) {
                array_shift($this->colors);
            }
        }

        /**
           Organization totals section
        */

        $data[] = array(
            'Organization Sales',
            number_format($org['lastYear'], 0),
            number_format($org['projSales'], 0),
            '',
            number_format($org['sales'], 0),
            sprintf('%.2f%%', $this->percentGrowth($org['sales'], $org['lastYear'])),
            number_format(($org['sales']) - ($org['projSales']), 0),
            '',
            'meta' => FannieReportPage::META_COLOR | FannieReportPage::META_BOLD,
            'meta_background' => $this->colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = $this->discountsThisWeek($dbc, $start_ts, $end_ts, $start_ly, $end_ly);

        $quarter_actual_sph = $total_hours->quarterActual == 0 ? 0 : ($total_sales->quarterActual)/($total_hours->quarterActual);
        $quarter_proj_sph = $total_hours->quarterProjected == 0 ? 0 : ($total_sales->quarterProjected)/($total_hours->quarterProjected);
        $data[] = array(
            'SLPH per Hour',
            '',
            sprintf('%.2f', ($org['projSales']) / ($org['projHours'])),
            '',
            number_format($total_hours->actual == 0 ? 0 : ($org['sales']) / ($org['hours']), 2),
            '',
            number_format($total_hours->actual == 0 ? 0 : (($org['sales'])/($org['hours'])) - (($org['projSales'])/($org['projHours'])), 2),
            '',//number_format($quarter_actual_sph - $quarter_proj_sph, 2),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $this->colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Labor % of Sales',
            '',
            '16.00%',
            '',
            sprintf('%.2f%%', $org['wages'] / $org['sales'] * 100),
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $this->colors[0],
            'meta_foreground' => 'black',
        );

        if (count($this->colors) > 1) {
            array_shift($this->colors);
        }

        $owners = $this->ownershipThisWeek($dbc, $start_ts, $end_ts, $start_ly, $end_ly);
        unset($owners[8]);
        unset($owners[9]);
        $data[] = $owners;
        $owners = $this->ownershipThisYear($dbc, $end_ts);
        unset($owners[8]);
        unset($owners[9]);
        $data[] = $owners;

        return $data;
    }

    private function discountsThisWeek($dbc, $start_ts, $end_ts, $start_ly, $end_ly)
    {
        $date1 = date('Y-m-d', $start_ts);
        $date2 = date('Y-m-d', $end_ts);
        $date3 = date('Y-m-d', $start_ly);
        $date4 = date('Y-m-d', $end_ly);

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $dlogLY = DTransactionsModel::selectDlog($date3, $date4);
        $opdb = $this->config->get('OP_DB') . $dbc->sep();

        $discountQ = "
            SELECT SUM(d.total)
            FROM __DLOG__ AS d
                LEFT JOIN {$opdb}houseCoupons AS h ON RIGHT(d.upc,5) = h.coupID
            WHERE d.tdate BETWEEN ? AND ?
                AND (
                    (d.upc='DISCOUNT' AND d.memType=5)
                    OR
                    (d.upc LIKE '00499999%' AND h.memberOnly=1)
                )
        ";

        $obj = new DateTime($date3);
        $days = $obj->diff(new DateTime($date4))->days;

        $discountP = $dbc->prepare(str_replace('__DLOG__', $dlog, $discountQ));
        $total = $dbc->getValue($discountP, array($date1 . ' 00:00:00', $date2 . ' 23:59:59'));
        $discountP = $dbc->prepare(str_replace('__DLOG__', $dlogLY, $discountQ));
        $totalLY = $dbc->getValue($discountP, array($date3 . ' 00:00:00', $date4 . ' 23:59:59'));
        $totalLY = 7 * ($totalLY / $days);

        return array(
            'Owner Discounts',
            sprintf('%.0f', $totalLY),
            '',
            '',
            sprintf('%.0f', $total),
            sprintf('%.2f%%', $this->percentGrowth($total, $totalLY)),
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $this->colors[0],
            'meta_foreground' => 'black',
        );
    }

    private function getOtherStore($storeID, $weekID)
    {
        $dbc = $this->connection;
        $conf = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($conf['ObfDatabaseV2']);
        /**
          Get sales, plan, and transactions from cache
          Loops through categories to project hours for
          each individual category based on sales
        */
        $query = $dbc->prepare('
            SELECT SUM(actualSales) AS actual,
                SUM(lastYearSales) AS lastYear,
                MAX(transactions) AS trans,
                MAX(lastYearTransactions) AS lyTrans,
                SUM(lastYearSales * (1+s.growthTarget)) AS plan,
                s.obfCategoryID AS catID
            FROM ObfSalesCache AS s
                INNER JOIN ObfCategories AS c ON s.obfCategoryID=c.obfCategoryID
            WHERE s.obfWeekID=?
                AND c.storeID=?
            GROUP BY s.obfCategoryID');
        $args = array($weekID, $storeID==1?2:1);
        $info = array('actual'=>0, 'lastYear'=>0, 'trans'=>0, 'lyTrans'=>0, 'plan'=>0);
        $res = $dbc->execute($query, $args);
        $cat = new ObfCategoriesModelV2($dbc);
        $plan = array();
        while ($row = $dbc->fetchRow($res)) {
            $info['actual'] += $row['actual'];
            $info['lastYear'] += $row['lastYear'];
            $info['trans'] = $row['trans'];
            $info['lyTrans'] = $row['lyTrans'];
            $info['plan'] += $row['plan'];
            $cat->obfCategoryID($row['catID']);
            $cat->load();
            $plan[$row['catID']] = $this->projectHours($cat->salesPerLaborHourTarget(), $row['plan'], $row['plan']);
        }

        /**
          Get additional hours & wages from non-inventory labor
          Plan hours is built from hours projected in the previous loop
          If plan hours was NOT previously calculated it means the category has
          no sales and should use total store sales for projecting instead
        */
        $extra = $dbc->prepare('
            SELECT hours,
                l.obfCategoryID AS catID
            FROM ObfLabor AS l
                INNER JOIN ObfCategories AS c ON l.obfCategoryID=c.obfCategoryID
            WHERE l.obfWeekID=?
                AND c.storeID=?');
        $info['hours'] = 0;
        $info['planHours'] = 0;
        $res = $dbc->execute($extra, $args);
        while ($row = $dbc->fetchRow($res)) {
            $info['hours'] += $row['hours'];
            if (isset($plan[$row['catID']])) {
                $info['planHours'] += $plan[$row['catID']][0];
            } else {
                $cat->obfCategoryID($row['catID']);
                $cat->load();
                list($tmpP, $tmpT) = $this->projectHours($cat->salesPerLaborHourTarget(), $info['plan'], $info['plan']);
                $info['planHours'] += $tmpP;
            }
        }

        return $info;
    }

}

FannieDispatch::conditionalExec();
