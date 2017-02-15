<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op, Duluth, MN

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

namespace COREPOS\Fannie\API\item;

class InventoryLib 
{
    public static function isBreakdown($dbc, $upc, $recurse=true)
    {
        $bdP = $dbc->prepare('
            SELECT i.upc,
                v.units,
                v.sku,
                v.vendorID
            FROM VendorBreakdowns AS v
                INNER JOIN vendorItems AS i ON v.sku=i.sku AND v.vendorID=i.vendorID
            WHERE v.upc=?');
        $bdInfo = $dbc->getRow($bdP, array($upc));
        if ($recurse && $bdInfo && ($bdInfo['units'] == 1 || $bdInfo['units'] == null)) {
            $model = new \VendorBreakdownsModel($dbc);
            $model->vendorID($bdInfo['vendorID']);
            $model->sku($bdInfo['sku']);
            if ($model->initUnits()) {
                return self::isBreakdown($dbc, $upc, false); 
            }
        }

        return $bdInfo;
    }

    public static function orderExporters()
    {
        $ret = array();
        $path = dirname(__FILE__) . '/../../purchasing/exporters/';
        $dir = opendir($path);
        while (($file=readdir($dir)) !== false) {
            if (substr($file,-4) != '.php') {
                continue;
            }
            $class = substr($file,0,strlen($file)-4);
            if (!class_exists($class)) {
                include($path . $file);
            }
            if (!class_exists($class)) {
                continue;
            }
            $obj = new $class();
            if (!isset($obj->nice_name)) {
                continue;
            }

            $ret[$class] = $obj->nice_name;
        }

        return $ret;
    }
}

