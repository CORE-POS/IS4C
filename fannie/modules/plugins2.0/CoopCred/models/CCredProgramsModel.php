<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

    This file is part of IT CORE.

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
  @class CCredProgramsModel
*/
class CCredProgramsModel extends BasicModel
{

    // The actual name of the table.
    protected $name = 'CCredPrograms';
    protected $preferred_db = 'plugin:CoopCredDatabase';

    protected $columns = array(
        'programID' => array('type'=>'SMALLINT(6)', 'default'=>0, 'primary_key'=>True,
            'increment'=>True),
        'programName' => array('type'=>'VARCHAR(100)', 'not_null'=>True,
            'default'=>"''"),
        // default is ignored
        'active' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'startDate' => array('type'=>'DATE', 'not_null'=>True,
            'default'=>"'0000-00-00'"),
        'endDate' => array('type'=>'DATE', 'default'=>'NULL'),
        // FK to op.custdata and CoopCred.Members
        'bankID' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0),
        // FK to op.departments
        'paymentDepartment' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0),
        'tenderType' => array('type'=>'VARCHAR(2)', 'not_null'=>True, 'default'=>"''"),
        'inputTenderType' => array('type'=>'VARCHAR(2)', 'not_null'=>True, 'default'=>"''"),
        'creditOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'inputOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'transferOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        // The most that can be deposited in a Member's account.
        // Is negative, so CCredMembers.creditBalance must be larger.
        // This is the default for the Program, can be overridden per-member.
        'maxCreditBalance' => array('type'=>'MONEY', 'not_null'=>True, 'default'=>0),
        'modified' => array('type'=>'DATETIME', 'not_null'=>True,
            'default'=>"'0000-00-00 00:00:00'"),
        'modifiedBy' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0),
        // Text for departments.dept_name
        'paymentName' => array('type'=>'VARCHAR(30)', 'not_null'=>True, 'default'=>"''"),
        // Text for payment/input keycap, or very short-form references.
        'paymentKeyCap' => array('type'=>'VARCHAR(25)', 'not_null'=>True, 'default'=>"''"),
        // Text for tenders.TenderName
        'tenderName' => array('type'=>'VARCHAR(25)', 'not_null'=>True, 'default'=>"''"),
        // Text for tender keycap, or very short-form references.
        'tenderKeyCap' => array('type'=>'VARCHAR(25)', 'not_null'=>True, 'default'=>"''")
    );

    public function name()
    {
        return $this->name;
    }

    /* Run the regular save() with the connection switched to each lane.
     * Restore the connection to the server before returning.
     * @return true on success for all lanes, or
     *  messages on failure to load record or
     *  find lane db name or per-lane errors.
     * Accumulate errors about lane connection and lane save() and
     *  log to Fannie and return.
     */
    public function pushToLanesCoopCred()
    {
        global $FANNIE_LANES, $FANNIE_PLUGIN_SETTINGS;

        $errors = array();

        /* Columns for unique-ness must already be assigned.
         * The save() to server must already be done.
         */
        if (!$this->load()) {
            $msg="pTLCC Program load failed";
            $this->connection->logger($msg);
            return $msg;
        }

        if (array_key_exists('CoopCredLaneDatabase', $FANNIE_PLUGIN_SETTINGS) &&
            $FANNIE_PLUGIN_SETTINGS['CoopCredLaneDatabase'] != "") {
            $coopCredLaneDatabase = $FANNIE_PLUGIN_SETTINGS['CoopCredLaneDatabase'];
        } else {
            $msg ="pTLCC Program failed to get lane db name";
            $this->connection->logger($msg);
            return $msg;
        }

        $current = $this->connection;
        // save to each lane
        $laneNumber = 0;
        foreach($FANNIE_LANES as $lane) {
            $laneNumber++;
            $lane['op'] = $coopCredLaneDatabase;
            $sql = new SQLManager($lane['host'],$lane['type'],$lane['op'],
                        $lane['user'],$lane['pw']);    
            if (!is_object($sql) || $sql->connections[$lane['op']] === false) {
                $errors[] = "pTLCC Program connect to lane{$laneNumber} failed.";
                continue;
            }
            $this->connection = $sql;
            /* Update or Insert as appropriate.
             * Return membershipID or False
             */
            if ($this->save() === False) {
                $errors[] = "pTLCC Program save to lane{$laneNumber} failed.";
            }
        }
        /* Restore connection to Fannie. */
        $this->connection = $current;

        if (count($errors)>0) {
            $msg = implode("\n",$errors);
            $this->connection->logger($msg);
            return $msg;
        } else {
            return true;
        }

    // pushToLanesCoopCred
    }

}

