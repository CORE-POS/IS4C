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
  @class BatchTypeModel
*/
class BatchTypeModel extends BasicModel
{

    protected $name = "batchType";
    protected $preferred_db = 'op';

    protected $columns = array(
        'batchTypeID' => array('type'=>'INT', 'primary_key'=>true),
        'typeDesc' => array('type'=>'VARCHAR(50)'),
        'discType' => array('type'=>'INT'),
        'datedSigns' => array('type'=>'TINYINT', 'default'=>1),
        'specialOrderEligible' => array('type'=>'TINYINT', 'default'=>1),
        'editorUI' => array('type'=>'TINYINT', 'default'=>1),
        'allowSingleStore' => array('type'=>'TINYINT', 'default'=>0),
    );

    public function doc()
    {
        return '
Use:
This table contains types of batches that
can be created. You really only need one
for each discount type, but you can have
more for organizational purposes
        ';
    }
}

