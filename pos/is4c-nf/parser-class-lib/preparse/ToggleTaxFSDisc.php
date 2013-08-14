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

class ToggleTaxFSDisc extends PreParser {
	var $tfd;
	var $remainder;

	var $TAX = 4;
	var $FS = 2;
	var $DISC = 1;

	// use bit-masks to determine the which toggles
	// should be enabled
	function check($str){
		global $CORE_LOCAL;
		$this->tfd = 0;
		if (substr($str,0,5) == "1TNFN" || substr($str,0,5) == "FN1TN"){
			$this->remainder = substr($str,5);
			$this->tfd = $this->tfd | $this->TAX;
			$this->tfd = $this->tfd | $this->FS;	
			return True;
		}
		elseif (substr($str,0,4) == "FNDN" || substr($str,0,4) == "DNFN"){
			$this->remainder = substr($str,4);
			$this->tfd = $this->tfd | $this->DISC;
			$this->tfd = $this->tfd | $this->FS;	
			return True;
		}
		elseif (substr($str,0,3) == "1TN"){
			$this->remainder = substr($str,3);
			$this->tfd = $this->tfd | $this->TAX;
			return True;

		}
		elseif (substr($str,0,2) == "FN" && substr($str,2,2) != "TL"){
			$this->remainder = substr($str,2);
			$this->tfd = $this->tfd | $this->FS;	
			return True;
		}
		elseif (substr($str,0,2) == "DN"){
			$this->remainder = substr($str,2);
			$this->tfd = $this->tfd | $this->DISC;	
			return True;
		}
		return False;	
	}

	function parse($str){
		global $CORE_LOCAL;
		if ($this->tfd & $this->TAX)
			$CORE_LOCAL->set("toggletax",1);
		if ($this->tfd & $this->FS)
			$CORE_LOCAL->set("togglefoodstamp",1);
		if ($this->tfd & $this->DISC)
			$CORE_LOCAL->set("toggleDiscountable",1);
		return $this->remainder;	
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>1TN<i>ringable</i></td>
				<td>Toggle tax setting for <i>ringable</i>
				which may be an item or group of same items
				using *</td>
			</tr>
			<tr>
				<td>FN<i>ringable</i></td>
				<td>Toggle foodstamp setting for <i>ringable</i>
				which may be an item or group of same items
				using *</td>
			</tr>
			<tr>
				<td>DN<i>ringable</i></td>
				<td>Toggle discount setting for <i>ringable</i>
				which may be an item or group of same items
				using *</td>
			</tr>
			</table>";
	}
}

?>
