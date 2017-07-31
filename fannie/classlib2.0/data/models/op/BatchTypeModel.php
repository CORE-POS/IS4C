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
        'exitInventory' => array('type'=>'TINYINT', 'default'=>0),
    );

    public function doc()
    {
        return '
Use:
This table contains types of batches that
can be created. You really only need one
for each discount type, but you can have
more for organizational purposes

typeDesc is the human-readable description

discType is the discount type. Common values:
  * 0 => Price Change
  * 1 => Sale for everyone
  * 2 => Member/Owner only sale

datedSigns controls whether sale signs will
include dates. Non-dated signs will either
say "Discontinued" if the typeDesc includes
the letters "DISCO" otherwise it will say
"While supplies last"

specialOrderEligible indicates whether the
sale price should apply to special orders

editorUI controls that user interface for
editing batches of a given type.
  * 1 => Standard editor
  * 2 => Paired sale editor
  * 3 => Partial-day batch editor

allowSingleStore controls whether a batch
can apply to a subset of stores. The default, 0,
means batches of that type must apply simultaneously
to all stores

exitInventory links batches to perpetual inventory.
If a batch type is set to exitInventory all item
ordering pars will change to zero when the batch 
goes into effect.
        ';
    }
}

