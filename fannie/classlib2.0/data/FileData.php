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

namespace COREPOS\Fannie\API\data {

/**
  @class FileData

  Helper functions for extracting data
  from files
*/
class FileData
{
    /**
      Get two-dimensional array of file data
      @param $filename [string] file name with full path
      @param $limit if specified only return $limit records
      @return An array of arrays. Each inner array
        represents one line of data
    */
    public static function fileToArray($filename, $limit=0) 
    {
        if (!file_exists($filename)) {
            return array();
        } elseif (substr(basename($filename),-3) == 'csv') {
            return self::csvToArray($filename, $limit);
        } elseif (substr(basename($filename),-3) == 'xls') {
            return self::xlsToArray($filename, $limit);
        } elseif (substr(basename($filename),-3) == 'lsx') {
            // php tempfile nameing only allows a three character prefix
            return self::xlsxToArray($filename, $limit);
        } else {
            return array();
        }
    }

    /**
      Helper for csv files. See fileToArray()
    */
    public static function csvToArray($filename, $limit=0)
    {
        $fptr = fopen($filename,'r');
        if (!$fptr) {
            return array();
        }
        $ret = array();
        while (!feof($fptr)) {
            $ret[] = fgetcsv($fptr);
            if ($limit != 0 && count($ret) >= $limit) {
                break;
            }
        }
        fclose($fptr);

        return $ret;
    }

    /**
      Helper for xls files. See fileToArray()
    */
    public static function xlsToArray($filename, $limit)
    {
        /** 
          PHPExcel can read both file variants just fine if it's
          available.
        */
        if (class_exists('\\PHPExcel_IOFactory')) {
            return self::xlsxToArray($filename, $limit);
        }

        if (!class_exists('Spreadsheet_Excel_Reader')) {
            include_once(dirname(__FILE__).'/../../src/Excel/xls_read/reader.php');
        }

        $data = new \Spreadsheet_Excel_Reader();
        $data->setOutputEncoding('ISO-8859-1');
        $data->read($filename);

        $sheet = $data->sheets[0];
        $rows = $sheet['numRows'];
        $cols = $sheet['numCols'];
        $ret = array();
        for($i=1; $i<=$rows; $i++) {
            $line = array();
            for ($j=1; $j<=$cols; $j++) {
                if (isset($sheet['cells'][$i]) && isset($sheet['cells'][$i][$j])) {
                    $line[] = $sheet['cells'][$i][$j];
                } else {
                    $line[] = '';
                }
            }
            $ret[] = $line;
            if ($limit != 0 && count($ret) >= $limit) {
                break;
            }
        }

        return $ret;
    }

    /**
      Helper for xlsx files. See fileToArray()
    */
    public static function xlsxToArray($filename, $limit)
    {
        if (!class_exists('\\PHPExcel_IOFactory')) {
            return false;
        }

        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        $sheet = $objPHPExcel->getActiveSheet();
        $rows = $sheet->getHighestRow();
        $cols = \PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
        $ret = array();
        for ($i=1; $i<=$rows; $i++) {
            $new = array_map(function ($j) use ($i, &$sheet) {
                return $sheet->getCellByColumnAndRow($j, $i)->getValue();
            }, range(0, $cols));
            $ret[] = $new;
            if ($limit != 0 && count($ret) >= $limit) {
                break;
            }
        }

        return $ret;
    }

    public static function excelFloatToDate($float)
    {
        $days = floor($float);
        $time = 24.0 * ($float - $days);
        $hour = floor($time); 
        $remainder = 60.0 * ($time - $hour);
        $minutes = floor($remainder);
        $remainder = 60.0 * ($remainder - $minutes);
        $seconds = floor($remainder);

        return date('Y-m-d H:i:s', mktime($hour, $minutes, $seconds, 1, $days-1, 1900));
    }

}

}

namespace {
    class FileData extends \COREPOS\Fannie\API\data\FileData {}
}
