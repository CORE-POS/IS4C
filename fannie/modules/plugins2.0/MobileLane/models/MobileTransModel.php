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

class MobileTransModel extends BasicModel 
{
    protected $name = 'MobileTrans';
    protected $preferred_db = 'plugin:MobileLaneDB';

    protected $columns = array(
    'datetime'    => array('type'=>'DATETIME','index'=>true),
    'store_id'    => array('type'=>'SMALLINT', 'default'=>1),
    'register_no'    => array('type'=>'SMALLINT'),
    'emp_no'    => array('type'=>'SMALLINT'),
    'trans_no'    => array('type'=>'INT'),
    'upc'        => array('type'=>'VARCHAR(13)','index'=>true),
    'description'    => array('type'=>'VARCHAR(30)'),
    'trans_type'    => array('type'=>'VARCHAR(1)'),
    'trans_subtype'    => array('type'=>'VARCHAR(2)', 'default'=>"''"),
    'trans_status'    => array('type'=>'VARCHAR(1)', 'default'=>"''"),
    'department'    => array('type'=>'SMALLINT', 'default'=>0),
    'quantity'    => array('type'=>'DOUBLE', 'default'=>0),
    'scale'        => array('type'=>'TINYINT','default'=>0.00),
    'cost'        => array('type'=>'MONEY', 'default'=>0),
    'unitPrice'    => array('type'=>'MONEY'),
    'total'        => array('type'=>'MONEY'),
    'regPrice'    => array('type'=>'MONEY'),
    'tax'        => array('type'=>'SMALLINT', 'default'=>0),
    'foodstamp'    => array('type'=>'TINYINT', 'default'=>0),
    'discount'    => array('type'=>'MONEY', 'default'=>0),
    'memDiscount'    => array('type'=>'MONEY', 'default'=>0),
    'discountable'    => array('type'=>'TINYINT', 'default'=>0),
    'discounttype'    => array('type'=>'TINYINT', 'default'=>0),
    'voided'    => array('type'=>'TINYINT', 'default'=>0),
    'percentDiscount'=> array('type'=>'TINYINT', 'default'=>0),
    'ItemQtty'    => array('type'=>'DOUBLE', 'default'=>0),
    'volDiscType'    => array('type'=>'TINYINT', 'default'=>0),
    'volume'    => array('type'=>'TINYINT', 'default'=>0),
    'VolSpecial'    => array('type'=>'MONEY', 'default'=>0),
    'mixMatch'    => array('type'=>'VARCHAR(13)', 'default'=>"''"),
    'matched'    => array('type'=>'SMALLINT', 'default'=>0),
    'memType'    => array('type'=>'TINYINT', 'default'=>0),
    'staff'        => array('type'=>'TINYINT', 'default'=>0),
    'numflag'    => array('type'=>'INT','default'=>0, 'default'=>0),
    'charflag'    => array('type'=>'VARCHAR(2)','default'=>"''"),
    'card_no'    => array('type'=>'INT', 'default'=>0),
    'pos_row_id' => array('type'=>'BIGINT UNSIGNED', 'primary_key'=>true, 'increment'=>true),
    );
}

