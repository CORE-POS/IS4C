<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

class HobartDgwLib 
{
    /* CSV fields for WriteOneItem & ChangeOneItem records
       Required does not mean you *have to* specify a value,
       but the default will be included if you omit that field.
       Non-required fields won't be sent to the scale at all
       unless specified by the caller
    */
    private static $WRITE_ITEM_FIELDS = array(
        'RecordType' => array('name'=>'Record Type', 'required'=>true, 'default'=>'ChangeOneItem'),
        'PLU' => array('name'=>'PLU Number', 'required'=>true, 'default'=>'0000'),
        'Description' => array('name'=>'Item Description', 'required'=>false, 'default'=>'', 'quoted'=>true),
        'ReportingClass' => array('name'=>'Reporting Class', 'required'=>true, 'default'=>'999999'),
        'Label' => array('name'=>'Label Type 01', 'required'=>false, 'default'=>'53'),
        'Tare' => array('name'=>'Tare 01', 'required'=>false, 'default'=>'0'),
        'ShelfLife' => array('name'=>'Shelf Life', 'required'=>false, 'default'=>'0'),
        'Price' => array('name'=>'Price', 'required'=>true, 'default'=>'0.00'),
        'ByCount' => array('name'=>'By Count', 'required'=>false, 'default'=>'0'),
        'Type' => array('name'=>'Item Type', 'required'=>true, 'default'=>'Random Weight'),
        'NetWeight' => array('name'=>'Net Weight', 'required'=>false, 'default'=>'0'),
        'Graphics' => array('name'=>'Graphics Number', 'required'=>false, 'default'=>'0'),
    );

    /**
      Generate CSV line for a given item
      @param $item_info [keyed array] of value. Keys correspond to WRITE_ITEM_FIELDS
      @return [string] CSV formatted line
    */
    static public function getItemLine($item_info)
    {
        $line = '';
        // first write fields that are present
        foreach(self::$WRITE_ITEM_FIELDS as $key => $field_info) {
            if (isset($item_info[$key])) {
                if (isset($field_info['quoted']) && $field_info['quoted']) {
                    $line .= '"' . $item_info[$key] . '",';
                } else {
                    $line .= $item_info[$key] . ',';
                }
                /**
                  PLU has a few corresponding fields that always follow:
                  1. Barcode Number System
                  2. Bar Code
                  3. Expanded Text Number
                */
                if ($key == 'PLU') {
                    $barcode_type = '2';
                    $barcode = str_pad($item_info[$key],5,"0",STR_PAD_RIGHT);
                    $line .= $barcode_type . ',' . $barcode . ',' . $item_info[$key] . ',';;
                }
            }
        }
        // next write required fields that are not present
        foreach(self::$WRITE_ITEM_FIELDS as $key => $field_info) {
            if (!isset($item_info[$key]) && $field_info['required']) {
                if (isset($field_info['quoted']) && $field_info['quoted']) {
                    $line .= '"' . $field_info['default'] . '",';
                } else {
                    $line .= $field_info['default'] . ',';
                }
                // see above; same deal for PLU
                if ($key == 'PLU') {
                    $barcode_type = '2';
                    $barcode = str_pad($field_info['default'],5,"0",STR_PAD_RIGHT);
                    $line .= $barcode_type . ',' . $barcode . ',' . $field_info['default'] . ',';;
                }
            }
        }
        // remove last trailing comma & finish
        $line = substr($line, 0 , strlen($line)-1);
        $line .= "\r\n";

        return $line;
    }

    /**
      Write item update CSVs to Data Gate Weigh
      @param $items [keyed array] of values. Keys correspond to WRITE_ITEM_FIELDS
        $items may also be an array of keyed arrays to write multiple items
        One additional key, ExpandedText, is used to write Expanded Text. This
        is separate from the Write Item operation so it's excluded from that
        set of fields.
      @param $scales [keyed array, optional] List of scales items will be written to
        Must have keys "host", "type", and "dept". 
        May have boolean value with key "new".
    */
    static public function writeItemsToScales($items, $scales=array())
    {
        include(dirname(__FILE__).'/../../config.php');
        if (!isset($items[0])) {
            $items = array($items);
        }
        $new_item = false;
        $header_line = '';
        foreach (self::$WRITE_ITEM_FIELDS as $key => $field_info) {
            if (isset($items[0][$key])) {
                $header_line .= $field_info['name'] . ',';
                if ($key == 'PLU') {
                    $header_line .= 'Bar Code Number System,Bar Code,Expanded Text Number,';
                }
            }
            if (isset($items[0]['RecordType']) && $items[0]['RecordType'] == 'WriteOneItem') {
                $new_item = true;
            }
        }
        foreach(self::$WRITE_ITEM_FIELDS as $key => $field_info) {
            if (!isset($items[0][$key]) && $field_info['required']) {
                $header_line .= $field_info['name'] . ',';
                if ($key == 'PLU') {
                    $header_line .= 'Bar Code Number System,Bar Code,Expanded Text Number,';
                }
            }
        }
        $header_line = substr($header_line, 0, strlen($header_line)-1);
        $header_line .= "\r\n";

        $file_prefix = self::sessionKey();
        $output_dir = realpath(dirname(__FILE__) . '/../../item/hobartcsv/csv_output');
        $selected_scales = $scales;
        if (!is_array($scales) || count($selected_scales) == 0) {
            $selected_scales = $FANNIE_SCALES;
        }
        $i = 0;
        foreach ($selected_scales as $scale) {
            $file_name = sys_get_temp_dir() . '/' . $file_prefix . '_writeItem_' . $i . '.csv';
            $fp = fopen($file_name, 'w');
            fwrite($fp,"Record Type,Task Department,Task Destination,Task Destination Device,Task Destination Type\r\n");
            fwrite($fp, "ExecuteOneTask,{$scale['dept']},{$scale['host']},{$scale['type']},SCALE\r\n");
            fwrite($fp, $header_line);
            foreach($items as $item) {
                $item_line = self::getItemLine($item);
                fwrite($fp, $item_line);
            }
            fclose($fp);

            // move to DGW; cleanup the file in the case of failure
            if (!rename($file_name, $output_dir . '/' . basename($file_name))) {
                unlink($file_name);
            }

            $et_file = sys_get_temp_dir() . '/' . $file_prefix . '_exText' . $i . '.csv';
            $fp = fopen($et_file, 'w');
            fwrite($fp,"Record Type,Task Department,Task Destination,Task Destination Device,Task Destination Type\r\n");
            fwrite($fp, "ExecuteOneTask,{$scale['dept']},{$scale['host']},{$scale['type']},SCALE\r\n");
            $has_et = false;
            foreach($items as $item) {
                if (isset($item['ExpandedText']) && isset($item['PLU'])) {
                    $has_et = true;
                    $mode = $new_item ? 'WriteOneExpandedText' : 'ChangeOneExpandedText';
                    fwrite($fp,"Record Type,Expanded Text Number,Expanded Text\r\n");
                    $text = '';
                    foreach (explode("\n", $item['ExpandedText']) as $line) {
                        $text .= wordwrap($line, 50, "\n") . "\n";
                    }
                    $text = preg_replace("/\\r/", '', $text);
                    $text = preg_replace("/\\n/", '<br />', $text);
                    fwrite($fp, $mode . ',' . $item['PLU'] . ',"' . $text . "\"\r\n");
                }
            }
            fclose($fp);
            if (!$has_et) {
                // don't send empty file
                unlink($et_file);
            } else {
                // move to DGW dir
                if (!rename($et_file, $output_dir . '/' . basename($et_file))) {
                    unlink($et_file);
                }
            }

            $i++;
        }
    }

    /**
      Delete item(s) from scale
      @param $items [string] four digit PLU 
        or [array] of [string] 4 digit PLUs
    */
    static public function deleteItemsFromScales($items)
    {
        include(dirname(__FILE__).'/../../config.php');

        if (!is_array($items)) {
            $items = array($items);
        }

        $file_prefix = self::sessionKey();
        $output_dir = realpath(dirname(__FILE__) . '/../../item/hobartcsv/csv_output');
        $i = 0;
        foreach($FANNIE_SCALES as $scale) {
            $file_name = sys_get_temp_dir() . '/' . $file_prefix . '_deleteItem_' . $i . '.csv';
            $et_name = sys_get_temp_dir() . '/' . $file_prefix . '_deleteText_' . $i . '.csv';
            $fp = fopen($file_name, 'w');
            $fp2 = fopen($et_name, 'w');
            fwrite($fp,"Record Type,Task Department,Task Destination,Task Destination Device,Task Destination Type\r\n");
            fwrite($fp, "ExecuteOneTask,{$scale['dept']},{$scale['host']},{$scale['type']},SCALE\r\n");
            fwrite($fp,"Record Type,PLU Number\r\n");
            fwrite($fp2,"Record Type,Task Department,Task Destination,Task Destination Device,Task Destination Type\r\n");
            fwrite($fp2, "ExecuteOneTask,{$scale['dept']},{$scale['host']},{$scale['type']},SCALE\r\n");
            fwrite($fp2,"Record Type,Expanded Text Number\r\n");
            foreach($items as $plu) {
                if (strlen($plu) !== 4) {
                    // might be a UPC
                    $upc = str_pad($plu, 13, '0', STR_PAD_LEFT);
                    if (substr($upc, 0, 3) != '002') {
                        // not a valid UPC either
                        continue;
                    }
                    preg_match("/002(\d\d\d\d)0/",$upc,$matches);
                    $plu = $matches[1];
                }
                fwrite($fp,"DeleteOneItem,$plu\r\n");
                fwrite($fp2,"DeleteOneExpandedText,$plu\r\n");
            }
            fclose($fp);
            fclose($fp2);

            // move to DGW dir
            if (!rename($file_name, $output_dir . '/' . basename($file_name))) {
                unlink($file_name);
            }
            if (!rename($et_name, $output_dir . '/' . basename($et_name))) {
                unlink($et_name);
            }

            $i++;
        }
    }

    /**
      Import Hobart Data into scaleItems table
      This one is for "item" data
      @param $filename [string] scale-exported data CSV
      @return [int] number of items imported
    */
    static public function readItemsFromFile($filename)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $product = new ProductsModel($dbc);
        $scaleItem = new ScaleItemsModel($dbc);
        
        $fp = fopen($filename, 'r');
        // detect column indexes via header line
        $column_index = array(
            'PLU Number' => -1,
            'Price' => -1,
            'Item Description' => -1,
            'Item Type' => -1,
            'By Count' => -1,
            'Tare 01' => -1,
            'Shelf Life' => -1,
            'Net Weight' => -1,
            'Label Type 01' => -1,
            'Graphics Number' => -1,
        );
        $headers = fgetcsv($fp);
        for ($i=0;$i<count($headers);$i++) {
            $header = $headers[$i];
            if (isset($column_index[$header])) {
                $column_index[$header] = $i;
            }
        }

        $item_count = 0;
        while(!feof($fp)) {
            $line = fgetcsv($fp);
            if (!isset($line[$column_index['PLU Number']])) {
                // can't import item w/o PLU
                continue;
            }

            $plu = $line[$column_index['PLU Number']];
            $upc = $this->scalePluToUpc($plu);

            $product->reset();
            $product->upc($upc);
            if (!$product->load()) {
                // no entry in products table
                // should one be created?
                continue;
            }

            $scaleItem->reset();
            $scaleItem->plu($upc);
            if ($column_index['Price'] != -1) {
                $scaleItem->price($line[$column_index['Price']]);
            }
            if ($column_index['Item Description'] != -1) {
                $scaleItem->itemdesc($line[$column_index['Item Description']]);
            }
            if ($column_index['Item Type'] != -1) {
                $scale_type = $line[$column_index['Item Description']];
                $scaleItem->weight( $scale_type == 'Fixed Weight' ? 1 : 0 );
            }
            if ($column_index['By Count'] != -1) {
                $scaleItem->bycount($line[$column_index['By Count']]);
            }
            if ($column_index['Tare 01'] != -1) {
                $scaleItem->tare($line[$column_index['Tare 01']]);
            }
            if ($column_index['Shelf Life'] != -1) {
                $scaleItem->shelflife($line[$column_index['Shelf Life']]);
            }
            if ($column_index['Net Weight'] != -1) {
                $scaleItem->netWeight($line[$column_index['Net Weight']]);
            }
            if ($column_index['Label Type 01'] != -1) {
                $scaleItem->weight($line[$column_index['Label Type 01']]);
            }
            if ($column_index['Graphics Number'] != -1) {
                $scaleItem->graphics($line[$column_index['Graphics Number']]);
            }
            $scaleItem->save();
            $item_count++;
        }

        fclose($fp);

        return $item_count;
    }

    /**
      Import Hobart Data into scaleItems table
      This one is for "expanded text" data
      @param $filename [string] scale-exported data CSV
      @return [int] number of items imported
    */
    static public function readTextsFromFile($filename)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $product = new ProductsModel($dbc);
        $scaleItem = new ScaleItems($dbc);

        $number_index = -1;
        $text_index = -1;

        $fp = fopen($filename, 'r');
        $headers = fgetcsv($fp);
        for ($i=0;$i<count($headers);$i++) {
            $header = $headers[$i];
            if ($header == 'Expanded Text Number') {
                $number_index = $i;
            } else if ($header == 'Expanded Text') {
                $text_index = $i;
            }
        }

        if ($text_index == -1 || $number_index == -1) {
            // no valid data
            return 0;
        }

        $item_count = 0;
        while(!feof($fp)) {
            $line = fgetcsv($fp);
            $plu = $line[$number_index];
            $upc = $this->scalePluToUpc($plu);

            $product->reset();
            $product->upc($upc);
            if (!$product->load()) {
                // no entry in products table
                // should one be created?
                continue;
            }

            $scaleItem->reset();
            $scaleItem->plu($upc);
            $scaleItem->text($line[$text_index]);
            $scaleItem->save();
            $item_count++;
        }

        fclose($fp);

        return $item_count;
    }

    static private function scalePluToUpc($plu)
    {
        // convert PLU to UPC
        // includes WFC oddities with zero alignment
        // on short PLUs (less than 4 digits)
        $upc = str_pad($plu, 3, '0', STR_PAD_LEFT);
        $upc = str_pad($upc, 4, '0', STR_PAD_LEFT);
        $upc = '002' . $upc . '000000';

        return $upc;
    }

    static private function sessionKey()
    {
        $session_key = '';
        for ($i = 0; $i < 20; $i++) {
            $num = rand(97,122);
            $session_key = $session_key . chr($num);
        }

        return $session_key;
    }
}

