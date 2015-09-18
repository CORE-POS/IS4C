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
  @class TenderTapeGenericModel
*/
class TenderTapeGenericModel extends ViewModel
{

    protected $name = "TenderTapeGeneric";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'tdate' => array('type'=>'DATETIME'),
    'emp_no' => array('type'=>'INT'),
    'register_no' => array('type'=>'INT'),
    'trans_no' => array('type'=>'INT'),
    'trans_subtype' => array('type'=>'VARCHAR(2)'),
    'tender' => array('type'=>'MONEY'),
    );

    public function definition()
    {
        return "
            SELECT MAX(tdate) AS tdate,
                emp_no, 
                register_no,
                trans_no,
                CASE WHEN trans_subtype = 'CP' AND upc LIKE '%MAD%' THEN ''
                     WHEN trans_subtype IN ('EF','EC','TA') THEN 'EF'
                     ELSE trans_subtype
                END AS tender_code,
                -1 * SUM(total) AS tender
            FROM dlog
            WHERE trans_subtype NOT IN ('0', '')
                AND " . $this->connection->datediff('tdate', $this->connection->now()) . " = 0
            GROUP BY emp_no,
                register_no,
                trans_no,
                tender_code";
    }

    public function doc()
    {
        return '
Depends on:
* dlog (view)

Use:
This view lists all a cashier\'s 
tenders for the day. It is used for 
generating tender reports at the registers.

Ideally this deprecates the old system of
having a different view for every tender
type.

Behavior in calculating trans_subtype and
total may be customized on a per-co-op
basis without changes to the register code
        ';
    }
}

