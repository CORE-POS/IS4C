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
use COREPOS\pos\lib\DeptLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\Scanning\SpecialDept;
use COREPOS\pos\parser\Parser;

class DeptKey extends Parser 
{
    public function check($str)
    {
        /**
           Ex:
           199DP10
           199DP
        */
        if (preg_match('/^[\d-]+DP\d*$/', $str)) {
            return true;
        }

        return false;
    }

    private function strToPieces($str)
    {
        $split = explode("DP",$str);
        $dept = $split[1];
        $amt = $split[0];
        if (strstr($amt, '.')) {
            $amt = round($amt * 100);
        }

        return array($amt, $dept);
    }

    public function parse($str)
    {
        $myUrl = MiscLib::baseURL();
        $ret = $this->default_json();
        list($amt, $dept) = $this->strToPieces($str);

        /**
          This "if" is the new addition to trigger the
          department select screen
        */
        if (empty($dept)) {
            // no department specified, just amount followed by DP
            
            // maintain refund if needed
            if ($this->session->get("refund")) {
                $amt = "RF" . $amt;
            }

            // go to the department select screen
            $ret['main_frame'] = $myUrl.'gui-modules/deptlist.php?in=' . $amt;
        } elseif ($this->session->get("refund")==1 && $this->session->get("refundComment") == "") {
            if ($this->session->get("SecurityRefund") > 20) {
                $ret['main_frame'] = $myUrl."gui-modules/adminlogin.php?class=COREPOS-pos-lib-adminlogin-RefundAdminLogin";
            } else {
                $ret['main_frame'] = $myUrl.'gui-modules/refundComment.php';
            }
            $this->session->set("refundComment",$this->session->get("strEntered"));
        }

        /* apply any appropriate special dept modules */
        $deptmods = $this->getMods();
        $ret = $this->applyMods($deptmods, $dept, $amt, $ret);
        
        if (!$ret['main_frame']) {
            $lib = new DeptLib($this->session);
            $ret = $lib->deptkey($amt, $dept, $ret);
        }

        return $ret;
    }

    private function getMods()
    {
        $deptmods = $this->session->get('SpecialDeptMap');
        $dbc = Database::pDataConnect();
        if (!is_array($deptmods) && ($this->session->get('NoCompat') == 1 || $dbc->table_exists('SpecialDeptMap'))) {
            $model = new \COREPOS\pos\lib\models\op\SpecialDeptMapModel($dbc);
            $deptmods = $model->buildMap();
            $this->session->set('SpecialDeptMap', $deptmods);
        }

        return $deptmods;
    }

    private function applyMods($deptmods, $dept, $amt, $ret)
    {
        $index = (int)($dept/10);
        if (is_array($deptmods) && isset($deptmods[$index])) {
            foreach($deptmods[$index] as $mod) {
                $obj = SpecialDept::factory($mod, $this->session);
                $ret = $obj->handle($dept,$amt/100,$ret);
            }
        }

        return $ret;
    }

    public function doc()
    {
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td><i>amount</i>DP<i>department</i>0</td>
                <td>Ring up <i>amount</i> to the specified
                <i>department</i>. The trailing zero is
                necessary for historical purposes</td>
            </tr>
            </table>";
    }
}

