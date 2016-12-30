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

namespace COREPOS\pos\parser\parse;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\lib\Tenders\TenderModule;
use \CoreLocal;
use COREPOS\pos\parser\Parser;

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
            return $dbc->numRows($res) > 0 ? true : false;
        }
        return false;
    }

    private function blocked($str)
    {
        /**
          If customer card is available, prevent other tenders
          unless specficially allowed (e.g., coupons).
        */
        if ($this->session->get('PaycardsBlockTenders') == 1) {
            $tenderCode = strtoupper(substr($str, -2));
            $exceptions = strtoupper($this->session->get('PaycardsBlockExceptions'));
            $exceptArray = preg_split('/[^A-Z]+/', $exceptions, 0, PREG_SPLIT_NO_EMPTY);
            if ($this->session->get('ccTermState') == 'ready' && !in_array($tenderCode, $exceptArray)) {
                $this->session->set('boxMsg', _('Tender customer card before other tenders'));
                $this->session->set('boxMsgButtons', array(
                    _('Charge Card [enter]') => '$(\'#reginput\').val(\'\');submitWrapper();',
                    _('Cancel [clear]') => '$(\'#reginput\').val(\'CL\');submitWrapper();',
                ));
                $this->session->set('strEntered', 'CCFROMCACHE');
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
        } 

        $baseObject = new TenderModule($str, False);
        $objs = array($baseObject);
        $map = $this->session->get("TenderMap");
        if (is_array($map) && isset($map[$str])){
            $class = $map[$str];
            if (!class_exists($class)) { // try namespaced version
                $class = 'COREPOS\\pos\\lib\\Tenders\\' . $class;
            }
            $tenderObject = new $class($str, False);
            $objs[] = $tenderObject;
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
            }
            $this->session->set('RepeatAgain', true);
            $ret['main_frame'] = $object->DefaultPrompt();
            return $ret;
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

