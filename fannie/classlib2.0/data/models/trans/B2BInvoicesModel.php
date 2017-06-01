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
  @class B2BInvoicesModel
*/
class B2BInvoicesModel extends BasicModel
{
    protected $name = "B2BInvoices";
    protected $preferred_db = 'trans';

    protected $columns = array(
        'b2bInvoiceID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
        'cardNo' => array('type'=>'INT'),
        'amount' => array('type'=>'MONEY'),
        'description' => array('type'=>'VARCHAR(255)'),
        'coding' => array('type'=>'VARCHAR(255)'),
        'terms' => array('type'=>'VARCHAR(255)'),
        'createdDate' => array('type'=>'DATETIME'),
        'createdTransNum' => array('type'=>'VARCHAR(255)'),
        'createdBy' => array('type'=>'INT'),
        'isPaid' => array('type'=>'TINYINT', 'default'=>0),
        'paidDate' => array('type'=>'DATETIME'),
        'paidTransNum' => array('type'=>'VARCHAR(255)'),
        'customerNotes' => array('type'=>'TEXT'),
        'internalNotes' => array('type'=>'TEXT'),
        'lastModifiedBy' => array('type'=>'INT'),
    );

    public function doc()
    {
        return 'The B2bInvoices structure is meant for billing other businesses that
            operate on e.g. Net 30 terms. The main difference from the older A/R system
            is each billing is treated as a discrete event and there is more backend
            flexibility to make direct corrections and associate specific payments with
            specific bills.';
    }
}

