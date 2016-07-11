<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

use COREPOS\pos\lib\Scanning\SpecialUPC;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;

/**
  @class MagicPLU

  This module matches specific UPCs to a function
  Only here for legacy support. Probably not a great
  idea. Hardcoding a function to a UPC doesn't scale
  very well. Keeping track of what every special PLU
  does would get messy.
*/
class MagicPLU extends SpecialUPC 
{

    public function isSpecial($upc)
    {
        if ($upc == "0000000008005" || $upc == "0000000008006") {
            return true;
        }

        return false;
    }

    public function handle($upc,$json)
    {
        $my_url = MiscLib::base_url();

        switch(ltrim($upc,'0')) {
            case '8006':
                if (CoreLocal::get("memberID") == 0) {
                    $json['main_frame'] = $my_url.'gui-modules/memlist.php';
                } else if (CoreLocal::get("msgrepeat") == 0){
                    CoreLocal::set("boxMsg","<B>".$total." stock payment</B><BR>insert form<BR>press [enter] to endorse<P><FONT size='-1'>[clear] to cancel</FONT>");
                    $ret["main_frame"] = $my_url."gui-modules/boxMsg2.php?endorse=stock&endorseAmt=".$total;
                }
                break;
            case '8005':
                if (CoreLocal::get("memberID") == 0) {
                    $json['main_frame'] = $my_url.'gui-modules/memlist.php';
                } elseif (CoreLocal::get("isMember") == 0) {
                    $json['output'] = DisplayLib::boxMsg(
                        _("member discount not applicable"),
                        '',
                        false,
                        DisplayLib::standardClearButton()
                    );
                } elseif (CoreLocal::get("percentDiscount") > 0) {
                    $json['output'] = DisplayLib::boxMsg(
                        CoreLocal::get("percentDiscount")."% discount already applied",
                        '',
                        false,
                        DisplayLib::standardClearButton()
                    );
                }
                break;    
        }

        // magic plu, but other conditions not matched
        if ($json['main_frame'] === false && empty($json['output'])) {
            $json['output'] = DisplayLib::boxMsg(
                $upc."<br />is not a valid item",
                '',
                false,
                DisplayLib::standardClearButton()
            );
        }

        return $json;
    }
}

