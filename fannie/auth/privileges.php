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
these functions manage user priviledges which are stored in
a table called userPrivs.  Records in the table specify a user id
number (uid), authorization class, and a sub-class start and end
authorization class will probably be like admin, addproducts,
updateproducts,editmembers, etc.  sub-class start and end are
in place to potentially add finer-grained control (subset of member
numbers, range of departments).  The standard (for now) will be
to set both start and end to 'all' if full access is desired.

Unless otherwise noted, functions return true on success
and false on failure
*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 10Nov12 Eric Lee Add FANNIE_AUTH_ENABLED test in addAuth per intent(?)
    *                   of create-first-user call from install/auth.php.

*/

require_once('utilities.php');

function addAuth($name,$auth_class,$sub_start='all',$sub_end='all'){
    // 10Nov12 EL Add FANNIE_AUTH_ENABLED
    global $FANNIE_AUTH_ENABLED;
  $sql = dbconnect();
  if (!isAlphanumeric($name) or !isAlphanumeric($auth_class) or
      !isAlphanumeric($sub_start) or !isAlphanumeric($sub_end)){
    return false;
  }
  $uid = getUID($name);
  if (!$uid){
    return $uid;
  }

  /* 10Nov12 EL Add FANNIE_AUTH_ENABLED test per intent of create-first-user
   *             call from auth.php to skip validation check.
   *             auth_enabled() does not return the correct value.
  */
  if ( $FANNIE_AUTH_ENABLED ) {
    if (($auth_class == 'admin' || $auth_class=='sysadmin') && getNumUsers() == 1){
        // skip validation check in
        // this instance
    }
    elseif (!validateUser('admin')){
        return false;
    }
  }

  $addQ = $sql->prepare_statement("insert into userPrivs values (?,?,?,?)");
  $addR = $sql->exec_statement($addQ,array($uid,$auth_class,$sub_start,$sub_end));
  return true;
}

function createClass($name, $notes){
    if (!isAlphanumeric($name) ){
        return false;
    }

    $sql = dbconnect();
    $checkQ = $sql->prepare_statement("select * from userKnownPrivs where auth_class='$name'");
    $checkR = $sql->exec_statement($checkQ);
    if ($sql->num_rows($checkR) != 0){
        return true;
    }

    if (!validateUser('admin')){
        return false;
    }

    $notes = str_replace("\n","<br />",$notes);
    $insQ = $sql->prepare_statement("INSERT INTO userKnownPrivs (auth_class, notes)
            VALUES (?, ?)");
    $insR = $sql->exec_statement($insQ,array($name,$notes));
    return ($insR) ? true : false;
}

function deleteClass($name){
    if (!isAlphanumeric($name) ){
        return false;
    }

    if (!validateUser('admin')){
        return false;
    }

    $sql = dbconnect();

    $q1 = $sql->prepare_statement("DELETE FROM userKnownPrivs WHERE auth_class=?");
    $r1 = $sql->exec_statement($q1,array($name));

    $q2 = $sql->prepare_statement("DELETE FROM userPrivs WHERE auth_class=?");
    $r2 = $sql->exec_statement($q2,array($name));

    $q3 = $sql->prepare_statement("DELETE FROM userGroupPrivs WHERE auth=?");
    $r3 = $sql->exec_statement($q3,array($name));
    return true;
}

function deleteAuth($name,$auth_class){
  if (!isAlphanumeric($name) or !isAlphanumeric($auth_class)){
    return false;
  }
  
  if (!validateUser('admin')){
    return false;
  }

  $uid = getUID($name);
  if (!$uid){
    return false;
  }
  $sql = dbconnect();
  $delQ = $sql->prepare_statement("delete from userPrivs where uid=? and auth_class=?");
  $delR = $sql->exec_statement($delQ,array($uid,$auth_class));
  return true;
}

function showAuths($name){
  if (!isAlphanumeric($name)){
    echo "Invalid name<p />";
    return false;
  }
  
  if (!validateUser('admin')){
    return false;
  }

  $uid = getUID($name);
  if (!$uid){
    echo "No such user '$name'<p />";
    return false;
  }
  echo "Showing authorizations for $name";
  echo "<table cellspacing=2 cellpadding=2 border=1><tr>";
  echo "<th>Authorization class</th><th>Subclass start</th><th>Subclass end</th>";
  echo "</tr>";
  $sql = dbconnect();
  $fetchQ = $sql->prepare_statement("select auth_class,sub_start,sub_end from userPrivs where uid=?");
  $fetchR = $sql->exec_statement($fetchQ,array($uid));
  while ($row = $sql->fetch_array($fetchR)){
    echo "<tr>";
    echo "<td>$row[0]</td><td>$row[1]</td><td>$row[2]</td>";
    echo "</tr>";
  }
  echo "</table>";
  return true;
}

function showClasses(){
  if (!validateUser('admin')){
    return false;
  }

  echo "Showing authorization classes";
  echo "<table cellspacing=0 cellpadding=4 border=1><tr>";
  echo "<th>Authorization class</th><th>Notes</th>";
  echo "</tr>";
  $sql = dbconnect();
  $fetchQ = $sql->prepare_statement("select auth_class,notes from userKnownPrivs order by auth_class");
  $fetchR = $sql->exec_statement($fetchQ);
  while ($row = $sql->fetch_array($fetchR)){
    echo "<tr>";
    echo "<td>$row[0]</td><td>".(empty($row[1])?'&nbsp;':$row[1])."</td>";
    echo "</tr>";
  }
  echo "</table>";
  return true;
}

function getAuthNotes($name){
    $sql = dbconnect();
    $q = $sql->prepare_statement("SELECT notes FROM userKnownPrivs WHERE auth_class=?");
    $r = $sql->exec_statement($q,array($name));
    if ($sql->num_rows($r) == 0) return "";
    $w = $sql->fetch_row($r);
    return str_replace("<br />","\n",$w['notes']);
}

function updateAuthNotes($name,$notes){
    if (!validateUser('admin')){
        return false;
    }
    $sql = dbconnect();
    $notes = str_replace("\n","<br />",$notes);
    $q = $sql->prepare_statement("UPDATE userKnownPrivs SET notes=? WHERE auth_class=?");
    $r = $sql->exec_statement($q,array($notes,$name));
    return true;
}

function getAuthList(){
    $sql = dbconnect();
    $ret = array();
    $prep = $sql->prepare_statement("SELECT auth_class FROM userKnownPrivs ORDER BY auth_class");
    $result = $sql->exec_statement($prep);
    while($row = $sql->fetch_Row($result))
        $ret[] = $row['auth_class'];

    if (!in_array('admin',$ret)){
        $ret[] = 'admin';
        sort($ret);
    }
    return $ret;
}

/*
with how authorization checking currently works, sub classes
must be countable (i.e., a sub class must be able to be 
tested as to whether or not it's 'between' start and end
*/
function checkAuth($name,$auth_class,$sub='all'){
  if (init_check())
    return 'init';
  if (!isAlphanumeric($name) or !isAlphanumeric($auth_class) or !isAlphanumeric($sub)){
    return false;
  }
  $uid = getUID($name);
  if (!$uid){
    return false;
  }
  $sql = dbconnect();
  $checkQ = $sql->prepare_statement("select * from userPrivs where uid=? and auth_class=? and
             ((? between sub_start and sub_end) or (sub_start='all' and sub_end='all'))");
  $checkR = $sql->exec_statement($checkQ,array($uid,$auth_class,$sub));
  if ($sql->num_rows($checkR) == 0){
    return false;
  }
  return true;
}

?>
