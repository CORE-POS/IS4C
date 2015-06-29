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

class HouseholdSeparateDiscounts extends \COREPOS\Fannie\API\member\MemberModule {

    public function width()
    {
        return parent::META_WIDTH_HALF;
    }

    function showEditForm($memNum, $country="US")
    {
        $account = self::getAccount();
        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">Household Members</div>
            <div class=\"panel-body\">";
        
        $count = 0; 
        foreach ($account['customers'] as $infoW) {
            if ($infoW['accountHolder']) {
                continue;
            }
            $ret .= sprintf('
                <div class="form-inline form-group">
                    <span class="label primaryBackground">Name</span>
                    <input name="HouseholdSeparateDiscounts_fn[]" placeholder="First"
                        maxlength="30" value="%s" class="form-control" />
                    <input name="HouseholdSeparateDiscounts_ln[]" placeholder="Last"
                        maxlength="30" value="%s" class="form-control" />
                    <div class="input-group">
                        <input type="text" class="form-control price-field"
                            name="HouseholdSeparateDiscounts_discount[]" value="%d" />
                        <span class="input-group-addon">%%</span>
                    </div>
                </div>',
                $infoW['firstName'],$infoW['lastName'],$infoW['discount']);
            $count++;
        }

        while ($count < 3) {
            $ret .= sprintf('
                <div class="form-inline form-group">
                    <span class="label primaryBackground">Name</span>
                    <input name="HouseholdSeparateDiscounts_fn[]" placeholder="First"
                        maxlength="30" value="" class="form-control" />
                    <input name="HouseholdSeparateDiscounts_ln[]" placeholder="Last"
                        maxlength="30" value="" class="form-control" />
                    <div class="input-group">
                        <input type="text" class="form-control price-field"
                            name="HouseholdSeparateDiscounts_discount[] value="0" />
                        <span class="input-group-addon">%%</span>
                    </div>
                </div>');
            $count++;
        }

        $ret .= "</div>";
        $ret .= "</div>";

        return $ret;
    }

    function saveFormData($memNum)
    {
        $dbc = $this->db();
        if (!class_exists("CustdataModel")) {
            include(dirname(__FILE__) . '/../../classlib2.0/data/models/CustdataModel.php');
        }

        /**
          Use primary member for default column values
        */
        $custdata = new CustdataModel($dbc);
        $custdata->CardNo($memNum);
        $custdata->personNum(1);
        if (!$custdata->load()) {
            return "Error: Problem saving household members<br />"; 
        }

        $fns = FormLib::get('HouseholdSeparateDiscounts_fn',array());
        $lns = FormLib::get('HouseholdSeparateDiscounts_ln',array());
        $discs = FormLib::get('HouseholdSeparateDiscounts_discount', array());
        $pn = 2;
        $errors = false;
        for ($i=0; $i<count($lns); $i++) {
            if (empty($fns[$i]) && empty($lns[$i])) {
                continue;
            }

            $custdata->personNum($pn);
            $custdata->FirstName($fns[$i]);
            $custdata->LastName($lns[$i]);
            $custdata->Discount($discs[$i]);
            if (!$custdata->save()) {
                $errors = true;
            }

            $pn++;
        }

        /**
          Remove any names outside the set that just saved
        */
        $clearP = $dbc->prepare('
            DELETE FROM custdata
            WHERE CardNo=?
                AND personNum >= ?');
        $clearR = $dbc->execute($clearP, array($memNum, $pn));

        if ($errors) {
            return "Error: Problem saving household members<br />"; 
        }

        return '';
    }
}

?>
