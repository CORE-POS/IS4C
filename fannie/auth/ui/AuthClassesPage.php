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

class AuthClassesPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('admin');
    protected $title = 'Fannie : Auth : Classes';
    protected $header = 'Fannie : Auth : Classes';

    public $description = "
    Manage authentication classes.
    ";
    public $themed = true;

    public function preprocess()
    {
        $this->__routes[] = 'get<new>';
        $this->__routes[] = 'post<id><description>';
        $this->__routes[] = 'get<edit>';
        $this->__routes[] = 'get<remove>';

        return parent::preprocess();
    }

    protected function post_id_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $notes = FormLib::get('notes');
        $checkP = $dbc->prepare('
            SELECT auth_class
            FROM userKnownPrivs
            WHERE auth_class=?');
        $checkR = $dbc->execute($checkP, array($this->id));

        if ($checkR && $dbc->num_rows($checkR) == 0) {
            $insP = $dbc->prepare('
                INSERT INTO userKnownPrivs
                    (auth_class, notes)
                VALUES
                    (?, ?)');
            $dbc->execute($insP, array($this->id, $notes));
        } else {
            updateAuthNotes($this->id, $notes);
        }

        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));

        return false;
    }

    protected function delete_id_handler()
    {
        deleteClass($this->id);
        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF'));

        return false;
    }

    protected function get_id_handler()
    {
        $this->notes = getAuthNotes($this->id); 

        return true;
    }

    protected function get_new_handler()
    {
        $this->id = '';
        $this->notes = '';

        return true;
    }

    protected function get_new_view()
    {
        return $this->get_id_view();
    }

    protected function get_id_view()
    {
        $this->add_onload_command("\$('input.form-control').focus();\n");
        $ret = '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post">
            <div class="form-group">
                <label>Authorization class</label>
                <input name="id" class="form-control" type="text"
                    value="' . $this->id . '" required />
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" required class="form-control">' . $this->notes . '</textarea>
            </div>
            <p><button type="submit" class="btn btn-default">Submit</button></p>
            </form>';

        return $ret;
    }

    protected function get_edit_view()
    {
        return $this->listButtonForm('get', 'Edit Class');
    }

    private function listButtonForm($method, $button)
    {
        $this->add_onload_command("\$('select.form-control').focus();\n");
        $ret = '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="get">
            <input type="hidden" name="_method" value="' . $method . '" />
            <label>Authorization class</label>
            <select name="id" class="form-control">';
        foreach (getAuthList() as $name) {
            $ret .= '<option>' . $name . '</option>';
        }
        $ret .= '</select>';
        $ret .= '<p><button type="submit" class="btn btn-default">' . $button . '</button></p>';
        $ret .= '</form>';

        return $ret;
    }

    protected function get_remove_view()
    {
        return $this->listButtonForm('delete', 'Delete Class');
    }
    
    protected function get_view()
    {
        ob_start();
        echo '<div class="row container">';
        echo '<a class="btn btn-default" href="AuthIndexPage.php">Menu</a> ';
        echo '<a class="btn btn-default" href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?new=1">Add Class</a> ';
        echo '<a class="btn btn-default" href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?edit=1">Edit Class</a> ';
        echo '<a class="btn btn-default" href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?remove=1">Delete Class</a>';
        echo '</div>';
        showClasses();
        return ob_get_clean();
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_remove_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_edit_view()));
        $this->id = 1;
        $this->notes = '';
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $phpunit->assertEquals(true, $this->get_new_handler());
        $phpunit->assertEquals(true, $this->get_id_handler());
    }
}

FannieDispatch::conditionalExec();

