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
class GumEquityPayoffPage extends FannieRESTfulPage 
{
    public function preprocess()
    {
        $this->header = 'Class C Payoff';
        $this->title = 'Class C Payoff';

        return parent::preprocess();
    }

    public function get_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $this->payoff = new GumEquitySharesModel($dbc);
        $this->payoff->gumEquityShareID($this->id);
        if (!$this->payoff->load()) {
            echo _('Error: payoff') . ' #' . $this->id . ' ' . _('does not exist');
            return false;
        }

        $this->all = new GumEquitySharesModel($dbc);
        $this->all->card_no($this->payoff->card_no());

        $this->custdata = new CustdataModel($dbc);
        $this->custdata->whichDB($FANNIE_OP_DB);
        $this->custdata->CardNo($this->payoff->card_no());
        $this->custdata->personNum(1);
        $this->custdata->load();

        $this->meminfo = new MeminfoModel($dbc);
        $this->meminfo->whichDB($FANNIE_OP_DB);
        $this->meminfo->card_no($this->payoff->card_no());
        $this->meminfo->load();

        $this->taxid = new GumTaxIdentifiersModel($dbc);
        $this->taxid->card_no($this->payoff->card_no());

        $this->settings = new GumSettingsModel($dbc);

        return true;
    }

    public function css_content()
    {
        return '
            table#infoTable td {
                text-align: center;
                padding: 0 40px 0 40px;
            }
            table #infoTable {
                margin-left: auto;
                margin-right: auto;
            }
            table#infoTable tr.red td {
                color: red;
            }
        ';
    }

    public function get_id_view()
    {
        global $FANNIE_URL;
        $ret = '';

        $ret .= '<p>
            Based on the terms of Wholefoods Community Coop Class C Stock you Payout Request has been received and approved by  our Board of Directors.  Please find attached a schedule of your most recent Class C activity as well as a check for the class C payout.  Thankyou for your continued support.  
            </p>';

        $ret .= '<table id="infoTable" cellspacing="0" cellpadding="4">';
        $ret .= '<tr><td colspan="3">Class C Schedule</td></tr>';
        $ret .= '<tr><td>Date</td><td>Shares</td><td>Total</td></tr>';
        foreach($this->all->find('tdate') as $obj) {
            $ret .= sprintf('<tr class="%s">
                            <td>%s</td>
                            <td>%d</td>
                            <td>%s</td>
                            </tr>',
                            $obj->shares() < 0 ? 'red' : '',
                            date('m/d/Y', strtotime($obj->tdate())),
                            $obj->shares(),
                            number_format($obj->value(), 2)
            );
            if ($obj->gumEquityShareID() == $this->id) {
                break;
            }
        }
        $ret .= '</table>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

