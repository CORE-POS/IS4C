<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

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

include('../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class BadScanTool extends FannieRESTfulPage
{
    protected $header = 'Bad Scans';
    protected $title = 'Bad Scans';

    function get_view()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;

        $data = DataCache::check();
        if (!$data) {
            /**
              Excludes:
              Values with spaces (fixed in lanecode going forward)
              One and two digit PLUs (likely simply miskeys)
              Values with no leading zeroes (EAN-13 and UPC-A should have
                at least one. I do have some values with no leading zeroes
                but not sure yet what they are. Do not appear to be GTIN-14).
            */
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $query = "SELECT t.upc, COUNT(t.upc) AS instances,
                    MIN(datetime) as oldest,
                    MAX(datetime) as newest,
                    p.description as prod,
                    v.description as vend, n.vendorName, s.srp
                    FROM " . $FANNIE_TRANS_DB . $dbc->sep() . "transarchive AS t
                    LEFT JOIN products AS p ON p.upc=t.upc
                    LEFT JOIN vendorItems AS v ON t.upc=v.upc
                    LEFT JOIN vendors AS n ON v.vendorID=n.vendorID
                    LEFT JOIN vendorSRPs AS s ON v.upc=s.upc AND v.vendorID=s.vendorID
                    WHERE t.trans_type='L' AND t.description='BADSCAN'
                    AND t.upc NOT LIKE '% %'
                    AND t.upc NOT LIKE '00000000000%'
                    AND t.upc LIKE '0%'
                    AND (t.upc NOT LIKE '00000000%' OR p.upc IS NOT NULL OR v.upc IS NOT NULL)
                    GROUP BY t.upc
                    ORDER BY t.upc DESC";
            $result = $dbc->query($query);
            $data = array();
            while($row = $dbc->fetch_row($result)) {
                $data[] = $row;
            }
            DataCache::freshen($data, 'day');
        }

        $ret = '';
        $ret .= '<b>Show</b>: ';
        $ret .= '<input type="radio" name="rdo" onclick="showAll();" checked /> All';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="radio" name="rdo" onclick="showMultiple();" /> Repeats';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="radio" name="rdo" onclick="showFixable();" /> Fixable';
        $ret .= '<br />';
        $ret .= '<span style="color: green;">Green items have been entered in POS</span>. ';
        $ret .= '<span style="color: red;">Red items can be added from vendor catalogs</span>. ';
        $ret .= '<span style="color: blue;">Blue items can also be added from vendor catalogs but
                may not be needed. All scans are within a 5 minute window. May indicate a special
                order case scanned by mistake or a bulk purchase in a barcoded container.</span> ';
        $ret .= 'Other items are completely unknown.';
        $ret .= '<table id="scantable" cellspacing="0" cellpadding="4" border=1">';
        $ret .= '<tr><td>UPC</td><td># Scans</td><td>Oldest</td><td>Newest</td>
                <td>In POS</td><td>In Vendor Catalog</td><td>SRP</td></tr>';
        foreach($data as $row) {
            $css = '';
            $fixButton = '';
            $span = strtotime($row['newest']) - strtotime($row['oldest']);
            if (!empty($row['prod'])) {
                $css = 'class="fixed"';
            } else if (!empty($row['vend']) && !empty($row['srp'])) {
                if ($span > 300) {
                    $css = 'class="fixable"';
                } else {
                    $css = 'class="semiFixable"';
                }
                $fixButton = ' <a href="ItemEditorPage.php?searchupc= ' . $row['upc'] . '" target="_new' . $row['upc'] . '">ADD</a>';
            } else if ($row['instances'] == 1) {
                $css = 'class="loner"';
            }
            $ret .= sprintf('<tr %s><td>%s</td><td>%d</td><td>%s</td><td>%s</td>
                            <td>%s</td><td>%s</td><td>%s</td></tr>',
                            $css,
                            $row['upc'], $row['instances'], $row['oldest'], $row['newest'],
                            (!empty($row['prod']) ? "Yes ({$row['prod']})" : 'No'),
                            (!empty($row['vend']) ? "Yes ({$row['vendorName']} {$row['vend']})" : 'No'),
                            (!empty($row['srp']) ? $row['srp'] . $fixButton : 'n/a')
            );
        }
        $ret .= '</table>';

        return $ret;
    }

    function javascript_content(){
        ob_start();
        ?>
function showAll() {
    $('#scantable tr').each(function(){
        $(this).show();
    });
}
function showFixable() {
    $('#scantable tr').each(function(){
        $(this).hide();
    });
    $('tr.fixed').each(function(){
        $(this).show();
    });
    $('tr.fixable').each(function(){
        $(this).show();
    });
    $('tr.semiFixable').each(function(){
        $(this).show();
    });
}
function showMultiple() {
    showAll();
    $('tr.loner').each(function(){
        $(this).hide();
    });
}
        <?php
        return ob_get_clean();
    }

    function css_content()
    {
        return '
            tr.fixed td {
                background: green;
                color: white;
            }
            tr.fixable td {
                background: red;
                color: white;
            }
            tr.semiFixable td {
                background: blue;
                color: white;
            }
            tr.fixable a, tr.semiFixable a {
                color: white;
            }
        ';
    }
}

FannieDispatch::go();

