<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

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

namespace COREPOS\pos\lib\gui;
use COREPOS\pos\lib\DisplayLib;

/** @class InputCorePage

    This class automatically adds the input header
    and the footer. Any display script using this
    class will POST form input to itself as that
    is the default action inherited from BasicCorePage.
 */

class NoInputCorePage extends BasicCorePage 
{
    public function getHeader()
    {
        $ret = parent::getHeader();
        ob_start();
        $this->noinput_header();
        $ret .= ob_get_clean();
        $ret .= DisplayLib::printheaderb();

        return $ret;
    }

    public function getFooter()
    {
        $ret = "<div id=\"footer\">"
            . DisplayLib::printfooter()
            . "</div>\n"
            . "</div>\n";
        ob_start();
        $this->scale_box();
        $this->scanner_scale_polling(false);
        $ret .= ob_get_clean();

        return $ret;
    }
}

