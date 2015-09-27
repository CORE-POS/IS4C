<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class ServiceScalesModel
*/
class ServiceScalesModel extends BasicModel
{

    protected $name = "ServiceScales";
    protected $preferred_db = 'op';

    protected $columns = array(
    'serviceScaleID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(50)'),
    'host' => array('type'=>'VARCHAR(50)'),
    'scaleType' => array('type'=>'VARCHAR(50)'),
    'scaleDeptName' => array('type'=>'VARCHAR(25)'),
    'superID' => array('type'=>'INT'),
    'epDeptNo' => array('type'=>'SMALLINT', 'default'=>1),
    'epStoreNo' => array('type'=>'SMALLINT', 'default'=>0),
    'epScaleAddress' => array('type'=>'SMALLINT', 'default'=>1),
    );

    public function doc()
    {
        return '
Use:
List service scales and network info.
        ';
    }
}

