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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class WicTenderPage extends BasicCorePage 
{
    private $step = 0;
    private $box_color="coloredArea";
    private $errMsg;

    public function preprocess()
    {
        if (FormLib::get('reginput', false) !== false) {
            $inp = FormLib::get('reginput');
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
                    if (strlen($inp) != 8 || !is_numeric($inp)) {
                        $this->box_color="errorColoredArea";
                        $this->errMsg = 'Invalid Date: MMDDYYYY';
                    } else {
                        $stamp = mktime(0, 0, 0, substr($inp, 0, 2), substr($inp, 2, 2), substr($inp, -4));
                        $today = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
                        if ($stamp > $today) {
                            $this->box_color="errorColoredArea";
                            $this->errMsg = 'Note valid until ' . date('m/d/Y', $stamp);
                        } else {
                            $this->step++;
                        }
                    }
                    break;
                case 1:
                    if (strlen($inp) != 8 || !is_numeric($inp)) {
                        $this->box_color="errorColoredArea";
                        $this->errMsg = 'Invalid Date: MMDDYYYY';
                    } else {
                        $stamp = mktime(0, 0, 0, substr($inp, 0, 2), substr($inp, 2, 2), substr($inp, -4));
                        $today = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
                        if ($stamp < $today) {
                            $this->box_color="errorColoredArea";
                            $this->errMsg = 'Expired ' . date('m/d/Y', $stamp);
                        } else {
                            $this->step++;
                        }
                    }
                    break;
                case 2:
                    if ($inp !== '') {
                        $this->box_color="errorColoredArea";
                        $this->errMsg = '[enter] to continue';
                    } else {
                        $this->step++;
                    }
                    break;
                case 3:
                    if (!is_numeric($inp)) {
                        $this->box_color="errorColoredArea";
                        $this->errMsg = 'Invalid amount';
                    } elseif ($inp - CoreLocal::get('amtdue') > 0.005) {
                        $this->box_color="errorColoredArea";
                        $this->errMsg = 'Max amount is ' . CoreLocal::get('amtdue');
                    } else {
                        $tender = MiscLib::truncate2($amt*100) . 'WI';
                        CoreLocal::set('strRemembered', $tender);
                        CoreLocal::set('msgrepeat', 1);
                        $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php');
                        return false;
                    }
                    break;
            }
        }

        return true;
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
                return $this->sigForm();
            case 3:
                return $this->amountForm();
        }
    }

    private function amountForm()
    {
        return '<span class="larger">
            Enter Amount
            </span><br />
            <p>
            [clear] to go back
            </p>';
    }

    private function issueForm()
    {
        return '<span class="larger">
            Issue Date
            </span><br />
            <p>
            enter issue date MMDDYYYY or [clear] to cancel
            </p>';
    }

    private function expiresForm()
    {
        return '<span class="larger">
            Expiration Date
            </span><br />
            <p>
            enter expiration date MMDDYYYY or [clear] to go back
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

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
    new WicTenderPage();

