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

/**
  @class PatronageModel
*/
class PatronageModel extends BasicModel 
{
    protected $name = "patronage";

    protected $preferred_db = 'op';

    protected $columns = array(
    'cardno' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'purchase' => array('type'=>'MONEY'),
    'discounts' => array('type'=>'MONEY'),
    'rewards' => array('type'=>'MONEY'),
    'net_purch' => array('type'=>'MONEY'),
    'tot_pat' => array('type'=>'MONEY'),
    'cash_pat' => array('type'=>'MONEY'),
    'equit_pat' => array('type'=>'MONEY'),
    'FY' => array('type'=>'SMALLINT','primary_key'=>True,'default'=>0),
    'check_number' => array('type'=>'INT'),
    'cashed_date' => array('type'=>'DATETIME'),
    'cashed_here' => array('type'=>'TINYINT', 'default'=>0),
    );

    public function gumPayoffID($id)
    {
        // stub function so I can call GumLib::allocateCheck
    }

    public function doc()
    {
        return '
Use:
The patronage table records patronage distributions to owners. There is
one entry per-owner per-fiscal year.
* purchase is total, gross spending on eligible items
* discounts are owner benefits paid out during the fiscal year, typically
  via a percentage discount on transactions
* rewards are also owner benefits paid out during the fiscal year, typically
  by some other mechanism like owner-only coupons
* net_purch is purchase minus discounts minus rewards
* tot_pat is the total patronage distribution to the owner
* cash_pat is the cash portion of the distribution
* equit_pat is the retained equity portion of the distribution

Check number and cashing info are only relevant when patronage is distributed
via check.
            ';
    }
}

