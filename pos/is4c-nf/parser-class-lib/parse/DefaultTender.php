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

class DefaultTender extends Parser {

	function check($str){
		if (!is_numeric(substr($str,-2)) && 
		    is_numeric(substr($str,0,strlen($str)-2)))
			return True;
		elseif (strlen($str) == 2 && !is_numeric($str)){
			$db = Database::pDataConnect();
			$q = "SELECT TenderCode FROM tenders WHERE TenderCode='$str'";
			$r = $db->query($q);
			if ($db->num_rows($r) > 0)
				return True;
		}
		return False;
	}

	function parse($str)
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
                $ret = $this->default_json();
                $ret['main_frame'] = MiscLib::baseURL() . 'gui-modules/boxMsg2.php';

                return $ret;
            }
        }

		if (strlen($str) > 2){
			$left = substr($str,0,strlen($str)-2);
			$right = substr($str,-2);
			$ret = PrehLib::tender($right,$left);
			return $ret;
		}
		else {
			$ret = $this->default_json();

			$base_object = new TenderModule($str, False);
			$tender_object = 0;
			$map = CoreLocal::get("TenderMap");
			if (is_array($map) && isset($map[$str])){
				$class = $map[$str];
				$tender_object = new $class($str, False);
			}

			$errors = $base_object->ErrorCheck();
			if ($errors !== True){
				$ret['output'] = $errors;
				return $ret;
			}

			if (is_object($tender_object)){
				$errors = $tender_object->ErrorCheck();
				if ($errors !== True){
					$ret['output'] = $errors;
					return $ret;
				}
			}
		
			if (is_object($tender_object) && !$tender_object->AllowDefault()){
				$ret['output'] = $tender_object->DisabledPrompt();
				return $ret;
			}
			elseif(is_object($tender_object) && $tender_object->AllowDefault()){
                CoreLocal::set('RepeatAgain', true);
				$ret['main_frame'] = $tender_object->DefaultPrompt();
				return $ret;
			}
			else if ($base_object->AllowDefault()){
                CoreLocal::set('RepeatAgain', true);
				$ret['main_frame'] = $base_object->DefaultPrompt();
				return $ret;
			}
			else {
				$ret['output'] = $base_object->DisabledPrompt();
				return $ret;
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

?>
