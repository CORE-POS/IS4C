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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}
if (!function_exists('validateUserQuiet')) {
    require(dirname(__FILE__) . '/../login.php');
}

class FannieAuthLoginPage extends FannieRESTfulPage
{
    protected $title = 'Fannie : Auth';
    protected $header = 'Fannie : Auth';

    public $themed = true;

    /**
     * Force authenticate requirement off
     * to avoid a redirect loop
     */
    public function __construct()
    {
        parent::__construct();
        $this->must_authenticate = false;
    }

    public function preprocess()
    {
        if (isset($_GET["redirect"]) && init_check()){
            header("Location:".$_GET['redirect']);
            return false;
        }
        $this->__routes[] = 'get<logout>';
        $this->__routes[] = 'post<name><password>';

        return parent::preprocess();
    }

    /**
      Logout the current user as requested 
    */
    public function get_logout_handler()
    {
        logout();

        return true;
    }

    /**
      Check submitted credentials. Redirect to destination
      on success, proceed to error message on failure
    */
    public function post_name_password_handler()
    {
        $name = FormLib::get('name');
        $password = FormLib::get('password');
        $login = login($name,$password);
        $redirect = FormLib::get('redirect', 'menu.php');

        if (!$login && FannieConfig::config('AUTH_LDAP', false)) {
            $login = ldap_login($name,$password);
        }

        if (!$login && FannieConfig::config('AUTH_SHADOW', false)) {
            $login = shadow_login($name,$password);
        }

        if ($login) {
            header("Location: $redirect");
            return false;
        } else {
            return true;
        }
    }

    /**
      Error message for failed login
    */
    public function post_name_password_view()
    {
        $redirect = FormLib::get('redirect', 'menu.php');
        return "Login failed. <a href=loginform.php?redirect=$redirect>Try again</a>?";
    }

    /**
      After logout, just display the regular login form
      with a line noting logout was successful
    */
    public function get_logout_view()
    {
        return "<blockquote><i>You've logged out</i></blockquote>"
            . $this->get_view();
    }

    /**
      Show the login form unless the user is already logged in
      Logged in users get links to potentially intended destinations
    */
    public function get_view()
    {
        $current_user = checkLogin();

        ob_start();
        if ($current_user) {
            echo "You are logged in as $current_user<p />";
            if (isset($_GET['redirect'])){
                echo "<b style=\"font-size:1.5em;\">It looks like you don't have permission to access this page</b><p />";
            }
            echo "<a href=menu.php>Main menu</a>  |  <a href=loginform.php?logout=yes>Logout</a>?";
        } else {
            $redirect = FormLib::get('redirect', 'menu.php');
            echo "<form action=loginform.php method=post>";
            if ($this->themed) {
                echo '<p>';
                echo '<div class="form-group">';
                echo '<label for="authUserName">Name</label>';
                echo '<input class="form-control" id="authUserName" name="name" type="text" />';
                echo '</div>';
                echo '<div class="form-group">';
                echo '<label for="authPassword">Password</label>';
                echo '<input class="form-control" id="authPassword" name="password" type="password" />';
                echo '</div>';
                echo '</p>';
            } else {
                echo '<table>';
                echo '<tr><td>';
                echo '<label for="authUserName">Name</label>';
                echo '</td><td>';
                echo '<input class="form-control" id="authUserName" name="name" type="text" />';
                echo '</td></tr>';
                echo '<tr><td>';
                echo '<label for="authPassword">Password</label>';
                echo '</td><td>';
                echo '<input class="form-control" id="authPassword" name="password" type="password" />';
                echo '</td></tr>';
                echo '</table>';
            }
            echo '<button type="submit" class="btn btn-default">Login</button>';
            echo "<input type=hidden value=$redirect name=redirect />";
            echo "</form>";
            $this->add_onload_command('$(\'#authUserName\').focus();');
        }

        return ob_get_clean();
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new FannieAuthLoginPage();
    $obj->drawPage();
}

