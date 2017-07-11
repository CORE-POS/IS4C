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
    /**
     * First check if the item is a primary alias. If it is, get non-primary
     * aliases for the same SKU
     */
    public static function getAliases($dbc, $upc)
    {
        $skuP = $dbc->prepare('SELECT sku, vendorID FROM VendorAliases WHERE upc=? AND isPrimary=1');
        $row = $dbc->getRow($skuP, array($upc));
        if ($row === false) {
            return array();
        }
        $ret = array();
        $otherP = $dbc->prepare('SELECT upc, multiplier FROM VendorAliases WHERE sku=? AND vendorID=? AND isPrimary=0');
        $otherR = $dbc->execute($otherP, array($row['sku'], $row['vendorID']));
        while ($otherW = $dbc->fetchRow($otherR)) {
            if ($otherW['upc'] != $upc) {
                $ret[] = $otherW;
            }
        }

        return $ret;
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

