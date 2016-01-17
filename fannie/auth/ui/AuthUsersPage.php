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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}
if (!function_exists('checkLogin')) {
    require(dirname(__FILE__) . '/../login.php');
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

    protected function post_id_reset_handler()
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

    protected function delete_id_handler()
    {
        foreach ($this->id as $id) {
            deleteLogin($id);
        }
        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));

        return false;
    }

    protected function delete_id_authClass_handler()
    {
        deleteAuth($this->id, $this->authClass);
        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));

        return false;
    }

    protected function post_name_pass1_pass2_handler()
    {
        if ($this->pass1 != $this->pass2) {
            $this->add_onload_command("showBootstrapAlert('form', 'danger', 'Passwords do not match');\n");
            return true;
        }

        $created = createLogin($this->name, $this->pass1);
        if ($created) {
            header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));
            return false;
        } else {
            $this->add_onload_command("showBootstrapAlert('form', 'danger', 'Error creating users');\n");
            return true;
        }
    }

    protected function post_id_authClass_start_end_handler()
    {
        addAuth($this->id, $this->authClass, $this->start, $this->end);
        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));

        return false;
    }

    protected function post_id_reset_view()
    {
        // handler adds javascript messaging
        return $this->get_view();
    }

    protected function get_id_view()
    {
        $ret = '<h3>User: ' . $this->id . '</h3>';
        $ret .= '<p><strong>User ID</strong>: ' . getUID($this->id) . '</p>';
        $auths = showAuths($this->id);
        $ret .= '<table class="table table-bordered">
            <tr>
                <th>Auth Class</th>
                <th>Subclass Start</th>
                <th>Subclass End</th>
                <th>Delete from User</th>
            </tr>';
        foreach ($auths as $info) {
            $link = sprintf('<a href="?_method=delete&id=%s&authClass=%s">%s</a>',
                $this->id, $info[0],
                COREPOS\Fannie\API\lib\FannieUI::deleteIcon());
            $ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $info[0], $info[1], $info[2], $link);
        }
        $ret .= '</table>
            <p>
                <a href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" class="btn btn-default">User Menu</a>
                <a href="?newAuth=1&init=' . $this->id . '" class="btn btn-default btn-reset">Add Auth</a>
                <a href="?reset=1&init=' . $this->id . '" class="btn btn-default btn-reset">Reset Password</a>
            </p>';

        return $ret;
    }

    protected function get_remove_view()
    {
        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">
            <input type="hidden" name="_method" value="delete" />
            <table class="table table-bordered table-striped">
            <tr>
                <th>Name</th>
                <th>' . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . '</th>
            </tr>';
        foreach (getUserList() as $name) {
            $ret .= sprintf('<tr>
                <td>%s</td>
                <td><input type="checkbox" name="id[]" value="%s" /></td>
                </tr>',
                $name, $name);
        }
        $ret .= '</table>
            <p>
                <button type="submit" class="btn btn-default btn-danger">
                Delete Selected Users
                </button>
            <p>
            </form>';

        return $ret;
    }

    protected function post_name_pass1_pass2_view()
    {
        // handler scripted error messages to run on load
        return $this->get_new_view();
    }

    protected function get_new_view()
    {
        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">
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

    protected function get_reset_view()
    {
        return $this->user_form(array('_method'=>'post','reset'=>'1'), 'Reset Password');
    }

    private function userSelect()
    {
        $init = FormLib::get('init', -999);
        $this->add_onload_command("\$('select.form-control').focus();\n");
        $ret = '<div class="form-group">
            <label>Username</label>
            <select name="id" class="form-control">';
        foreach (getUserList() as $uid => $name) {
            $ret .=  "<option " . ($init == $name ? 'selected' : '') . ">".$name."</option>";
        }
        $ret .= '</select></div>';

        return $ret;
    }

    private function user_form($hidden, $verb)
    {
        $ret = '<form method="get" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">';
        $ret .= $this->userSelect();
        $ret .= '
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

        return $ret;
    }

    protected function get_newAuth_view()
    {
        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">';
        $ret .= $this->userSelect();
        $ret .= '
            <table class="table table-bordered table-striped">
            <tr>
                <th>Authorization Class</th>
                <th class="col-sm-3">Notes</th>
                <th>Subclass Start</th>
                <th>Subclass End</th>
            </tr>';
            $ret .= '<tr>
                <td><select name="authClass" class="form-control"
                onchange="$(\'.auth-notes\').hide();$(\'#auth-notes-\'+this.value).show();">';
            foreach (getAuthList() as $name) {
                $ret .= sprintf('<option>' . $name . '</option>');
            }
            $ret .= '</select></td>
                <td>';
            $first = true;
            foreach (getAuthList() as $name) {
                $notes = getAuthNotes($name);
                $ret .= sprintf('<span class="auth-notes %s" id="auth-notes-%s">%s</span>',
                    ($first ? '' : 'collapse'), $name, $notes);
                $first = false;
            }
            $ret .= '</td>
                <td><input type="text" name="start" value="all" class="form-control" /></td>
                <td><input type="text" name="end" value="all" class="form-control" /></td>
                </tr>';
        $ret .= '</table>
            <p>
            <button class="btn btn-default" type="submit">Add Authorization</button>
            <a href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" class="btn btn-default btn-reset">Users Menu</a>
            </p>';
        $ret .= '</form>';

        return $ret;
    }

    protected function get_removeAuth_view()
    {
        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">
            <input type="hidden" name="_method" value="delete" />';
        $ret .= $this->userSelect();
        $ret .= '
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

        return $ret;
    }

    protected function get_view()
    {
        ob_start();
        echo '<div class="row container" id="btn-bar">';
        echo '<a class="btn btn-default" href="AuthIndexPage.php">Menu</a> ';
        echo '<a class="btn btn-default" href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?new=1">Add User</a> ';
        echo '<a class="btn btn-default" href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?remove=1">Delete User</a> ';
        echo '<a class="btn btn-default" href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?newAuth=1">Add Auth</a> ';
        echo '</div>';
        echo '<table class="table table-bordered">
            <tr><th>Name</th><th>User ID</th></tr>';
        foreach (getUserList() as $uid => $name) {
            printf('<tr><td><a href="?id=%s">%s</a></td><td>%s</td></tr>',
                $name, $name, $uid);
        }
        echo '</table>';

        return ob_get_clean();
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_removeAuth_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_newAuth_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_new_view()));
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_remove_view()));
    }
}

FannieDispatch::conditionalExec();

