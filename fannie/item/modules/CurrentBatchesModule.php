<?php
/*******************************************************************************

    Copyright 2023 Whole Foods Co-op, Duluth, MN

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
class CurrentBatchesModule extends \COREPOS\Fannie\API\item\ItemModule 
{
    public function showEditForm($upc, $display_mode=1, $expand_mode=0)
    {
        $ret = '';
        $thead = '';
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();
        $css = ($expand_mode == 1) ? '' : ' collapse';

        $ajax = <<<JAVASCRIPT
var upc = $(this).attr('data-upc');
var batchID = $(this).attr('data-batchID');
$.ajax({
    type: 'post',
    data: 'id='+batchID+'&upc='+upc+'&forceoneitem=true',
    url: '../batches/newbatch/EditBatchPage.php',
    beforeSend() {
        $('body').css('cursor', 'wait');
    },
    success: function(resp) {
        console.log('Success');
        window.location.reload();
    },
    fail: function(resp) {
        console.log('Ajax Request Failed');
        $('body').css('cursor', 'default');
    }
});
JAVASCRIPT;

        $preP = $dbc->prepare("SELECT upc, likeCode FROM upcLike WHERE upc = ?");
        $preR = $dbc->execute($preP, array($upc));
        $preW = $dbc->fetchRow($preR);
         
        $args = array();
        $args[] = $upc;
        if (is_array($preW) && $preW['likeCode'] != '') {
            $args[] = 'LC'.$preW['likeCode'];
        }
        $prep = $dbc->prepare("
            SELECT 
            l.batchID,
            DATE(startDate) AS startDate, 
            DATE(endDate) AS endDate, 
            batchName, batchType, salePrice,
            t.typeDesc
            FROM batches AS b
                INNER JOIN batchList AS l ON l.batchID=b.batchID
                INNER JOIN batchType AS t ON t.batchTypeID=b.batchType
            WHERE b.startDate <= DATE(NOW())
                AND b.endDate >= DATE(NOW())
                AND (
                    l.upc = ?
                    OR l.upc = ?
                )
            ORDER BY l.salePrice ASC
                ");
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $id = $row['batchID'];
            $start = $row['startDate'];
            $end = $row['endDate'];
            $name = $row['batchName'];
            $type = $row['typeDesc'];
            $price = $row['salePrice'];
            $ret .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>
                <td align=\"center\"><span class=\"btn btn-success\" onClick=\"$ajax\" data-batchID=\"$id\" data-upc=\"$upc\">Apply Sale</span></td></tr>",
                $id, $name, $start, $end, $price, $type
            );
        }
        if ($ret == '') {
            $ret = "This item is not currently in a sales batch.";
        } else {
            $thead = "<th>BatchID</th> <th>Batch Name</th> <th>Start Date</th>
                <th>End Date</th> <th>Sale Price</th> <th>Batch Type</th>";
        }

        return <<<HTML
<div id="CurrentBatchesFieldset" class="panel panel-default">
    <div class="panel-heading">
        <a href="" onclick="$('#CurrentBatchesContents').toggle();return false;">
            Current Batches</a>
    </div>
    <div id="CurrentBatchesContents" class="panel-body $css">
        <table class="table table-bordered">
            <thead>$thead</thead>
            <tbody>$ret</tbody></table>
    </div>
</div>
HTML;
    }
}
