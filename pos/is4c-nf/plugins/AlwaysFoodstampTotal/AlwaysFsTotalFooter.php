<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

use COREPOS\pos\lib\FooterBoxes\FooterBox;

class AlwaysFsTotalFooter extends FooterBox 
{

    public $display_css = "font-weight:bold;font-size:150%;";

    private function isAmountDue()
    {
        if (CoreLocal::get("ttlflag") == 1 and CoreLocal::get("End") != 1) {
            return true;
        } else {
            return false;
        }
    }

    private function isChange()
    {
        if (CoreLocal::get("ttlflag") == 1 and CoreLocal::get("End") == 1) {
            return true;
        } else {
            return false;
        }
    }
    function header_content()
    {
        if ($this->isAmountDue()) {
            $this->header_css_class = 'errorColoredArea';
            return _("Amount Due");
        } elseif ($thiw->isChange()) {
            $this->header_css_class = 'coloredArea';
            return _("Change");
        } else {
            $this->header_css_class = 'totalArea';
            return _("Total");
        }
    }

    function display_content()
    {
        if ($this->isAmountDue()) {
            $this->display_css_class = 'errorColoredText';
            return number_format(CoreLocal::get("runningTotal"),2);
        } elseif ($thiw->isChange()) {
            $this->display_css_class = 'coloredText';
            return number_format(CoreLocal::get("runningTotal"),2);
        } else {
            $this->display_css_class = 'totalLine';
            return number_format((double)CoreLocal::get("runningTotal"),2);
        }
    }
}

