<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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

if (!class_exists('CoreWarehouseModel')) {
    include_once(dirname(__FILE__).'/CoreWarehouseModel.php');
}

class SumRingSalesByDayModel extends CoreWarehouseModel {

    protected $name = 'sumRingSalesByDay';
    protected $preferred_db = 'plugin:WarehouseDatabase';
    
    protected $columns = array(
    'date_id' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'upc' => array('type'=>'VARCHAR(13)','primary_key'=>True,'default'=>''),
    'department' => array('type'=>'INT','primary_key'=>True,'default'=>''),
    'total' => array('type'=>'MONEY','default'=>0.00),
    'quantity' => array('type'=>'DOUBLE','default'=>0.00)
    );

    public function refresh_data($trans_db, $month, $year, $day=False){
        list($start_id, $start_date, $end_id, $end_date) = $this->dates($month, $year, $day);

        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');
        $sql = FannieDB::get($settings['WarehouseDatabase']);

        $target_table = DTransactionsModel::selectDlog($start_date, $end_date);

        $this->clearDates($sql, $start_id, $end_id);

        /* reload table from transarction archives */
        $sql = "INSERT INTO ".$this->name."
            SELECT DATE_FORMAT(tdate, '%Y%m%d') as date_id,
            upc,department,
            CONVERT(SUM(total),DECIMAL(10,2)) as total,
            CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as quantity
            FROM $target_table WHERE
            tdate BETWEEN ? AND ? AND
            trans_type IN ('I','D') AND upc <> '0'
            GROUP BY DATE_FORMAT(tdate,'%Y%m%d'), upc, department";
        $prep = $this->connection->prepare($sql);
        $result = $this->connection->execute($prep, array($start_date.' 00:00:00',$end_date.' 23:59:59'));
    }
}

