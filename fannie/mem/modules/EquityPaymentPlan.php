<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

class EquityPaymentPlan extends \COREPOS\Fannie\API\member\MemberModule {

    public function width()
    {
        return parent::META_WIDTH_THIRD;
    }

    public function showEditForm($memNum, $country="US")
    {
        global $FANNIE_URL,$FANNIE_TRANS_DB;

        $dbc = $this->db();
        $trans = $FANNIE_TRANS_DB.$dbc->sep();
        
        $infoQ = $dbc->prepare("SELECT payments
                FROM {$trans}equity_live_balance
                WHERE memnum=?");
        $infoR = $dbc->execute($infoQ,array($memNum));
        $equity = 0;
        if ($dbc->num_rows($infoR) > 0) {
            $w = $dbc->fetch_row($infoR);
            $equity = $w['payments'];
        }

        $plan = new EquityPaymentPlanAccountsModel($dbc);
        $plan->cardNo($memNum);
        foreach ($plan->find() as $p) {
            $plan = $p;
            break;
        }
        $allplans = new EquityPaymentPlansModel($dbc);

        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">Equity</div>
            <div class=\"panel-body\">";

        $ret .= '<div class="form-group">';
        $ret .= '<span class="label primaryBackground">Stock Purchased</span> ';
        $ret .= sprintf('%.2f',$equity);
        $ret .= " <a href=\"{$FANNIE_URL}reports/Equity/index.php?memNum=$memNum\">History</a>";
        $ret .= '</div>';

        $ret .= '<div class="form-group form-inline">';
        $ret .= '<span class="label primaryBackground">Payment Plan</span> ';
        $ret .= '<select name="payment-plan" class="form-control input-sm">
                    <option value="0">None</option>';
        $ret .= $allplans->toOptions($plan->equityPaymentPlanID());
        $ret .= '</select>
                </div>';

        $ret .= '<div class="form-group">';
        $ret .= '<span class="label primaryBackground">Last Payment</span> ';
        $ret .= sprintf('%s $%.2f', $plan->lastPaymentDate(), $plan->lastPaymentAmount());
        $ret .= '</div>';

        $ret .= '<div class="form-group">';
        $ret .= '<span class="label primaryBackground">Next Payment</span> ';
        $ret .= sprintf('%s $%.2f', $plan->nextPaymentDate(), $plan->nextPaymentAmount());
        $ret .= '</div>';

        $ret .= '<div class="form-group">';
        $ret .= "<a href=\"{$FANNIE_URL}mem/correction_pages/MemEquityTransferTool.php?memIN=$memNum\">Transfer Equity</a>";
        $ret .= ' | ';
        $ret .= "<a href=\"{$FANNIE_URL}mem/correction_pages/MemArEquitySwapTool.php?memIN=$memNum\">Convert Equity</a>";
        $ret .= '</div>';

        $ret .= "</div>";
        $ret .= "</div>";

        return $ret;
    }

    public function saveFormData($memNum, $json=array())
    {
        $dbc = $this->db();
        $model = new EquityPaymentPlanAccountsModel($dbc);
        $model->cardNo($memNum);
        $plan = FormLib::get('payment-plan');
        $errors = false;
        if ($plan > 0) {
            $model->equityPaymentPlanID($plan);
            $errors = $model->save() ? false : true;
        } else {
            $errors = $model->delete() ? false : true;
        }

        if ($errors) {
            return 'Error: problem saving Payment Plan<br />';
        } else {
            return '';
        }
    }
}

