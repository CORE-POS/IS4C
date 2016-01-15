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
  @class NutriFactReqItemsModel
*/
class NutriFactReqItemsModel extends BasicModel
{

    protected $name = "NutriFactReqItems";
    protected $preferred_db = 'op';

    protected $columns = array(
    'nutriFactReqItemID' => array('type'=>'INT', 'index'=>true, 'increment'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'index'=>true, 'primary_key'=>true),
    'servingSize' => array('type'=>'VARCHAR(15)'),
    'numServings' => array('type'=>'VARCHAR(15)'),
    'calories' => array('type'=>'SMALLINT'),
    'fatCalories' => array('type'=>'SMALLINT'),
    'totalFat' => array('type'=>'VARCHAR(15)'),
    'saturatedFat' => array('type'=>'VARCHAR(15)'),
    'transFat' => array('type'=>'VARCHAR(15)'),
    'cholesterol' => array('type'=>'VARCHAR(15)'),
    'sodium' => array('type'=>'VARCHAR(15)'),
    'totalCarbs' => array('type'=>'VARCHAR(15)'),
    'fiber' => array('type'=>'VARCHAR(15)'),
    'sugar' => array('type'=>'VARCHAR(15)'),
    'protein' => array('type'=>'VARCHAR(15)'),
    );

}

