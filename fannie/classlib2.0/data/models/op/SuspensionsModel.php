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
  @class SuspensionsModel
*/
class SuspensionsModel extends BasicModel 
{

    protected $name = "suspensions";

    protected $preferred_db = 'op';

    protected $columns = array(
    'cardno' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'type' => array('type'=>'CHAR(1)'),
    'memtype1' => array('type'=>'INT'),
    'memtype2' => array('type'=>'VARCHAR(6)'),
    'suspDate' => array('type'=>'DATETIME'),
    'reason' => array('type'=>'TEXT'),
    'mailflag' => array('type'=>'INT'),
    'discount' => array('type'=>'INT'),
    'chargelimit' => array('type'=>'MONEY'),
    'reasoncode' => array('type'=>'INT')
    );

    public function doc()
    {
        return '
Depends on:
* custdata (table)

Use:
suspensions are a way of putting a membership on
hold. When an account is suspended, it reverts
to the lowest possible privileges and custdata\'s
settings for Type, memType, Discount, and 
ChargeLimit are stored here in memtype1, memtype2,
discount, and chargelimit (respectively). When
the account is restored, custdata\'s original settings
are repopulated from these saved values.

type currently contains \'I\' (inactive memberships
that may return) or \'T\' (terminated memberships that
will not return).

Historically, the "reason" field was used to manually
enter a reason for the suspension. Using the reasoncode
is now preferred. This field is interpretted as binary
using masks from the reasoncodes table to determine
which reason(s) have been given.
        ';
    }
}

