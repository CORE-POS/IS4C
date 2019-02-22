<?php

/*******************************************************************************

    Copyright 2017 Whole Foods Co-op

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
  @class InstaTransactionsModel
*/
class InstaTransactionsModel extends BasicModel
{
    protected $name = "InstaTransactions";
    protected $preferred_db = 'plugin:InstaCartDB';

    protected $columns = array(
    'instaTransactionID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'userID' => array('type'=>'INT'),
    'orderID' => array('type'=>'INT'),
    'deliveryID' => array('type'=>'INT'),
    'orderDate' => array('type'=>'DATETIME', 'index'=>true),
    'deliveryDate' => array('type'=>'DATETIME'),
    'itemID' => array('type'=>'INT'),
    'upc' => array('type'=>'VARCHAR(13)'),
    'quantity' => array('type'=>'MONEY'),
    'retailPrice' => array('type'=>'MONEY'),
    'retailTotal' => array('type'=>'MONEY'),
    'onlinePrice' => array('type'=>'MONEY'),
    'onlineTotal' => array('type'=>'MONEY'),
    'tax' => array('type'=>'MONEY'),
    'deposit' => array('type'=>'MONEY'),
    'bagFee' => array('type'=>'MONEY'),
    'total' => array('type'=>'MONEY'),
    'cardNo' => array('type'=>'INT'),
    'storeID' => array('type'=>'INT'),
    'signupZip' => array('type'=>'VARCHAR(255)'),
    'deliveryZip' => array('type'=>'VARCHAR(255)'),
    'fullfillmentType' => array('type'=>'CHAR(1)'),
    'platform' => array('type'=>'VARCHAR(255)'),
    'isExpress' => array('type'=>'TINYINT'),
    );
}


