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

class AuthGroupsPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('admin');
    protected $title = 'Fannie : Auth : Groups';
    protected $header = 'Fannie : Auth : Groups';

    public $description = "
    Manage authentication groups.
    ";
    public $themed = true;

    public function preprocess()
    {
        $this->__routes[] = 'get<new>';
        $this->__routes[] = 'get<remove>';
        $this->__routes[] = 'get<reset>';
        $this->__routes[] = 'get<newUser>';
        $this->__routes[] = 'get<removeUser>';
        $this->__routes[] = 'get<newAuth>';
        $this->__routes[] = 'get<removeAuth>';
        $this->__routes[] = 'post<id><name>';
        $this->__routes[] = 'post<id><name><newUser>';
        $this->__routes[] = 'delete<id><name>';
        $this->__routes[] = 'post<id><authClass><start><end>';
        $this->__routes[] = 'delete<id><authClass>';

        return parent::preprocess();
    }

    protected function post_id_authClass_start_end_handler()
    {
        addAuthToGroup($this->id, $this->authClass, $this->start, $this->end);
        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));

        return false;
    }

    protected function delete_id_authClass_handler()
    {
        deleteAuthFromGroup($this->id, $this->authClass);
        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));

        return false;
    }

    protected function post_id_name_handler()
    {
        addGroup($this->id, $this->name);
        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));

        return false;
    }

    protected function post_id_name_newUser_handler()
    {
        addUserToGroup($this->id, $this->name);
        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));

        return false;
    }

    protected function delete_id_name_handler()
    {
        deleteUserFromGroup($this->id, $this->name);
        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));

        return false;
    }

    
    protected function delete_id_handler()
    {
        deleteGroup($this->id);
        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));

        return false;
    }

    protected function get_remove_view()
    {
        return $this->group_form(array('_method'=>'delete'), 'Delete Group');
    }

    protected function get_id_view()
    {
        $group = detailGroup($this->id);
        $ret = '<h2>' . $group['name'] . '</h2>';

        $delUsers = count($group['users'] > 1) ? true : false;
        $ret .= '<strong>Users</strong>
            <table class="table table-bordered">
            <tr>
                <th>User</th>
                <th>Remove from Group</th>
            </tr>';
        foreach ($group['users'] as $user) {
            $link = sprintf('<a href="?_method=delete&id=%s&name=%s">%s</a>',
                $group['name'], $user,
                COREPOS\Fannie\API\lib\FannieUI::deleteIcon());
            $ret .= sprintf('<tr><td>%s</td><td>%s</td></tr>',
                $user,
                $delUsers ? $link : '&nbsp'
            );
        }
        $ret .= '</table>';

        $ret .= '<hr />';

        $ret .= '<strong>Users</strong>
            <table class="table table-bordered">
            <tr>
                <th>Authorization</th>
                <th>Start</th>
                <th>End</th>
                <th>Remove from Group</th>
            </tr>';
        foreach ($group['auths'] as $info) {
            $link = sprintf('<a href="?_method=delete&id=%s&authClass=%s">%s</a>',
                $group['name'], $info[0],
                COREPOS\Fannie\API\lib\FannieUI::deleteIcon());
            $ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $info[0], $info[1], $info[2], $link);
        }
        $ret .= '</table>
            <p>
                <a href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" class="btn btn-default">Group Menu</a>
                <a href="?newUser=1&init=' . $this->id . '" class="btn btn-default btn-reset">Add User</a>
                <a href="?newAuth=1&init=' . $this->id . '" class="btn btn-default btn-reset">Add Auth</a>
            </p>';

        return $ret;
    }

    protected function groupSelect()
    {
        $selected = FormLib::get('init', -999);
        $this->add_onload_command("\$('select.form-control').focus();\n");
        $ret = '<div class="form-group">
            <label>Group</label>
            <select name="id" class="form-control">';
        foreach (getGroupList() as $uid => $name) {
            $ret .=  "<option " . ($name == $selected ? 'selected' : '') . ">".$name."</option>";
        }
        $ret .= '</select></div>';

        return $ret;
    }

    protected function group_form($hidden, $verb)
    {
        $ret = '<form method="get" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">';
        $ret .= $this->groupSelect();
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

    protected function get_newUser_view()
    {
        return $this->user_group_form(array('newUser'=>'1'), 'Add User to Group');
    }

    protected function get_removeUser_view()
    {
        return $this->user_group_form(array('_method'=>'delete'), 'Delete User from Group');
    }

    protected function user_group_form($hidden, $verb)
    {
        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">';
        $ret .= $this->groupSelect();
        $ret .= '
            <div class="form-group">
            <label>User</label>
            <select name="name" class="form-control">';
        foreach (getUserList() as $uid => $name) {
            $ret .=  "<option>".$name."</option>";
        }
        $ret .= '</select></div>
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

    protected function get_new_view()
    {
        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">
            <div class="form-group">
                <label>Group Name</label>
                <input type="text" name="id" class="form-control" required />
            </div>
            <div class="form-group">
                <label>First User</label>
                <select name="name" class="form-control">';
        foreach (getUserList() as $uid => $name) {
            $ret .= '<option>' . $name . '</option>';
        }
        $ret .= '</select></div>
            <p><button type="submit" class="btn btn-default">Create Group</button></p>
            </form>';
        $this->add_onload_command("\$('input.form-control').focus();\n");

        return $ret;
    }

    protected function get_newAuth_view()
    {
        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">';
        $ret .= $this->groupSelect();
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
            <a href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" class="btn btn-default btn-reset">Groups Menu</a>
            </p>';
        $ret .= '</form>';

        return $ret;
    }

    protected function get_removeAuth_view()
    {
        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">
            <input type="hidden" name="_method" value="delete" />';
        $ret .= $this->groupSelect();
        $ret .= '
            <label>Authorization Class</label>';
        $ret .= getAuthSelect();
        $ret .= '
            <p>
            <button class="btn btn-default" type="submit">Remove Authorization from Group</button>
            </p>';
        $ret .= '</form>';

        return $ret;
    }

    protected function get_view()
    {
        ob_start();
        echo '<div class="row container" id="btn-bar">';
        echo '<a class="btn btn-default" href="AuthIndexPage.php">Menu</a> ';
        echo '<a class="btn btn-default" href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?new=1">Add Group</a> ';
        echo '<a class="btn btn-default" href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?remove=1">Delete Group</a> ';
        echo '<a class="btn btn-default" href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?newUser=1">Add User</a> ';
        echo '<a class="btn btn-default" href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?newAuth=1">Add Auth</a> ';
        echo '</div>';
        echo '<table class="table table-bordered">
            <tr><th>Group Name</th><th>Group ID</th></tr>';
        foreach (getGroupList() as $gid => $name) {
            printf('<tr><td><a href="?id=%s">%s</a></td><td>%s</td></tr>',
                $name, $name, $gid);
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
        $phpunit->assertNotEquals(0, strlen($this->get_removeUser_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_newUser_view()));
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_remove_view()));
    }
}

FannieDispatch::conditionalExec();

