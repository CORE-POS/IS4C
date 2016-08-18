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

namespace COREPOS\Fannie\API;

/**
  @class FannieCRUDPage
  Base class to generate an editor page for a given
  table supporting Create/Read/Update/Delete.

  Supports tables with a single primary key column
  with an integer type. Primary key may or may not
  be identity/increment.
*/
class FannieCRUDPage extends \FannieRESTfulPage
{
    /**
      @property $model_name
      The model class for the table this page will
      be managing
    */
    protected $model_name = 'BasicModel';

    /**
      @property $column_name_map
      By default, the display will show actual 
      column names from the underlying table as
      headers. To put alternate names in the user
      facing interface (e.g., with spaces, more descriptive,
      etc) use an associative array with:
        [database column name] => [displayed name]

      It is not necessary to specify aliases for all
      (or any) columns.
    */
    protected $column_name_map = array();

    /**
      By default, the user facing data is sorted on
      the primary key column. To use an alternative sorting,
      specify one or more database column names here.
    */
    protected $display_sorting = array();

    protected $model = null;
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
                $db_name = $settings[$pluginDB]; 
                $this->connection->setDefaultDB($db_name);
                $this->model->whichDB($db_name);
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
            array_map(function ($col_name) use ($id_col, $obj, $i) {
                if ($col_name == $id_col) {
                    return false;
                }
                $vals = \FormLib::get($col_name);
                if (!is_array($vals) || !isset($vals[$i])) {
                    return false;
                }
                $obj->$col_name($vals[$i]);
            }, array_keys($columns));
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
        list($col_name, $col_val) = $this->findPlaceholder($columns, $id_col);
        if ($col_name !== false) {
            $obj->$col_name($col_val);
        }

        $saved = $obj->save();
        if ($saved) {
            echo json_encode(array('error'=>0, 'added'=>1, 'id'=>$saved));
        } else {
            echo json_encode(array('error'=>1, 'added'=>0));
        }

        return false;
    }

    protected function findPlaceholder($columns, $id_col)
    {
        foreach ($columns as $col_name => $c) {
            if ($col_name != $id_col && strstr(strtoupper($c['type']), 'CHAR')) {
                return array($col_name, 'NEW');
            }
        }
        foreach ($columns as $col_name => $c) {
            if ($col_name != $id_col && strstr(strtoupper($c['type']), 'DATE')) {
                return array($col_name, date('Y-m-d'));
            }
        }

        return array(false, false);
    }

    public function delete_id_handler()
    {
        $obj = $this->getCRUDModel();
        $id_col = $this->getIdCol();
        $obj->$id_col($this->id);
        if ($obj->delete()) {
            return filter_input(INPUT_SERVER, 'PHP_SELF') . '?flash[]=sDeleted+Entry';
        } else {
            return filter_input(INPUT_SERVER, 'PHP_SELF') . '?flash[]=dError+Deleting+Entry';
        }
    }

    public function get_view()
    {
        $obj = $this->getCRUDModel();
        $id_col = $this->getIdCol();
        $columns = $obj->getColumns();
        $ret = '<form class="crud-form" method="post">';
        $ret .= '<div class="flash-div">';
        $ret .= array_reduce(\FormLib::get('flash', array()),
            function ($carry, $item) {
                $css = '';
                switch (substr($item, 0, 1)) {
                    case 's':
                        $css = 'alert-success';
                        break;
                    case 'd':
                        $css = 'alert-danger';
                        break;
                }
                $carry .= '<div class="alert ' . $css . '" role="alert">' 
                    . substr($item, 1) 
                    . '<button type="button" class="close" data-dismiss="alert">'
                    . '<span>&times;</span></button>'
                    . '</div>';
                return $carry;
            }, '');
        $ret .= '</div>';
        $ret .= '<table class="table table-bordered">';
        $ret .= '<tr>';
        foreach ($columns as $col_name => $c) {
            if ($col_name != $id_col) {
                if (isset($this->column_name_map[$col_name])) {
                    $col_name = $this->column_name_map[$col_name];
                }
                $ret .= '<th>' . ucwords($col_name) . '</th>';
            }
        }
        $ret .= '</tr>';
        $sort = !empty($this->display_sorting) ? $this->display_sorting : $id_col;
        foreach ($obj->find($sort) as $o) {
            $ret .= '<tr>';
            foreach ($columns as $col_name => $c) {
                if ($col_name == $id_col) {
                    $ret .= '<input type="hidden" class="crudID" name="id[]" value="' . $o->$id_col() . '" />';    
                } else {
                    $css = 'form-control';
                    if (strtoupper($c['type'] == 'DATETIME')) {
                        $css .= ' date-field';
                    }
                    $ret .= sprintf('<td><input type="text" class="%s" 
                                            name="%s[]" value="%s" /></td>',
                                $css, $col_name, $o->$col_name());
                }
            }
            $ret .= sprintf('<td>
                        <a href="?_method=delete&id=%s" class="btn btn-xs btn-default btn-danger"
                            onclick="return confirm(\'Delete entry?\');">
                        ' . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . '</a>
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
                    dataType: "json"
                }).done(function(resp) {
                    if (resp.added && $(\'input.crudID\').length > 0) {
                        $("form.crud-form").submit();
                    } else if (resp.added) {
                        window.location.reload();
                    } else {
                        showBootstrapAlert(".flash-div", "danger", "Error adding entry");
                    }
                });
            }
            </script>';

        return $ret;
    }

    public function baseUnitTest($phpunit)
    {
        $this->model_name = 'FloorSectionsModel';
        $phpunit->assertEquals('floorSectionID', $this->getIdCol());
        $phpunit->assertEquals('FloorSectionsModel', get_class($this->getCRUDModel()));
        $phpunit->assertEquals(array('name', 'NEW'), $this->findPlaceholder($this->model->getColumns(), $this->getIdCol()));
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->connection->throwOnFailure(true);
        ob_start();
        $phpunit->assertEquals(false, $this->put_handler());
        $json = ob_get_clean();
        $json = json_decode($json, true);
        $model = new \FloorSectionsModel($this->connection);
        $model->floorSectionID($json['id']);
        $phpunit->assertEquals(true, $model->load());
        $this->id = $json['id'];
        $this->delete_id_handler();
        $model->reset();
        $model->floorSectionID($json['id']);
        $phpunit->assertEquals(false, $model->load());
    }
}

