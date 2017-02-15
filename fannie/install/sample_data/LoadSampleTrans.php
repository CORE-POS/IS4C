<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
    include(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class LoadSampleTrans
{
    /**
      Load sample transaction data
      @param $rowlimit [int, optional] only import this many records
      @return [boolean] success

      Requires php_zip extension to unpack the sample data.

      The sample data set includes 144,866 records with days
      numbered 1-31. Each record is loaded *twice*: once in
      the current month and once in the previous month. For
      instance, running this in June will result in fully
      populated (although duplicate) data for both May and
      June. This should be sufficient for most demo and testing
      purposes. Loading nearly 300k records does take a while
      though.

      This does not handle rotation/archiving yet.

      Data is loaded into unit_test_trans.dtransactions. This
      is not configurable and is intended to avoid catastrophic
      mistakes. This should never be pointed directly at a
      production database.
    */
    public function loadData($rowlimit=0)
    {
        if (!class_exists('ZipArchive')) {
            echo "No ZIP support\n";
            return false;
        }

        $za = new ZipArchive();
        $zipfile = dirname(__FILE__) . '/sampletrans.csv.zip';
        $za->open($zipfile);
        $entry = $za->getNameIndex(0);
        $tempfile = sys_get_temp_dir() . '/' . $entry;
        $za->extractTo(sys_get_temp_dir(), $entry);
        $za->close();

        $month = date('n');
        $year = date('Y');
        $lastmonth = date('n', strtotime('last month'));
        $lastyear = date('Y', strtotime('last month'));
        echo "Data will be imported as months {$month}/{$year} and {$lastmonth}/{$lastyear}\n";

        $dbc = FannieDB::get('unit_test_trans');
        $query = $dbc->prepare('
            INSERT INTO dtransactions (
                datetime,
                register_no,
                emp_no,
                trans_no,
                upc,
                description,
                trans_type,
                trans_subtype,
                trans_status,
                department,
                quantity,
                scale,
                cost,
                unitPrice,
                total,
                regPrice,
                tax,
                foodstamp,
                discount,
                memDiscount,
                discountable,
                discounttype,
                voided,
                percentDiscount,
                ItemQtty,
                volDiscType,
                volume,
                VolSpecial,
                mixMatch,
                matched,
                memType,
                staff,
                numflag,
                charflag,
                card_no,
                trans_id,
                pos_row_id
              ) VALUES (
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?, 
                ?
              )');
        $dbc->query('TRUNCATE TABLE unit_test_trans.dtransactions');

        /**
          Note: sample data is dated
          1900-07-01 through 1900-07-31
        */
        $fp = fopen($tempfile, 'r');
        $pos_row_id = 1;
        while (($data=fgetcsv($fp)) !== false) {
            if ($pos_row_id % 250 == 0) {
                echo "$pos_row_id/144866\r";
            }
            $ts = strtotime($data[0]);
            $date1 = mktime(date('H',$ts), date('i',$ts), date('s',$ts), $month, date('j',$ts), $year);
            $date2 = mktime(date('H',$ts), date('i',$ts), date('s',$ts), $lastmonth, date('j',$ts), $lastyear);
            $data[] = $pos_row_id;
            $data[0] = date('Y-m-d H:i:s', $date1);
            $dbc->execute($query, $data);
            $data[0] = date('Y-m-d H:i:s', $date2);
            $dbc->execute($query, $data);
            $pos_row_id++;
            if ($rowlimit > 0 && $pos_row_id > $rowlimit) {
                break;
            }
        }
        $pos_row_id--;
        echo "$pos_row_id/144866\r";
        echo "\nData loaded\n";

        unlink($tempfile);

        return true;
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $obj = new LoadSampleTrans();
    $obj->loadData(1500);
}

