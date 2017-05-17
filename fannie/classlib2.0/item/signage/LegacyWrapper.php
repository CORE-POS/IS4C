<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

namespace COREPOS\Fannie\API\item\signage;

class LegacyWrapper extends \COREPOS\Fannie\API\item\FannieSignage 
{
    private static $wrapped;
    public static function setWrapped($w)
    {
        self::$wrapped = $w;
    }

    public static function getLayouts()
    {
        if (!function_exists('scan_layouts')) {
            include(__DIR__ . '/../../../admin/labels/scan_layouts.php');
        }
        
        return scan_layouts();
    }

    public function drawPDF()
    {
        if (empty(self::$wrapped)) {
            return 'No legacy layout selected';
        }
        $data = $this->loadItems();
        $data = array_map(function($i) {
            if (isset($i['posDescription'])) {
                $i['description'] = $i['posDescription'];
            }
            return $i;
        }, $data);
        $layout = str_replace(' ', '_', self::$wrapped);
        $file = __DIR__ . '/../../../admin/labels/pdf_layouts/' . $layout . '.php';
        if (file_exists($file) && !function_exists($layout)) {
            include($file);
        }
        if (!function_exists($layout)) {
            return 'No legacy layout selected';
        }
        $layout($data, 0);
    }
}

