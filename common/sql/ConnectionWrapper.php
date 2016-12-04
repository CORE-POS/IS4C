<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

namespace COREPOS\common\sql;

class ConnectionWrapper
{
    private $connection;
    private $dbName;

    public function __construct($con, $databaseName)
    {
        $this->connection = $con;
        $this->dbName = $databaseName;
    }

    /**
      Switch to the desired database, execute query, and switch
      back again (if needed)
    */
    public function query($query_text,$whichCon='',$params=false)
    {
        $current = $this->connection->defaultDatabase($whichCon);
        $this->connection->selectDB($this->dbName, $whichCon);
        $ret = $this->connection->query($query_text, $whichCon='', $params);
        if ($current) {
            $this->connection->selectDB($current, $whichCon);
        }

        return $ret;
    }

    /**
      Call method on underlying SQLManager object
    */
    public function __call($method, $args)
    {
        if (!method_exists($this->connection, $method)) {
            throw new \Exception("No method $method");
        }

        return call_user_func_array(array($this->connection, $method), $args);
    }
}
