<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class DataConvert
{
    /**
      Convert HTML table to array of records
      @str [string] html 
      @return [array] of table data
    */
    public static function htmlToArray($str)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($str); // ignore warning on [my] poorly formed html

        $tables = $dom->getElementsByTagName("table");
        $rows = $tables->item(0)->getElementsByTagName('tr');

        /* convert tables to 2-d array */
        $ret = array();
        $i = 0;
        foreach ($rows as $row) {
            $ret[$i] = array();
            foreach ($row->childNodes as $node) {
                if (!property_exists($node,'tagName')) {
                    continue;
                }
                $val = trim($node->nodeValue,chr(160).chr(194));
                if ($node->tagName=="th") {
                    $val .= chr(0) . 'bold';
                }

                if ($node->tagName=="th" || $node->tagName=="td") {
                    $ret[$i][] = $val;
                }
            }
            $i++;
        }

        /* prepend any other lines to the array */
        $str = preg_replace("/<table.*?>.*<\/table>/s","",$str);
        $str = preg_replace("/<head.*?>.*<\/head>/s","",$str);
        $str = preg_replace("/<body.*?>/s","",$str);
        $str = str_replace("</body>","",$str);
        $str = str_replace("<html>","",$str);
        $str = str_replace("</html>","",$str);

        $extra = preg_split("/<br.*?>/s",$str);
        foreach (array_reverse($extra) as $e) {
            if (!empty($e)) {
                array_unshift($ret,array($e));
            }
        }

        return $ret;
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
                $r = "\"";
                if ( ($pos = strpos($col,chr(0))) !== False) {
                    $col = substr($col,0,$pos);
                }
                $r .= str_replace("\"","",$col);
                $r .= "\",";
                $ret .= $r;
            }
            $ret = rtrim($ret,",")."\r\n";
        }

        return $ret;
    }

    /**
      Convert array of data to excel XLS format
      @param $array [array] input data
      @return [string] XLS file content
    */
    public static function arrayToXls($array)
    {
        global $FANNIE_ROOT;

        include_once($FANNIE_ROOT.'src/Excel/xls_write/Spreadsheet_Excel_Writer/Writer.php');

        $fn = tempnam(sys_get_temp_dir(),"xlstemp");
        $workbook = new Spreadsheet_Excel_Writer($fn);
        $worksheet =& $workbook->addWorksheet();

        $format_bold =& $workbook->addFormat();
        $format_bold->setBold();

        for ($i=0;$i<count($array);$i++) {
            for ($j=0;$j<count($array[$i]);$j++) {
                // 5Apr14 EL Added the isset test for StoreSummaryReport.php with multiple header sets.
                //            Why should it be needed?
                if (isset($array[$i][$j])) {
                    if ( ($pos = strpos($array[$i][$j],chr(0))) !== false) {
                        $val = substr($array[$i][$j],0,$pos);
                        $worksheet->write($i,$j,$val,$format_bold);
                    } else {
                        $worksheet->write($i,$j,$array[$i][$j]);
                    }
                }
            }
        }

        $workbook->close();

        $ret = file_get_contents($fn);
        unlink($fn);

        return $ret;
    }

    /**
      Convert array of data to excel XLS format
      This variation does not require an external
      library/PEAR but likely does not work as well
      @param $array [array] input data
      @return [string] XLS file content
    */
    public static function arrayToXls2($array)
    {
        $ret = self::xlsBOF();
        $rownum = 1;
        foreach ($array as $row) {
            $colnum = 0;
            foreach ($row as $col) {
                if (is_numeric($col)) {
                    $ret .= self::xlsWriteNumber($rownum,$colnum,$col);
                } elseif(!empty($col)) {
                    $ret .= self::xlsWriteLabel($rownum,$colnum,$col);
                }
                $colnum++;
            }
            $rownum++;
        }
        $ret .= self::xlsEOF();

        return $ret;
    }

    /* additional functions from example @
       http://www.appservnetwork.com/modules.php?name=News&file=article&sid=8
    */
    private static function xlsBOF() 
    {
        return pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);  
    } 

    private static function xlsEOF() 
    {
        return pack("ss", 0x0A, 0x00);
    }

    private static function xlsWriteNumber($Row, $Col, $Value) 
    {
        return  pack("sssss", 0x203, 14, $Row, $Col, 0x0)
            . pack("d", $Value);
    } 

    private static function xlsWriteLabel($Row, $Col, $Value ) 
    {
        $L = strlen($Value);
        return pack("ssssss", 0x204, 8 + $L, $Row, $Col, 0x2bc, $L)
            . $Value;
    }
}

