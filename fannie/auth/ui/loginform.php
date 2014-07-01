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

include('../../config.php');
require('../login.php');
if (isset($_GET["redirect"]) && init_check()){
    header("Location:".$_GET['redirect']);
    return;
}

$page_title = 'Fannie : Auth';
$header = 'Fannie : Auth';

$current_user = checkLogin();

if (isset($_GET['logout'])){
  logout();
  $current_user = false;
}


include($FANNIE_ROOT."config.php");

if ($current_user){
  include($FANNIE_ROOT."src/header.html");
  echo "You are logged in as $current_user<p />";
  if (isset($_GET['redirect'])){
    echo "<b style=\"font-size:1.5em;\">It looks like you don't have permission to access this page</b><p />";
  }
  echo "<a href=menu.php>Main menu</a>  |  <a href=loginform.php?logout=yes>Logout</a>?";
  include($FANNIE_ROOT."src/footer.html");
}
else {
  if (isset($_POST['name'])){
    $name = $_POST['name'];
    $password = $_POST['password'];
    $login = login($name,$password);
    $redirect = $_POST['redirect'];

    if (!$login && $FANNIE_AUTH_LDAP)
    $login = ldap_login($name,$password);

    if (!$login && $FANNIE_AUTH_SHADOW)
        $login = shadow_login($name,$password);

    if ($login){
      header("Location: $redirect");
    }
    else {
      include($FANNIE_ROOT."src/header.html");
      echo "<html><body bgcolor=cabb1e>";
      echo "Login failed. <a href=loginform.php?redirect=$redirect>Try again</a>?";
      include($FANNIE_ROOT."src/footer.html");
    }
  }
  else {
    $redirect = 'menu.php';
    if (isset($_GET['redirect'])){
       $redirect = $_GET['redirect'];
    }
    include($FANNIE_ROOT."src/header.html");
    if (isset($_GET['logout']))
    echo "<blockquote><i>You've logged out</i></blockquote>";
    echo "<form action=loginform.php method=post>";
    echo "<table cellspacing=2 cellpadding=4><tr>";
    echo "<td>Name:</td><td><input type=text name=name></td>";
    echo "</tr><tr>";
    echo "<td>Password:</td><td><input type=password name=password></td>";
    echo "</tr><tr>";
    echo "<td><input type=submit value=Login></td><td><input type=reset value=Clear></td>";
    echo "</tr></table>";
    echo "<input type=hidden value=$redirect name=redirect />";
    echo "</form>";
    echo "<script type=text/javascript>";
    echo "document.forms[0].name.focus();";
    echo "</script>";
    include($FANNIE_ROOT."src/footer.html");
  }
}


?>

