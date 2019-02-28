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
  @class EWicItemsModel
*/
class EWicItemsModel extends BasicModel
{
    protected $name = "EWicItems";
    protected $preferred_db = 'op';

    protected $columns = array(
    'eWicItemID' => array('type'=>'INT', 'index'=>true, 'increment'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'upcCheck' => array('type'=>'VARCHAR(17)'),
    'alias' => array('type'=>'VARCHAR(13)'),
    'eWicCategoryID' => array('type'=>'INT'),
    'eWicSubCategoryID' => array('type'=>'INT'),
    );

    public function doc()
    {
        return '
Maps UPC items to EWic categories. The check digit is stored in upcCheck
in case the processor side expects check digits to be included in submissions.
The alias field is an alternate UPC for cases where it\'s permissible for the
system to send a UPC that differs from what the customer is actually purchasing.
This is most likely to occur with produce where UPCs and PLUs change frequently
and the issuing state/entity may not have 100% of valid UPCs on their list.
            ';
    }
}

