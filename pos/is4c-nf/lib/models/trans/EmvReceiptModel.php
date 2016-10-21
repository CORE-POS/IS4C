<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of Fannie.

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

namespace COREPOS\pos\lib\models\trans;
use COREPOS\pos\lib\models\BasicModel;

/**
  @class EmvReceiptModel
*/
class EmvReceiptModel extends BasicModel
{
    protected $name = "EmvReceipt";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'dateID' => array('type'=>'INT', 'index'=>true),
    'tdate' => array('type'=>'DATETIME', 'index'=>true),
    'empNo' => array('type'=>'INT'),
    'registerNo' => array('type'=>'INT', 'index'=>true),
    'transNo' => array('type'=>'INT'),
    'transID' => array('type'=>'INT'),
    'content' => array('type'=>'BLOB'),
    );
}

