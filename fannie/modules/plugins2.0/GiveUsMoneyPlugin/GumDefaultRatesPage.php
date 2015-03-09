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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class GumDefaultRatesPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Default Rates] set default rates for loans based on principal thresholds.';

    public function preprocess()
    {
        $this->header = 'Default Interest Rates';
        $this->title = 'Default Interest Rates';
        $this->__routes[] = 'post<rate><lower><upper>';
        $this->__routes[] = 'get<delete>';

        return parent::preprocess();
    }

    public function get_delete_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $model = new GumLoanDefaultInterestRatesModel($dbc);
        $model->gumLoanDefaultInterestRateID($this->delete);
        $model->delete();

        header('Location: GumDefaultRatesPage.php');

        return false;
    }

    public function post_rate_lower_upper_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $model = new GumLoanDefaultInterestRatesModel($dbc);
        $model->interestRate($this->rate / 100.00);
        $model->lowerBound($this->lower);
        $model->upperBound($this->upper);
        $model->save();

        header('Location: GumDefaultRatesPage.php');

        return false;
    }

    public function get_view()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $model = new GumLoanDefaultInterestRatesModel($dbc);
        $ret = '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>Between</th><th>Rate (%)</th><th>&nbsp;</th></tr>';
        foreach($model->find('interestRate') as $obj) {
            $ret .= sprintf('<tr>
                            <td>%s and %s</td>
                            <td>%.2f</td>
                            <td><a href="GumDefaultRatesPage.php?delete=%d">Delete</a></td>
                            </tr>',
                            number_format($obj->lowerBound(), 2), number_format($obj->upperBound(), 2),
                            $obj->interestRate() * 100,
                            $obj->gumLoanDefaultInterestRateID()
            );
        }
        $ret .= '</table>';

        $ret .= '<hr />';

        $ret .= '<form action="GumDefaultRatesPage.php" method="post">';
        $ret .= '<b>Add New</b><br />';
        $ret .= '<b>Rate (%)</b>: <input type="text" size="4" name="rate" />';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>Between</b>: <input type="text" size="8" name="lower" value="0" />';
        $ret .= ' and ';
        $ret .= '<input type="text" size="8" name="upper" value="0" />';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" value="Add" />';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

