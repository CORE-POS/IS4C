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

class AnnualMeetingParser extends Parser {

	var $plus = array('1042','1041');
	var $descriptions = array(
		'1042' => 'OWNER MEAL',
		'1041' => 'GUEST MEAL'
	);

	function check($str){
		if (strlen($str) < 4) return False;
		$plu = substr($str,0,4);
		if (in_array($plu, $this->plus)){
			if (strlen($str)==4)
				return True;
			elseif(strtoupper($str[4])=='M')
				return True;
			elseif(strtoupper($str[4])=='V')
				return True;
			elseif(strtoupper($str[4])=='S')
				return True;
			elseif(strtoupper($str[4])=='K')
				return True;
		}
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$ret = $this->default_json();
		if (strlen($str)==4){
			$CORE_LOCAL->set('qmInput',$str);
			$desc = $this->descriptions[$str];
			$opts = array(
				$desc.' (Steak)' => 'M',
				$desc.' (Risotto)' => 'V',
				$desc.' (Squash V)' => 'S'
			);
			if ($str == 1041){
				$opts[$desc.' (Kids)'] = 'K';
			}
			$CORE_LOCAL->set('qmNumber', $opts);
			$plugin_info = new QuickMenus();
			$ret['main_frame'] = $plugin_info->plugin_url().'/QMDisplay.php';
			return $ret;
		}
		else {
			$flag = strtoupper($str[4]);
			$plu = substr($str,0,4);
			$price = ($flag == 'K') ? 5.00 : 20.00;
			TransRecord::addRecord(array(
				'upc' => str_pad($plu,13,'0',STR_PAD_LEFT),
				'description' => $this->descriptions[$plu].' ('.$flag.')',
				'trans_type' => 'I',
				'department' => 235, 
                'quantity' => 1.0, 
                'ItemQtty' => 1.0, 
                'unitPrice' => $price,
                'total' => $price,
                'regPrice' => $price,
				'charflag' => $flag
			));
			$ret['output'] = DisplayLib::lastpage();
			$ret['redraw_footer'] = True;
			return $ret;
		}
	}

}
