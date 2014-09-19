<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
class NeedDiscountParser extends Parser {
	function check($str){
		if ($str == "FF") return True;
		else return False;
	}
	function parse($str){
		global $CORE_LOCAL;
        $ret = $this->default_json();

        if ($CORE_LOCAL->get('isMember') !== 1) {
            $ret['output'] =  DisplayLib::boxMsg(_("must be a member to use this discount"));
            return $ret;
        } elseif ($CORE_LOCAL->get('NeedDiscountFlag')==1) {
        	$ret['output'] =  DisplayLib::boxMsg(_("discount already applied"));
    		return $ret;
    	} else {
    		$CORE_LOCAL->set('NeedDiscountFlag',1);
        	Database::getsubtotals();
        	$NBDisc = number_format($CORE_LOCAL->get('discountableTotal') * $CORE_LOCAL->get('needBasedPercent'), 2);
        	// $NBDupc = substr(strtoupper(str_replace(' ','',$CORE_LOCAL->get('needBasedName'))),0,13);
        	$NBDupc = "NEEDBASEDDISC";
        	$NBDname = $CORE_LOCAL->get('needBasedName');
        	TransRecord::addRecord(array(
                'upc' => $NBDupc, 
                'description' => $NBDname, 
                'trans_type' => "I", 
                'trans_subtype' => "IC", 
                'trans_status' => "C", 
                'quantity' => 1, 
                'ItemQtty' => 1, 
                'unitPrice' => -1*$NBDisc,
                'total' => -1*$NBDisc,
                'regPrice' => -1*$NBDisc,
                'voided' => 29
            ));
        	$ret['output'] = DisplayLib::lastpage();
        	$ret['redraw_footer'] = True;
        	return $ret;
        }
    }
}
?>
