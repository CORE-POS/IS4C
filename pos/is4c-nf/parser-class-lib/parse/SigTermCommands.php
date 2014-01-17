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
		global $CORE_LOCAL;
		if ($str == "TERMMANUAL") {
			UdpComm::udpSend("termManual");
			$CORE_LOCAL->set("paycard_keyed", true);

			return true;

		} else if ($str == "TERMRESET" || $str == "TERMREBOOT") {
			if ($str == "TERMRESET") {
				UdpComm::udpSend("termReset");
			} else {
				UdpComm::udpSend("termReboot");
            }
			$CORE_LOCAL->set("paycard_keyed", false);
			$CORE_LOCAL->set("CachePanEncBlock","");
			$CORE_LOCAL->set("CachePinEncBlock","");
			$CORE_LOCAL->set("CacheCardType","");
			$CORE_LOCAL->set("CacheCardCashBack",0);
			$CORE_LOCAL->set('ccTermState','swipe');

			return true;

		} else if ($str == "CCFROMCACHE") {

			return true;

		} else if (substr($str,0,9) == "PANCACHE:") {
			$CORE_LOCAL->set("CachePanEncBlock",substr($str,9));
			$CORE_LOCAL->set('ccTermState','type');
            if ($CORE_LOCAL->get('PaycardsStateChange') == 'coordinated') {
                UdpComm::udpSend('termGetType');
            } else {
                // check for out of order messages from terminal
                if ($CORE_LOCAL->get('CacheCardType') != '' && $CORE_LOCAL->get('CacheCardType') == 'CREDIT') {
                    $CORE_LOCAL->set('ccTermState', 'ready');
                } else if ($CORE_LOCAL->get('CacheCardType') != '' && $CORE_LOCAL->get('CachePinEncBlock') != '') {
                    $CORE_LOCAL->set('ccTermState', 'ready');
                }
            }

			return true;

		} else if (substr($str,0,9) == "PINCACHE:") {
			$CORE_LOCAL->set("CachePinEncBlock",substr($str,9));
			$CORE_LOCAL->set('ccTermState','ready');
            if ($CORE_LOCAL->get('PaycardsStateChange') == 'coordinated') {
                UdpComm::udpSend('termWait');
            }

			return true;

		} else if (substr($str,0,6) == "VAUTH:") {
			$CORE_LOCAL->set("paycard_voiceauthcode",substr($str,6));

			return true;

		} else if (substr($str,0,8) == "EBTAUTH:") {
			$CORE_LOCAL->set("ebt_authcode",substr($str,8));

			return true;

		} else if (substr($str,0,5) == "EBTV:"){
			$CORE_LOCAL->set("ebt_vnum",substr($str,5));

			return true;

		} else if ($str == "TERMCLEARALL") {
			$CORE_LOCAL->set("CachePanEncBlock","");
			$CORE_LOCAL->set("CachePinEncBlock","");
			$CORE_LOCAL->set("CacheCardType","");
			$CORE_LOCAL->set("CacheCardCashBack",0);
            $CORE_LOCAL->set('ccTermState', 'swipe');

			return true;

		} else if (substr($str,0,5) == "TERM:") {
			$CORE_LOCAL->set("CacheCardType",substr($str,5));
			switch($CORE_LOCAL->get('CacheCardType')) {
                case 'CREDIT':
                    $CORE_LOCAL->set('ccTermState','ready');
                    if ($CORE_LOCAL->get('PaycardsStateChange') == 'coordinated') {
                        UdpComm::udpSend('termWait');
                    }
                    break;
                case 'DEBIT':
                    if ($CORE_LOCAL->get('PaycardsOfferCashBack') == 1) {
                        $CORE_LOCAL->set('ccTermState','cashback');
                        if ($CORE_LOCAL->get('PaycardsStateChange') == 'coordinated') {
                            UdpComm::udpSend('termCashBack');
                        }
                    } else {
                        $CORE_LOCAL->set('ccTermState','pin');
                        if ($CORE_LOCAL->get('PaycardsStateChange') == 'coordinated') {
                            UdpComm::udpSend('termGetPin');
                        }
                    }
                    break;
                case 'EBTFOOD':
                    $CORE_LOCAL->set('ccTermState','pin');
                    if ($CORE_LOCAL->get('PaycardsStateChange') == 'coordinated') {
                        UdpComm::udpSend('termGetPin');
                    }
                    break;
                case 'EBTCASH':
                    if ($CORE_LOCAL->get('PaycardsOfferCashBack') == 1) {
                        $CORE_LOCAL->set('ccTermState','cashback');
                        if ($CORE_LOCAL->get('PaycardsStateChange') == 'coordinated') {
                            UdpComm::udpSend('termCashBack');
                        }
                    } else {
                        $CORE_LOCAL->set('ccTermState','pin');
                        if ($CORE_LOCAL->get('PaycardsStateChange') == 'coordinated') {
                            UdpComm::udpSend('termGetPin');
                        }
                    }
                    break;
			}

            if ($CORE_LOCAL->get('PaycardsStateChange') == 'direct') {
                // check for out of order messages from terminal
                if ($CORE_LOCAL->get('CacheCardType') != '' && $CORE_LOCAL->get('CachePanEncBlock') != '' && $CORE_LOCAL->get('CachePinEncBlock') != '') {
                    $CORE_LOCAL->set('ccTermState', 'ready');
                }
            }

			return true;

		} else if (substr($str,0,7) == "TERMCB:") {
			$cashback = substr($str,7);
			if ($cashback <= 40) {
				$this->cb_error = false;
				$CORE_LOCAL->set("CacheCardCashBack",$cashback);
			} else {
				$this->cb_error = true;
			}
			$CORE_LOCAL->set('ccTermState','pin');
            if ($CORE_LOCAL->get('PaycardsStateChange') == 'coordinated') {
                UdpComm::udpSend('termGetPin');
            }

			return true;
		}

		return false;
	}

	function parse($str)
    {
		global $CORE_LOCAL;
		$ret = $this->default_json();
		$ret['scale'] = ''; // redraw righthand column
		if ($str == "CCFROMCACHE") {
			$ret['retry'] = $CORE_LOCAL->get("CachePanEncBlock");
		}
		if ($this->cb_error) {
			$CORE_LOCAL->set('boxMsg','Warning: Invalid cash back<br />
					selection ignored');
			$ret['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php';	
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

