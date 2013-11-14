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

if (!class_exists('FannieDB')) { 
    include_once(dirname(__FILE__).'/../data/FannieDB.php');
}

class FannieAuth 
{

    /**
      Check who is logged in
      @return string username or False

      If authentication is not enabled, this
      function returns the string 'null'. If
      an init.php file is in place it returns
      the string 'init'.
    */
    static public function checkLogin()
    {
        global $FANNIE_OP_DB;

        if (!self::enabled()) {
            return 'null';
        }
        if (self::initCheck()) {
            return 'init';
        }

        if (!isset($_COOKIE['session_data'])){
            return false;
        }

        $cookie_data = base64_decode($_COOKIE['session_data']);
        $session_data = unserialize($cookie_data);

        $name = $session_data['name'];
        $session_id = $session_data['session_id'];

        if (!self::isAlphanumeric($name) or !self::isAlphanumeric($session_id)) {
            return false;
        }

        $sql = FannieDB::get($FANNIE_OP_DB);
        $checkQ = $sql->prepare_statement("select * from Users AS u LEFT JOIN
                userSessions AS s ON u.uid=s.uid where u.name=? 
                and s.session_id=?");
        $checkR = $sql->exec_statement($checkQ,array($name,$session_id));

        if ($sql->num_rows($checkR) == 0) {
            return false;
        }

        return $name;
    }

    /**
      Check if the current user has the given
      permission
      @param $auth authorization class name
      @param $sub optional subclass
      @return current username or False

      If authentication is not enabled, this
      function returns the string 'null'. If
      an init.php file is in place it returns
      the string 'init'.
    */
    static public function validateUserQuiet($auth, $sub='all')
    {
        if (!self::enabled()) {
            return 'null';
        }
        if (self::initCheck()) {
            return 'init';
        }

        $current_user = self::checkLogin();
        if (!$current_user) {
            return false;
        }

        $groupPriv = self::checkGroupAuth($current_user,$auth,$sub);
        if ($groupPriv) {
            return $current_user;
        }

        $priv = self::checkAuth($current_user,$auth,$sub);
        if (!$priv) {
            return false;
        }

        return $current_user;
    }

    /**
      Check if the given user has the given permission
      @param $name the username
      @param $auth_class the authorization class
      @param $sub optional subclass
      @return boolean
    */
    static private function checkAuth($name, $auth_class, $sub='all')
    {
        global $FANNIE_OP_DB;
        if (self::initCheck()) {
            return 'init';
        }

        if (!self::isAlphanumeric($name) || !self::isAlphanumeric($auth_class) || !self::isAlphanumeric($sub)) {
            return false;
        }

        $uid = self::getUID($name);
        if (!$uid) {
            return false;
        }
        $sql = FannieDB::get($FANNIE_OP_DB);
        $checkQ = $sql->prepare_statement("select * from userPrivs where uid=? and auth_class=? and
                 ((? between sub_start and sub_end) or (sub_start='all' and sub_end='all'))");
        $checkR = $sql->exec_statement($checkQ,array($uid,$auth_class,$sub));
        if ($sql->num_rows($checkR) == 0) {
            return false;
        }

        return true;
    }

    /**
      Check if the given user is part of a group that
      has the given permission
      @param $user the username
      @param $auth the authorization class
      @param $sub optional subclass
      @return boolean
    */
    static private function checkGroupAuth($user, $auth, $sub='all')
    {
        global $FANNIE_OP_DB;
        $sql = FannieDB::get($FANNIE_OP_DB); 
        if (!self::isAlphaNumeric($user) || !self::isAlphaNumeric($auth) ||
            !self::isAlphaNumeric($sub)) {
            return false;
        }
        $checkQ = $sql->prepare_statement("select g.gid  from userGroups as g, userGroupPrivs as p where
                        g.gid = p.gid and g.username=?
                        and p.auth=? and
                        ((? between p.sub_start and p.sub_end) or
                        (p.sub_start='all' and p.sub_end='all'))");
        $checkR = $sql->exec_statement($checkQ,array($user,$auth,$sub));

        if ($sql->num_rows($checkR) == 0) {
            return false;
        }

        return true;
    }

    /**
      Get UID for given username
      @param $name the username
      @return string UID or False
    
      If authentication is not enabled,
      returns string '0000'.
    */
    public static function getUID($name) 
    {
        global $FANNIE_OP_DB;
        if (!self::enabled()) {
            return '0000';
        }

        $sql = FannieDB::get($FANNIE_OP_DB);
        $fetchQ = $sql->prepare_statement("select uid from Users where name=?");
        $fetchR = $sql->exec_statement($fetchQ,array($name));
        if ($sql->num_rows($fetchR) == 0) {
            return false;
        }
        $uid = $sql->fetch_array($fetchR);
        $uid = $uid[0];

        return $uid;
    }

    /**
      Check if authentication is enabled in
      Fannie's configuration
      @return boolean
    */
    static private function enabled()
    {
        global $FANNIE_AUTH_ENABLED;
        if (!isset($FANNIE_AUTH_ENABLED)) {
            include(dirname(__FILE__).'/../../config.php');
            return $FANNIE_AUTH_ENABLED;
        } else {
            return $FANNIE_AUTH_ENABLED;
        }
    }

    /**
      Check if an init.php file exists
      @return boolean
    */
    static private function initCheck()
    {
        return file_exists(dirname(__FILE__).'/../../auth/init.php');
    }

    /**
      Check if a string is alphanumeric
      @return boolean
    */
    static private function isAlphanumeric($str)
    {
        if (preg_match("/^\\w*$/",$str) == 0) {
            return false;
        }

        return true;
    }
}

