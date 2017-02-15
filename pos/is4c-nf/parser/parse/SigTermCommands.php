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
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\parser\Parser;

class SigTermCommands extends Parser 
{
    private $cbError;

    function check($str)
    {
        if ($str == "TERMMANUAL") {
            UdpComm::udpSend("termManual");
            $this->session->set("paycard_keyed", true);

            return true;

        } elseif ($str == "TERMRESET" || $str == "TERMREBOOT") {
            UdpComm::udpSend($str == 'TERMRESET' ? 'termReset' : 'termReboot');
            $this->stateReset();

            return true;

        } elseif ($str == "CCFROMCACHE") {

            return true;

        } elseif (substr($str,0,9) == "PANCACHE:") {
            $this->session->set("CachePanEncBlock",substr($str,9));
            $this->session->set('ccTermState','type');
            if ($this->session->get('PaycardsStateChange') == 'coordinated') {
                UdpComm::udpSend($this->session->get('PaycardsAllowEBT') == 1 ? 'termGetTypeWithFS' : 'termGetType'); 
            } else {
                // check for out of order messages from terminal
                if ($this->session->get('CacheCardType') != '' && $this->session->get('CacheCardType') == 'CREDIT') {
                    $this->session->set('ccTermState', 'ready');
                } elseif ($this->session->get('CacheCardType') != '' && $this->session->get('CachePinEncBlock') != '') {
                    $this->session->set('ccTermState', 'ready');
                }
            }

            return true;

        } elseif (substr($str,0,9) == "PINCACHE:") {
            $this->session->set("CachePinEncBlock",substr($str,9));
            $this->session->set('ccTermState','ready');
            if ($this->session->get('PaycardsStateChange') == 'coordinated') {
                UdpComm::udpSend('termWait');
            }

            return true;

        } elseif (substr($str,0,6) == "VAUTH:") {
            $this->session->set("paycard_voiceauthcode",substr($str,6));

            return true;

        } elseif (substr($str,0,8) == "EBTAUTH:") {
            $this->session->set("ebt_authcode",substr($str,8));

            return true;

        } elseif (substr($str,0,5) == "EBTV:"){
            $this->session->set("ebt_vnum",substr($str,5));

            return true;

        } elseif ($str == "TERMCLEARALL") {
            $this->stateReset();

            return true;
        } elseif ($str == 'TERMAUTOENABLE') {
            $this->session->set('PaycardsStateChange', 'direct');
            $query = "
                UPDATE parameters
                SET param_value='direct'
                WHERE param_key='PaycardsStateChange'
                    AND (lane_id=0 OR lane_id=?)";
            $dbc = Database::pDataConnect();
            $prep = $dbc->prepare($query);
            $res = $dbc->execute($prep, array($this->session->get('laneno')));

            return true;
        } elseif ($str == 'TERMAUTODISABLE') {
            $this->session->set('PaycardsStateChange', 'coordinated');
            $query = "
                UPDATE parameters
                SET param_value='coordinated'
                WHERE param_key='PaycardsStateChange'
                    AND (lane_id=0 OR lane_id=?)";
            $dbc = Database::pDataConnect();
            $prep = $dbc->prepare($query);
            $res = $dbc->execute($prep, array($this->session->get('laneno')));

            return true;
        } elseif (substr($str, 0, 7) == "TERM:DC") { 
            $this->session->set('ccTermState', substr($str, -4));
            return true;
        } elseif (substr($str,0,5) == "TERM:") {
            $this->session->set("CacheCardType",substr($str,5));
            switch($this->session->get('CacheCardType')) {
                case 'CREDIT':
                    $this->session->set('ccTermState','ready');
                    if ($this->session->get('PaycardsStateChange') == 'coordinated') {
                        UdpComm::udpSend('termWait');
                    }
                    break;
                case 'DEBIT':
                    if ($this->session->get('PaycardsOfferCashBack') == 1) {
                        $this->session->set('ccTermState','cashback');
                        if ($this->session->get('PaycardsStateChange') == 'coordinated') {
                            if ($this->session->get('runningtotal') >= 0) {
                                UdpComm::udpSend('termCashBack');
                            } else { // skip ahead to PIN entry on refunds
                                $this->session->set('ccTermState','cashback');
                                UdpComm::udpSend('termGetPin');
                            }
                        }
                    } elseif ($this->session->get('PaycardsOfferCashBack') == 2 && $this->session->get('isMember') == 1) {
                        $this->session->set('ccTermState','cashback');
                        if ($this->session->get('PaycardsStateChange') == 'coordinated') {
                            if ($this->session->get('runningtotal') >= 0) {
                                UdpComm::udpSend('termCashBack');
                            } else { // skip ahead to PIN entry on refunds
                                $this->session->set('ccTermState','cashback');
                                UdpComm::udpSend('termGetPin');
                            }
                        }
                    } else {
                        $this->session->set('ccTermState','pin');
                        if ($this->session->get('PaycardsStateChange') == 'coordinated') {
                            UdpComm::udpSend('termGetPin');
                        }
                    }
                    break;
                case 'EBTFOOD':
                    $this->session->set('ccTermState','pin');
                    if ($this->session->get('PaycardsStateChange') == 'coordinated') {
                        UdpComm::udpSend('termGetPin');
                    }
                    break;
                case 'EBTCASH':
                    if ($this->session->get('PaycardsOfferCashBack') == 1) {
                        $this->session->set('ccTermState','cashback');
                        if ($this->session->get('PaycardsStateChange') == 'coordinated') {
                            if ($this->session->get('runningtotal') >= 0) {
                                UdpComm::udpSend('termCashBack');
                            } else { // skip ahead to PIN entry on refunds
                                $this->session->set('ccTermState','cashback');
                                UdpComm::udpSend('termGetPin');
                            }
                        }
                    } else {
                        $this->session->set('ccTermState','pin');
                        if ($this->session->get('PaycardsStateChange') == 'coordinated') {
                            UdpComm::udpSend('termGetPin');
                        }
                    }
                    break;
            }

            if ($this->session->get('PaycardsStateChange') == 'direct') {
                // check for out of order messages from terminal
                if ($this->session->get('CacheCardType') != '' && $this->session->get('CachePanEncBlock') != '' && $this->session->get('CachePinEncBlock') != '') {
                    $this->session->set('ccTermState', 'ready');
                }
            }

            return true;

        } elseif (substr($str,0,7) == "TERMCB:") {
            $cashback = substr($str,7);
            $termLimit = $this->session->get('PaycardsTermCashBackLimit');
            if ($termLimit === '') {
                $termLimit = 40;
            }
            $this->cbError = true;
            if ($cashback <= $termLimit) {
                $this->cbError = false;
                $this->session->set("CacheCardCashBack",$cashback);
            }
            $this->session->set('ccTermState','pin');
            if ($this->session->get('PaycardsStateChange') == 'coordinated') {
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
            $ret['retry'] = $this->session->get("CachePanEncBlock");
        }
        if ($this->cbError) {
            $ret['output'] = DisplayLib::boxMsg(
                _('Cash back set to zero instead'),
                _('Invalid cash back selection'),
                false,
                DisplayLib::standardClearButton()
            );
        }

        return $ret;
    }

    private function stateReset()
    {
        $this->session->set("paycard_keyed", false);
        $this->session->set("CachePanEncBlock","");
        $this->session->set("CachePinEncBlock","");
        $this->session->set("CacheCardType","");
        $this->session->set("CacheCardCashBack",0);
        $this->session->set('ccTermState', 'swipe');
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

