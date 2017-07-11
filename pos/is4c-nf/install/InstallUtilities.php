<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

namespace COREPOS\pos\install;

use COREPOS\pos\install\conf\Conf;
use COREPOS\pos\install\conf\PhpConf;
use COREPOS\pos\install\conf\JsonConf;
use COREPOS\pos\install\conf\ParamConf;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\Database;
use \CoreLocal;

class InstallUtilities 
{
    static public function dbOrFail($dbname)
    {
        return self::dbTestConnect(
                CoreLocal::get('localhost'),
                CoreLocal::get('DBMS'),
                $dbname,
                CoreLocal::get('localUser'),
                CoreLocal::get('localPass'));
    }

    /**
      Save value to the parameters table.
    */
    static public function paramSave($key, $value) 
    {
        $sql = self::dbOrFail(CoreLocal::get('pDatabase'));

        return ParamConf::save($sql, $key, $value);
    }

    static public function dbTestConnect($host,$type,$dbname,$user,$pass)
    {
        $sql = false;
        try {
            if ($type == 'mysql') {
                ini_set('mysql.connect_timeout',1);
            } elseif ($type == 'mssql') {
                ini_set('mssql.connect_timeout',1);
            }
            $sql =  new \COREPOS\pos\lib\SQLManager($host,$type,$dbname,$user,$pass);
        } catch(Exception $ex) {}

        if ($sql === false || $sql->isConnected($dbname) === false) {
            return false;
        }

        return $sql;
    }

    /* query to create another table with the same
        columns
       @retrun string query or boolean false
    */
    static public function duplicateStructure($dbms,$table1,$table2)
    {
        if (strstr($dbms,"MYSQL")) {
            return "CREATE TABLE `$table2` LIKE `$table1`";
        } elseif ($dbms == "MSSQL") {
            return "SELECT * INTO [$table2] FROM [$table1] WHERE 1=0";
        }

        return false;
    }

    public static function normalizeDbName($name)
    {
        if ($name == 'op') {
            return CoreLocal::get('pDatabase');
        } elseif ($name == 'trans') {
            return CoreLocal::get('tDatabase');
        } elseif (substr($name, 0, 7) == 'plugin:') {
            $pluginDbKey = substr($name, 7);
            if (CoreLocal::get("$pluginDbKey",'') !== '') {
                return CoreLocal::get("$pluginDbKey");
            }
        }

        return false;
    }

    private static function checkParameter($param, $checked, $wrong)
    {
        $pValue = $param->materializeValue();
        $checked[$param->param_key()] = true;
        $iValue = CoreLocal::get($param->param_key());
        if (isset($checked[$param->param_key()])) {
            // setting has a lane-specific parameters
        } elseif (is_numeric($iValue) && is_numeric($pValue) && $iValue == $pValue) {
            // allow loose comparison on numbers
            // i.e., permit integer 1 equal string '1'
        } elseif ($pValue !== $iValue) {
            printf('<span style="color:red;">' . _('Setting mismatch for') . '</span>
                <a href="" onclick="$(this).next().toggle(); return false;">%s</a>
                <span style="display:none;"> ' . _('parameters says') . ' %s, ' . _('ini says') . ' %s</span></p>',
                $param->param_key(), print_r($pValue, true), print_r($iValue, true)
            );
            $wrong[$param->param_key()] = $pValue;
        }

        return array($checked, $wrong);
    }
    
    public static function validateConfiguration()
    {
        global $CORE_LOCAL;
        /**
          Opposite of normal. Load the parameters table values
          first, then include ini.php second. If the resulting
          $CORE_LOCAL does not match the paramters table, that
          means ini.php overwrote a setting with a different
          value.
        */
        CoreState::loadParams();
        include(dirname(__FILE__) . '/../ini.php');

        $dbc = Database::pDataConnect();

        /**
          Again backwards. Check lane-specific parameters first
        */
        $parameters = new \COREPOS\pos\lib\models\op\ParametersModel($dbc);
        $parameters->store_id(0);
        $parameters->lane_id($CORE_LOCAL->get('laneno'));
        $checked = array();
        $wrong = array();
        foreach ($parameters->find() as $param) {
            list($checked, $wrong) = self::checkParameter($param, $checked, $wrong);
        }

        /**
          Now check global parameters
        */
        $parameters->reset();
        $parameters->store_id(0);
        $parameters->lane_id(0);
        foreach ($parameters->find() as $param) {
            list($checked, $wrong) = self::checkParameter($param, $checked, $wrong);
        }

        /**
          Finally, re-save any conflicting values.
          This should rewrite them in ini.php if that
          file is writable.
        */
        foreach ($wrong as $key => $value) {
            self::paramSave($key, $value);
        }
    }

}

