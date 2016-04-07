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
                    <label>
                        <span class="label primaryBackground">Checks</span>
                        <input type="checkbox" name="HouseholdSeparateDiscounts_writeChecks[]" %s value="%d" />
                    </label>
                    <label>
                        <span class="label primaryBackground">FFA</span>
                        <input type="checkbox" name="HouseholdSeparateDiscounts_SSI[]" %s value="%d" />
                    </label>
                    <input name="HouseholdMembers_ID[]" type="hidden" value="%d" />
                </div>',
                $infoW['firstName'],$infoW['lastName'],$infoW['discount'],
                ($infoW['checksAllowed'] ? 'checked' : ''), $count+2, 
                ($infoW['lowIncomeBenefits'] ? 'checked' : ''), $count+2, 
                $infoW['customerID']); // $count+2 == personNum
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
                    <label>
                        <span class="label primaryBackground">Checks</span>
                        <input type="checkbox" name="HouseholdSeparateDiscounts_writeChecks[] value="%d" />
                    </label>
                    <label>
                        <span class="label primaryBackground">FFA</span>
                        <input type="checkbox" name="HouseholdSeparateDiscounts_SSI[] value="%d" />
                    </label>
                    <input name="HouseholdMembers_ID[]" type="hidden" value="0" />
                </div>', $count+2, $count+2);
            $count++;
        }

        $ret .= "</div>";
        $ret .= "</div>";

        return $ret;
    }

    public function saveFormData($memNum, $json=array())
    {
        $primary = array('discount'=>0, 'staff'=>0, 'lowIncomeBenefits'=>0, 'chargeAllowed'=>0, 'checksAllowed'=>0);
        foreach ($json['customers'] as $c) {
            if ($c['accountHolder']) {
                $primary = $c;
                break;
            }
        }

        $fns = FormLib::get('HouseholdSeparateDiscounts_fn',array());
        $lns = FormLib::get('HouseholdSeparateDiscounts_ln',array());
        $discs = FormLib::get('HouseholdSeparateDiscounts_discount', array());
        $checks = FormLib::get('HouseholdSeparateDiscounts_writeChecks', array());
        $ssi = FormLib::get('HouseholdSeparateDiscounts_SSI', array());
        $ids = FormLib::get('HouseholdMembers_ID', array());
        $pn = 2;
        for ($i=0; $i<count($lns); $i++) {
            if ($ids[$i] == 0) { // add new name
                $json['customers'][] = array(
                    'customerID' => $ids[$i],
                    'firstName' => $fns[$i],
                    'lastName' => $lns[$i],
                    'accountHolder' => 0,
                    'discount' => $discs[$i],
                    'staff' => $primary['staff'],
                    'lowIncomeBenefits' => in_array($pn, $ssi) ? 1 : 0,
                    'chargeAllowed' => $primary['chargeAllowed'],
                    'checksAllowed' => in_array($pn, $checks) ? 1 : 0,
                );
                $pn++;
            } else { // update or remove existing name
                for ($j=0; $j<count($json['customers']); $j++) {
                    if ($json['customers'][$j]['customerID'] == $ids[$i]) {
                        if ($fns[$i] == '' && $lns[$i] == '') {
                            unset($json['customers'][$j]);
                        } else {
                            $json['customers'][$j]['firstName'] = $fns[$i];
                            $json['customers'][$j]['lastName'] = $lns[$i];
                            $json['customers'][$j]['accountHolder'] = 0;
                            $json['customers'][$j]['discount'] = $discs[$i];
                            $json['customers'][$j]['lowIncomeBenefits'] = in_array($pn, $ssi) ? 1 : 0;
                            $json['customers'][$j]['checksAllowed'] = in_array($pn, $checks) ? 1 : 0;
                        }
                    }
                }
            }
        }

        return $json;
    }
}

