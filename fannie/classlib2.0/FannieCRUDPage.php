<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

/**
  @class FannieRESTfulPage
*/
class FannieCRUDPage extends FannieRESTfulPage
{
    protected $model_name = 'BasicModel';
    protected $model = null;
    protected $column_name_map = array();
    public $themed = true;
    protected $flashes = array();

    protected function getIdCol()
    {
        $pks = array();
        $obj = $this->getCRUDModel();
        foreach ($obj->getColumns() as $name => $c) {
            if (isset($c['primary_key']) && $c['primary_key']) {
                $pks[] = $name;
            }
        }
        if (count($pks) == 0) {
            throw new Exception('Missing primary key!');
        } elseif (count($pks) > 1) {
            throw new Exception('Cannot handle composite primary key!');
        } else {
            return $pks[0];
        }
    }

    protected function getCRUDModel()
    {
        if ($this->model === null) {
            $class_name = $this->model_name;
            $this->model = new $class_name($this->connection);
            switch ($this->model->preferredDB()) {
                case 'op':
                    $this->connection->setDefaultDB($this->config->get('OP_DB')); 
                    $this->model->whichDB($this->config->get('OP_DB'));
                    break;
                case 'trans':
                    $this->connection->setDefaultDB($this->config->get('TRANS_DB')); 
                    $this->model->whichDB($this->config->get('TRANS_DB'));
                    break;
                case 'archive':
                    $this->connection->setDefaultDB($this->config->get('ARCHIVE_DB')); 
                    $this->model->whichDB($this->config->get('ARCHIVE_DB'));
                    break;
                default:
                    break;
            }
            if (substr($this->model->preferredDB(), 0, 7) == 'plugin:') {
                $settings = $this->config->get('PLUGIN_SETTINGS');
                $pluginDB = substr($this->model->preferredDB(), 7);
                $this->connection->setDefaultDB($pluginDB);
                $this->model->whichDB($pluginDB);
            }
        }

        return $this->model;
    }

    public function post_id_handler()
    {
        if (!is_array($this->id)) {
            return $this->unknownRequestHandler();
        }

        $obj = $this->getCRUDModel();
        $id_col = $this->getIdCol();
        $columns = $obj->getColumns();
        $errors = 0;
        for ($i=0; $i<count($this->id); $i++) {
            $obj->reset();
            $obj->$id_col($this->id[$i]); 
            foreach ($columns as $col_name => $c) {
                if ($col_name == $id_col) {
                    continue;
                }
                $vals = FormLib::get($col_name);
                if (!is_array($vals) || !isset($vals[$i])) {
                    continue;
                }
                $obj->$col_name($vals[$i]);
            }
            if (!$obj->save()) {
                $errors++;
            }
        }

        if ($errors == 0) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?flash[]=sSaved+Data');
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?flash[]=dError+Saving+Data');
        }

        return false;
    }

    public function put_handler()
    {
        $obj = $this->getCRUDModel();
        $id_col = $this->getIdCol();
        $columns = $obj->getColumns();
        $id_info = $columns[$id_col];
        // assign next ID if not using increment
        if (!isset($id_info['increment'])) {
            $current = $obj->find($id_col, true);
            if (count($current) == 0) {
                $obj->$id_col(1);
            } else {
                $latest = $current[0];
                if (is_numeric($latest->$id_col())) {
                    $obj->$id_col($latest->$id_col()+1);
                }
            }
        }
        // find a character column to plug in
        // a placeholder value
        foreach ($columns as $col_name => $c) {
            if ($col_name != $id_col && strstr(strtoupper($c['type']), 'CHAR')) {
                $obj->$col_name('NEW');
                break;
            }
        }

        $saved = $obj->save();
        if ($saved) {
            echo json_encode(array('error'=>0, 'added'=>1));
        } else {
            echo json_encode(array('error'=>1, 'added'=>0));
        }

        return false;
    }

    public function delete_id_handler()
    {
        $obj = $this->getCRUDModel();
        $id_col = $this->getIdCol();
        $obj->$id_col($this->id);
        if ($obj->delete()) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?flash[]=sDeleted+Entry');
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?flash[]=dError+Deleting+Entry');
        }

        return false;
    }

    public function get_view()
    {
        $obj = $this->getCRUDModel();
        $id_col = $this->getIdCol();
        $columns = $obj->getColumns();
        $ret = '<form class="crud-form" method="post">';
        $ret .= '<div class="flash-div">';
        foreach (FormLib::get('flash', array()) as $f) {
            $css = '';
            switch (substr($f, 0, 1)) {
                case 's':
                    $css = 'alert-success';
                    break;
                case 'd':
                    $css = 'alert-danger';
                    break;
            }
            $ret .= '<div class="alert ' . $css . '" role="alert">' 
                . substr($f, 1) 
                . '<button type="button" class="close" data-dismiss="alert">'
                . '<span>&times;</span></button>'
                . '</div>';
        }
        $ret .= '</div>';
        $ret .= '<table class="table table-bordered">';
        $ret .= '<tr>';
        foreach ($columns as $col_name => $c) {
            if ($col_name != $id_col) {
                $ret .= '<th>' . ucwords($col_name) . '</th>';
            }
        }
        $ret .= '</tr>';
        foreach ($obj->find($id_col) as $o) {
            $ret .= '<tr>';
            foreach ($columns as $col_name => $c) {
                if ($col_name == $id_col) {
                    $ret .= '<input type="hidden" name="id[]" value="' . $o->$id_col() . '" />';    
                } else {
                    $ret .= sprintf('<td><input type="text" class="form-control" 
                                            name="%s[]" value="%s" /></td>',
                                $col_name, $o->$col_name());
                }
            }
            $ret .= sprintf('<td>
                        <a href="?_method=delete&id=%s" class="btn btn-xs btn-default btn-danger"
                            onclick="return confirm(\'Delete entry?\');">
                        ' . FannieUI::deleteIcon() . '</a>
                        </td>',
                        $o->$id_col());
            $ret .= '</tr>';
        }
        $ret .= '</table>';
        $ret .= '<p>
            <button type="submit" class="btn btn-default">Save Changes</button>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="" onclick="addEntry(); return false;" class="btn btn-default">Add Entry</a>
            </p>';
        $ret .= '</form>';
        $ret .= '<script type="text/javascript">
            function addEntry()
            {
                $.ajax({
                    method: "PUT",
                    dataType: "json",
                    success: function(resp) {
                        if (resp.added) {
                            $("form.crud-form").submit();
                        } else {
                            showBootstrapAlert(".flash-div", "danger", "Error adding entry");
                        }
                    }
                });
            }
            </script>';

        return $ret;
    }
}

