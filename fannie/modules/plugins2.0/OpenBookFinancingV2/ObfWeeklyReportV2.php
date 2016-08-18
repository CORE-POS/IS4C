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

class ObfWeeklyReportV2 extends ObfWeeklyReport
{
    protected $sortable = false;
    protected $no_sort_but_style = true;

    protected $report_headers = array(
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Current Year', 'Last Year', '', '', '', '', '', '', ''),
    );

    protected $class_lib = 'ObfLibV2';

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

        $store = FormLib::get('store');
        
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

        $total_sales = $this->initTotalSales();
        $total_trans = $this->initTotalTrans();
        $total_hours = $this->initTotalHours();
        $total_wages = $this->initTotalWages();
        $qtd_sales_ou = 0;
        $qtd_hours_ou = 0;

        $this->prepareStatements($dbc);
        $this->prepTrendsStatement($dbc, $week);

        /**
          LOOP ONE
          Examine OBF Categories that have sales. These will include
          both sales and labor information
        */
        $categories = new ObfCategoriesModelV2($dbc);
        $categories->hasSales(1);
        $categories->storeID($store);
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

            $total_hours->projected += $proj_hours;
            $total_hours->trend += $trend_hours;

            $quarter_actual_sph = $quarter['hours'] == 0 ? 0 : ($qtd_dept_sales)/($quarter['hours']);
            $quarter_proj_sph = ($qt_proj_hours == 0) ? 0 : ($qtd_dept_plan)/($qt_proj_hours);
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
        $cat = new ObfCategoriesModelV2($dbc);
        $cat->hasSales(0);
        $cat->storeID($store);
        $cat->name('Admin', '<>');
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

            list($proj_hours, $trend_hours) = $this->projectHours($labor->splhTarget(), $total_sales->projected, $total_sales->trend);
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

            $quarter_actual_sph = $quarter['hours'] == 0 ? 0 : ($total_sales->quarterActual)/($quarter['hours']);
            $quarter_proj_sph = $qt_proj_hours == 0 ? 0 : ($total_sales->quarterProjected)/($qt_proj_hours);
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
            $total_hours->projected += $proj_hours;
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

        $quarter_actual_sph = $total_hours->quarterActual == 0 ? 0 : ($total_sales->quarterActual)/($total_hours->quarterActual);
        $quarter_proj_sph = $total_hours->quarterProjected == 0 ? 0 : ($total_sales->quarterProjected)/($total_hours->quarterProjected);
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

        $transGrowth = $store == 1 ? 0.925 : 1.0;
        $proj_trans = $total_trans->lastYear * $transGrowth;
        $qtd_proj_trans = $total_trans->quarterLastYear * $transGrowth;
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

        $otherStore = $this->getOtherStore($store, $week->obfWeekID());

        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        $cat = new ObfCategoriesModelV2($dbc);
        $cat->hasSales(0);
        $cat->name('Admin');
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

            list($proj_hours, $trend_hours) = $this->projectHours($labor->splhTarget(), $total_sales->projected+$otherStore['plan'], $total_sales->trend);
            list($proj_wages, $trend_wages) = $this->projectWages($labor, $proj_hours, $trend_hours);

            $data[] = array(
                'Hours',
                '',
                number_format($proj_hours, 0),
                '',
                '',//number_format($trend_hours, 0),
                number_format($labor->hours(), 0),
                '',
                '',
                number_format($labor->hours() - $proj_hours, 0),
                '',//number_format($quarter['hours'] - $qt_proj_hours, 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $this->colors[0],
                'meta_foreground' => 'black',
            );
            $qtd_hours_ou += ($quarter['hours'] - $qt_proj_hours);

            $quarter_actual_sph = $quarter['hours'] == 0 ? 0 : ($total_sales->quarterActual)/($quarter['hours']);
            $quarter_proj_sph = $qt_proj_hours == 0 ? 0 : ($total_sales->quarterProjected)/($qt_proj_hours);
            $data[] = array(
                'Sales per Hour',
                '',
                sprintf('%.2f', ($total_sales->projected+$otherStore['plan']) / $proj_hours),
                '',
                '',//sprintf('%.2f', $total_sales->trend / $trend_hours),
                number_format($labor->hours() == 0 ? 0 : ($total_sales->thisYear+$otherStore['actual']) / $labor->hours(), 2),
                '',
                '',
                number_format(($labor->hours() == 0 ? 0 : $total_sales->thisYear/$labor->hours()) - ($total_sales->projected / $proj_hours), 2),
                '',//number_format($quarter_actual_sph - $quarter_proj_sph, 2),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $this->colors[0],
                'meta_foreground' => 'black',
            );

            $total_hours->actual += $labor->hours();
            $total_hours->projected += $proj_hours;
            $total_hours->trend += $trend_hours;

            if (count($this->colors) > 1) {
                array_shift($this->colors);
            }
        }

        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        /**
           Organization totals section
        */
        $data[] = $this->headerRow('Total Organization');

        $data[] = array(
            'Sales',
            number_format($total_sales->lastYear+$otherStore['lastYear'], 0),
            number_format($total_sales->projected+$otherStore['plan'], 0),
            '',
            '',
            number_format($total_sales->thisYear+$otherStore['actual'], 0),
            sprintf('%.2f%%', $this->percentGrowth($total_sales->thisYear+$otherStore['actual'], $total_sales->lastYear+$otherStore['lastYear'])),
            '',
            number_format(($total_sales->thisYear+$otherStore['actual']) - ($total_sales->projected+$otherStore['plan']), 0),
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $this->colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Hours',
            '',
            number_format($total_hours->projected+$otherStore['planHours'], 0),
            '',
            '',
            number_format($total_hours->actual+$otherStore['hours'], 0),
            '',
            '',
            number_format(($total_hours->actual+$otherStore['hours']) - ($total_hours->projected+$otherStore['planHours']), 0),
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $this->colors[0],
            'meta_foreground' => 'black',
        );

        $quarter_actual_sph = $total_hours->quarterActual == 0 ? 0 : ($total_sales->quarterActual)/($total_hours->quarterActual);
        $quarter_proj_sph = $total_hours->quarterProjected == 0 ? 0 : ($total_sales->quarterProjected)/($total_hours->quarterProjected);
        $data[] = array(
            'Sales per Hour',
            '',
            sprintf('%.2f', ($total_sales->projected+$otherStore['plan']) / ($total_hours->projected+$otherStore['planHours'])),
            '',
            '',
            number_format($total_hours->actual == 0 ? 0 : ($total_sales->thisYear+$otherStore['actual']) / ($total_hours->actual+$otherStore['hours']), 2),
            '',
            '',
            number_format($total_hours->actual == 0 ? 0 : (($total_sales->thisYear+$otherStore['actual'])/($total_hours->actual+$otherStore['hours'])) - (($total_sales->projected+$otherStore['plan'])/($total_hours->projected+$otherStore['planHours'])), 2),
            '',//number_format($quarter_actual_sph - $quarter_proj_sph, 2),
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
