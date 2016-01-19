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

class AuthChangePassword extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $title = 'Fannie : Auth : Change Password';
    protected $header = 'Fannie : Auth : Change Password';

    public $description = "
    Change a user's password";
    public $themed = true;

    public function post_handler()
    {
        $this->changed = false;
        $old = FormLib::get('oldpass');
        $new1 = FormLib::get('newpass1');
        $new2 = FormLib::get('newpass2');

        if ($new1 != $new2) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'New passwords do not match');\n");
            return true;
        }

        $this->changed = changePassword($this->current_user, $old, $new1);
        if (!$this->changed) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'Password change failed. Ensure old password is correct');\n");
        }

        return true;
    }

    public function post_view()
    {
        if ($this->changed) {
            return '
                <div class="alert alert-success">Password changed</div>
                <p>
                    <a href="AuthIndexPage.php" class="btn btn-default">Return to Menu</a>
                </p>';
        } else {
            return $this->get_view();
        }
    }

    public function get_view()
    {
        $this->add_onload_command("\$('.form-control:first').focus();\n");
        ob_start();
        ?>
        <div id="alert-area"></div>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <div class="form-group">
            <label>Old Password</label>
            <input type="password" name="oldpass" class="form-control" required />
        </div> 
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="newpass1" class="form-control" required />
        </div> 
        <div class="form-group">
            <label>Re-Type New Password</label>
            <input type="password" name="newpass2" class="form-control" required />
        </div> 
        <p>
            <button class="btn btn-default" type="submit">Change Password</button>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }

    public function unitTest($phpunit)
    {
        $this->changed = false;
        $phpunit->assertNotEquals(0, strlen($this->post_view()));
        $this->changed = true;
        $phpunit->assertNotEquals(0, strlen($this->post_view()));
    }
}

FannieDispatch::conditionalExec();

