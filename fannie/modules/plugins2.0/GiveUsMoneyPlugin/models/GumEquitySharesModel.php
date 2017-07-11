<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class GumEquitySharesModel

  This table stores class "C" equity transactions.
  Positive numbers for shares/values represent the
  member purchasing equity; negative shares/values
  represent the co-op buying equity back from the
  member.

  Negative entries may (should?) have a corresponding
  entry in GumPayoffs for the check that was issued.
  That table can be joined to this table via
  GumEquityPayoffMap.
*/
class GumEquitySharesModel extends BasicModel
{

    protected $name = "GumEquityShares";
    protected $preferred_db = 'plugin:GiveUsMoneyDB';

    protected $columns = array(
    'gumEquityShareID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'card_no' => array('type'=>'INT', 'index'=>true),
    'shares' => array('type'=>'INT', 'default'=>0),
    'value' => array('type'=>'MONEY', 'default'=>0),
    'tdate' => array('type'=>'DATETIME'),
    'trans_num' => array('type'=>'VARCHAR(50)'),
    );
}

