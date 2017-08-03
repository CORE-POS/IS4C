<?php
/*******************************************************************************

    Copyright 2009,2010 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!function_exists('checkLogin')) {
    include_once($FANNIE_ROOT . 'auth/login.php');
}

class BatchHistoryPage extends FannieRESTfulPage
{
    protected $title = 'Batch History';
    protected $header = '';

    public $description = '[Batch History Page] is the primary tool for viewing 
        historical activity of batches.';

    function preprocess()
    {
        global $FANNIE_OP_DB;
        $this->con = FannieDB::get($FANNIE_OP_DB);
        $this->__routes[] = 'post<delete><id>';

        return parent::preprocess();
    }

    function get_view()
    {
        
    }
    
    /** 
        @getBatchHistory
        Return batch history info from another page.
    */
    public function getBatchHistory($bid)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ret = '';
        $ret .= '<div class="" align="center"><h4 style="color: grey">Batch Info</h4></div>';
        $bu = new BatchUpdateModel($dbc);
        $bu->batchID($bid);
        $bt = new BatchTypeModel($dbc);
        $users = new UsersModel($dbc);
        $columns = array('updateType','batchName','batchType','owner','startDate',
            'endDate','user','modified');
        $ret .= '<table class="table table-bordered table-condensed small" id="bTable"><thead>';
        foreach ($columns as $column) {
            $ret .= '<th>' . ucwords($column) . '</th>';
        }
        $ret .= '</thead><tbody>';
        $s = 1;
        foreach ($bu->find() as $obj) {
            if ($obj->upc() == NULL) {
                $ret .= '<tr class="warning">';
                foreach ($columns as $column) {
                    if ($s === 0 && $column != 'modified' && $column != 'updateType') {
                        if ($obj->$column() != ${'last_'.$column}) {
                            $fweight = 'font-weight: bold; color: #6b0000;';
                        } else {
                            $fweight = '';
                        }
                    }
                    ${'last_'.$column} = $obj->$column();
                    if ($column == 'startDate' || $column == 'endDate'){
                        $ret .= '<td style="'.$fweight.'">' . $obj->$column() . '</td>';
                    } else if ($column == 'modified') {
                        $ret .= '<td style="'.$fweight.'">' . $obj->$column() . '</td>';
                    } else if ($column == 'batchType') {
                        $bt->reset();
                        $bt->batchTypeID($obj->$column());
                        $bt->load();
                        $ret .= '<td style="'.$fweight.'">' . $bt->typeDesc() . '</td>';
                    } else if ($column == 'user') {
                        $users->reset();
                        $users->uid($obj->$column());
                        $users->load();
                    } else {
                        $ret .= '<td style="'.$fweight.'">' . $users->real_name() . '</td>';
                    }
                }
                $s = 0;
            } 
            $ret .= '</tr>';
        }
        $ret .= '</tbody></table>';

        $ret .= '<div class="" align="center"><h4 style="color: grey">Product Info</h4></div>';
        $upcCols = array('updateType','upc','modified','user','specialPrice');
        $ret .= '<table class="table table-bordered table-condensed small" id="iTable"><thead>';
        foreach ($upcCols as $column) {
            $ret .= '<th>' . ucwords($column) . '</th>';
        }
        $ret .= '</thead><tbody>';
        foreach ($bu->find() as $obj) {
            $ret .= '<tr class="info">';
            if (!$obj->upc() == NULL) {
                foreach ($upcCols as $upcCol) {
                    $ret .= '<td>' . $obj->$upcCol() . '</td>';
                }
            } 
            $ret .= '</tr>';
        }
  
        $ret .= '</tbody></table>';
        
        return $ret;
    }

    public function helpContent()
    {
        return '';
    }

    /**
      Create, update, and delete a batch
      Try each mode with and without an owner filter
    */
    public function unitTest($phpunit)
    {
        $get = $this->get_view();
        $phpunit->assertNotEquals(0, strlen($get));

        $this->connection->selectDB($this->config->get('OP_DB'));
        $model = new BatchesModel($this->connection);

        $this->newType = 1;
        $this->newName = 'Test BatchListPage';
        $this->newStart = date('Y-m-d 00:00:00');
        $this->newEnd = date('Y-m-d 00:00:00');
        $this->newOwner = 'MULTIPLE DEPTS.';
        ob_start();
        $this->post_newType_newName_newStart_newEnd_newOwner_handler();
        ob_end_clean();
        $model->batchName($this->newName);
        $matches = $model->find();
        $phpunit->assertEquals(1, count($matches));
        $model->reset();
        $model->batchID($matches[0]->batchID());
        $phpunit->assertEquals(true, $model->load());
        $phpunit->assertEquals($this->newType, $model->batchType());
        $phpunit->assertEquals($this->newName, $model->batchName());
        $phpunit->assertEquals($this->newStart, $model->startDate());
        $phpunit->assertEquals($this->newEnd, $model->endDate());
        $phpunit->assertEquals($this->newOwner, $model->owner());

        $this->id = $model->batchID();
        $this->batchName = 'Change BatchListPage';
        $this->batchType = 2;
        $this->startDate = date('Y-m-d 00:00:00', strtotime('yesterday'));
        $this->endDate = $this->startDate;
        $this->owner = 'Admin';
        ob_start();
        $this->post_id_batchName_batchType_startDate_endDate_owner_handler();
        ob_end_clean();
        $model->reset();
        $model->batchID($this->id);
        $phpunit->assertEquals(true, $model->load());
        $phpunit->assertEquals($this->batchType, $model->batchType());
        $phpunit->assertEquals($this->batchName, $model->batchName());
        $phpunit->assertEquals($this->startDate, $model->startDate());
        $phpunit->assertEquals($this->endDate, $model->endDate());
        $phpunit->assertEquals($this->owner, $model->owner());

        $this->delete = 1;
        ob_start();
        $this->post_delete_id_handler();
        ob_end_clean();
        $model->reset();
        $model->batchID($this->id); 
        $phpunit->assertEquals(false, $model->load());

        $modes = array('pending', 'current', 'historical', 'all');
        foreach ($modes as $m) {
            $get = $this->batchListDisplay('', $m, rand(0, 50));
            $phpunit->assertNotEquals(0, strlen($get));
            $get = $this->batchListDisplay('MULTIPLE DEPTS.', $m, rand(0, 50));
            $phpunit->assertNotEquals(0, strlen($get));
        }
    }
}

FannieDispatch::conditionalExec();

