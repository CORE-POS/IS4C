<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op, Duluth, MN

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class AuthPagePermissions extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $title = 'Fannie : Auth : Change Password';
    protected $header = 'Fannie : Auth : Change Password';
    protected $auth_classes = array('admin');
    public $description = "[Customer Page Permissions] adjusts the permissions required to access a given page.";

    protected function delete_id_handler()
    {
        $custom = new PagePermissionsModel($this->connection);
        $custom->pageClass($this->id);
        $custom->delete();

        return filter_input(INPUT_SERVER, 'PHP_SELF');
    }

    protected function post_handler()
    {
        try {
            $custom = new PagePermissionsModel($this->connection);
            $custom->pageClass($this->form->page);
            $custom->authClass($this->form->auth);
            $custom->save();
        } catch (Exception $ex) {
        }

        return filter_input(INPUT_SERVER, 'PHP_SELF');
    }

    protected function get_view()
    {
        $privs = new UserKnownPrivsModel($this->connection);
        $pages = FannieAPI::listModules('FanniePage');
        sort($pages);
        $custom = new PagePermissionsModel($this->connection);
        $ret = '<form method="post">
            <div class="panel panel-default">
                <div class="panel-heading">Create Custom Permissions</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label>Page</label>
                        <select name="page" class="form-control">
                            <option value="">Choose page</option>
                            ' . array_reduce($pages, function($carry, $page){ return $carry . '<option>' . $page . '</option>'; }) . '
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Permission Class</label>
                        <select name="auth" class="form-control">
                        ' . $privs->toOptions(-1, true) . '
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-default btn-core">Create</button>
                    </div>
                </div>
            </div>
            </form>
            <div class="panel panel-default">
                <div class="panel-heading">Pages With Custom Permissions</div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped">
                        <tr><th>Name</th><th>Description</th><th>&nbsp;</th></tr>
                        ' . array_reduce($custom->find('pageClass'), function($carry, $obj) {
                            $page_class = $obj->pageClass();
                            $page = new $page_class();
                            return $carry . sprintf('<tr><td>%s</td><td>%s</td><td>
                                <a href="_method=delete&id=%s" class="btn btn-danger btn-xs">%s</a></td></tr>',
                                $page_class, $page->description,
                                $page_class,
                                \COREPOS\Fannie\API\lib\FannieUI::deleteIcon()
                            );
                        }) . '
                    </table>
                </div>
            </div>
            ';

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $start = $this->get_view();
        $phpunit->assertNotEquals(0, strlen($start));

        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->page = 'FanniePage';
        $form->auth = 'admin';
        $this->setForm($form);
        $this->post_handler();
        $added = $this->get_view();
        $phpunit->assertNotEquals($start, $added);

        $this->id = 'admin';
        $this->delete_id_handler();
        $deleted = $this->get_view();
    }
}

FannieDispatch::conditionalExec();

