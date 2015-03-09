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

class SigTermCommands extends Parser 
{
	private $cb_error;

	function check($str)
    {
		if ($str == "TERMMANUAL") {
			UdpComm::udpSend("termManual");
			CoreLocal::set("paycard_keyed", true);

			return true;

		} else if ($str == "TERMRESET" || $str == "TERMREBOOT") {
			if ($str == "TERMRESET") {
				UdpComm::udpSend("termReset");
			} else {
				UdpComm::udpSend("termReboot");
            }
			CoreLocal::set("paycard_keyed", false);
			CoreLocal::set("CachePanEncBlock","");
			CoreLocal::set("CachePinEncBlock","");
			CoreLocal::set("CacheCardType","");
			CoreLocal::set("CacheCardCashBack",0);
			CoreLocal::set('ccTermState','swipe');

			return true;

		} else if ($str == "CCFROMCACHE") {

			return true;

		} else if (substr($str,0,9) == "PANCACHE:") {
			CoreLocal::set("CachePanEncBlock",substr($str,9));
			CoreLocal::set('ccTermState','type');
            if (CoreLocal::get('PaycardsStateChange') == 'coordinated') {
                if (CoreLocal::get('PaycardsAllowEBT') == 1) {
                    UdpComm::udpSend('termGetTypeWithFS');
                } else {
                    UdpComm::udpSend('termGetType');
                }
            } else {
                // check for out of order messages from terminal
                if (CoreLocal::get('CacheCardType') != '' && CoreLocal::get('CacheCardType') == 'CREDIT') {
                    CoreLocal::set('ccTermState', 'ready');
                } else if (CoreLocal::get('CacheCardType') != '' && CoreLocal::get('CachePinEncBlock') != '') {
                    CoreLocal::set('ccTermState', 'ready');
                }
            }

			return true;

		} else if (substr($str,0,9) == "PINCACHE:") {
			CoreLocal::set("CachePinEncBlock",substr($str,9));
			CoreLocal::set('ccTermState','ready');
            if (CoreLocal::get('PaycardsStateChange') == 'coordinated') {
                UdpComm::udpSend('termWait');
            }

			return true;

		} else if (substr($str,0,6) == "VAUTH:") {
			CoreLocal::set("paycard_voiceauthcode",substr($str,6));

			return true;

		} else if (substr($str,0,8) == "EBTAUTH:") {
			CoreLocal::set("ebt_authcode",substr($str,8));

			return true;

		} else if (substr($str,0,5) == "EBTV:"){
			CoreLocal::set("ebt_vnum",substr($str,5));

			return true;

		} else if ($str == "TERMCLEARALL") {
			CoreLocal::set("CachePanEncBlock","");
			CoreLocal::set("CachePinEncBlock","");
			CoreLocal::set("CacheCardType","");
			CoreLocal::set("CacheCardCashBack",0);
            CoreLocal::set('ccTermState', 'swipe');

			return true;

		} else if (substr($str,0,5) == "TERM:") {
			CoreLocal::set("CacheCardType",substr($str,5));
			switch(CoreLocal::get('CacheCardType')) {
                case 'CREDIT':
                    CoreLocal::set('ccTermState','ready');
                    if (CoreLocal::get('PaycardsStateChange') == 'coordinated') {
                        UdpComm::udpSend('termWait');
                    }
                    break;
                case 'DEBIT':
                    if (CoreLocal::get('PaycardsOfferCashBack') == 1) {
                        CoreLocal::set('ccTermState','cashback');
                        if (CoreLocal::get('PaycardsStateChange') == 'coordinated') {
                            UdpComm::udpSend('termCashBack');
                        }
                    } else {
                        CoreLocal::set('ccTermState','pin');
                        if (CoreLocal::get('PaycardsStateChange') == 'coordinated') {
                            UdpComm::udpSend('termGetPin');
                        }
                    }
                    break;
                case 'EBTFOOD':
                    CoreLocal::set('ccTermState','pin');
                    if (CoreLocal::get('PaycardsStateChange') == 'coordinated') {
                        UdpComm::udpSend('termGetPin');
                    }
                    break;
                case 'EBTCASH':
                    if (CoreLocal::get('PaycardsOfferCashBack') == 1) {
                        CoreLocal::set('ccTermState','cashback');
                        if (CoreLocal::get('PaycardsStateChange') == 'coordinated') {
                            UdpComm::udpSend('termCashBack');
                        }
                    } else {
                        CoreLocal::set('ccTermState','pin');
                        if (CoreLocal::get('PaycardsStateChange') == 'coordinated') {
                            UdpComm::udpSend('termGetPin');
                        }
                    }
                    break;
			}

            if (CoreLocal::get('PaycardsStateChange') == 'direct') {
                // check for out of order messages from terminal
                if (CoreLocal::get('CacheCardType') != '' && CoreLocal::get('CachePanEncBlock') != '' && CoreLocal::get('CachePinEncBlock') != '') {
                    CoreLocal::set('ccTermState', 'ready');
                }
            }

			return true;

		} elseif (substr($str,0,7) == "TERMCB:") {
			$cashback = substr($str,7);
            $termLimit = CoreLocal::get('PaycardsTermCashBackLimit');
            if ($termLimit === '') {
                $termLimit = 40;
            }
			if ($cashback <= $termLimit) {
				$this->cb_error = false;
				CoreLocal::set("CacheCardCashBack",$cashback);
			} else {
				$this->cb_error = true;
			}
			CoreLocal::set('ccTermState','pin');
            if (CoreLocal::get('PaycardsStateChange') == 'coordinated') {
                UdpComm::udpSend('termGetPin');
            }

			return true;
		}

		return false;
	}

	function parse($str)
    {
		$ret = $this->default_json();
		$ret['scale'] = ''; // redraw righthand column
		if ($str == "CCFROMCACHE") {
			$ret['retry'] = CoreLocal::get("CachePanEncBlock");
		}
		if ($this->cb_error) {
            $ret['output'] = DisplayLib::boxMsg(
                'Cash back set to zero instead',
                _('Invalid cash back selection'),
                false,
                DisplayLib::standardClearButton()
            );
		}

		return $ret;
	}

	function doc()
    {
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>TERMMANUAL</td>
				<td>
				Send CC terminal to manual entry mode
				</td>
			</tr>
			<tr>
				<td>TERMRESET</td>
				<td>Reset CC terminal to begin transaction</td>
			</tr>
			<tr>
				<td>CCFROMCACHE</td>
				<td>Charge the card cached earlier</td>
			</tr>
			<tr>
				<td>PANCACHE:<encrypted block></td>
				<td>Cache an encrypted block on swipe</td>
			</tr>
			<tr>
				<td>PINCACHE:<encrypted block></td>
				<td>Cache an encrypted block on PIN entry</td>
			</tr>
			</table>";
	}
}

