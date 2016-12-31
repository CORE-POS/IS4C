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

namespace COREPOS\pos\parser\preparse;
use COREPOS\pos\parser\PreParser;

class ToggleTaxFSDisc extends PreParser 
{
    private $tfd;
    private $remainder;

    public $TAX = 4;
    public $FS = 2;
    public $DISC = 1;

    // use bit-masks to determine the which toggles
    // should be enabled
    function check($str)
    {
        // ignore comments; they may have all sorts of
        // random character cominations
        if (substr($str, 0, 2) == "CM") {
            return false;
        }
        $this->tfd = 0;
        $this->remainder = '';

        if (strstr($str, '1TN')) {
            $this->tfd = $this->tfd | $this->TAX;
            $parts = explode('1TN', $str, 2);
            foreach ($parts as $p) {
                $this->remainder .= $p;
            }
        }

        if (strstr($str, 'DN')) {
            $this->tfd = $this->tfd | $this->DISC;
            $parts = explode('DN', $str, 2);
            foreach ($parts as $p) {
                $this->remainder .= $p;
            }
        }

        if (strstr($str, 'FN') && !strstr($str, 'FNTL')) {
            $this->tfd = $this->tfd | $this->FS;
            $parts = explode('FN', $str, 2);
            foreach ($parts as $p) {
                $this->remainder .= $p;
            }
        }

        return $this->tfd != 0 ? true : false;;    
    }

    function parse($str)
    {
        if ($this->tfd & $this->TAX) {
            $this->session->set("toggletax",1);
        }
        if ($this->tfd & $this->FS) {
            $this->session->set("togglefoodstamp",1);
        }
        if ($this->tfd & $this->DISC) {
            $this->session->set("toggleDiscountable",1);
        }

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

