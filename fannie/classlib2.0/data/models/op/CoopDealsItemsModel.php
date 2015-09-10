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
  @class CoopDealsItemsModel
*/
class CoopDealsItemsModel extends BasicModel
{

    protected $name = "CoopDealsItems";
    protected $preferred_db = 'op';

    protected $columns = array(
    'coopDealsItemID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'dealSet' => array('type'=>'VARCHAR(25)', 'index'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'index'=>true),
    'price' => array('type'=>'MONEY'),
    'abtpr' => array('type'=>'VARCHAR(3)'),
    'multiplier' => array('type'=>'INT', 'default'=>1),
    );

    /* START ACCESSOR FUNCTIONS */

    public function coopDealsItemID()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('coopDealsItemID');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('coopDealsItemID', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('coopDealsItemID', func_get_arg(0));
        }
        return $this;
    }

    public function dealSet()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('dealSet');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('dealSet', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('dealSet', func_get_arg(0));
        }
        return $this;
    }

    public function upc()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('upc');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('upc', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('upc', func_get_arg(0));
        }
        return $this;
    }

    public function price()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('price');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('price', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('price', func_get_arg(0));
        }
        return $this;
    }

    public function abtpr()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('abtpr');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('abtpr', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('abtpr', func_get_arg(0));
        }
        return $this;
    }

    public function multiplier()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('multiplier');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('multiplier', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('multiplier', func_get_arg(0));
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

