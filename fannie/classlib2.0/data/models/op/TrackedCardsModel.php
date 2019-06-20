<?php

/*******************************************************************************

    Copyright 2019 Whole Foods Co-op

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
  @class TrackedCardsModel
*/
class TrackedCardsModel extends BasicModel
{
    protected $name = "TrackedCards";
    protected $preferred_db = 'op';

    protected $columns = array(
    'hash' => array('type'=>'VARCHAR(32)', 'primary_key'=>true),
    'PAN' => array('type'=>'VARCHAR(19)'),
    'name' => array('type'=>'VARCHAR(50)'),
    'firstSeen' => array('type'=>'DATETIME'),
    'lastSeen' => array('type'=>'DATETIME'),
    'times' => array('type'=>'INT', 'default'=>0),
    'cardNo' => array('type'=>'INT'),
    'converted' => array('type'=>'INT'),
    );

    public function doc()
    {
        return '
Track credit cards as a mechanism to distinguish non-owner
shoppers. Hash here is an MD5 hash of the last four digits
of the card plus the name on the card to minimize duplicates
(common names or repeats of the last 4 digits).


Times is the number of times a card has been encountered.
cardNo is the custdata account that has been assigned to this
shopper. converted is to store the member number if the person
eventually joins.
';
    }
}

