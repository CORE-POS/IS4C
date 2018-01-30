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
        $existsP = $dbc->prepare('SELECT upc FROM products WHERE upc=?');
        $exists = $dbc->getValue($existsP, array($upc));
        $reviewP = $dbc->prepare('SELECT * FROM prodReview WHERE upc=?');
        $review = $dbc->getRow($reviewP, array($upc));
        $newItem = ($exists === false && $review === false) ? 'checked' : '';
        if ($review === false) {
            $review = array('upc'=>$upc, 'user'=>'n/a', 'reviewed'=>'never');
        }
        $css = ($expand_mode == 1 || $newItem == 'checked') ? '' : ' collapse';

        return <<<HTML
<div id="ProdReviewFieldset" class="panel panel-default">
    <div class="panel-heading">
        <a href="" onclick="$('#ProdReviewContents').toggle();return false;">
            Product Review</a>
    </div>
    <div id="ProdReviewContents" class="panel-body {$css}">
        <strong>Last Reviewed</strong> {$review['reviewed']} by {$review['user']}<br />
        <label>Mark as reviewed today 
            <input type="checkbox" name="prodReview" value="1" {$newItem} />
        </label>
    </div>
</div>
HTML;
    }

    public function saveFormData($upc)
    {
        try {
            $mark = $this->form->prodReview;
            if ($mark) {
                $dbc = $this->db();
                $model = new ProdReviewModel($dbc);
                $model->upc(BarcodeLib::padUPC($upc));
                $model->user(FannieAuth::getUID());
                $model->reviewed(date('Y-m-d H:i:s'));
                $model->save();
            }
        } catch (Exception $ex) {
        }

        return true;
    }
}

