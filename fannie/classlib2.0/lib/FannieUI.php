<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

namespace COREPOS\Fannie\API\lib;

/**
  @class FannieUI
  Generator methods for common UI elements' markup
*/
class FannieUI 
{
    public static function editIcon($alt='Edit')
    {
        return '<span class="glyphicon glyphicon-pencil" title="' . $alt . '"></span>';
    }

    public static function saveIcon($alt='Save')
    {
        return '<span class="glyphicon glyphicon-floppy-disk" title="' . $alt . '"></span>';
    }

    public static function deleteIcon($alt='Delete')
    {
        return '<span class="glyphicon glyphicon-trash" title="' . $alt . '"></span>';
    }

    public static function loadingBar($id='')
    {
        return '
        <div class="progress" ' . (!empty($id) ? "id=\"$id\"" : '') . '>
            <div class="progress-bar progress-bar-striped active"  role="progressbar" 
                aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                <span class="sr-only">Loading</span>
            </div>
        </div>';
    }

    public static function tableSortIcons()
    {
        return '
            <span class="core-sort-none glyphicon glyphicon-sort"></span>
            <span class="core-sort-down glyphicon glyphicon-arrow-down"></span>
            <span class="core-sort-up glyphicon glyphicon-arrow-up"></span>
        ';
    }

    public static function prettyJSON($json)
    {
        $result= '';
        $pos = 0;
        $strLen= strlen($json);
        $indentStr = '    ';
        $newLine = "\n";
        $prevChar= '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {
            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;
            // If this character is the end of an element, 
            // output a new line and indent the next line.
            } elseif (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos--;
                $result .= str_repeat($indentStr, $pos);
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element, 
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos++;
                }
                $result .= str_repeat($indentStr, $pos);
            }

            $prevChar = $char;
        }

        return $result;
    }

    public static function itemEditorLink($upc)
    {
        return sprintf('<a href="%sitem/ItemEditorPage.php?searchupc=%s">%s</a>',
            \FannieConfig::config('URL'), $upc, $upc);
    }

    public static function receiptLink($date, $trans_num)
    {
        $date = date('Y-m-d', strtotime($date));
        return sprintf('<a href="%sadmin/LookupReceipt/RenderReceiptPage.php?date=%s&receipt=%s">%s</a>',
            \FannieConfig::config('URL'), $date, $trans_num, $trans_num);
    }

    public static function formatDate($date, $format='Y-m-d')
    {
        if (strtotime($date) !== false && substr($date, 0, 10) !== '0000-00-00') {
            return date($format, strtotime($date));
        } else {
            return '';
        }
    }
}

