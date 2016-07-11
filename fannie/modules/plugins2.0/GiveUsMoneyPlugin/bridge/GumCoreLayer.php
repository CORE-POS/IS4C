<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class GumCoreLayer extends GumPosLayer
{
    /**
      Create a POS transaction
      @param $emp_no [int] employee ID
      @param $register_no [int] lane ID
      @param $lines [array] of records

      Each record is a set of key/value pairs 
      with the following keys:
      amount        => purchase amount
      department    => department ID#
      description   => text description
      card_no       => member ID#
    */
    public static function writeTransaction($emp_no, $register_no, $lines)
    {
        global $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_TRANS_DB); 
        $prep = $dbc->prepare('SELECT MAX(trans_no) FROM dtransactions
                            WHERE emp_no=? AND register_no=?');
        $result = $dbc->execute($prep, array($emp_no, $register_no));
        $trans_no = 1;
        if ($dbc->num_rows($result) > 0) {
            $row = $dbc->fetch_row($result);
            if ($row[0] != '') {
                $trans_no = $row[0] + 1;
            }
        }

        $record = DTrans::defaults();
        $record['register_no'] = $register_no;
        $record['emp_no'] = $emp_no;
        $record['trans_no'] = $trans_no;
        $record['trans_id'] = 1;
        $record['trans_type'] = 'D';
        $record['quantity'] = 1.0;
        $record['ItemQtty'] = 1.0;
        $record['memType'] = 1;

        foreach($lines as $line) {
            $record['total'] = sprintf('%.2f', $line['amount']); 
            $record['unitPrice'] = sprintf('%.2f', $line['amount']); 
            $record['regPrice'] = sprintf('%.2f', $line['amount']); 
            $record['department'] = $line['department'];
            $record['description'] = substr($line['description'], 0, 30);
            $record['card_no'] = $line['card_no'];
            $record['upc'] = sprintf('%.2fDP%d', $line['amount'], $line['department']);

            $p = DTrans::parameterize($record, 'datetime', $dbc->now());
            $query = "INSERT INTO dtransactions ({$p['columnString']}) VALUES ({$p['valueString']})";
            $prep = $dbc->prepare($query);
            $write = $dbc->execute($prep, $p['arguments']);

            $record['trans_id']++;
        }

        return $emp_no . '-' . $register_no . '-' . $trans_no;
    }

    public static function getCustdata($card_no)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new CustdataModel($dbc);
        $model->CardNo($card_no);
        $model->personNum(1);

        if ($model->load()) {
            return $model;
        } else {
            return false;
        }
    }

    public static function getMeminfo($card_no)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new MeminfoModel($dbc);
        $model->card_no($card_no);
        $model->load();

        if ($model->load()) {
            return $model;
        } else {
            return false;
        }
    }
}

