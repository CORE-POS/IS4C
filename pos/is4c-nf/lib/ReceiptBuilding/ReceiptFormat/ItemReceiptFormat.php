<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op.

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

/**
  @class ItemFormat
  Module for print-formatting 
  item records. 
*/
class ItemReceiptFormat extends DefaultReceiptFormat 
{

	/**
	  Formatting function
	  @param $row a single receipt record
	  @return a formatted string
	*/
	public function format($row)
    {
		if ($row['trans_type'] == 'D') {
			// department open ring; not much to format
			return $this->align($row['description'],'',$row['total'],$this->flags($row));
		} else if ($row['trans_status'] == 'D') {
			// a "YOU SAVED" line
			$description = strtolower($row['description']);
			$description = str_replace("**"," >",$description);
			return $description;
		} else if ($row['trans_status'] == 'M') {
			// member special line
            return $this->align($row['description'], 'Owner Special', $row['total'], '');
		} else {
			// an item record

			$comment = "";
			if ($row['charflag']=='SO') {
				// intentional. special orders can have weird
				// quantity fields
				$comment = "";
			} elseif (isset($row['scale']) && $row['scale'] != 0 && $row['quantity'] != 0) {
				$comment = sprintf('%.2f @ %.2f',$row['quantity'],$row['unitPrice']);
			} else if (abs($row['ItemQtty']>1)) {
				$comment = sprintf('%d @ %.2f',$row['quantity'],$row['total']/$row['quantity']);
			} else if ($row['matched'] > 0) {
				$comment = 'w/ vol adj';
			}

			if ($row['numflag'] > 0) $row['description'] .= '*';

			return $this->align($row['description'],$comment,$row['total'],$this->flags($row));
		}
	}

	/**
	  Determine flags for a row
	*/
	private function flags($row)
    {
		if ($row['trans_status']=='V') {
            return 'VD';
		} elseif ($row['trans_status']=='R') {
            return 'RF';
		} else {
			$flags = '';
			if($row['tax'] != 0) {
				$flags .= 'T';
            }
			if($row['foodstamp'] != 0) {
				$flags .= 'F';
            }
			return $flags;
		}
	}

	/**
	  Pad fields into a standard width and alignment
	*/
	private function align($description, $comment, $amount, $flags="")
    {
		$amount = sprintf('%.2f',$amount);
		if ($amount=="0.00") $amount="";

		$ret = str_pad($description,30,' ',STR_PAD_RIGHT);
		$ret .= str_pad($comment,14,' ',STR_PAD_RIGHT);
		$ret .= str_pad($amount,8,' ',STR_PAD_LEFT);
		$ret .= str_pad($flags,4,' ',STR_PAD_LEFT);
		
		return $ret;
	}
}

