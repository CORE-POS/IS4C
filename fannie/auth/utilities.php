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

/*
utility functions
*/


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    * 12Nov2012 Eric Lee In getGID() test FANNIE_DBMS_SERVER for SQL syntax.
*/

/*
connect to the database
having this as a separate function makes changing
the database easier
*/
function dbconnect()
{
    include(dirname(__FILE__).'/../config.php');
    if (!class_exists("FannieAPI")){
        include(dirname(__FILE__).'/../classlib2.0/FannieAPI.php');
    }
    $dbc = FannieDB::get($FANNIE_OP_DB);

    return $dbc;
}

function guesspath(){
    $path = "";
    $found = False;
    $uri = $_SERVER["REQUEST_URI"];
    $tmp = explode("?",$uri);
    if (count($tmp) > 1) $uri = $tmp[0];
    foreach(explode("/",$uri) as $x){
        if (strpos($x,".php") === False
            && strlen($x) != 0){
            $path .= "../";
        }
        if (!$found && stripos($x,"fannie") !== False){
            $found = True;
            $path = "";
        }
        
    }
    return $path;
}

function init_check(){
    return file_exists(dirname(__FILE__)."/init.php");
}

/*
checking whether a string is alphanumeric is
a good idea to prevent sql injection
*/
function isAlphanumeric($str){
  if (preg_match("/^\\w*$/",$str) == 0){
    return false;
  }
  return true;
}

function getUID($name){
  if (!auth_enabled()) return '0000';

  $sql = dbconnect();
  $fetchQ = $sql->prepare_statement("select uid from Users where name=?");
  $fetchR = $sql->exec_statement($fetchQ,array($name));
  if ($sql->num_rows($fetchR) == 0){
    return false;
  }
  $uid = $sql->fetch_array($fetchR);
  $uid = $uid[0];
  return $uid;
}

function getNumUsers(){
  if (!auth_enabled()) return 9999;
    
  $sql = dbconnect();
  $fetchQ = $sql->prepare_statement("select uid from Users");
  $fetchR = $sql->exec_statement($fetchQ);

  return $sql->num_rows($fetchR);
}

function getNumAdmins(){
    $sql = dbconnect();
    $num = 0;
    if ($sql->table_exists('userPrivs')){
        $q = $sql->prepare_statement("SELECT uid FROM userPrivs WHERE auth_class='admin'");
        $r = $sql->exec_statement($q);
        $num += $sql->num_rows($r);
    }
    if ($sql->table_exists('userGroups') && $sql->table_exists('userGroupPrivs')){
        $q = $sql->prepare_statement("SELECT username FROM userGroups AS g LEFT JOIN
            userGroupPrivs AS p ON g.gid=p.gid
            WHERE p.auth='admin'");
        $r = $sql->exec_statement($q);
        $num += $sql->num_rows($r);

    }
    return $num;
}

function getGID($group){
    // 11Nov12 EL Bring in config for SERVER_DBMS test.
    include(dirname(__FILE__)."/../config.php");

  if (!isAlphaNumeric($group))
    return false;
  $sql = dbconnect();

  $gidQ = "select gid from userGroups where name=?";
  $gidQ = $sql->add_select_limit($gidQ,1); 
  $gidP = $sql->prepare_statement($gidQ);
  $gidR = $sql->exec_statement($gidP,array($group));

  if ($sql->num_rows($gidR) == 0)
    return false;

  $row = $sql->fetch_array($gidR);
  return $row[0];
}

function genSessID(){
  $session_id = '';
  srand(time());
  for ($i = 0; $i < 50; $i++){
    $digit = (rand() % 35) + 48;
    if ($digit > 57){
      $digit+=7;
    }
    $session_id .= chr($digit);
  }
  return $session_id;
}

function doLogin($name){
    $session_id = genSessID();  

    $sql = dbconnect();
    $sessionQ = $sql->prepare_statement("update Users set session_id = ? where name=?");
    $sessionR = $sql->exec_statement($sessionQ,array($session_id,$name));

    /**
      Periodically purge expired records
        9May13 EL Not periodic.
    */
    $delP = $sql->prepare_statement('DELETE FROM userSessions
            WHERE expires < '.$sql->now());
    $delR = $sql->exec_statement($delP);

    /**
      New behavior - Store session id in dedicated table.
      This allows more than one session record per user
      record - i.e., someone can be logged in on multiple
      computers simultaneously.
    */
    $uid = getUID($name);
    $ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $expires = date('Y-m-d',strtotime('tomorrow'));
    $sessionP = $sql->prepare_statement('INSERT INTO userSessions 
                (uid,session_id,ip,expires)
                VALUES (?,?,?,?)');
    $sessionR = $sql->exec_statement($sessionP,array($uid,$session_id,$ip,$expires));

    $session_data = array("name"=>$name,"session_id"=>$session_id);
    $cookie_data = serialize($session_data);

    setcookie('session_data',base64_encode($cookie_data),time()+(60*600),'/');
}

function syncUserShadow($name){
    $localdata = posix_getpwnam($name);

    $currentUID = getUID($name);
    $posixUID = str_pad($localdata['uid'],4,"0",STR_PAD_LEFT);
    $realname = str_replace("'","''",$localdata['gecos']);
    $sql = dbconnect(); 

    if (!$currentUID){
        $addQ = $sql->prepare_statement("INSERT INTO Users 
            (name,password,salt,uid,session_id,real_name)
            VALUES (?,'','',?,'',?)");
        $sql->exec_statement($addQ,array($name,$posixUID,$realname));
    }
    else {
        $upQ1 = $sql->prepare_statement("UPDATE Users SET real_name=?
                WHERE name=?");
        $sql->exec_statement($upQ1,array($realname,$name));
    }
}

function syncUserLDAP($name,$uid,$fullname){
    $currentUID = getUID($name);
    $sql = dbconnect();

    if (!$currentUID){
        $addQ = $sql->prepare_statement("INSERT INTO Users 
            (name,password,salt,uid,session_id,real_name)
            VALUES (?,'','',?,'',?)");
        $sql->exec_statement($addQ,array($name,$uid,$fullname));
    }
    else {
        $upQ1 = $sql->prepare_statement("UPDATE Users SET real_name=?
                WHERE name=?");
        $sql->exec_statement($upQ1,array($fullname,$name));
    }
}

function auth_enabled(){
    global $FANNIE_AUTH_ENABLED;
    if (!isset($FANNIE_AUTH_ENABLED)){
        include(dirname(__FILE__)."/../config.php");
        return $FANNIE_AUTH_ENABLED;
    }
    else
        return $FANNIE_AUTH_ENABLED;
}

function table_check(){
    $sql = dbconnect();
    if (!$sql->table_exists('Users')){
        $p = $sql->prepare_statement("CREATE TABLE Users (
            name varchar(50),
            password varchar(50),
            salt varchar(10),
            uid varchar(4),
            session_id varchar(50),
            real_name varchar(75),
            PRIMARY KEY (name)
            )");
        $sql->exec_statement($p);
    }
    if (!$sql->table_exists('userPrivs')){
        $p = $sql->prepare_statement("CREATE TABLE userPrivs (
            uid varchar(4),
            auth_class varchar(50),
            sub_start varchar(50),
            sub_end varchar(50)
            )");
        $sql->exec_statement($p);
    }
    if (!$sql->table_exists('userKnownPrivs')){
        $p = $sql->prepare_statement("CREATE TABLE userKnownPrivs (
            auth_class varchar(50),
            notes text,
            PRIMARY KEY (auth_class)
            )");
        $sql->exec_statement($p);
    }
    if (!$sql->table_exists('userGroups')){
        $p = $sql->prepare_statement("CREATE TABLE userGroups (
            gid int,
            name varchar(50),
            username varchar(50)
            )");
        $sql->exec_statement($p);
    }
    if (!$sql->table_exists('userGroupPrivs')){
        $p = $sql->prepare_statement("CREATE TABLE userGroupPrivs (
            gid int,
            auth varchar(50),
            sub_start varchar(50),
            sub_end varchar(50)
            )");
        $sql->exec_statement($p);
    }
    if (!$sql->table_exists('userSessions')){
        $p = $sql->prepare_statement("CREATE TABLE userSessions (
            uid varchar(4),
            session_id varchar(50),
            ip varchar(45),
            expires datetime,
            PRIMARY KEY (uid,session_id)
            )");
        $sql->exec_statement($p);
    }
}

?>
