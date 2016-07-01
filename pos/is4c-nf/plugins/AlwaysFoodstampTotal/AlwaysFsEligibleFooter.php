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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\FooterBoxes\FooterBox;

class AlwaysFsEligibleFooter extends FooterBox 
{

    public $header_css_class = 'fsLine';
    public $display_css_class = 'fsLine';

    function __construct() 
    {
        if (CoreLocal::get('fntlflag') == 0 && CoreLocal::get('End') != 1){
            CoreLocal::set("fntlflag",1);
            Database::setglobalvalue("FntlFlag", 1);
        }
    }

    function header_content()
    {
        return _("FS Eligible");
    }

    function display_content()
    {
        if (CoreLocal::get('End') != 1)
            return number_format((double)CoreLocal::get("fsEligible"),2);
        else
            return '0.00';
    }
        
}

