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
  @class MeminfoModel

*/

class MeminfoModel extends BasicModel 
{

    protected $name = 'meminfo';

    protected $preferred_db = 'op';

    protected $columns = array(
    'card_no' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'last_name' => array('type'=>'VARCHAR(30)'),
    'first_name' => array('type'=>'VARCHAR(30)'),
    'othlast_name' => array('type'=>'VARCHAR(30)'),
    'othfirst_name' => array('type'=>'VARCHAR(30)'),
    'street' => array('type'=>'VARCHAR(255)'),
    'city' => array('type'=>'VARCHAR(20)'),
    'state' => array('type'=>'VARCHAR(2)'),
    'zip' => array('type'=>'VARCHAR(10)'),
    'phone' => array('type'=>'VARCHAR(30)'),
    'email_1' => array('type'=>'VARCHAR(50)'),
    'email_2' => array('type'=>'VARCHAR(50)'),
    'ads_OK' => array('type'=>'TINYINT','default'=>1),
    'modified'=>array('type'=>'DATETIME','ignore_updates'=>true),
    );

    public function save()
    {
        $stack = debug_backtrace();
        $lane_push = false;
        if (isset($stack[1]) && $stack[1]['function'] == 'pushToLanes') {
            $lane_push = true;
        }

        if ($this->record_changed && !$lane_push) {
            $this->modified(date('Y-m-d H:i:s'));
        }

        return parent::save();
    }

    public function doc()
    {
        return '
Depends on:
* custdata (table)

Use:
This table has contact information for a member,
i.e. it extends custdata on card_no.
See also: memContact.

Usage doesn\'t have to match mine (AT). The member section of
fannie should be modular enough to support alternate
usage of some fields.

card_no key to custdata and other customer tables.

Straightforward:
- street varchar 255
- city
- state
- zip
- phone
  long enough to include extension but don\'t put more than
  one number in it.

The name fields are for two different people.
This approach will work if your co-op allows only
1 or 2 people per membership, but custdata can hold
the same information in a more future-proof way,
i.e. support any number of people per membership,
so better to not use them in favour of custdata.

- email_1 for email
- email_2 for second phone

- ads_OK EL: Perhaps: flag for whether OK to send ads.
  Don\'t know whether implemented for this or any purpose.
        ';
    }

    /**
      Use custdata to set initial change timestamps
    */
    public function hookAddColumnmodified()
    {
        $dbms = $this->connection->dbmsName();
        if ($dbms == 'mssql') {
            $this->connection->query('
                UPDATE meminfo
                SET m.modified=c.LastChange
                FROM meminfo AS m
                    INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1');
        } elseif ($dbms === 'postgres9') {
            $this->connection->query('
                UPDATE meminfo AS m
                SET m.modified=c.LastChange
                FROM custdata AS c
                WHERE m.card_no=c.CardNo AND c.personNum=1');
        } else {
            $this->connection->query('
                UPDATE meminfo AS m
                    INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1
                SET m.modified=c.LastChange');
        }
    }
}

