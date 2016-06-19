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

use COREPOS\pos\lib\FooterBoxes\TransPercentDiscount;

class NeedDiscountFooter extends TransPercentDiscount {

    function header_content()
    {
        $percent = CoreLocal::get('percentDiscount');
        if (CoreLocal::get('NeedDiscountFlag')===1)
            $percent += (CoreLocal::get('needDiscountPercent') * 100);
        if ($percent == 0)
            return _("% Discount");
        else {
            CoreLocal::set('percentDiscount', $percent);
            return $percent._("% Discount");
        }
    }
    function display_content() 
    {
        if (CoreLocal::get("percentDiscount") != 0 )
            return number_format(CoreLocal::get("transDiscount"), 2);
        else
            return "n/a";
    }
}

