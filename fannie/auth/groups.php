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
   These functions manage user groups and groups authorizations.
   Groups are stored in two tables: userGroups and userGroupPrivs

   userGroups contains a list of the members of a group.  Each record
   is (group id, group name, user name).  Hence, there is one record
   in userGroups for each user.  Yes, the id/name data gets duplicated.
   It's not a big deal.

   userGroupPrivs contains a list of authorizations for groups.  Each
   record is (group id, authorization class, subclass start, subclass end).
   Authorizations function identically to the per-user ones in 
   privileges.php, just for all members of a group.

   Functions return true on success, false on failure.
*/

require_once('privileges.php');

/* addGroup(groupname, username)
   creates a new group and adds the user to it
   a user is required because of db structuring and
   because an empty group makes little sense
*/
function addGroup($group,$user){
  $sql = dbconnect();  
  
  if (!isAlphaNumeric($group) || !isAlphaNumeric($user)){
    return false;
  }  
 
  $gid = getGID($group);
  if ($gid > 0){
    echo "Group $group already exists<p />";
    return false;
  }

  $gidQ = $sql->prepare_statement("select max(gid) from userGroups");
  $gidR = $sql->exec_statement($gidQ);
  $row = $sql->fetch_array($gidR);
  $gid = $row[0] + 1;

  $addQ = $sql->prepare_statement("insert into userGroups values (?,?,?)");
  $addR = $sql->exec_statement($addQ,array($gid,$group,$user));
  return true;
}

/* delteGroup(groupname)
   deletes the given group, removing all
   users and all authorizations
*/
function deleteGroup($group){
  $sql = dbconnect();  
  if (!isAlphaNumeric($group)){
    return false;
  }

  $gid = getGID($group);
  if (!$gid){
    echo "Group $group doesn't exist<p />";
    return false;
  } 

  $delQ = $sql->prepare_statement("delete from userGroupPrivs where gid=?");
  $delR = $sql->exec_statement($delQ,array($gid));

  $delQ = $sql->prepare_statement("delete from userGroups where gid=?");
  $delR = $sql->exec_statement($delQ,array($gid));
  return true;  
}

/* addUSerToGroup(groupname, username)
   adds the given user to the given group
*/
function addUserToGroup($group,$user){
  $sql = dbconnect();  
  
  if (!isAlphaNumeric($group) || !isAlphaNumeric($user)){
    return false;
  }

  $gid = getGID($group);
  if (!$gid){
    echo "Group $group doesn't exist<p />";
    return false;
  }

  $checkQ = $sql->prepare_statement("select gid from userGroups where gid=? and username=?");
  $checkR = $sql->exec_statement($checkQ,array($gid,$user));
  if ($sql->num_rows($checkR) > 0){
    echo "User $user is already a member of group $group<p />";
    return false;
  }

  $addQ = $sql->prepare_statement("insert into userGroups values (?,?,?)");
  $addR = $sql->exec_statement($addQ,array($gid,$group,$user));
  return true;
}

/* deleteUserFromGroup(groupname, username)
   removes the given user from the given group
*/
function deleteUserFromGroup($group,$user){
  $sql = dbconnect();  
  if (!isAlphaNumeric($group) || !isAlphaNumeric($user)){
    return false;
  }

  $gid = getGID($group);
  if (!$gid){
    echo "Group $group doesn't exist<p />";
    return false;
  }

  $delQ = $sql->prepare_statement("delete from userGroups where gid = ? and username=?");
  $delR = $sql->exec_statement($delQ,array($gid,$user));
  return true;

}

/* addAuthToGroup(groupname, authname, subclass boundaries)
   adds the authorization to the given group
*/
function addAuthToGroup($group,$auth,$start='admin',$end='admin'){
  $sql = dbconnect();  
  
  if (!isAlphaNumeric($group) || !isAlphaNumeric($auth) ||
      !isAlphaNumeric($start) || !isAlphaNumeric($end)){
    return false;
  }

  $gid = getGID($group);
  if (!$gid){
    echo "Group $group doesn't exist<p />";
    return false;
  }
  
  $addQ = $sql->prepare_statement("insert into userGroupPrivs values (?,?,?,?)");
  $addR = $sql->exec_statement($addQ,array($gid,$auth,$start,$end));
  return true;
}

/* checkGroupAuth(username, authorization class, subclass)
   checks to see if the user is in a group that has 
   authorization in the given class
*/
function checkGroupAuth($user,$auth,$sub='all'){
  $sql = dbconnect();
  if (!isAlphaNumeric($user) || !isAlphaNumeric($auth) ||
      !isAlphaNumeric($sub)){
    return false;
  }

  $checkQ = $sql->prepare_statement("select g.gid  from userGroups as g, userGroupPrivs as p where
             g.gid = p.gid and g.username=?
             and p.auth=? and
             ((? between p.sub_start and p.sub_end) or
             (p.sub_start='all' and p.sub_end='all'))");
  $checkR = $sql->exec_statement($checkQ,array($user,$auth,$sub));
   
  if ($sql->num_rows($checkR) == 0){
    return false;
  }
  return true;
}

/* deleteAuthFromGroup(groupname, authname)
   deletes the given authorization class from the given
   group.  Note that it doesn't take into account
   subclasses, so ALL authorizations in the base
   class will be deleted.
*/
function deleteAuthFromGroup($group,$auth){
  $sql = dbconnect();  
  if (!isAlphaNumeric($group,$auth)){
    return false;
  }

  $gid = getGID($group);
  if (!$gid){
    echo "Group $group doesn't exist<p />";
    return false;  
  }

  $delQ = $sql->prepare_statement("delete from userGroupPrivs where gid=? and auth=?");
  $delR = $sql->exec_statement($delQ,array($gid,$auth));
  return true;
}

/* showGroups()
   prints a table of all the groups
*/
function showGroups(){
  $sql = dbconnect();
  
  $fetchQ = $sql->prepare_statement("select distinct gid, name from userGroups order by name");
  $fetchR = $sql->exec_statement($fetchQ);

  echo "<table cellspacing=2 cellpadding=2 border=1>";
  echo "<tr><th>Group ID</th><th>Group Name</th></tr>";
  while ($row=$sql->fetch_array($fetchR)){
    echo "<tr><td>$row[0]</td><td>$row[1]</td></tr>";
  }
  echo "</table>";
  
  return true;
}

function getGroupList(){
    $sql = dbconnect();
    $ret = array();
    $prep = $sql->prepare_statement("SELECT name,gid FROM userGroups 
            GROUP BY name,gid ORDER BY name");
    $result = $sql->exec_statement($prep);
    while($row = $sql->fetch_row($result))
        $ret[$row['gid']] = $row['name'];
    return $ret;
}

/* detailGroup(groupname)
   prints out all the users and authorizations in
   the given group
*/
function detailGroup($group){
  if (!isAlphaNumeric($group)){
    return false;
  }

  $sql = dbconnect();
  
  $usersQ = $sql->prepare_statement("select gid,username from userGroups where name=? order by username");
  $usersR = $sql->exec_statement($usersQ,array($group));
  
  $gid = 0;
  echo "<table cellspacing=2 cellpadding=2 border=1>";
  echo "<tr><th>Users</th></tr>";
  while ($row = $sql->fetch_array($usersR)){
    $gid = $row[0];
    echo "<tr><td>$row[1]</td></tr>";
  }
  echo "</table>";

  $authsQ = $sql->prepare_statement("select auth,sub_start,sub_end from userGroupPrivs where gid=? order by auth");
  $authsR = $sql->exec_statement($authsQ,array($gid));
  echo "<table cellspacing=2 cellpadding=2 border=1>";
  echo "<tr><th>Authorization Class</th><th>Subclass start</th><th>Subclass End</th></tr>";
  while ($row = $sql->fetch_array($authsR)){
    echo "<tr><td>$row[0]</td><td>$row[1]</td><td>$row[2]</td></tr>";
  }
  echo "</table>";
}


?>
