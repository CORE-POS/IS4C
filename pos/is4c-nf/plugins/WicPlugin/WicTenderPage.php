<?php
/*******************************************************************************
    Copyright 2001, 2004 Wedge Community Co-op
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

use COREPOS\pos\lib\gui\BasicCorePage;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');
class WicTenderPage extends BasicCorePage 
{
    private $step = 0;
    private $box_color="coloredArea";
    private $errMsg;
    public function preprocess()
    {
        if (FormLib::get('reginput', false) !== false) {
            $inp = strtoupper(FormLib::get('reginput'));
            $this->step = FormLib::get('step', 0);
            // clear backtracks through steps
            if ($inp == 'CL' && $this->step == 0) {
                $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php');
                return false;
            } elseif ($inp == 'CL') {
                $this->step--;
                return true;
            }
            switch ($this->step) {
                case 0:
                    $this->handleDateInput($inp, true, false);
                    break;
                case 1:
                    $this->handleDateInput($inp, false, true);
                    break;
                case 2:
                case 3:
                    if ($inp !== '') {
                        $this->box_color="errorColoredArea";
                        $this->errMsg = '[enter] to continue';
                    } else {
                        $this->step++;
                    }
                    break;
                case 4:
                    if ($this->validateAmount($inp)) {
                        $tender = $inp . 'WT';
                        CoreLocal::set('RepeatAgain', true);
                        $this->change_page(
                            MiscLib::baseURL() 
                            . 'gui-modules/pos2.php'
                            . '?reginput=' . urlencode($tender)
                            . '&repeat=1');
                        return false;
                    }
                    break;
            }
        }
        return true;
    }
    private function validateAmount($inp)
    {
        if (!is_numeric($inp)) {
            $this->box_color="errorColoredArea";
            $this->errMsg = 'Invalid amount';
            return false;
        } elseif (($inp/100) - CoreLocal::get('amtdue') > 0.005) {
            $this->box_color="errorColoredArea";
            $this->errMsg = 'Max amount is ' . CoreLocal::get('amtdue');
            return false;
        } else {
            return true;
        }
    }
    private function handleDateInput($inp, $issue=true, $expire=false)
    {
        if (strlen($inp) != 6 || !is_numeric($inp)) {
            $this->box_color="errorColoredArea";
            $this->errMsg = 'Invalid Date: MMDDYY';
        } else {
            $stamp = mktime(0, 0, 0, substr($inp, 0, 2), substr($inp, 2, 2), 2000+substr($inp, -2));
            $today = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
            if ($issue && $stamp > $today) {
                $this->box_color="errorColoredArea";
                $this->errMsg = 'Not valid until ' . date('m/d/Y', $stamp);
            } elseif ($expire && $stamp < $today) {
                $this->box_color="errorColoredArea";
                $this->errMsg = 'Expired ' . date('m/d/Y', $stamp);
            } else {
                $this->step++;
            }
        }
    }
    public function body_content()
    {
        $this->input_header();
        echo DisplayLib::printheaderb();
        echo '<div class="baseHeight">';
        echo '<div class=" ' .$this->box_color . ' centeredDisplay">';
        echo $this->stepContent();
        echo '</div>';
        echo '</div>';
        Database::getsubtotals();
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";
        $this->add_onload_command("\$('<input type=\"hidden\" name=\"step\">').val(" . $this->step . ").appendTo('#formlocal');\n");
    }
    private function stepContent()
    {
        switch ($this->step) {
            case 0:
                return $this->issueForm();
            case 1:
                return $this->expiresForm();
            case 2:
                return $this->priceForm();
            case 3:
                return $this->sigForm();
            case 4:
                return $this->amountForm();
        }
    }
    private function amountForm()
    {
        return '<span class="larger">
            ' . ($this->errMsg ? $this->errMsg : 'Enter Amount') . '
            </span><br />
            <p>
            [clear] to go back
            </p>';
    }
    private function issueForm()
    {
        return '<span class="larger">
            ' . ($this->errMsg ? $this->errMsg : 'Issue Date') . '
            </span><br />
            <p>
            enter issue date MMDDYY or [clear] to cancel
            </p>';
    }
    private function expiresForm()
    {
        return '<span class="larger">
            ' . ($this->errMsg ? $this->errMsg : 'Expiration Date') . '
            </span><br />
            <p>
            enter expiration date MMDDYY or [clear] to go back
            </p>';
    }
    
    private function priceForm()
    {
        return '<span class="larger">
            Write Price on WIC Voucher
            </span><br />
            <p>
            Write Price on Voucher or [clear] to go back
            </p>';
    }
    private function sigForm()
    {
        return '<span class="larger">
            Confirm signature and ID
            </span><br />
            <p>
            [enter] to continue or [clear] to go back
            </p>';
    }
}

AutoLoader::dispatch();

