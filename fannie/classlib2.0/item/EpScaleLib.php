<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

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

namespace COREPOS\Fannie\API\item;

class EpScaleLib 
{
    static private $NEWLINE = "\r\n";

    /**
      Generate CSV line for a given item
      @param $item_info [keyed array] of value. Keys correspond to WRITE_ITEM_FIELDS
      @param $scale_model [ServiceScaleModel]
      @return [string] CSV formatted line
    */
    static public function getItemLine($item_info, $scale_model, $asNew)
    {
        $scale_fields = '';
        if ($scale_model->epStoreNo() != 0) {
            $scale_fields .= 'SNO' . $scale_model->epStoreNo() . chr(253);
        }
        $scale_fields .= 'DNO' . $scale_model->epDeptNo() . chr(253);
        $scale_fields .= 'SAD' . $scale_model->epScaleAddress() . chr(253);

        $labelInfo = array('labelType'=>103, 'descriptionWidth'=>26, 'textWidth'=>0);
        if (isset($item_info['Label'])) {
            $labelInfo = ServiceScaleLib::labelTranslate($item_info['Label'], $scale_model->scaleType());
        }

        // Always requested "add" might work better. Setting description
        // isn't reliable when "updating" an item that doesn't actually
        // exist on the other side of processing
        /* ePlum nominally supports both an "add" and an "update"
         * operation but the latter is not always reliable.
         * Specifically, ePlum stores multiple descriptions where
         * 'Description 1' is the first line of text, 'Description 2'
         * is the second line, etc. Specifying two descriptions
         * in an "add" command always works but specifying two
         * descriptions in an "update" command sometimes ends
         * up ignoring the second description.
         *
         * CORE doesn't only support ePlum; it also still supports
         * Hobart's Data GateWeigh which also has separate
         * "add and "update" operations. The goal of the current
         * setup is to make it so you can feed the same '$items'
         * data into both scale communication layers and generally
         * have ePlum almost always use "add" operations but have
         * Hobart switch appropriately between the two operations.
         */
        if ($asNew || $item_info['RecordType'] == 'WriteOneItem') {
            $line = self::getAddItemLine($item_info, $labelInfo) . $scale_fields;
        } else {
            $line = self::getUpdateItemLine($item_info, $labelInfo) . $scale_fields;
        }

        if ($scale_model->scaleType() == 'HOBART_HTI') {
            preg_match('/UTA(\d\d\d)/', $line, $matches);    
            $tare = $matches[1];
            $fixed_tare = substr($tare . '0', -3);
            $line = str_replace($matches[0], 'UTA' . $fixed_tare, $line);
        }

        return $line;
    }

    static public function getIngredientLine($item_info, $scale_model)
    {
        $et_line = ($item_info['RecordType'] == 'WriteOneItem' ? 'CCOSIIA' : 'CCOSIIC') . chr(253);
        if ($scale_model->epStoreNo() != 0) {
            $et_line .= 'SNO' . $scale_model->epStoreNo() . chr(253);
        }
        $et_line .= 'DNO' . $scale_model->epDeptNo() . chr(253);
        $et_line .= 'SAD' . $scale_model->epScaleAddress() . chr(253);
        $et_line .= 'PNO' . $item_info['PLU'] . chr(253);
        $et_line .= 'INO' . $item_info['PLU'] . chr(253);
        $et_line .= 'ITE' . self::expandedText($item_info['ExpandedText'], $item_info, $scale_model) . chr(253);

        return $et_line;
    }

    static private function expandedText($text, $item_info, $scale_model)
    {
        if ($item_info['MOSA']) {
            $text = str_replace('{mosa}', 'Certified Organic By MOSA', $text);
        } else {
            $text = str_replace('{mosa}', '', $text);
            $text = str_ireplace('CERTIFIED ', '', $text);
        }
        if (!isset($item_info['OriginText'])) {
            $item_info['OriginText'] = '';
        }
        $text = str_replace('{cool}', $item_info['OriginText'], $text);
        $text = str_replace("\r", '', $text);
        return str_replace("\n", chr(0xE), $text);
    }

    static private function getAddItemLine($item_info, $labelInfo)
    {
        $line = 'CCOSPIA' . chr(253);
        $line .= 'PNO' . $item_info['PLU'] . chr(253);
        $line .= 'UPC' . ServiceScaleLib::pluToUPC($item_info['PLU']) . chr(253);
        $desc = (isset($item_info['Description'])) ? $item_info['Description'] : '';
        $line .= self::wrapDescription($desc, $labelInfo['descriptionWidth']);
        $line .= 'DS1' . '0' . chr(253);
        if (!strstr($line, 'DN2')) {
            $line .= 'DN2' . chr(253);
            $line .= 'DS2' . '0' . chr(253);
        }
        $line .= 'DN3' . chr(253);
        $line .= 'DS3' . '0' . chr(253);
        $line .= 'DN4' . chr(253);
        $line .= 'DS4' . '0' . chr(253);
        $line .= 'UPR' . (isset($item_info['Price']) ? round(100*$item_info['Price']) : '0') . chr(253);
        $line .= 'EPR' . '0' . chr(253);
        $line .= 'FWT' . (isset($item_info['NetWeight']) ? sprintf('%d0', $item_info['NetWeight']) : '0') . chr(253);
        if ($item_info['Type'] == 'Random Weight') {
            $line .= 'UMELB' . chr(253);
        } elseif (isset($item_info['NetWeight']) && $item_info['NetWeight']) {
            $line .= 'UMEFW' . chr(253);
        } else {
            $line .= 'UMEBC' . chr(253);
        }
        $line .= 'BCO' . '0' . chr(253);
        $line .= 'WTA' . '0' . chr(253);
        $line .= 'UTA' . (isset($item_info['Tare']) ? str_pad(floor(100*$item_info['Tare']).'0', 3, '0', STR_PAD_LEFT) : '0') . chr(253);
        $line .= 'SLI' . (isset($item_info['ShelfLife']) ? $item_info['ShelfLife'] : '0') . chr(253);
        $line .= 'SLT' . '0' . chr(253);
        $line .= 'EBY' . '0' . chr(253);
        $line .= 'CCL' . (isset($item_info['ReportingClass']) ? $item_info['ReportingClass'] : '0') . chr(253);
        $line .= 'LNU' . '0' . chr(253);
        $line .= 'GNO' . (isset($item_info['Graphics']) ? str_pad($item_info['Graphics'],6,'0',STR_PAD_LEFT) : '0') . chr(253);
        $line .= 'GNU' . '0' . chr(253);
        $line .= 'MNO' . '0' . chr(253);
        $line .= 'INO' . $item_info['PLU'] . chr(253);
        $line .= 'TNO' . '0' . chr(253);
        $line .= 'NTN' . '0' . chr(253);
        $line .= 'NRA' . '95' . chr(253);
        $line .= 'ANO' . '0' . chr(253);
        $line .= 'FTA' . 'N' . chr(253);
        $line .= 'LF1' . (isset($item_info['Label']) ? $labelInfo['labelType'] : '0') . chr(253);
        $line .= 'LF2' . '0' . chr(253);
        $line .= 'FR1' . '0' . chr(253);
        $line .= 'FDT' . '0' . chr(253);
        $line .= 'PTA' . '0' . chr(253);
        $line .= 'PC1' . chr(253);
        $line .= 'EAS' . '0' . chr(253);
        $line .= 'FSL' . 'N' . chr(253);
        $line .= 'FUB' . 'N' . chr(253);
        $line .= 'UF1' . chr(253);
        $line .= 'UF2' . chr(253);
        $line .= 'UF3' . chr(253);
        $line .= 'UF4' . chr(253);
        $line .= 'UF5' . chr(253);
        $line .= 'UF6' . chr(253);
        $line .= 'UF7' . chr(253);
        $line .= 'UF8' . '1' . chr(253);
        $line .= 'PTN' . '1' . chr(253);

        return $line;
    }

    static private function getUpdateItemLine($item_info, $labelInfo)
    {
        $line = 'CCOSPIC' . chr(253); 
        foreach (ServiceScaleLib::$WRITE_ITEM_FIELDS as $key => $field_info) {
            if (isset($item_info[$key])) {
                switch ($key) {
                    case 'PLU':
                        $line .= 'PNO' . $item_info[$key] . chr(253);
                        $line .= 'UPC' . ServiceScaleLib::pluToUPC($item_info[$key]) . chr(253);
                        $line .= 'INO' . $item_info[$key] . chr(253);
                        break;
                    case 'Description':
                        if (strstr($item_info[$key], "\n")) {
                            list($line1, $line2) = explode("\n", $item_info[$key]);
                            $line .= 'DN1' . $line1 . chr(253);
                            $line .= 'DS1' . '0' . chr(253);
                            $line .= 'DN2' . $line2 . chr(253);
                            $line .= 'DS2' . '0' . chr(253);
                        } elseif (strlen($item_info[$key]) > $labelInfo['descriptionWidth'] && $labelInfo['descriptionWidth'] != 0) {
                            $line .= self::wrapDescription($item_info[$key], $labelInfo['descriptionWidth']);
                        } else {
                            $line .= 'DN1' . $item_info[$key] . chr(253);
                            $line .= 'DS1' . '0' . chr(253);
                        }
                        break;
                    case 'ReportingClass':
                        $line .= 'CCL' . $item_info[$key] . chr(253);
                    case 'Label':
                        /** disabled 11Nov2015 - doesn't syncing seems broken **/
                        $line .= 'LF1' . $labelInfo['labelType'] . chr(253);
                        break;
                    case 'Tare':
                        $line .= 'UTA' . str_pad(floor(100*$item_info['Tare']).'0', 3, '0', STR_PAD_LEFT). chr(253);
                        break;
                    case 'ShelfLife':
                        $line .= 'SLI' . $item_info[$key] . chr(253) . 'SLT0' . chr(253);
                        break;
                    case 'Price':
                        if ($item_info['Price'] != 0 && $item_info['Price'] < 9999) {
                            $line .= 'UPR' . round(100*$item_info[$key]) . chr(253);
                        }
                        break;
                    case 'Type':
                        if ($item_info[$key] == 'Random Weight') {
                            $line .= 'UMELB' . chr(253);
                            $line .= 'BCO' . '0' . chr(253);
                        } else {
                            $line .= 'UMEBC' . chr(253);
                            $line .= 'BCO' . '1' . chr(253);
                        }
                        break;
                    case 'NetWeight':
                        $line .= 'FWT' . sprintf('%d', $item_info[$key]) . chr(253);
                        break;
                    case 'Graphics':
                        $line .= 'GNO' . str_pad($item_info[$key],6,'0',STR_PAD_LEFT) . chr(253);
                        break;
                    case 'inUse':
                        $line .= 'UF8' . ($item_info[$key] ? 1 : 0) . chr(253);
                        break;
                }
            }
        }

        return $line;
    }

    static private function wrapDescription($desc, $length, $limit=2)
    {
        $lines = array($desc);
        if ($length > 0) {
            $wrapped = wordwrap($desc, $length, "\n", true);
            $lines = explode("\n", $wrapped);
        }
        $keys = array_filter(array_keys($lines), function($i) use ($limit) { return $i<$limit; });
        return array_reduce($keys, function($carry, $key) use ($lines) {
            return $carry . 'DN' . ($key+1) . trim($lines[$key]) . chr(253)
                . 'DS' . ($key+1) . '0' . chr(253);
        });
    }

    /**
      Write item update file(s) to ePlum
      @param $items [keyed array] of values. Keys correspond to WRITE_ITEM_FIELDS
        $items may also be an array of keyed arrays to write multiple items
        One additional key, ExpandedText, is used to write Expanded Text. This
        is separate from the Write Item operation so it's excluded from that
        set of fields.
      @param $scales [keyed array, optional] List of scales items will be written to
        Must have keys "host", "type", and "dept". 
        May have boolean value with key "new".
    */
    static public function writeItemsToScales($items, $scales=array(), $asNew=true)
    {
        $config = \FannieConfig::factory(); 
        if (!isset($items[0])) {
            $items = array($items);
        }
        $header_line = '';
        $file_prefix = ServiceScaleLib::sessionKey();
        $output_dir = $config->get('EPLUM_DIRECTORY');
        if ($output_dir == '') {
            return false;
        }
        $selected_scales = $scales;
        if (!is_array($scales) || count($selected_scales) == 0) {
            $selected_scales = $config->get('SCALES');
        }
        $scale_model = new \ServiceScalesModel(\FannieDB::get($config->get('OP_DB')));
        $counter = 0;
        $depts = array();
        foreach ($selected_scales as $scale) {
            $scale_model = ServiceScaleLib::getModelByHost($scale['host']);
            // batches run per-department rather than per-scale
            // so duplicates can be skipped
            if ($scale_model === false) {
                continue;
            } elseif (in_array($scale_model->epDeptNo(), $depts)) {
                continue;
            } else {
                $depts[] = $scale_model->epDeptNo();
            }

            $file_name = sys_get_temp_dir() . '/' . $file_prefix . '_writeItem_' . $counter . '.dat';
            $fptr = fopen($file_name, 'w');
            fwrite($fptr, 'BNA' . $file_prefix . '_' . $counter . chr(253) . self::$NEWLINE);
            foreach ($items as $item) {
                $item_line = self::getItemLine($item, $scale_model, $asNew);
                fwrite($fptr, $item_line . self::$NEWLINE);

                if (isset($item['ExpandedText'])) {
                    $et_line = self::getIngredientLine($item, $scale_model);
                    fwrite($fptr, $et_line . self::$NEWLINE);
                }
            }
            fclose($fptr);

            // move to DGW; cleanup the file in the case of failure
            if (!rename($file_name, $output_dir . '/' . basename($file_name))) {
                unlink($file_name);
            }

            $counter++;
        }
    }

    /**
      Delete item(s) from scale
      @param $items [string] four digit PLU 
        or [array] of [string] 4 digit PLUs
    */
    static public function deleteItemsFromScales($items, $scales=array())
    {
        $config = \FannieConfig::factory(); 
        $dbc = \FannieDB::get($config->get('OP_DB'));

        if (!is_array($items)) {
            $items = array($items);
        }

        $file_prefix = ServiceScaleLib::sessionKey();
        $output_dir = $config->get('EPLUM_DIRECTORY');
        if ($output_dir == '') {
            return false;
        }
        $selected_scales = $scales;
        if (!is_array($scales) || count($selected_scales) == 0) {
            $prep = $dbc->prepare("SELECT * FROM ServiceScales");
            $selected_scales = $dbc->getAllRows($prep);
        }
        $scale_model = new \ServiceScalesModel(\FannieDB::get($config->get('OP_DB')));
        $counter = 0;
        $epScales = array();
        foreach ($selected_scales as $scale) {
            $file_name = sys_get_temp_dir() . '/' . $file_prefix . '_deleteItem_' . $counter . '.dat';
            $fptr = fopen($file_name, 'w');
            foreach ($items as $plu) {
                if (isset($epScales[$scale['epDeptNo']])) {
                    continue;
                }
                if (strlen($plu) !== 4) {
                    // might be a UPC
                    $upc = str_pad($plu, 13, '0', STR_PAD_LEFT);
                    if (substr($upc, 0, 3) != '002') {
                        // not a valid UPC either
                        continue;
                    }
                    $plu = ServiceScaleLib::upcToPLU($upc);
                }
                fwrite($fptr, 'BNA' . $file_prefix . '_' . $counter . chr(253) . self::$NEWLINE);
                $line = 'CCOSPID' . chr(253); 
                $line .= 'SNO' . $scale['epStoreNo'] . chr(253);
                $line .= 'DNO' . $scale['epDeptNo'] . chr(253);
                $line .= 'PNO' . $plu . chr(253);
                fwrite($fptr, $line . self::$NEWLINE);
                $line = 'CCOSIID' . chr(253); 
                $line .= 'SNO' . $scale['epStoreNo'] . chr(253);
                $line .= 'DNO' . $scale['epDeptNo'] . chr(253);
                $line .= 'INO' . $plu . chr(253);
                fwrite($fptr, $line . self::$NEWLINE);
                $epScales[$scale['epDeptNo']] = true;
            }
            fclose($fptr);

            // move to DGW dir
            if (!rename($file_name, $output_dir . '/' . basename($file_name))) {
                unlink($file_name);
            }

            $counter++;
        }
    }
}

