<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of Fannie.

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
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class CWMemberSummaryReport extends FannieRESTfulPage {

    public $description = '[Member Summary Report] lists information about purchase history.
        Requires CoreWarehouse plugin.';
    public $themed = true;

    protected $header = 'Member Summary';
    protected $title = 'Member Summary';

    // conversion factor for
    // spotlight => year
    const S_TO_Y = 4;

    public function get_id_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['WarehouseDatabase']);

        $model = new MemberSummaryModel($dbc);
        $model->card_no($this->id);
        $model->load();

        $nf = new NumberFormatter('en_US', NumberFormatter::ORDINAL);

        $ret = '<table class="table table-bordered table-striped">
            <tr>
                <th>Member#</th>
                <td>' . $this->id . '</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <th>First Visit</th>
                <td>' . $this->fixDate($model->firstVisit()) . '</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <th>Lastest Visit</th>
                <td>' . $this->fixDate($model->lastVisit()) . '</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <th>Total Visits</th>
                <td>' . $model->totalVisits() . '</td>
                <td>' 
                    . $nf->format($model->yearTotalVisitsRank()) . ' this year; '
                    . $nf->format($model->totalVisitsRank()) . ' all time
                </td>
            </tr>
            <tr>
                <th>Total Spending ($)</th>
                <td>' . $model->totalSpending() . '</td>
                <td>' 
                    . $nf->format($model->yearTotalSpendingRank()) . ' this year; '
                    . $nf->format($model->totalSpendingRank()) . ' all time
                </td>
            </tr>
            <tr>
                <th>Total Spending (#items)</th>
                <td>' . $model->totalItems() . '</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <th>Average Spending ($)</th>
                <td>' . $model->averageSpending() . '</td>
                <td>' 
                    . $nf->format($model->yearAverageSpendingRank()) . ' this year; '
                    . $nf->format($model->averageSpendingRank()) . ' all time
                </td>
            </tr>
            <tr>
                <th>Average Spending (#items)</th>
                <td>' . sprintf('%.2f', $model->averageItems()) . '</td>
                <td>&nbsp;</td>
            </tr>
            </table>';

        $ret .= '<h3>Trends &amp; Patterns</h3>';
        $ret .= '<table class="table table-bordered">
            <tr>
                <th>Spotlight period</th>
                <td>' . $this->fixDate($model->spotlightStart()) . ' to ' 
                    . $this->fixDate($model->spotlightEnd()) . '</td>
            </tr>
            <tr>
                <th>Last Year period</th>
                <td>' . $this->fixDate($model->yearStart()) . ' to ' 
                    . $this->fixDate($model->yearEnd()) . '</td>
            </tr>
            </table>';

        $ret .= '<table class="table table-bordered table-striped">
            <tr>
                <th>&nbsp;</th>
                <th>Spotlight</th>
                <th title="Same period last year">YoY</th>
                <th title="Spotlight vs YoY">% Growth</th>
                <th title="Same period all years">All YoY</th>
                <th title="Spotlight vs All YoY">% Growth</th>
                <th>Last Year</th>
                <th title="Spotlight vs Last Year">% Growth</th>
            </tr>';

        $ret .= sprintf('<tr>
            <th>Total Spending ($)</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>n/a</th>
            <td>%.2f</th>
            <td>%.2f</th>
            </tr>',
            $model->spotlightTotalSpending(),
            $model->oldlightTotalSpending(),
            $this->percentGrowth($model->spotlightTotalSpending(), $model->oldlightTotalSpending()),
            $model->longlightTotalSpending(),
            $model->yearTotalSpending(),
            $this->percentGrowth(self::S_TO_Y*$model->spotlightTotalSpending(), $model->yearTotalSpending())
        );

        $ret .= sprintf('<tr>
            <th>Average Spending ($)</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            </tr>',
            $model->spotlightAverageSpending(),
            $model->oldlightAverageSpending(),
            $this->percentGrowth($model->spotlightAverageSpending(), $model->oldlightAverageSpending()),
            $model->longlightAverageSpending(),
            $this->percentGrowth($model->spotlightAverageSpending(), $model->longlightAverageSpending()),
            $model->yearAverageSpending(),
            $this->percentGrowth($model->spotlightAverageSpending(), $model->yearAverageSpending())
        );

        $ret .= sprintf('<tr>
            <th>Total Spending (Items)</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>n/a</th>
            <td>%.2f</th>
            <td>%.2f</th>
            </tr>',
            $model->spotlightTotalItems(),
            $model->oldlightTotalItems(),
            $this->percentGrowth($model->spotlightTotalItems(), $model->oldlightTotalItems()),
            $model->longlightTotalItems(),
            $model->yearTotalItems(),
            $this->percentGrowth(self::S_TO_Y*$model->spotlightTotalItems(), $model->yearTotalItems())
        );

        $ret .= sprintf('<tr>
            <th>Average Spending (Items)</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            </tr>',
            $model->spotlightAverageItems(),
            $model->oldlightAverageItems(),
            $this->percentGrowth($model->spotlightAverageItems(), $model->oldlightAverageItems()),
            $model->longlightAverageItems(),
            $this->percentGrowth($model->spotlightAverageItems(), $model->longlightAverageItems()),
            $model->yearAverageItems(),
            $this->percentGrowth($model->spotlightAverageItems(), $model->yearAverageItems())
        );

        $ret .= sprintf('<tr>
            <th>Total Visits</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>%.2f</th>
            <td>n/a</th>
            <td>%.2f</th>
            <td>%.2f</th>
            </tr>',
            $model->spotlightTotalVisits(),
            $model->oldlightTotalVisits(),
            $this->percentGrowth($model->spotlightTotalVisits(), $model->oldlightTotalVisits()),
            $model->longlightTotalVisits(),
            $model->yearTotalVisits(),
            $this->percentGrowth(self::S_TO_Y * $model->spotlightTotalVisits(), $model->yearTotalVisits())
        );

        $ret .= '</table>';

        return $ret;
    }

    public function get_view()
    {
        return '<form method="get">
            <label>Member#</label>
            <input type="text" name="id" class="form-control" />
            <button type="submit" class="btn btn-default">Submit</button>
            </form>';
    }

    private function percentGrowth($a, $b)
    {
        return sprintf('%.2f%%', 100 * ($a-$b)/((float)$a));
    }

    private function fixDate($date)
    {
        return date('M j, Y', strtotime($date));
    }

}

FannieDispatch::conditionalExec();

