<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

class DefaultTender extends Parser 
{
    public function check($str)
    {
        if (!is_numeric(substr($str,-2)) && 
            is_numeric(substr($str,0,strlen($str)-2))) {
            return true;
        } elseif (strlen($str) == 2 && !is_numeric($str)){
            $dbc = Database::pDataConnect();
            $res = $dbc->query("SELECT TenderCode FROM tenders WHERE TenderCode='$str'");
            if ($dbc->num_rows($res) > 0) {
                return true;
            }
        }
        return false;
    }

    private function blocked($str)
    {
        /**
          If customer card is available, prevent other tenders
          unless specficially allowed (e.g., coupons).
        */
        if (CoreLocal::get('PaycardsBlockTenders') == 1) {
            $tender_code = strtoupper(substr($str, -2));
            $exceptions = strtoupper(CoreLocal::get('PaycardsBlockExceptions'));
            $except_array = preg_split('/[^A-Z]+/', $exceptions, 0, PREG_SPLIT_NO_EMPTY);
            if (CoreLocal::get('ccTermState') == 'ready' && !in_array($tender_code, $except_array)) {
                CoreLocal::set('boxMsg', _('Tender customer card before other tenders'));
                CoreLocal::set('boxMsgButtons', array(
                    'Charge Card [enter]' => '$(\'#reginput\').val(\'\');submitWrapper();',
                    'Cancel [clear]' => '$(\'#reginput\').val(\'CL\');submitWrapper();',
                ));
                CoreLocal::set('strEntered', 'CCFROMCACHE');
                $ret['main_frame'] = MiscLib::baseURL() . 'gui-modules/boxMsg2.php';

                return $ret;
            }
        }

        return false;
    }

    function parse($str)
    {
        $ret = $this->default_json();

        $block = $this->blocked($str);
        if ($block !== false) {
            return $block;
        }

        if (strlen($str) > 2){
            $left = substr($str,0,strlen($str)-2);
            $right = substr($str,-2);
            return PrehLib::tender($right,$left);
        } else {
            $base_object = new TenderModule($str, False);
            $objs = array($base_object);
            $map = CoreLocal::get("TenderMap");
            if (is_array($map) && isset($map[$str])){
                $class = $map[$str];
                $tender_object = new $class($str, False);
                $objs[] = $tender_object;
            }

            foreach ($objs as $object) {
                $errors = $object->ErrorCheck();
                if ($errors !== true){
                    $ret['output'] = $errors;
                    return $ret;
                }
            }
            
            $objs = array_reverse($objs);

            foreach ($objs as $object) {
                if (!$object->AllowDefault()) {
                    $ret['output'] = $object->DisabledPrompt();
                    return $ret;
                } else {
                    CoreLocal::set('RepeatAgain', true);
                    $ret['main_frame'] = $object->DefaultPrompt();
                    return $ret;
                }
            }
        }
    }

    function isLast(){
        return True;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td><b>ANYTHING</b></td>
                <td>If all else fails, assume the last
                two letters are a tender code and the
                rest is an amount</td>
            </tr>
            <tr>
                <td colspan=2><i>This module is last. Cashier training
                can ignore this completely</i></td>
            </tr>
            </table>";
    }
}

