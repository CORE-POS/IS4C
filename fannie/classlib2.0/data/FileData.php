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

namespace COREPOS\Fannie\API\data;

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
        }

        switch (substr(basename($filename), -3)) {
            case 'csv':
                return self::csvToArray($filename, $limit);
            case 'xls':
                return self::xlsToArray($filename, $limit);
            case 'lsx':
                // php tempfile nameing only allows a three character prefix
                return self::xlsxToArray($filename, $limit);
            case 'pdf':
                return self::pdfToArray($filename, $limit);
        }

        return array();
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
            if ($limit > 0 && count($ret) >= $limit) {
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
    public static function xlsxToArray($filename, $limit=0)
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
            if ($limit > 0 && count($ret) >= $limit) {
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

    /**
      Reduce potential for CSV based exploits

      Different spreadsheet software *may* interpret values in CSVs/TSVs
      that begin with =, @, +, or - as forumals and cause the spreadsheet to
      execute the cell's value. This may or may not include user-facing 
      warning messages.

      One common solution is to prefix such fields with single quote ('). I'm
      not using that option since it creates different headaches for users trying
      to use the CSV/TSV as a data interchange format rather than just look at
      it in Excel. Instead:

      1. Leading =, @, or + characters are simply removed. This should include multiples,
         e.g. "=@+=1+1" becomes "1+1". This creates a small set of strings that cannot
         be used as product names, brands, etc but should be an OK compromise.
      2. Values with a leading - do need to be allowed. This are validated as either
         negative integers (-123) or negative floats (-123.45).
    */
    public static function excelNoFormula($str)
    {
        $str = trim($str);
        $first = substr(trim($str), 0, 1);
        while ($first == '=' || $first == '@' || $first == '+' || $first == '-') {
            switch ($first) {
                case '-':
                    if (preg_match('/^-[0-9]+\.[0-9]+$/', $str) || preg_match('/^-[0-9]+$/', $str)) {
                        return $str;
                    }
                    return 'badval';
                    break;

                case '=':
                case '@':
                case '+':
                default:
                    $str = substr($str, 1);
                    break;
            }
            $first = substr(trim($str), 0, 1);
        }

        return $str;
    }

    public static function pdfToArray($filename, $limit)
    {
        if (!class_exists('\\Smalot\\PdfParser\\Parser')) {
            return false;
        }

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filename);
        $lines = explode("\n", $pdf->getText());

        return $limit ? array_slice($lines, 0, $limit) : $lines;
    }
}

