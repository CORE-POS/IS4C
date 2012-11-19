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

  $gidQ = "select max(gid) from userGroups";
  $gidR = $sql->query($gidQ);
  $row = $sql->fetch_array($gidR);
  $gid = $row[0] + 1;

  $addQ = "insert into userGroups values ($gid,'$group','$user')";
  $addR = $sql->query($addQ);
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

  $delQ = "delete from userGroupPrivs where gid=$gid";
  $delR = $sql->query($delQ);

  $delQ = "delete from userGroups where gid=$gid";
  $delR = $sql->query($delQ);
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

  $checkQ = "select gid from userGroups where gid=$gid and username='$user'";
  $checkR = $sql->query($checkQ);
  if ($sql->num_rows($checkR) > 0){
    echo "User $user is already a member of group $group<p />";
    return false;
  }

  $addQ = "insert into userGroups values ($gid,'$group','$user')";
  $addR = $sql->query($addQ);
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

  $delQ = "delete from userGroups where gid = $gid and username='$user'";
  $delR = $sql->query($delQ);
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
  
  $addQ = "insert into userGroupPrivs values ($gid,'$auth','$start','$end')";
  $addR = $sql->query($addQ);
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

  $checkQ = "select g.gid  from userGroups as g, userGroupPrivs as p where
             g.gid = p.gid and g.username='$user'
             and p.auth='$auth' and
             (('$sub' between p.sub_start and p.sub_end) or
             (p.sub_start='all' and p.sub_end='all'))";
  $checkR = $sql->query($checkQ);
   
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

  $delQ = "delete from userGroupPrivs where gid=$gid and auth='$auth'";
  $delR = $sql->query($delQ);
  return true;
}

/* showGroups()
   prints a table of all the groups
*/
function showGroups(){
  $sql = dbconnect();
  
  $fetchQ = "select distinct gid, name from userGroups order by name";
  $fetchR = $sql->query($fetchQ);

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
	$result = $sql->query("SELECT name,gid FROM userGroups 
			GROUP BY name,gid ORDER BY name");
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
  
  $usersQ = "select gid,username from userGroups where name='$group' order by username";
  $usersR = $sql->query($usersQ);
  
  $gid = 0;
  echo "<table cellspacing=2 cellpadding=2 border=1>";
  echo "<tr><th>Users</th></tr>";
  while ($row = $sql->fetch_array($usersR)){
    $gid = $row[0];
    echo "<tr><td>$row[1]</td></tr>";
  }
  echo "</table>";

  $authsQ = "select auth,sub_start,sub_end from userGroupPrivs where gid=$gid order by auth";
  $authsR = $sql->query($authsQ);
  echo "<table cellspacing=2 cellpadding=2 border=1>";
  echo "<tr><th>Authorization Class</th><th>Subclass start</th><th>Subclass End</th></tr>";
  while ($row = $sql->fetch_array($authsR)){
    echo "<tr><td>$row[0]</td><td>$row[1]</td><td>$row[2]</td></tr>";
  }
  echo "</table>";
}


?>
