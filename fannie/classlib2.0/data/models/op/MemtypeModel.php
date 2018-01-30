<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class MemtypeModel
*/
class MemtypeModel extends BasicModel 
{

    protected $name = "memtype";

    protected $preferred_db = 'op';

    protected $columns = array(
    'memtype' => array('type'=>'TINYINT','primary_key'=>true,'default'=>0),
    'memDesc' => array('type'=>'VARCHAR(20)'),
    'custdataType' => array('type'=>'VARCHAR(10)'),
    'discount' => array('type'=>'SMALLINT'),
    'staff' => array('type'=>'TINYINT'),
    'ssi' => array('type'=>'TINYINT'),
    'salesCode' => array('type'=>'INT'),
    );

    public function doc()
    {
        return '
Use:
Housekeeping. If you want to sort people in
custdata into more categories than just
member/nonmember, use memtype.

The custdataType, discount, staff, and ssi
are the default values for custdata\'s
Type, discount, staff, and ssi columns
when creating a new record of a given
memtype.
        ';
    }

    protected function addColumnFromDefaults($defaultCol, $myCol)
    {
        if ($this->connection->tableExists('memdefaults')) {
            $dataR = $this->connection->query("SELECT memtype, {$defaultCol} FROM memdefaults");
            $tempModel = new MemtypeModel($this->connection);
            while ($dataW = $this->connection->fetchRow($dataR)) {
                $tempModel->reset();
                $tempModel->memtype($dataW['memtype']);
                if ($tempModel->load()) {
                    $tempModel->$myCol($dataW[$defaultCol]);
                    $tempModel->save();
                }
            }
        }
    }

    protected function hookAddColumnCustdataType()
    {
        $this->addColumnFromDefaults('cd_type', 'custdataType');
    }

    protected function hookAddColumnDiscount()
    {
        $this->addColumnFromDefaults('discount', 'discount');
    }

    protected function hookAddColumnStaff()
    {
        $this->addColumnFromDefaults('staff', 'staff');
    }

    protected function hookAddColumnSsi()
    {
        $this->addColumnFromDefaults('SSI', 'ssi');
    }
}

