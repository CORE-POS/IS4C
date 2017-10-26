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
  @class IdentifiersModel
*/
class IdentifiersModel extends BasicModel
{
    protected $name = "Identifiers";

    protected $columns = array(
    'identifierID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'cardNo' => array('type'=>'INT'),
    'guid' => array('type'=>'CHAR(40)'),
    'status' => array('type'=>'INT', 'default'=>0),
    );

    public function etl($config)
    {
        $settings = $config->get('PLUGIN_SETTINGS');
        $mydb = $settings['MyWebDB'] . $this->connection->sep();
        $opdb = $config->get('OP_DB') . $this->connection->sep();
        $this->whichDB($settings['MyWebDB']);

        $needR = $this->connection->query("
            SELECT CardNo
            FROM {$opdb}custdata
            WHERE personNum=1
                AND Type='PC'
                AND CardNo NOT IN (SELECT cardNo FROM {$mydb}Identifiers)");
        $this->connection->startTransaction();
        while ($row = $this->connection->fetchRow($needR)) {
            $this->reset();
            $this->cardNo($row['CardNo']);
            $uuid = Ramsey\Uuid\Uuid::uuid4();
            $uuid = str_replace('-', '', $uuid->toString());
            $this->guid($uuid);
            $this->save();
        }
        $this->connection->commitTransaction();

        /**
         * Auto-disable live pages for accounts not in good standing
         */
        $this->connection->query("
            UPDATE {$mydb}Identifiers AS i
                INNER JOIN custdata AS c ON i.cardNo=c.CardNo AND c.personNum=1
            SET i.status=2
            WHERE c.Type <> 'PC'
                AND i.status=1");

        /**
         * Reverse auto-disabling if account issues are resolved
         */
        $this->connection->query("
            UPDATE {$mydb}Identifiers AS i
                INNER JOIN custdata AS c ON i.cardNo=c.CardNo AND c.personNum=1
            SET i.status=1
            WHERE c.Type='PC'
                AND i.status=2");
    }
}

