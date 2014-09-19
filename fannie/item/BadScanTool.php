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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BadScanTool extends FannieRESTfulPage
{
    protected $header = 'Bad Scans';
    protected $title = 'Bad Scans';

    public $description = '[Bad Scan Tool] shows information about UPCs that were scanned
    at the lanes but not found in POS.';

    private $date_restrict = false;

    function preprocess()
    {
        $this->__routes[] = 'get<lastweek>';
        return parent::preprocess();
    }

    function get_lastweek_view()
    {
        $this->date_restrict = true;

        return $this->get_view();
    }

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
                    MAX(v.description) as vend, MAX(n.vendorName) as vendorName, MAX(s.srp) as srp
                    FROM " . $FANNIE_TRANS_DB . $dbc->sep() . "transarchive AS t
                    LEFT JOIN products AS p ON p.upc=t.upc
                    LEFT JOIN vendorItems AS v ON t.upc=v.upc
                    LEFT JOIN vendors AS n ON v.vendorID=n.vendorID
                    LEFT JOIN vendorSRPs AS s ON v.upc=s.upc AND v.vendorID=s.vendorID
                    WHERE t.trans_type='L' AND t.description='BADSCAN'
                    AND t.upc NOT LIKE '% %'
                    AND t.upc NOT LIKE '00000000000%'
                    AND t.upc LIKE '0%'
                    AND (t.upc NOT LIKE '00000000%' OR p.upc IS NOT NULL OR v.upc IS NOT NULL)";
            if ($this->date_restrict) {
                $query .= ' AND datetime >= ' . date('\'Y-m-d 00:00:00\'', strtotime('-8 days'));
            }
            $query .= "GROUP BY t.upc, p.description
                    ORDER BY t.upc DESC";
            $result = $dbc->query($query);
            $data = array();
            while($row = $dbc->fetch_row($result)) {
                $data[] = $row;
            }

            // stick a total in the cache along with SQL results
            $dbc = FannieDB::get($FANNIE_TRANS_DB);
            $query = "SELECT COUNT(*) FROM transarchive WHERE trans_type='I' AND upc <> '0'";
            $result = $dbc->query($query);
            $row = $dbc->fetch_row($result);
            $data['itemTTL'] = $row[0];

            DataCache::freshen($data, 'day');
        }

        $ret = '';
        if ($this->date_restrict) {
            $ret .= '<a href="BadScanTool.php">View Last Quarter</a>';
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $ret .= 'Viewing Last Week';
        } else {
            $ret .= 'Viewing Last Quarter';
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $ret .= '<a href="BadScanTool.php?lastweek=1">View Last Week</a>';
        }
        $ret .= '<br /><b>Show</b>: ';
        $ret .= '<input type="radio" name="rdo" id="rdoa" onclick="showAll();" /> 
                    <label for="rdoa">All</label>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="radio" name="rdo" id="rdom" onclick="showMultiple();" /> 
                    <label for="rdom">Repeats</label>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="radio" name="rdo" id="rdof" onclick="showFixable();" checked /> 
                    <label for="rdof">Fixable</label>';
        $ret .= '<br />';
        $ret .= '<span style="color: green;">Green items have been entered in POS</span>. ';
        $ret .= '<span style="color: red;">Red items can be added from vendor catalogs</span>. ';
        $ret .= '<span style="color: blue;">Blue items can also be added from vendor catalogs but
                may not be needed. All scans are within a 5 minute window. May indicate a special
                order case scanned by mistake or a bulk purchase in a barcoded container.</span> ';
        $ret .= 'Other items are not identifiable with available information';
        $ret .= '<table id="scantable" cellspacing="0" cellpadding="4" border=1">';
        $ret .= '<tr id="tableheader"><th>UPC</th><th># Scans</th><th>Oldest</th><th>Newest</th>
                <th>In POS</th><th>In Vendor Catalog</th><th>SRP</th></tr>';
        $scanCount = 0;
        foreach($data as $row) {
            if (count($row) == 1) {
                // cached item total
                continue;
            }
            $css = '';
            $fixButton = '';
            $span = strtotime($row['newest']) - strtotime($row['oldest']);
            if (!empty($row['prod'])) {
                $css = 'class="fixed" style="display:none;"';
            } else if (!empty($row['vend']) && !empty($row['srp'])) {
                if ($span > 300) {
                    $css = 'class="fixable"';
                } else {
                    $css = 'class="semiFixable"';
                }
                $fixButton = ' <a href="ItemEditorPage.php?searchupc= ' . $row['upc'] . '" target="_new' . $row['upc'] . '">ADD</a>';
            } else if ($row['instances'] == 1) {
                $css = 'class="loner" style="display:none;"';
            } else {
                $css = 'style="display:none;"';
            }
            $ret .= sprintf('<tr %s><td>%s</td><td>%d</td><td>%s</td><td>%s</td>
                            <td>%s</td><td>%s</td><td>%s</td></tr>',
                            $css,
                            $row['upc'], $row['instances'], $row['oldest'], $row['newest'],
                            (!empty($row['prod']) ? "Yes ({$row['prod']})" : 'No'),
                            (!empty($row['vend']) ? "Yes ({$row['vendorName']} {$row['vend']})" : 'No'),
                            (!empty($row['srp']) ? $row['srp'] . $fixButton : 'n/a')
            );
            $scanCount += $row['instances'];
        }
        $ret .= '</table>';


        $ret .= '<div id="ratio">';
        $ret .= sprintf('Approx. bad scan rate: %.2f%%', ((float)$scanCount) / ((float)$data['itemTTL']) * 100);
        $ret .= '</div>';

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
    $('tr#tableheader').show();
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
            div#ratio {
                margin: 10px;
                font-size: 125%;
            }
        ';
    }
}

FannieDispatch::conditionalExec();

