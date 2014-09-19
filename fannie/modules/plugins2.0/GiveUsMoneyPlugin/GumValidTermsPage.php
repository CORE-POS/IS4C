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
class GumValidTermsPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Loan Terms] configures what loan lengths are allowed.';

    public function preprocess()
    {
        $this->header = 'Available Loan Terms';
        $this->title = 'Available Loan Terms';
        $this->__routes[] = 'post<length><limit>';
        $this->__routes[] = 'get<delete>';

        return parent::preprocess();
    }

    public function get_delete_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $model = new GumLoanValidTermsModel($dbc);
        $model->gumLoanValidTermID($this->delete);
        // should only find one
        foreach($model->find() as $obj) {
            $obj->delete();
        }

        header('Location: GumValidTermsPage.php');

        return false;
    }

    public function post_length_limit_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $model = new GumLoanValidTermsModel($dbc);
        $model->termInMonths($this->length);
        $model->totalPrincipalLimit($this->limit); 
        $model->save();

        header('Location: GumValidTermsPage.php');

        return false;
    }

    public function get_view()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $model = new GumLoanValidTermsModel($dbc);
        $ret = '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>Length (Months)</th><th>Limit ($)</th><th>&nbsp;</th></tr>';
        $sum = 0.0;
        foreach($model->find('termInMonths') as $obj) {
            $ret .= sprintf('<tr>
                            <td>%d</td>
                            <td>%s</td>
                            <td><a href="GumValidTermsPage.php?delete=%d">Delete</a></td>
                            </tr>',
                            $obj->termInMonths(),
                            number_format($obj->totalPrincipalLimit(), 2),
                            $obj->gumLoanValidTermID()
            );
            $sum += $obj->totalPrincipalLimit();
        }
        $ret .= sprintf('<tr><th>Total</th><td>%s</td><td>&nbsp;</td></tr>', number_format($sum,2));
        $ret .= '</table>';

        $ret .= '<hr />';

        $ret .= '<form action="GumValidTermsPage.php" method="post">';
        $ret .= '<b>Add New</b><br />';
        $ret .= '<b>Length</b>: <input type="text" size="4" name="length" />';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>Limit</b>: <input type="text" size="8" name="limit" value="0" />';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" value="Add" />';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

