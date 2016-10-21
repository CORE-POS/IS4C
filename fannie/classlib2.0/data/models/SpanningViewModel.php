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
  @class SpannigViewModel
*/
class SpanningViewModel extends ViewModel
{
    /**
      Name(s) of other databases the view
      makes use of
    */
    protected $extra_db_names = array();

    /**
      Add another database name
      @param $name [string] database name
    */
    public function addExtraDB($name)
    {
        $this->extra_db_names[] = $name;
    }

    /**
      Locate a table in one of the extra databases
      @param $table [string] name of table/view
      @return database_name.table_name or [boolean] false
    */
    public function findExtraTable($table)
    {
        foreach ($this->extra_db_names as $db) {
            $fq_name = $this->connection->identifierEscape($db)
                    . $this->connection->sep()
                    . $this->connection->identifierEscape($table);
            /**
              Underlying tableExists function does not
              cope with identifier escapes correctly. Omitting
              backticks (etc) is not ideal but works in
              most cases.
            */
            $fq_name = $db . $this->connection->sep() . $table;
            if ($this->connection->tableExists($fq_name)) {
                return $fq_name;
            }
        }

        return false;
    }
}

