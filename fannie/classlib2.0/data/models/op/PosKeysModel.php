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

class PosKeysModel extends BasicModel 
{

    protected $name = 'PosKeys';

    protected $preferred_db = 'op';

    protected $columns = array(
    'pos'=>array('type'=>'DECIMAL(10,2)','primary_key'=>True),
    'rgb'=>array('type'=>'VARCHAR(14)'),
    'cmd'=>array('type'=>'VARCHAR(150)'),
    'label'=>array('type'=>'VARCHAR(65)'),
    'labelRgb'=>array('type'=>'VARCHAR(35)'),
    'labelBkg'=>array('type'=>'VARCHAR(35)'),
    'print'=>array('type'=>'TINYINT(1)'),
    'underline'=>array('type'=>'INT(3)')
    );
public function doc()
    {
        return '
Use:
Define POS Keyboard layout.
        ';
    }
}

