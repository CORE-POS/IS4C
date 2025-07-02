<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/**
    @class SignsLib
    Sign Printing related functions
*/
class SignsLib
{

    public static function visualSignSelectCSS()
    {
        return <<<HTML
.layoutHeading {
    color: purple;
    font-weight: bold;
}
.layoutHeading:hover {
    text-decoration: none;
}
.big-btn {
    border: 1px solid grey;
    background-color: lightgrey;
    border-radius: 5px;
    text-align: center;
}
button {
    width: 100px;
}
HTML;
    }

    public static function visualSignSelectJS($selectID='signmod')
    {
        $var = 'test';
        return <<<JAVASCRIPT
var curSelectID = '{$selectID}';
console.log(curSelectID);
$('a.layoutHeading').click(function(){
    var optVal = $(this).attr('data-name');
    $('option').each(function(){
        let text = $(this).text();
        if (text == optVal) {
            $(this).prop('selected', true);
            // submit form if called from SignFromSearch
            let page = window.location.pathname;
            let pageName = page.split("/").pop();
            if (pageName == 'SignFromSearch.php' || pageName == 'LessSignFromSearch.php') {
                document.forms['signform'].submit();
            }
        }
    });
    $('#visualSelect').css('display', 'none');
});
$('#'+curSelectID).click(function(){
    if ($('#visualSelect').is(':visible')) {
        $('#visualSelect').css('display', 'none');
    } else {
        $('#visualSelect').css('display', 'block');
        $('#visualSelect-container').focus();
    }
});
$('#close-visualSelect').click(function(){
    $('#visualSelect').css('display', 'none');
});
JAVASCRIPT;
    }

    public static function visualSignSelectHTML($less=false)
    {
        $data = array(
            // array( file name, shown name, class name )
            array('LegacyWFCHybrid.png', 'Shelf Tags Hybrid (Standard Shelf Tag)', 'Legacy:WFC Hybrid'),
            array('LegacyWFCProduce.png', 'Shelf Tags (Produce)', 'Legacy:WFC Produce'),
            array('TagsDoubleBarcode.png', 'Shelf Tags (2 Barcodes)', 'TagsDoubleBarcode'),

            //array('TagsNoPrice.png', 'Shelf Tags (No Price)', 'TagsNoPrice'),
            //array('RailSigns4x8P.png', 'Rail Signs ', 'RailSigns4x8P'),
            array('Compact12UpL.png', 'Compact 12 Up', 'Compact12UpL'),
            array('Compact16UpL.png', 'Compact 16 Up', 'Compact16UpP'),

            array('Smart12Up.png', 'WFC Smart Signs 12 up', 'WfcSmartSigns12UpP'),
            array('Smart16Up.png', 'WFC Smart Signs 16 up', 'WfcSmartSigns16UpP'),
            array('Smart4Up.png', 'WFC Smart Signs 4 up', 'WfcSmartSigns4UpP'),
            array('Smart2Up.png', 'WFC Smart Signs 2Up', 'WfcSmartSigns2UpP'),
            //array('Signage12.png', 'Signage 12Up', 'Signage12UpL'),
            //array('Signage16Up.png', 'Signage 16Up', 'Signage16UpP'),
            array('Giganto2Alt.png', 'Giganto 2Up', 'Giganto2UpP'),

            //array('Giganto4Alt.png', 'Giganto 4Up', 'Giganto4UpP'),
            array('LessSmart4Up.png', 'Use with Pre-Printed Fresh Deals 4Up', 'Giganto4UpCoopBasics'),

            array('ItemList4UpL.png', 'Item List 4UP', 'ItemList4UpL'),
            array('ItemList2UpP.png', 'Item List 2UP', 'ItemList2UpP'),
            array('WFCHerbNSpice3UP.png', 'Herb & Spice Jars', 'Legacy:HerbSpice9UpL'),
            //array('MeatLabel.png', 'Meat 14UP (full length)', 'Legacy:WFC MEAT 14UP'),
            //array('MeatLabelSN.png', 'Meat 14UP (shorter)', 'Legacy:WFC MEAT SN 14UP'),
            //array('WFCLegacyBulkRepack14UP.png', 'Bulk Repack 14UP (no price)', 'Legacy:WFC Bulk Repack 14UP'),
            array('DeliRegular.png', 'Fancy Regular Tags', 'FancyShelfTags'),
            array('DeliNarrow.png', 'Fancy Narrow Tags (less wide)', 'FancyShelfTags_Narrow'),
            array('DeliShort.png', 'MEAT - Fancy Short Tags (less high)', 'FancyShelfTags_Short'),
            array('DeliSquare.png', 'Deli HFM Tags', 'Legacy:New WFC Deli SquareTags'),

            array('NewDeliSoup4UpL.png', 'Deli Soup', 'Legacy:New Deli Soup 4UpL'),

            array('LegacyWFCHerbNspiceFlat2.png', 'Herb & Spice (xs)', 'Legacy:WFC HerbNspice Flat'),
            //array('Giganto4UpSingle.png', 'Single Giganto (4UP)', 'Giganto4UpSingle'),
            array('WFC_Produce_SmartSigns.png', 'Produce Smart Signs', 'Legacy:WFC Produce SmartSigns'),
            array('LessLegacyWFCHybridGuidelines.png', 'Shelf Tags with Guidelines', 'Legacy:WFC Hybrid Guidelines'),
        );
        $dummySelect = "";

        $lessData = array(
            array('LessLegacyWFCHybrid.png', 'Shelf Tags Hybrid (Standard Shelf Tag)', 'Legacy:WFC SingleHybrid'),
            array('LessLegacyWFCSingleHybridGuidelines.png', 'Shelf Tags Hybrid (Any Paper)', 'Legacy:WFC SingleHybrid Guidelines'),

            array('LessLegacyWFCProduce.png', 'Shelf Tags (Produce)', 'Legacy:Single WFC Produce'),
            array('LessWFC_Produce_SmartSigns.png', 'Produce Smart Signs', 'Legacy:Single WFC Produce SmartSigns'),

            array('LessSmart4Up.png', 'Single 4Up (use pre-printed)', 'SingleCompact4UpL'),
            array('LessSmart12Up.png', 'Smart 12Up', 'SingleWfcSmartSigns12UpL'),
            array('LessSmart16Up.png', 'Smart 16Up', 'SingleWfcSmartSigns16UpL'),

            array('LessDeliRegular.png', 'Fancy Regular Tags', 'FancyTags_Less'),
            array('LessDeliNarrow.png', 'Fancy Narrow Tags (less wide)', 'FancyTags_Less_Narrow'),
            array('LessDeliShort.png', 'MEAT - Fancy Short Tags (less high)', 'FancyTags_Less_Short'),
            array('LessDeliSquare.png', 'Deli HFM Tags', 'Legacy:Single New WFC Deli SquareTags'),

            array('WFC_Herbnspice_single.png', 'Herb & Spice Tags', 'Legacy:WFC HerbNspice Single'),
            array('WFC_Herbnspice_back.png', 'Herb & Spice Back', 'Legacy:WFC HerbNspice Back'),

            //array('WFC_MEAT_14UP_LESS.png', 'Meat Tags', 'Legacy:WFC MEAT 14UP LESS'),
        );

        if ($less === true) 
            $data = $lessData;

        $i = 0;
        $output = "<div class=\"row\">";
        foreach ($data as $layout) {
            if ($i == 3) {
                $i=0;
                $output .= <<<HTML
                </div>
                <div class="row">
                    <div class="col-lg-4 big-btn">
                        <a class="layoutHeading"  href="#" data-name="{$layout[2]}">
                            <img src="noauto/{$layout[0]}" alt="{$layout[0]}" width=300 />
                            <div>{$layout[1]}</div>
                        </a>
                    </div>
HTML;
            } else {
                $output .= <<<HTML
                    <div class="col-lg-4 big-btn">
                        <a class="layoutHeading"  href="#" data-name="{$layout[2]}">
                            <img src="noauto/{$layout[0]}" alt="{$layout[0]}" width=300 />
                            <div>{$layout[1]}</div>
                        </a>
                    </div>
HTML;
            }
            $dummySelect .= "<option value=\"{$layout[2]}\">{$layout[2]}</option>";
            $i++;
        }
        $output .= "</div>";

        return <<<HTML
<div style="position: relative; display: none;" id="visualSelect">
<div style="background-color: rgba(0,0,0,0.3); margin: 15px; padding: 25px; position: fixed; top: 0; bottom: 0; right: 0; overflow-y: scroll; overflow-x:hidden;
     border-radius: 5px">
    <div class="container-fluid" id="visualSelect-container">
        <div align="right" style="cursor: pointer; " id="close-visualSelect">
            <span style="background: grey; color: lightgrey; font-family: Consolas; font-size: 14px; height: 25px; width: 54px; padding: 3px;
                position: absolute; top: 0px; right: 0px; border: 1px solid black">CLOSE</span>
        </div>
        $output
    </div>
</div>
</div>
HTML;
    }

    /*
        getStrWidthGills takes a string and 
        returns an approximate text width 
        in Gill Sans
    */
    static function getStrWidthGillSans($str)
    {
        $table = array(
            '3' => array('Q', 'm', 'w'),
            '2.7' => array('C','D','G','H','K','O','M','N','U','V','W','X','Z'),
            '2.6' => array('A','B','E','F','L','P','R','S','T','Y'),
            '1.75' => array('b','d','k','o','p','q','u','v','x','1','2','3','4','5','6','7','8','9','0'),
            '1.35' => array('a','c','e','g','h','n','r','s','t','y','z'),
            '0.6' => array('I','J','i','j',' ')
        );

        $width = 0;
        $chars = str_split($str);
        foreach ($chars as $char) {
            $char = strtoupper($char);
            foreach ($table as $v => $row) {
                if (in_array($char, $row)) {
                    $width += $v;
                }
            }
        }

        return $width;
    }

}

