<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}
if (!function_exists('checkLogin')) {
    require(dirname(__FILE__) . '/../login.php');
}

class AuthIndexPage extends FanniePage {

    protected $must_authenticate = True;
    //No, the auth requirement has a fallback, see body_content().
    //protected $auth_classes = array('admin');
    protected $title = 'Fannie : Auth : Menu';
    protected $header = 'Fannie : Auth : Menu';

    public $description = "
    Class for the Authorization User Interface index page.
    ";
    public $themed = true;
    
    function body_content()
    {
        $priv = validateUserQuiet('admin');
        $options = 'all';
        if (!$priv){
            $options = 'limited';
        }

        ob_start();

        /* password change or reset only allowed if not using
             UNIX or LDAP passwords */
        echo "Welcome $this->current_user";
        echo "<ul>";
        if ($options == 'all'){
            echo '<li><a href="AuthClassesPage.php">View authorization classes</a></li>';
            echo '<li><a href="AuthClassesPage.php?new=1">Create authorization classes</a></li>';
            echo '<li><a href="AuthClassesPage.php?edit=1">Edit authorization classes</a></li>';
            echo '<li><a href="AuthClassesPage.php?remove=1">Delete authorization classes</a></li>';
            echo "<br />";
            echo '<li><a href="AuthUsersPage.php">View Users</a></li>';
            echo '<li><a href="AuthUsersPage.php?detail=1">View User\'s Authoriztions</a></li>';
            echo '<li><a href="AuthUsersPage.php?new=1">Create User</a></li>';
            echo '<li><a href="AuthUsersPage.php?newAuth=1">Add Authorization to User</a></li>';
            echo '<li><a href="AuthUsersPage.php?remove=1">Delete User</a></li>';
            echo '<li><a href="AuthUsersPage.php?removeAuth=1">Delete Authorization from User</a></li>';
            if (!$this->config->get('AUTH_SHADOW', false) && !$this->config->get('AUTH_LDAP', false)) {
                echo '<li><a href="AuthUsersPage.php?reset=1">Reset a User\'s password</a></li>';
            }
            echo "<br />";
            echo '<li><a href="AuthGroupsPage.php">View Groups</a></li>';
            echo '<li><a href="AuthGroupsPage.php?detail=1">View Details of a Group</a></li>';
            echo '<li><a href="AuthGroupsPage.php?new=1">Create a Group</a></li>';
            echo '<li><a href="AuthGroupsPage.php?newUser=1">Add User to a Group</a></li>';
            echo '<li><a href="AuthGroupsPage.php?newAuth=1">Add Authorization to a Group</a></li>';
            echo '<li><a href="AuthGroupsPage.php?remove=1">Delete a Group</a></li>';
            echo '<li><a href="AuthGroupsPage.php?removeUser=1">Delete User from Group</a></li>';
            echo '<li><a href="AuthGroupsPage.php?removeAuth=1">Delete Authorization from Group</a></li>';
            echo "<br />";
            echo "<li><a href=AuthReport.php>Report of All Authorizations</a></li>";
            echo "<br />";
            echo "<li><a href=AuthPosePage.php>Switch User</a></li>";
        }
        // The 'limited' options
        echo '<li><a href="AuthEmailAddress.php">Change email address</a></li>';
        if (!$this->config->get('AUTH_SHADOW', false) && !$this->config->get('AUTH_LDAP', false)) { 
            echo "<li><a href=AuthChangePassword.php>Change password</a></li>";
        }
        echo "</ul>";

        return ob_get_clean();
    }

    public function helpContent()
    {
        if (validateUserQuiet('admin')) {
            return '<p>Access control revolves around <em>authorization classes</em>. An authorization
                class is permission to access a particular tool or suite of tools. Authorizations are
                not hierarchical. One user may have permission to access member management but not
                item management, where as another user may have access to edit items but not members.
                </p>    
                <p>
                Authorizations may be assigned to either users or groups. A group is simply a collection
                of users with the same authorizations. This can be quicker if several people have the
                same or similar jobs and need identical access.
                </p>';
        }
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $phpunit->assertNotEquals(0, strlen($this->helpContent()));
    }

// class AuthIndexPage
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
    $obj = new AuthIndexPage();
    $obj->draw_page();
}

