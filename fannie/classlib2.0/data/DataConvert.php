<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

namespace COREPOS\Fannie\API\data {

class DataConvert
{
    /**
      Convert HTML table to array of records
      @str [string] html 
      @return [array] of table data
    */
    public static function htmlToArray($str)
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($str); // ignore warning on [my] poorly formed html

        $tables = $dom->getElementsByTagName("table");
        $rows = $tables->item(0)->getElementsByTagName('tr');

        /* convert tables to 2-d array */
        $ret = array();
        foreach ($rows as $row) {
            $good_nodes = array();
            foreach ($row->childNodes as $node) {
                if (!property_exists($node,'tagName') || $node->tagName!='th' || $node->tagName!='td') {
                    $good_nodes[] = $node;
                }
            }

            $record = array_map(function ($node) {
                $val = trim($node->nodeValue,chr(160).chr(194));
                if ($node->tagName=="th") {
                    $val .= chr(0) . 'bold';
                }
                return $val;
            }, $good_nodes);

            $ret[] = $record;
        }

        /* prepend any other lines to the array */
        $extra = self::getNonTableList($str);
        $prepend = array_filter(array_reverse($extra), function($ext) { 
            return empty($ext) ? false : true;
        }); 
        $as_array = array_map(function($item){return array($item); }, $prepend);

        return array_merge($as_array, $ret);
    }

    private static function getNonTableList($str)
    {
        $str = preg_replace("/<table.*?>.*<\/table>/s","",$str);
        $str = preg_replace("/<head.*?>.*<\/head>/s","",$str);
        $str = preg_replace("/<body.*?>/s","",$str);
        $str = str_replace("</body>","",$str);
        $str = str_replace("<html>","",$str);
        $str = str_replace("</html>","",$str);

        $extra = preg_split("/<br.*?>/s",$str);
        
        return $extra;
    }

    /**
      Convert array of data to CSV lines
      @param $array [array] input data
      @return [string] CSV lines
    */
    public static function arrayToCsv($array)
    {
        $ret = "";

        foreach ($array as $row) {
            foreach ($row as $col) {
                $item = "\"";
                list($col, $bold) = self::stringtoPair($col);
                $item .= str_replace("\"","",$col);
                $item .= "\",";
                $ret .= $item;
            }
            $ret = rtrim($ret,",")."\r\n";
        }

        return $ret;
    }

    private static function stringToPair($str)
    {
        if (($pos = strpos($str,chr(0))) !== false) {
            $str = substr($str,0,$pos);
            return array($str, true);
        } else {
            return array($str, false);
        }
    }

    /**
      Check whether an Excel library is present
    */
    public static function excelSupport()
    {
        if (self::composerExcelSupport()) {
            return true;
        } elseif (self::legacyExcelSupport()) {
            return true;
        } else {
            return false;
        }
    }

    /**
      Get file extension for the Excel library's 
      output format
    */
    public static function excelFileExtension()
    {
        if (self::composerExcelSupport()) {
            return 'xlsx';
        } elseif (self::legacyExcelSupport()) {
            return 'xls';
        } else {
            return false;
        }
    }

    /**
      Excel support installed via composer
    */
    private static function composerExcelSupport()
    {
        if (class_exists('\\PHPExcel_Writer_OpenDocument')) {
            return true;
        } else {
            return false;
        }
    }

    /**
      Excel support is available via built-in library
      that depends on PEAR
    */
    private static function legacyExcelSupport()
    {
        $pear = true;
        if (!class_exists('\\PEAR')) {
            $pear = stream_resolve_include_path('PEAR.php');
            if ($pear === false) {
                $pear = false;
            }
        }

        return true;
    }

    /**
      Convert array to Excel format using an
      available library
    */
    public static function arrayToExcel($array)
    {
        if (self::composerExcelSupport()) {
            return self::arrayToXlsx($array);
        } elseif (self::legacyExcelSupport()) {
            return self::arrayToXls($array);
        } else {
            throw new \Exception('No Excel support available');
        }
    }

    /**
      Convert array of data to excel XLS format
      @param $array [array] input data
      @return [string] XLS file content
    */
    private static function arrayToXls($array)
    {
        include_once(dirname(__FILE__) . '/../../src/Excel/xls_write/Spreadsheet_Excel_Writer/Writer.php');

        $filename = tempnam(sys_get_temp_dir(),"xlstemp");
        $workbook = new \Spreadsheet_Excel_Writer($filename);
        $worksheet =& $workbook->addWorksheet();

        $format_bold =& $workbook->addFormat();
        $format_bold->setBold();

        for ($i=0;$i<count($array);$i++) {
            for ($j=0;$j<count($array[$i]);$j++) {
                // 5Apr14 EL Added the isset test for StoreSummaryReport.php with multiple header sets.
                //            Why should it be needed?
                if (isset($array[$i][$j])) {
                    list($val, $bold) = self::stringtoPair($array[$i][$j]);
                    if ($bold) {
                        $worksheet->write($i,$j,$val,$format_bold);
                    } else {
                        $worksheet->write($i,$j,$val);
                    }
                }
            }
        }

        $workbook->close();

        $ret = file_get_contents($filename);
        unlink($filename);

        return $ret;
    }

    /**
      Convert array of data to excel XLS format
      @param $array [array] input data
      @return [string] XLS file content
    */
    private static function arrayToXlsx($array)
    {
        $obj = new \PHPExcel();
        $row = 1;
        foreach ($array as $row_array) {
            $col = 0;
            if (!is_array($row_array)) {
                $row_array = array($row_array);
            }
            foreach ($row_array as $val) {
                list($val, $bold) = self::stringtoPair($val);
                if ($bold) {
                    $obj->getActiveSheet()->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
                }
                $obj->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $val);
                $col++;
            }
            $row++;
        }
        $writer = \PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        $filename = tempnam(sys_get_temp_dir(),"xlsx");
        $writer->save($filename);

        $ret = file_get_contents($filename);
        unlink($filename);

        return $ret;
    }
}

}

