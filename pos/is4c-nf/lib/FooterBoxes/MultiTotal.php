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

class MultiTotal extends FooterBox 
{

    public $display_css = "font-weight:bold;font-size:150%;";

    public function header_content()
    {
        global $CORE_LOCAL;
        if ( $CORE_LOCAL->get("ttlflag") == 1 and $CORE_LOCAL->get("End") != 1 ) {
            if ($CORE_LOCAL->get("fntlflag") == 1) {
                $this->header_css_class = 'fsArea';
                return _("fs Amount Due");
            } else {
                $this->header_css_class = 'errorColoredArea';
                return _("Amount Due");
            }
        } elseif ($CORE_LOCAL->get("ttlflag") == 1  and $CORE_LOCAL->get("End") == 1 ) {
            $this->header_css_class = 'coloredArea';
            return _("Change");
        } else {
            $this->header_css_class = 'totalArea';
            return _("Total");
        }
    }

    public function display_content()
    {
        global $CORE_LOCAL;
        if ( $CORE_LOCAL->get("ttlflag") == 1 and $CORE_LOCAL->get("End") != 1 ) {
            if ($CORE_LOCAL->get("fntlflag") == 1) {
                $this->display_css_class = 'fsLine';
                return number_format($CORE_LOCAL->get("fsEligible"),2);
            } else {
                $this->display_css_class = 'errorColoredText';
                return number_format($CORE_LOCAL->get("runningTotal"),2);
            }
        } elseif ($CORE_LOCAL->get("ttlflag") == 1  and $CORE_LOCAL->get("End") == 1 ) {
            $this->display_css_class = 'coloredText';
            return number_format($CORE_LOCAL->get("runningTotal"),2);
        } else {
            $this->display_css_class = 'totalLine';
            return number_format((double)$CORE_LOCAL->get("runningTotal"),2);
        }
    }
}

