<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

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

class ProdReviewModule extends \COREPOS\Fannie\API\item\ItemModule 
{
    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();
        $defaultP = $dbc->prepare("SELECT default_vendor_id FROM products WHERE upc=?");
        $defaultID = $dbc->getValue($defaultP, array($upc));
        $existsP = $dbc->prepare('SELECT upc FROM products WHERE upc=?');
        $exists = $dbc->getValue($existsP, array($upc));
        $reviewP = $dbc->prepare('SELECT
            r.*, 
            CASE WHEN LENGTH(v.vendorName) > 0 THEN v.vendorName ELSE "default" END AS vendorName
            FROM prodReview AS r LEFT JOIN vendors AS v ON v.vendorID=r.vendorID WHERE upc=?');
        $comments = '';
        $reviews = array();
        $reviewR = $dbc->execute($reviewP, array($upc));
        while ($row = $dbc->fetchRow($reviewR)) {
            $reviews[] = $row;
        }
        $newItem = ($exists === false && count($reviews) == 0) ? 'checked' : '';
        if (empty($reviews)) {
            $reviews[] = array('upc'=>$upc, 'user'=>'n/a', 'reviewed'=>'never', 'comment'=>'+', 'vendorID'=>$defaultID, 'vendorName'=>$defaultID);
        }
        $reviewRet = '';
        foreach ($reviews as $row) {
            $vendorName = $row['vendorName'];
            $vendorID = $row['vendorID'];
            $reviewed = $row['reviewed'];
            $user = $row['user'];
            $comment = $row['comment'];

            $reviewRet .= "<div class=\"panel panel-info\" style=\"width: 500px;\">";
            $reviewRet .= "<div class=\"panel-heading\">$vendorName</div>";
            $reviewRet .= "<div class=\"panel-body\">";
            $reviewRet .= "<div><strong>Last Reviewed</strong> $reviewed by $user</div>";
            $reviewRet .= "<div><strong>Comments</strong></div><div><textarea name=\"prodReviewComment[$vendorID]\" class=\"form-control\">$comment</textarea></div>";
            $reviewRet .= "<div>
                <label>Mark as reviewed today 
                    <input type=\"checkbox\" name=\"prodReviewCheck[$vendorID]\" value=\"1\" {$newItem} />
                </label></div>";
            $reviewRet .= "</div>";
            $reviewRet .= "</div>";
        }
        $css = ($expand_mode == 1 || $newItem == 'checked') ? '' : ' collapse';

        return <<<HTML
<div id="ProdReviewFieldset" class="panel panel-default">
    <div class="panel-heading">
        <a href="" onclick="$('#ProdReviewContents').toggle();return false;">
            Product Review</a>
    </div>
    <div id="ProdReviewContents" class="panel-body {$css}">
        $reviewRet
    </div>
</div>
HTML;
    }

    public function saveFormData($upc)
    {
        $dbc = $this->db();
        $data = FormLib::get('prodReviewCheck', array());
        $comments = FormLib::get('prodReviewComment');
        $user = FannieAuth::getUID();

        $existsP = $dbc->prepare("SELECT reviewed FROM prodReview WHERE upc = ? AND vendorID = ?");

        foreach ($data as $vendorID => $checked) {
            if ($checked == 1) {
                $comment = (isset($comments[$vendorID])) ? $comments[$vendorID] : null;

                $exists = $dbc->getValue($existsP, array($upc, $vendorID));
                if ($exists) {
                    $args = array($comment, $user, $upc, $vendorID);
                    $prep = $dbc->prepare("UPDATE prodReview
                        SET comment = ?,
                            reviewed = DATE(NOW()),
                            user = ?
                        WHERE upc = ?
                            AND vendorID = ? ");
                    $dbc->execute($prep, $args);
                } else {
                    $args = array($comment, $upc, $vendorID, $user);
                    $prep = $dbc->prepare("INSERT INTO prodReview (comment, reviewed, upc, vendorID, user)
                        VALUES (?, NOW(), ?, ?, ?);");
                    $dbc->execute($prep, $args);
                }

            }
        }

        return true;
    }
}

