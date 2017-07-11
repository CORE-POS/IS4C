<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2015 West End Food Co-op, Toronto

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
  @class CCredConfigModel
*/
class CCredConfigModel extends BasicModel
{

    // The actual name of the table.
    protected $name = 'CCredConfig';
    protected $preferred_db = 'plugin:CoopCredDatabase';

    protected $columns = array(
        'configID' => array('type'=>'SMALLINT(6)', 'default'=>1, 'primary_key'=>True,
            'increment'=>False),
        /* Tender refers to tenders.TenderCode but value shouldn't exist.
         * The default Q9 is appropriate for a range of usable values QA-QZ.
         */
        'dummyTenderCode' => array('type'=>'VARCHAR(2)', 'not_null'=>True,
            'default'=>"'Q9'"),
        /* Department refers to departments.dept_no but values don't need to exist.
         * The default 1020 is appropriate for a range of usable values 1021-1099
         */
        'dummyDepartment' => array('type'=>'SMALLINT(6)', 'not_null'=>True,
            'default'=>1020),
        /* A range reserved for Coop Cred departments.
         */
        'deptMin' => array('type'=>'SMALLINT(6)', 'not_null'=>True,
            'default'=>1),
        'deptMax' => array('type'=>'SMALLINT(6)', 'not_null'=>True,
            'default'=>9999),
        /* Banker refers to custdata.CardNo but values don't need to exist.
         */
        'dummyBanker' => array('type'=>'INT(11)', 'not_null'=>True,
            'default'=>99900),
        /* A range in which some special rules apply.
         */
        'bankerMin' => array('type'=>'INT(11)', 'not_null'=>True,
            'default'=>1),
        'bankerMax' => array('type'=>'INT(11)', 'not_null'=>True,
            'default'=>99999),
        /* Member refers to custdata.CardNo but value doesn't need to exist.
         * A range in which non-banker memberships will fall.
         */
        'regularMemberMin' => array('type'=>'INT(11)', 'not_null'=>True,
            'default'=>1),
        'regularMemberMax' => array('type'=>'INT(11)', 'not_null'=>True,
            'default'=>99999),
        /* */
        'modified' => array('type'=>'DATETIME', 'not_null'=>True,
            'default'=>"'0000-00-00 00:00:00'"),
        'modifiedBy' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0)
    );

    public function name()
    {
        return $this->name;
    }

    /**
        @return string desribing
         - purpose of the table
         - depends on
         - depended on by
     */
    public function description()
    {
        $desc = "";
        $desc = "A single-record table
            containing coop-specific values
            that will help the plugin fit in the local system.
            <br />depends on: none
            <br />depended on by: none
            ";
        return $desc;
    }
}

