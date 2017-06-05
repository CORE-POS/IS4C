<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');
}

class ProdUserModule extends \COREPOS\Fannie\API\item\ItemModule 
{

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $FANNIE_URL = FannieConfig::config('URL');
        $upc = BarcodeLib::padUPC($upc);

        $ret = '<div id="ProdUserFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#ProdUserFieldsetContent').toggle();return false;\">
                Sign/Web Info</a>
                </div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="ProdUserFieldsetContent" class="panel-body' . $css . '">';

        $dbc = $this->db();
        $model = new ProductUserModel($dbc);
        $model->upc($upc);
        $model->load();

        $loc = new ProdPhysicalLocationModel($dbc);
        $loc->upc($upc);
        $loc->load();

        $sections = new FloorSectionsModel($dbc);

        $prod = new ProductsModel($dbc);
        $prod->upc($upc);
        $prod->load();

        $ret .= '<div class="col-sm-6">';
        $ret .= '<div class="row form-group">'
                . '<label class="col-sm-1">Brand</label> '
                . '<div class="col-sm-8">'
                . '<input type="text" class="form-control" id="lf_brand" name="lf_brand" value="' . $model->brand() . '" 
                    placeholder="Optional longer brand" />'
                . '</div>'
                . '<div class="col-sm-3">'
                . ' <a href="" onclick="createSign(); return false;">Make Sign</a>'
                . '</div>'
                . '</div>';
        $ret .= '<div class="row form-group">'
                . '<label class="col-sm-1">Desc.</label> '
                . '<div class="col-sm-8">'
                . '<textarea class="form-control" rows="2" id="lf_desc" name="lf_desc"
                    placeholder="Optional longer description; values from above are used if this brand and description are blank." >'
                . $model->description()
                . '</textarea>'
                . '</div>'
                . '</div>';

        $ret .= '<div class="row form-group">'
                . '<label class="small col-sm-1">Sign Ct.</label> '
                . '<div class="col-sm-4">'
                . '<input type="number" class="form-control price-field"
                    name="sign-count" value="' . $model->signCount() . '" />'
                . '</div>'
                . '<div class="col-sm-3">'
                . '<label>Narrow Tag <input type="checkbox" value="1" name="narrowTag" ' . ($model->narrow() ? 'checked' : '') . ' /></label>'
                . '</div>'
                . '</div>';

        $ret .= '<div class="row form-group">
                    <label tile="Location on the floor" class="col-sm-1">Loc.</label>
                    <div class="col-sm-8">
                        <select name="floor-id" class="form-control">
                            <option value="0">n/a</option>';
        foreach ($sections->find('name') as $section) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                    ($loc->floorSectionID() == $section->floorSectionID() ? 'selected' : ''),
                    $section->floorSectionID(), $section->name()
            );
        }
        $ret .= '</select>
                </div>
                <div class="col-sm-3 text-left">
                    <a href="mapping/FloorSectionsPage.php" target="_blank">Add more</a>
                </div>
                </div>';


        $otherOriginBlock = '<div class=row>
                <div class=col-sm-1 />
                <div class=col-sm-8>
            <select name=otherOrigin[] class=form-control><option value=0>n/a</option>';

        $ret .= '<div class="row form-group">'
                . '<label class="col-sm-1"><a href="' . $FANNIE_URL . 'item/origins/OriginEditor.php">Origin</a></label>'
                . '<div class="col-sm-8">'
                . ' <select name="origin" class="form-control">'
                . '<option value="0">n/a</option>';
        $origins = new OriginsModel($dbc);
        $origins->local(0);
        foreach ($origins->find('name') as $o) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                        $prod->current_origin_id() == $o->originID() ? 'selected' : '',
                        $o->originID(), $o->name());
            $otherOriginBlock .= sprintf('<option value=%d>%s</option>',
                                            $o->originID(), $o->name());
        }
        $ret .= '</select></div>';
        $otherOriginBlock .= '</div></div>';
        $ret .= '<div class="col-sm-3 text-left">';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;<a href="" 
                onclick="$(\'#originsBeforeMe\').before(\'' . $otherOriginBlock . '\'); return false;">Add more</a>';
        $ret .= '</div></div>';

        $mapP = 'SELECT originID FROM ProductOriginsMap WHERE upc=? AND originID <> ?';
        $mapR = $dbc->execute($mapP, array($upc, $prod->current_origin_id()));
        while ($mapW = $dbc->fetch_row($mapR)) {
            $ret .= '<div class="form-group form-inline">
                <select name="otherOrigin[]" class="form-control"><option value="0">n/a</option>';
            foreach ($origins->find('name') as $o) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                            $mapW['originID'] == $o->originID() ? 'selected' : '',
                            $o->originID(), $o->name());
            }
            $ret .= '</select></div>';
        }
        $ret .= '<div id="originsBeforeMe"></div>';
        $ret .= '<label>Item Photo</label>';
        $ret .= $this->imageInput($model->photo(), 'item');
        $ret .= '</div>';

        $ret .= '<div class="col-sm-6">';
        $ret .= '<div class="form-group"><label>Ad Text</label></div>';
        $ret .= '<div class="form-group">
                <textarea name="lf_text" class="form-control"
                    rows="8" cols="45">' 
                    . str_replace('<br />', "\n", $model->long_text()) 
                    . '</textarea></div>';

        $ret .= '<label>Nutrition Facts</label>';
        $ret .= $this->imageInput($model->nutritionFacts(), 'nf');
        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    private function imageInput($filename, $type='item')
    {
        $url = $this->imageUrl($type);
        $file = dirname(__FILE__) . '/../' . $url . $filename;
        $ret = '';
        if (file_exists($file) && is_file($file)) {
            $ret .= sprintf('<div><img src="%s" alt="image" width="200" /></div>', $url . $filename);
        } else {
            $ret .= '<div class="alert alert-info">Currently no image</div>';
        }
        $ret .= '<br /><label>Upload new image</label>
            <input type="file" name="image_' . $type . '" class="form-control" 
                accept="image/png" />';

        return $ret;
    }

    private function imageUrl($type)
    {
        switch (strtolower($type)) {
            case 'item':
                return 'images/done/';
            case 'nf':
                return 'images/nutrition-facts/';
            default:
                return false;
        }
    }

    function SaveFormData($upc)
    {
        $upc = BarcodeLib::padUPC($upc);
        $brand = FormLib::get('lf_brand');
        $desc = FormLib::get('lf_desc');
        $origin = FormLib::get('origin', 0);
        $floorID = FormLib::get('floor-id', 0);
        $narrow = FormLib::get('narrowTag', 0) ? 1 : 0;
        $text = FormLib::get('lf_text');
        $text = str_replace("\r", '', $text);
        $text = str_replace("\n", '<br />', $text);
        // strip non-ASCII (word copy/paste artifacts)
        $text = preg_replace("/[^\x01-\x7F]/","", $text); 
        $signs = FormLib::get('sign-count', 1);
        if ($signs < 1) {
            $signs = 1;
        }

        $dbc = $this->db();

        $loc = new ProdPhysicalLocationModel($dbc);
        $loc->upc($upc);
        $loc->floorSectionID($floorID);
        $loc->save();

        $model = new ProductUserModel($dbc);
        $model->upc($upc);
        $model->brand($brand);
        $model->description($desc);
        $model->long_text($text);
        $model->signCount($signs);
        $model->narrow($narrow);

        $multiOrigin = FormLib::get('otherOrigin', array());
        $originMap = array();
        if ($origin != 0) {
            $originMap[] = $origin;
        }
        foreach ($multiOrigin as $originID) {
            if ($originID != 0) {
                $originMap[] = $originID;
            }
        }
        
        $mapP = $dbc->prepare('DELETE FROM ProductOriginsMap WHERE upc=?');
        $addP = $dbc->prepare('INSERT INTO ProductOriginsMap
                                (originID, upc, active)
                                VALUES (?, ?, 1)');

        $lcP = $dbc->prepare('SELECT u.upc
                            FROM upcLike AS u
                                ' . DTrans::joinProducts('u', 'p', 'INNER') . '
                            WHERE u.likeCode IN (
                                SELECT l.likeCode
                                FROM upcLike AS l
                                WHERE l.upc = ?
                            )');
        $lcR = $dbc->execute($lcP, array($upc));
        $items = array($upc);
        while ($w = $dbc->fetch_row($lcR)) {
            if ($w['upc'] == $upc) {
                continue;
            }
            $items[] = $w['upc'];
        }

        $prod = new ProductsModel($dbc);
        $stores = new StoresModel($dbc);
        foreach ($items as $item) {
            $prod->upc($item);
            $prod->store_id(1);
            $prod->current_origin_id($origin);
            $prod->enableLogging(false);
            $prod->save();

            $dbc->execute($mapP, array($item));
            foreach ($originMap as $originID) {
                $dbc->execute($addP, array($originID, $item));
            }
        }

        $model = $this->savePhotos($model);
        
        return $model->save();
    }

    private function savePhotos($model)
    {
        if ($this->savePhoto($model->upc(), 'item')) {
            $model->photo(ltrim($model->upc(), '0') . '.png');
        }
        if ($this->savePhoto($model->upc(), 'nf')) {
            $model->nutritionFacts(ltrim($model->upc(), '0') . '.png');
        }

        return $model;
    }

    private function savePhoto($upc, $type)
    {
        if ($this->validPhoto($type)) {
            $infile = $_FILES['image_' . $type]['tmp_name'];
            $outfile = dirname(__FILE__) . '/../' . $this->imageUrl($type) . ltrim($upc, 0) . '.png';
            if (file_exists($outfile)) {
                unlink($outfile);
            }
            $res = move_uploaded_file($infile, $outfile);
        }

        return false;
    }

    private function validPhoto($type)
    {
        if (!isset($_FILES['image_' . $type])) {
            return false;
        }

        if ($_FILES['image_' . $type]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($_FILES['image_' . $type]['tmp_name']) !== 'image/png') {
            return false;
        }

        return true;
    }

    public function getFormJavascript($upc)
    {
        $FANNIE_URL = FannieConfig::config('URL');
        ob_start();
        ?>
        function createSign()
        {
           var form = $('<form />',
                            { action: '<?php echo $FANNIE_URL; ?>admin/labels/SignFromSearch.php',
                              method: 'post',
                              id: 'newSignForm' }
            );
           form.append($('<input />',
                        { type: 'hidden', name: 'u[]', value: '<?php echo $upc; ?>' }));

           $('body').append(form);
           $('#newSignForm').submit();
        }
        <?php
        return ob_get_clean();

    }

    public function summaryRows($upc)
    {
        $FANNIE_URL = FannieConfig::config('URL');
        $form = sprintf('<form id="newSignForm" method="post" action="%sadmin/labels/SignFromSearch.php">
                        <input type="hidden" name="u[]" value="%s" />
                        </form>', $FANNIE_URL, $upc);
        $ret = '<td>' . $form . '<a href="" onclick="$(\'#newSignForm\').submit();return false;">Create Sign</a></td>';

        return array($ret);
    }
}

