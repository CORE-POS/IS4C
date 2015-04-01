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

/**
  19Mar2015
  If the common library does not exist, use the
  old style SQLManager. This is a temporary measure
  to avoid breaking deployments with a non-standard
  directory structure.
*/
if (!file_exists(dirname(__FILE__) . '/../../../common/SQLManager.php')) {
    include(dirname(__FILE__) . '/SQLManager.old.php');
} else {

    /**
      Autoloader doesn't know about COREPOS\Common yet
    */
    if (!class_exists('\\COREPOS\\Common\\SQLManager', false)) {
        include(dirname(__FILE__) . '/../../../common/SQLManager.php');
    }

    class SQLManager extends \COREPOS\Common\SQLManager
    {
        /**
          Override to initialize QUERY_LOG
        */
        public function __construct($server,$type,$database,$username,$password='',$persistent=false, $new=false)
        {
            $this->setQueryLog(dirname(__FILE__) . '/../log/queries.log');

            parent::__construct(
                $server, 
                $type, 
                $database, 
                $username, 
                $password,
                $persistent,
                $new
            );
        }

        /**
          Override to convert $type argument if needed
          for backwards compatibility
        */
        public function addConnection($server,$type,$database,$username,$password='',$persistent=false,$new=false)
        {
            /**
              Convert old lane style to ADO style
              naming for PDO MySQL
            */
            if (strtolower($type) == 'pdomysql') {
                $type = 'pdo_mysql';
            }

            return parent::addConnection(
                $server, 
                $type, 
                $database, 
                $username, 
                $password,
                $persistent,
                $new
            );
        }
    }

} // end else case defining SQLManager based on \COREPOS\Common

