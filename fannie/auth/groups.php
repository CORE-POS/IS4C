<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
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

  $gidQ = $sql->prepare("select max(gid) from userGroups");
  $gidR = $sql->execute($gidQ);
  $row = $sql->fetchRow($gidR);
  $gid = $row[0] + 1;

  $addQ = $sql->prepare("insert into userGroups values (?,?,?)");
  $addR = $sql->execute($addQ,array($gid,$group,$user));
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

  $delQ = $sql->prepare("delete from userGroupPrivs where gid=?");
  $delR = $sql->execute($delQ,array($gid));

  $delQ = $sql->prepare("delete from userGroups where gid=?");
  $delR = $sql->execute($delQ,array($gid));
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

  $checkQ = $sql->prepare("select gid from userGroups where gid=? and username=?");
  $checkR = $sql->execute($checkQ,array($gid,$user));
  if ($sql->num_rows($checkR) > 0){
    echo "User $user is already a member of group $group<p />";
    return false;
  }

  $addQ = $sql->prepare("insert into userGroups values (?,?,?)");
  $addR = $sql->execute($addQ,array($gid,$group,$user));
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

  $delQ = $sql->prepare("delete from userGroups where gid = ? and username=?");
  $delR = $sql->execute($delQ,array($gid,$user));
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
  
  $addQ = $sql->prepare("insert into userGroupPrivs values (?,?,?,?)");
  $addR = $sql->execute($addQ,array($gid,$auth,$start,$end));
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

  $checkQ = $sql->prepare("select g.gid  from userGroups as g, userGroupPrivs as p where
             g.gid = p.gid and g.username=?
             and p.auth=? and
             ((? between p.sub_start and p.sub_end) or
             (p.sub_start='all' and p.sub_end='all'))");
  $checkR = $sql->execute($checkQ,array($user,$auth,$sub));
   
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

  $delQ = $sql->prepare("delete from userGroupPrivs where gid=? and auth=?");
  $delR = $sql->execute($delQ,array($gid,$auth));
  return true;
}

function getGroupList(){
    $sql = dbconnect();
    $ret = array();
    $prep = $sql->prepare("SELECT name,gid FROM userGroups 
            GROUP BY name,gid ORDER BY name");
    $result = $sql->execute($prep);
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
  $ret = array(
    'gid' => 0,
    'name' => $group,
    'users' => array(),
    'auths' => array(),
  );
  
  $usersQ = $sql->prepare("select gid,username from userGroups where name=? order by username");
  $usersR = $sql->execute($usersQ,array($group));
  
  $gid = 0;
  while ($row = $sql->fetchRow($usersR)){
    $ret['gid'] = $row[0];
    $ret['users'][] = $row[1];
  }

  $authsQ = $sql->prepare("select auth,sub_start,sub_end from userGroupPrivs where gid=? order by auth");
  $authsR = $sql->execute($authsQ,array($ret['gid']));
  while ($row = $sql->fetchRow($authsR)){
    $ret['auths'][] = array($row[0], $row[1], $row[2]);
  }

  return $ret;
}

