<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

/**
  @class VendorBreakdownsModel
*/
class VendorBreakdownsModel extends BasicModel
{

    protected $name = "VendorBreakdowns";
    protected $preferred_db = 'op';

    protected $columns = array(
    'vendorID' => array('type'=>'INT', 'primary_key'=>true),
    'sku' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'upc' => array('type'=>'VARCHAR(13)'),
    'units' => array('type'=>'SMALLINT', 'default'=>1),
    );

    public function getSplit($size)
    {
        $size = strtoupper($size);
        $split_factor = false;
        $unit_size = '';
        if (preg_match('/^\d+$/', $size)) {
            $split_factor = $size;
        } elseif (preg_match('/(\d+)\s*\\/\s*(.+)/', $size, $matches)) {
            $split_factor = $matches[1];
            $unit_size = $matches[2];
        } elseif (preg_match('/(\d+)\s*CT/', $size, $matches)) {
            $split_factor = $matches[1];
        } elseif (preg_match('/(\d+)\s*PKT/', $size, $matches)) {
            $split_factor = $matches[1];
        }

        return array($split_factor, $unit_size);
    }

    public function initUnits()
    {
        $prep = $this->connection->prepare('SELECT size FROM vendorItems WHERE sku=? AND vendorID=?');
        $size = $this->connection->getValue($prep, array($this->sku(), $this->vendorID()));
        if ($size) {
            list($split, $unit_size) = $this->getSplit($size);
            if ($split) {
                $this->units($split);
                return $this->save() ? true : false;
            }
        }

        return false;
    }

}

