<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class WaybackMachine extends FannieRESTfulPage 
{
    public $description = '[Wayback Machine] searches through the store\'s entire sales history';
    protected $header = 'Wayback Machine';
    protected $title = 'Wayback Machine';

    protected function get_id_handler()
    {
        $dbc = $this->connection;
        $ret = array();
        try {
            $stamp = strtotime($this->form->current);
            $args = array(
                '%' . $this->id . '%',
                date('Y-m-01 00:00:00', mktime(0,0,0,date('n',$stamp),date('j',$stamp),date('Y',$stamp))),
                date('Y-m-t 23:59:59', mktime(0,0,0,date('n',$stamp),date('j',$stamp),date('Y',$stamp))),
            );
            $dlog = DTransactionsModel::selectDlog($args[1], $args[2]);
            $prep = $dbc->prepare('
                SELECT upc, description, SUM(quantity), SUM(total)
                FROM ' . $dlog . '
                WHERE description LIKE ?
                    AND tdate BETWEEN ? AND ?
                GROUP BY upc, description');
            $res = $dbc->execute($prep, $args);
            $ret[] = array($args[1], $args[2], '', '', '');
            while ($row = $dbc->fetchRow($res)) {
                $ret[] = array(
                    '',
                    $row['upc'],
                    $row['description'],
                    sprintf('%.2f', $row[2]),
                    sprintF('%.2f', $row[3]),
                );
            }
        } catch (Exception $ex) {
        }

        echo json_encode($ret);
        return false;
    }

    protected function get_view()
    {
        $this->addScript('wayback.js');
        return '<form id="wayback-form">
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="id" class="form-control" />
            </div>
            <div class="form-group">
                <label>Stop At</label>
                <input type="text" name="date" class="form-control date-field" value="2004-09-01" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default btn-core">Search</button>
            </div>
            </form>
            <table id="wayback-table" class="table table-bordered small">
            </table>
            <div class="progress collapse" id="progress-bar">
                <div class="progress-bar progress-bar-striped active" 
                    role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" 
                    style="width: 100%" title="Working">
                    <span class="sr-only">Working</span>
                </div>
            </div>
            ';
    }

    public function helpContent()
    {
        return '<p>Search sales transaction for a given term going back to the requested <em>Stop At</em> date.
This will do a series of smaller searches and is intended for queries that need to cover years or decades of data</p>';
    }
}

FannieDispatch::conditionalExec();

