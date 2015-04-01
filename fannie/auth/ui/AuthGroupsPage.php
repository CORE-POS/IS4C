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
    require('../login.php');
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
        $this->__routes[] = 'get<detail>';
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

    public function post_id_authClass_start_end_handler()
    {
        addAuthToGroup($this->id, $this->authClass, $this->start, $this->end);
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function delete_id_authClass_handler()
    {
        deleteAuthFromGroup($this->id, $this->authClass);
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function post_id_name_handler()
    {
        addGroup($this->id, $this->name);
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function post_id_name_newUser_handler()
    {
        addUserToGroup($this->id, $this->name);
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function delete_id_name_handler()
    {
        deleteUserFromGroup($this->id, $this->name);
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    
    public function delete_id_handler()
    {
        deleteGroup($this->id);
        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function get_detail_view()
    {
        return $this->group_form(array(), 'View Details');
    }

    public function get_remove_view()
    {
        return $this->group_form(array('_method'=>'delete'), 'Delete Group');
    }

    public function get_id_view()
    {
        ob_start();
        detailGroup($this->id);
        return ob_get_clean();
    }

    private function group_form($hidden, $verb)
    {
        $ret = '<form method="get" action="' . $_SERVER['PHP_SELF'] . '">
            <label>Group</label>
            <select name="id" class="form-control">';
        foreach (getGroupList() as $uid => $name) {
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

    public function get_newUser_view()
    {
        return $this->user_group_form(array('newUser'=>'1'), 'Add User to Group');
    }

    public function get_removeUser_view()
    {
        return $this->user_group_form(array('_method'=>'delete'), 'Delete User from Group');
    }

    private function user_group_form($hidden, $verb)
    {
        $ret = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
            <div class="form-group">
            <label>Group</label>
            <select name="id" class="form-control">';
        foreach (getGroupList() as $uid => $name) {
            $ret .=  "<option>".$name."</option>";
        }
        $ret .= '</select></div>
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
        $this->add_onload_command("\$('select.form-control:first').focus();\n");

        return $ret;
    }

    public function get_new_view()
    {
        $ret = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
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

    public function get_newAuth_view()
    {
        $ret = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
            <label>Group</label>
            <select name="id" class="form-control">';
        foreach (getGroupList() as $uid => $name) {
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
            <label>Group</label>
            <select name="id" class="form-control">';
        foreach (getGroupList() as $uid => $name) {
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
            <button class="btn btn-default" type="submit">Remove Authorization from Group</button>
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
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?detail=1">View Group</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?new=1">Add Group</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?remove=1">Delete Group</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?newUser=1">Add User</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?removeUser=1">Delete User</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?newAuth=1">Add Auth</a> ';
        echo '<a class="btn btn-default" href="' . $_SERVER['PHP_SELF'] . '?removeAuth=1">Delete Auth</a> ';
        echo '</div>';
        showGroups();

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

