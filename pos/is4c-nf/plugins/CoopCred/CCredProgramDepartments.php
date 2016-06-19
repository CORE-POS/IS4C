<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto, Canada

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

use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\Scanning\SpecialDept;

class CCredProgramDepartments extends SpecialDept 
{

    public function handle($deptID,$amount,$json)
    {
        global $CORE_LOCAL;

        $realDeptID = ($deptID / 10);
        $programOK = CoopCredLib::programOK($realDeptID);
        if ($programOK !== True) {
            /* If there's a problem can boxMsg2 only accept CL?
             */
            $boxMsg = $programOK .
                "<br /><span style='font-size:0.8em;'>Press [clear]</span>";
        } else {

            $programCode = $CORE_LOCAL->get("CCredProgramCode");
            if ($programCode == '') {
                $programCode = 'empty';
            }
            $programName = $CORE_LOCAL->get("{$programCode}programName");

            CoopCredLib::addDepartmentUsed($realDeptID, $CORE_LOCAL->get("CCredProgramID"));

            $boxMsg = "<b>Coop Cred Input to<br />'{$programName}'</b><br />$" .
                number_format($amount,2) . "<br />Remember to " .
                "keep your receipt.<br />" .
                "<span style='font-size:0.8em;'>[enter] to continue<br />[clear] to cancel</span>";
        }

        if ($CORE_LOCAL->get('msgrepeat') == 0) {
            $CORE_LOCAL->set("boxMsg",$boxMsg);
            $json['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php?quiet=1';
        }

        return $json;
    }

}

