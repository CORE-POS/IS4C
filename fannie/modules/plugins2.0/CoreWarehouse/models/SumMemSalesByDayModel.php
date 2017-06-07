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

class SumMemSalesByDayModel extends CoreWarehouseModel {

    protected $name = 'sumMemSalesByDay';
    protected $preferred_db = 'plugin:WarehouseDatabase';
    
    protected $columns = array(
    'date_id' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'card_no' => array('type'=>'INT','primary_key'=>True),
    'total' => array('type'=>'MONEY','default'=>0.00),
    'quantity' => array('type'=>'DOUBLE','default'=>0.00),
    'retailTotal' => array('type'=>'MONEY','default'=>0.00),
    'retailQuantity' => array('type'=>'DOUBLE','default'=>0.00),
    'transCount' => array('type'=>'SMALLINT','default'=>0)
    );

    public function refresh_data($trans_db, $month, $year, $day=False){
        list($start_id, $start_date, $end_id, $end_date) = $this->dates($month, $year, $day);

        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');
        $sql = FannieDB::get($settings['WarehouseDatabase']);
        $supers = $config->get('OP_DB') . $sql->sep() . 'MasterSuperDepts';

        $target_table = DTransactionsModel::selectDlog($start_date, $end_date);

        $this->clearDates($sql, $start_id, $end_id);

        /* reload table from transarction archives */
        $sql = "INSERT INTO ".$this->name."
            SELECT DATE_FORMAT(tdate, '%Y%m%d') as date_id,
            card_no,
            CONVERT(SUM(total),DECIMAL(10,2)) as total,
            CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as quantity,
            CONVERT(SUM(CASE WHEN m.superID <> 0 THEN total ELSE 0 END),DECIMAL(10,2)) as retailTotal,
            CONVERT(SUM(CASE WHEN m.superID=0 THEN 0 WHEN trans_status='M' THEN itemQtty 
                WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as retailQuantity,
            COUNT(DISTINCT trans_num) AS transCount
            FROM $target_table AS t
                LEFT JOIN {$supers} AS m ON t.department=m.dept_ID
            WHERE tdate BETWEEN ? AND ? AND
            trans_type IN ('I','D') 
            AND card_no <> 0
            GROUP BY DATE_FORMAT(tdate,'%Y%m%d'), card_no";
        $prep = $this->connection->prepare($sql);
        $result = $this->connection->execute($prep, array($start_date.' 00:00:00',$end_date.' 23:59:59'));
    }
}

