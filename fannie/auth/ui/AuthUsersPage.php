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
if (!function_exists('checkLogin')) {
    require('../login.php');
}

class AuthUsersPage extends FannieRESTfulPage 
{

    protected $must_authenticate = true;
    protected $auth_classes = array('admin');
    protected $title = 'Fannie : Auth : Users';
    protected $header = 'Fannie : Auth : Users';
    public $themed = true;

    public function preprocess()
    {
        $this->__routes[] = 'get<detail>';
        $this->__routes[] = 'get<new>';
        $this->__routes[] = 'get<remove>';
        $this->__routes[] = 'get<reset>';
        $this->__routes[] = 'get<newAuth>';
        $this->__routes[] = 'get<removeAuth>';
        $this->__routes[] = 'post<name><pass1><pass2>';
        $this->__routes[] = 'post<id><authClass><start><end>';
        $this->__routes[] = 'delete<id><authClass>';
        $this->__routes[] = 'post<id><reset>';

        return parent::preprocess();
    }

    public function post_id_reset_handler()
    {
        $newpass = '';
        srand();
        for ($i=0;$i<8;$i++) {
            switch (rand(1,3)) {
                case 1: // digit
                    $newpass .= chr(48+rand(0,9));
                    break;
                case 2: // uppercase
                    $newpass .= chr(65+rand(0,25));
                    break;
                case 3:
                    $newpass .= chr(97+rand(0,25));
                    break;
            }
        }

        $changed = changeAnyPassword($this->id, $newpass);
        if ($changed) {
            $this->add_onload_command("showBootstrapAlert('#btn-bar', 'success', 'New password for {$this->id} is {$newpass}');\n");
        } else {
            $this->add_onload_command("showBootstrapAlert('#btn-bar', 'danger', 'Error changing password for {$this->id}');\n");
        }

        return true;
    }

    public function delete_id_handler()
    {
        deleteLogin($this->id);
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function delete_id_authClass_handler()
    {
        deleteAuth($this->id, $this->authClass);
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function post_name_pass1_pass2_handler()
    {
        if ($this->pass1 != $this->pass2) {
            $this->add_onload_command("showBootstrapAlert('form', 'danger', 'Passwords do not match');\n");
            return true;
        }

        $created = createLogin($this->name, $this->pass1);
        if ($created) {
            header('Location: ' . $_SERVER['PHP_SELF']);
            return false;
        } else {
            $this->add_onload_command("showBootstrapAlert('form', 'danger', 'Error creating users');\n");
            return true;
        }
    }

    public function post_id_authClass_start_end_handler()
    {
        addAuth($this->id, $this->authClass, $this->start, $this->end);
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function post_id_reset_view()
    {
        // handler adds javascript messaging
        return $this->get_view();
    }

    public function get_id_view()
    {
        ob_start();
        showAuths($this->id);

        return ob_get_clean();
    }

    public function get_detail_view()
    {
        return $this->user_form(array(), 'View Authorizations');
    }

    public function get_remove_view()
    {
        return $this->user_form(array('_method'=>'delete'), 'Delete User');
    }

    public function post_name_pass1_pass2_view()
    {
        // handler scripted error messages to run on load
        return $this->get_new_view();
    }

    public function get_new_view()
    {
        $ret = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="name" required class="form-control" />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="pass1" required class="form-control" />
            </div>
            <div class="form-group">
                <label>Re-type Password</label>
                <input type="password" name="pass2" required class="form-control" />
            </div>
            <p>
                <button type="submit" class="btn btn-default">Create User</button>
            </p>
            </form>';
        $this->add_onload_command("\$('input.form-control:first').focus();\n");

        return $ret;
    }

    public function get_reset_view()
    {
        return $this->user_form(array('_method'=>'post','reset'=>'1'), 'Reset Password');
    }

    private function user_form($hidden, $verb)
    {
        $ret = '<form method="get" action="' . $_SERVER['PHP_SELF'] . '">
            <label>Username</label>
            <select name="id" class="form-control">';
        foreach (getUserList() as $uid => $name) {
            $ret .=  "<option>".$name."</option>";
        }
        $ret .= '</select>
            <p>
            <button class="btn btn-default" type="submit">' . $verb . '</button>
            </p>';
        if (!is_array($hidden)) {
            $hidden = array($hidden => $hidden);
        }
        foreach ($hidden as $name => $value) {
            $ret .= sprintf('<input type="hidden" name="%s" value="%s" />', $name, $value);
        }
        $ret .= '</form>';
        $this->add_onload_command("\$('select.form-control').focus();\n");

        return $ret;
    }

    public function get_newAuth_view()
    {
        $ret = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
            <label>Username</label>
            <select name="id" class="form-control">';
        foreach (getUserList() as $uid => $name) {
            $ret .=  "<option>".$name."</option>";
        }
        $ret .= '</select>
            <label>Authorization Class</label>
            <select name="authClass" class="form-control">';
        foreach (getAuthList() as $name) {
            $ret .= '<option>' . $name . '</option>';
        }
        $ret .= '</select>
            <label>Subclass Start</label>
            <input type="text" name="start" value="all" class="form-control" />
            <label>Subclass End</label>
            <input type="text" name="end" value="all" class="form-control" />
            <p>
            <button class="btn btn-default" type="submit">Add Authorization</button>
            </p>';
        $ret .= '</form>';
        $this->add_onload_command("\$('select.form-control:first').focus();\n");

        return $ret;
    }

    public function get_removeAuth_view()
    {
        $ret = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
            <input type="hidden" name="_method" value="delete" />
            <label>Username</label>
            <select name="id" class="form-control">';
        foreach (getUserList() as $uid => $name) {
            $ret .=  "<option>".$name."</option>";
        }
        $ret .= '</select>
            <label>Authorization Class</label>
            <select name="authClass" class="form-control">';
        foreach (getAuthList() as $name) {
            $ret .= '<option>' . $name . '</option>';
        }
        $ret .= '</select>
            <p>
            <button class="btn btn-default" type="submit">Remove Authorization</button>
            </p>';
        $ret .= '</form>';
        $this->add_onload_command("\$('select.form-control:first').focus();\n");

        return $ret;
    }

    public function get_view()
    {
        ob_start();
        echo '<div class="row container" id="btn-bar">';
        echo '<a class="btn btn-default" href="AuthIndexPage.php">Menu</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?new=1">Add User</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?detail=1">View User</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?remove=1">Delete User</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?newAuth=1">Add Auth</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?removeAuth=1">Delete Auth</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?reset=1">Reset Password</a> ';
        echo '</div>';
        showUsers();

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

