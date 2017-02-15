<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op.
    Copyright 2015 West End Food Co-op, Toronto, Canada

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

/**
  @class FormattedNameWefcToronto  
  Return name formatted in 30 chars: Tomato Ketchup 375ml
*/
class FormattedNameWefcToronto extends ProductNameFormatter {

    /* True if this should be the only formatting module used. */
    public $this_mod_only = 0;

    public function compose($params = array())
    {
        $ret = "";

        $i = isset($params['index']) ? $params['index'] : 0;
        $desc = FormLib::get('descript');
        $c_desc = "";
        if (isset($desc[$i])) {
            $desc[$i] = str_replace("'", '', $desc[$i]);
        }
        $size = FormLib::get('size');
        $unit = FormLib::get('unitm');

        /* 
         * Base on the main OR inverted, prefixed desc.
         * Compose sizeUom
         * Shorten desc if needed
         * Append sizeUom
        */
        $useMainDesc = true;
        if ($useMainDesc) {
            $fmtName = (isset($desc[$i]) ? $desc[$i] : "No Main description");
        } else {
            if (isset($FANNIE_PRODUCT_MODULES['ProdUserModule'])) {
                $fmtName = FormLib::get('lf_desc');
                $fmtName = str_replace("'", '', $fmtName);
                $fmtName = str_replace("\r", '', $fmtName);
                $fmtName = str_replace("\n", ' ', $fmtName);
                $fmtName = preg_replace("/  +/", ' ', $fmtName);
            } else {
                $fmtName = FormLib::get('puser_description');
            }
            $fmtName = (!empty($fmtName) ? $fmtName : "No User description");
        }
        $sizeUom = '';
        if (isset($size[$i])) {
            $sizeUom = $size[$i];
        }
        if (isset($unit[$i])) {
            $sizeUom .= $unit[$i];
        }
        // Limit to 30 chars.
        $sul = strlen($sizeUom);
        $fnl = strlen($fmtName);
        if ($sul > 0 && $fnl > 0) {
            $sul++;
        }
        $ret = substr($fmtName, 0, (30 - $sul)) . " $sizeUom";

        $this->this_mod_only = (!empty($ret) ? 1 : 0);
        return $ret;
    }

}

