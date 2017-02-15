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
  @class CustdataModel

*/

class CustdataModel extends BasicModel 
{

    protected $name = 'custdata';

    protected $preferred_db = 'op';

    protected $columns = array(
    'CardNo' => array('type'=>'INT','index'=>True),
    'personNum' => array('type'=>'TINYINT'),
    'LastName' => array('type'=>'VARCHAR(30)','index'=>True),
    'FirstName' => array('type'=>'VARCHAR(30)'),
    'CashBack' => array('type'=>'MONEY'),
    'Balance' => array('type'=>'MONEY'),
    'Discount' => array('type'=>'SMALLINT'),
    'MemDiscountLimit' => array('type'=>'MONEY','default'=>0),
    'ChargeLimit' => array('type'=>'MONEY','default'=>0),
    'ChargeOk' => array('type'=>'TINYINT','default'=>1),
    'WriteChecks' => array('type'=>'TINYINT','default'=>1),
    'StoreCoupons' => array('type'=>'TINYINT','default'=>1),
    'Type' => array('type'=>'VARCHAR(10)','default'=>"'PC'"),
    'memType' => array('type'=>'TINYINT'),
    'staff' => array('type'=>'TINYINT','default'=>0),
    'SSI' => array('type'=>'TINYINT','default'=>0),
    'Purchases' => array('type'=>'MONEY','default'=>0),
    'NumberOfChecks' => array('type'=>'SMALLINT','default'=>0),
    'memCoupons' => array('type'=>'INT','default'=>1),
    'blueLine' => array('type'=>'VARCHAR(50)'),
    'Shown' => array('type'=>'TINYINT','default'=>1),
    'LastChange' => array('type'=>'TIMESTAMP'),
    'id' => array('type'=>'INT','primary_key'=>True,'increment'=>True)
    );

    public function doc()
    {
        return '
Use:
This is one of two "primary" tables dealing with membership
(the other is meminfo). Of the two, only custdata is present
at the checkout. Column meaning may not be quite identical 
across stores.

[Probably] The Same Everywhere:
- CardNo is the member\'s number. This identifies them.
- personNum is for stores that allow more than one person per membership.
  personNum starts at 1.
    The combination (CardNo, personNum) should be unique for each record.
- FirstName what it sounds like.
- LastName what it sounds like.
- Discount gives the member a percentage discount on purchases.
- Type identifies whether the record is for an actual member.
  If Type is \'PC\', the person is considered a member at the register.
    This is a little confusing, but not everyone in the table has to be
   a member.
- blueLine is displayed on the checkout screen when the member\'s number is entered.
- id just provides a guaranteed-unique row identifier.

[Probably] Just For Organizing:
The register is mostly unaware of these settings,
but they can be used on the backend for consistency checks
e.g., make sure all staff members have the appropriate percent discount
- staff identifies someone as an employee. Value: 1?
- memType allows a little more nuance than just member yes/no.
  FK to memtype.memtype
- SSI probably because of a historic senior citizen discount.
  (Sounds like it is obsolete or at least not used.)

WFC Specific:
- ChargeOk=1 if member may run a store charge balance; =0 may not.
- MemDiscountLimit is their store charge account limit.
- ChargeLimit is their store charge account limit.
- Balance is a store charge balance as of the start of the day,
   if the person has one.
     Some records are for organizations, esp vendors,
     that have charge accounts.
     Is updated from newBalanceToday_cust by cronjob arbalance.sanitycheck.php
- memCoupons indicates how many virtual coupons (tender MA) are available.

[Probably] Ignored:
To the best of my (Andy\'s) knowledge, these have no meaning on the front or back end.
- CashBack
- WriteChecks
- StoreCoupons
- Purchases
- NumberOfChecks
- Shown

Maintenance:
- Single edit: fannie/mem/search.php
- Single add: fannie/mem/new.php
- Batch import: fannie/mem/import/*.php
- custdata.Balance is updated from newBalanceToday_cust by cronjob arbalance.sanitycheck.php
        ';
    }

    /**
      Use this instead of primary key for identifying
      records
    */
    protected $unique = array('CardNo','personNum');

    protected $normalize_lanes = true;
}

