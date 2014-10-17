<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

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

class AR extends \COREPOS\Fannie\API\member\MemberModule 
{

    public function width()
    {
        return parent::META_WIDTH_THIRD;
    }

    function showEditForm($memNum,$country="US")
    {
        global $FANNIE_URL,$FANNIE_TRANS_DB, $FANNIE_ROOT;

        $dbc = $this->db();
        $trans = $FANNIE_TRANS_DB.$dbc->sep();
        
        $infoQ = $dbc->prepare_statement("SELECT n.balance
                FROM {$trans}ar_live_balance AS n 
                WHERE n.card_no=?");
        $infoR = $dbc->exec_statement($infoQ,array($memNum));
        $infoW = $dbc->fetch_row($infoR);

        if (!class_exists("CustdataModel")) {
            include($FANNIE_ROOT.'classlib2.0/data/models/CustdataModel.php');
        }
        $model = new CustdataModel($dbc);
        $model->CardNo($memNum);
        $model->personNum(1);
        $model->load();
        $limit = $model->ChargeLimit();
        if ($limit == 0) {
            $limit = $model->MemDiscountLimit();
        }


        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">A/R</div>
            <div class=\"panel-body\">";

        $ret .= '<div class="form-group form-inline">';
        $ret .= '<span class="label primaryBackground">Limit</span> ';
        $ret .= '<div class="input-group"><span class="input-group-addon">$</span>';
        $ret .= sprintf('<input name="AR_limit" value="%d" class="form-control" />
                ',$limit);
        $ret .= '</div>';
        $ret .= '</div>';

        $ret .= '<div class="form-group">';
        $ret .= '<span class="label primaryBackground">Current Balance</span> ';
        $ret .= sprintf('%.2f',$infoW['balance']); 
        $ret .= ' ';
        $ret .= "<a href=\"{$FANNIE_URL}reports/AR/index.php?memNum=$memNum\">History</a>";
        $ret .= '</div>';

        $ret .= '<div class="form-group">';
        $ret .= "<a href=\"{$FANNIE_URL}mem/correction_pages/MemArTransferTool.php?memIN=$memNum\">Transfer A/R</a>";
        $ret .= ' | ';
        $ret .= "<a href=\"{$FANNIE_URL}mem/correction_pages/MemArEquitySwapTool.php?memIN=$memNum\">Convert A/R</a>";
        $ret .= '</div>';

        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    function saveFormData($memNum)
    {
        global $FANNIE_ROOT;
        $dbc = $this->db();
        if (!class_exists("CustdataModel")) {
            include($FANNIE_ROOT.'classlib2.0/data/models/CustdataModel.php');
        }

        $limit = FormLib::get_form_value('AR_limit',0);
        $model = new CustdataModel($dbc);
        $model->CardNo($memNum);
        $test = false;
        foreach($model->find() as $obj) {
            $obj->MemDiscountLimit($limit);
            $obj->ChargeLimit($limit);
            $obj->ChargeOk( $limit == 0 ? 0 : 1 );
            $test = $obj->save();
        }
        
        if ($test === false) {
            return 'Error: Problme saving A/R limit<br />';
        } else {
            return '';
        }
    }
}

