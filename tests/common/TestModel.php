<?php

/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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
  @class TestModel
*/
class CommonTestModel extends \COREPOS\common\BasicModel
{
    protected $name = "UnitTestTable";

    protected $columns = array(
    'unitTestTableID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'fooColumn' => array('type'=>'INT'),
    );

    /**
      Manipulate $columns to that normalization runs a wide
      variety of ALTER queries
    */
    public function unitTest($phpunit, $dbc)
    {
        $this->connection = $dbc;
        $db_name = $dbc->defaultDatabase();
        $dbc->query('DROP TABLE ' . $this->name);

        ob_start();
        $this->normalize($db_name, \COREPOS\common\BasicModel::NORMALIZE_MODE_CHECK);
        $this->normalize($db_name, \COREPOS\common\BasicModel::NORMALIZE_MODE_APPLY);

        $this->columns['FooColumn'] = $this->columns['fooColumn'];
        unset($this->columns['fooColumn']);
        $this->normalize($db_name, \COREPOS\common\BasicModel::NORMALIZE_MODE_CHECK);
        $this->normalize($db_name, \COREPOS\common\BasicModel::NORMALIZE_MODE_APPLY);

        $this->columns['FooColumn']['type'] = 'VARCHAR(10)';
        $this->normalize($db_name, \COREPOS\common\BasicModel::NORMALIZE_MODE_CHECK);
        $this->normalize($db_name, \COREPOS\common\BasicModel::NORMALIZE_MODE_APPLY);

        $this->columns['barColumn'] = array('type'=>'INT', 'replaces'=>'FooColumn');
        unset($this->columns['FooColumn']);
        $this->normalize($db_name, \COREPOS\common\BasicModel::NORMALIZE_MODE_CHECK);
        $this->normalize($db_name, \COREPOS\common\BasicModel::NORMALIZE_MODE_APPLY);

        $this->columns['bazColumn'] = array('type'=>'INT', 'index'=>true, 'default'=>7);
        $this->normalize($db_name, \COREPOS\common\BasicModel::NORMALIZE_MODE_CHECK);
        $this->normalize($db_name, \COREPOS\common\BasicModel::NORMALIZE_MODE_APPLY);
        ob_end_clean();
    }
}

