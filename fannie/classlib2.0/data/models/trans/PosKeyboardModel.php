<?php
/*******************************************************************************

    Copyright 2020 Whole Foods Co-op

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
  @class PosKeyboardModel 
*/
class PosKeyboardModel extends BasicModel
{

    protected $name = "posKeyboardModel";
    protected $preferred_db = 'trans';
    protected $columns = array(
    'position' => array('type'=>'DECIMAL(10,2)','primary_key'=>true),
    'label' => array('type'=>'VARCHAR(65)'),
    'bkgColor' => array('type'=>'VARCHAR(19)'),
    'color' => array('type'=>'VARCHAR(19)'),
    'fontSize' => array('type'=>'VARCHAR(4)'),
    'cmd' => array('type'=>'VARCHAR(25)'),
    'shftCmd' => array('type'=>'VARCHAR(25)'),
    'altCmd' => array('type'=>'VARCHAR(24)'),
    'print' => array('type'=>'TINYINT(4)','default'=>1)
    );

    public function doc()
    {
        return '
Use:
POS keyboard keys retained for ease 
of label printing for programmible 
keyboards, in the future could be 
used to create a digital POS keyboard.
        ';
    }
}

