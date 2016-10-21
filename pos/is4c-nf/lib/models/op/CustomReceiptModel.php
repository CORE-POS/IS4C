<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

namespace COREPOS\pos\lib\models\op;
use COREPOS\pos\lib\models\BasicModel;

/**
  @class CustomReceiptModel
*/
class CustomReceiptModel extends BasicModel
{

    protected $name = "customReceipt";
    protected $preferred_db = 'op';

    protected $columns = array(
    'text' => array('type'=>'VARCHAR(80)'),
    'seq' => array('type'=>'INT', 'primary_key'=>true),
    'type' => array('type'=>'VARCHAR(20)', 'primary_key'=>true),
    );

    public function doc()
    {
        return '
Use:
This table contains strings of text
that originally lived in the lane\'s 
ini.php. At first it was only used
for receipt headers and footers, hence
the name. Submit a patch if you want
a saner name.

Current valid types are:
* receiptHeader
* receiptFooter
* ckEndorse
* welcomeMsg
* farewellMsg
* trainingMsg
* chargeSlip
        ';
    }
}

