<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 25Mar2013 AT Merged changes between CORE and flathat
    * 21Mar2013 EL Hacked FANNIE_POUNDS_AS_POUNDS until established.
    *              Use input description width 30, not 27, OK per AT.
    * 16Mar2013 Eric Lee Need to get the vendor name either from the form
    *            or from, ideally, vendors, or prodExtra.
    *            Currently the vendor name input is just text, not controlled.
    *           It would be better if it used size and unitofmeasure from the form.
    *            In update, would need a post-update shelftag create as in insertItem.php

*/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class addShelfTag extends FannieRESTfulPage
{
    public $discoverable = false;

    public function preprocess()
    {
        $this->addRoute('get<upc>','post<upc>');
        return parent::preprocess();
    }

    public function getHeader()
    {
        return <<<HTML
<!doctype html>
<html>
    <head>
        <title>Add Shelf Tag</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" type="text/css" href="../src/javascript/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="../src/javascript/bootstrap-default/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="../src/javascript/bootstrap-default/css/bootstrap-theme.min.css">
        <script type="text/javascript" src="../src/javascript/jquery.js"></script>
        <script type="text/javascript" src="../src/javascript/bootstrap/js/bootstrap.min.js"></script>
        <script type="text/javascript">
        $(document).ready(function(){
            $('input.focus').focus();
        });
        </script>
    </head>
<body>
HTML;
    }

    public function getFooter()
    {
        return '</body></html>';
    }

    private function print_tag() {
      $offset = FormLib::get('offset', 0);

      $data = array(array(
        'normal_price' => FormLib::get('price'),
        'description' => FormLib::get('description'),
        'brand' => FormLib::get('brand'),
        'units' => FormLib::get('units'),
        'size' => FormLib::get('size'),
        'sku' => FormLib::get('sku'),
        'pricePerUnit' => FormLib::get('ppo'),
        'upc' => FormLib::get('upc'),
        'vendor' => FormLib::get('vendor'),
        'scale' => FormLib::get('scale'),
        'numflag' => FormLib::get('numflag'),
        'count' => FormLib::get('count', 0)
      ));

      if (!defined('FPDF_FONTPATH')) {
        define('FPDF_FONTPATH','font/');
      }
      if (!class_exists('FPDF', false)) {
          require(dirname(__FILE__) . '/../src/fpdf/fpdf.php');
      }
      if (!class_exists('FpdfWithBarcode', false)) {
          include(dirname(__FILE__) . '/../admin/labels/FpdfWithBarcode.php');
      }

      $layout_file = dirname(__FILE__) . '/../admin/labels/pdf_layouts/' . $this->config->get('SINGLE_LABEL_LAYOUT') . '.php';
      if (count($data) > 0 && file_exists($layout_file) && !function_exists($this->config->get('SINGLE_LABEL_LAYOUT'))) {
          include($layout_file);
      }
      if (function_exists($this->config->get('SINGLE_LABEL_LAYOUT'))) {
          $filename = "/tmp/".uniqid().".pdf";
          $layout = $this->config->get('SINGLE_LABEL_LAYOUT');
          $layout($data, $offset, $filename);
          echo "<pre>";
          $printer = "";
          if($this->config->get('SINGLE_LABEL_PRINTER') != "") {
            $printer = "-d ".$this->config->get('SINGLE_LABEL_PRINTER')." ";
          }
          passthru("/usr/bin/lp ".$printer.$filename);
          echo "</pre>";
          unlink($filename);
          return '<div class="alert alert-success">Printing '.$filename.'</div>';
      } else {
          echo 'Invalid data and/or layout';
      }

      return false;

    }

    protected function post_upc_view()
    {
        if(FormLib::get('print') == "now") {
          return $this->print_tag();
        } else {
          $dbc = FannieDB::get($this->config->get('OP_DB'));

          $shelftag = new ShelftagsModel($dbc);
          $shelftag->id(FormLib::get('subID', 0));
          $shelftag->upc(FormLib::get('upc'));
          $shelftag->normal_price(FormLib::get('price'));
          $shelftag->pricePerUnit(FormLib::get('ppo'));
          $shelftag->description(FormLib::get('description'));
          $shelftag->brand(FormLib::get('brand'));
          $shelftag->sku(FormLib::get('sku'));
          $shelftag->size(FormLib::get('size'));
          $shelftag->units(FormLib::get('units'));
          $shelftag->vendor(FormLib::get('vendor'));
          $shelftag->count(FormLib::get('count', 1));
          $insR = $shelftag->save();

          if ($insR === false) {
            return '<div class="alert alert-danger">Error creating tag</div>';
          } else {
            return '<div class="alert alert-success">Created Tag</div>';
          }
        }
    }

    protected function get_upc_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $upc = BarcodeLib::padUPC($this->upc);
        $product = new ProductsModel($dbc);
        $product->upc($upc);
        $tagData = $product->getTagData();

        $prodQ = $dbc->prepare("SELECT p.*,s.superID FROM products AS p
            LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
            where upc=?");
        $prodR = $dbc->execute($prodQ,array($upc));
        $prodW = $dbc->fetchRow($prodR);
        $superID = $prodW['superID'];

        $price = $tagData['normal_price'];
        $desc = $tagData['description'];
        $brand = $tagData['brand'];
        $size = $tagData['size'];
        $units = $tagData['units'];
        $sku = $tagData['sku'];
        $vendor = $tagData['vendor'];
        $ppo = $tagData['pricePerUnit'];

        ob_start();
        ?>
        <div class="container-fluid">
        <form method='post'>
        <input type='hidden' name=upc value="<?php echo $upc; ?>">
        <div class="form-group form-inline">
            <label>Description</label>
            <input type='text' name='description' maxlength=30
                class="form-control focus" value="<?php echo strtoupper($desc); ?>" />
        <label>Brand</label>
            <input type='text' name='brand' maxlength=15 
                class="form-control" value="<?php echo strtoupper($brand); ?>" />
        </div>
        <div class="form-group form-inline">
        <label>Units</label>
            <input type='text' name='units' size=10
                class="form-control" value="<?php echo $units; ?>" />
        <label>Size</label>
        <input type='text' name='size' size=10
            class="form-control" value="<?php echo $size; ?>" />
        </div>
        <div class="form-group form-inline">
        <label>PricePer</label>
        <input type=text name=ppo
            class="form-control" value="<?php echo $ppo; ?>" />
        <label>Vendor</label>
        <input type='text' name='vendor'
            class="form-control" value="<?php echo $vendor; ?>" />
        </div>
        <div class="form-group form-inline">
        <label># Tags</label>
        <input type="text" name="count" size="3" value="1" 
            class="form-control" />
        <label>SKU</label>
        <input type='text' name='sku' size=8
            class="form-control" value="<?php echo $sku; ?>" />
        </div>
        <p>
        <label>Price</label>
        <span class="alert-success h3">
            <strong><?php printf("%.2f",$price); ?></strong>
        </span>
        <input type='hidden' name='price' size=8 value=<?php echo $price; ?> />
        <span class="btn-group">
          <button type="submit" class="btn btn-default"
              name="submit" value="New">Create Tag</button>
          <?php
          $instant_tag_disabled = "disabled";
          if($this->config->get('SINGLE_LABEL_LAYOUT') != "" &&
             $this->config->get('SINGLE_LABEL_PRINTER') != "") {
                 $instant_tag_disabled = "";
           } ?>
          <button type="submit" class="btn btn-default" <?php echo $instant_tag_disabled; ?>
              name="print" value="now">Instant Tag</button>
        </p>
        <div class="form-group form-inline">
        <label>Barcode page</label>
        <select name=subID class="form-control">
        <?php
        $qmodel = new ShelfTagQueuesModel($dbc);
        echo $qmodel->toOptions($superID);
        ?>
        </select>
        </div>
        </form>
        </div>
        <?php

        return ob_get_clean();
    }

    public function unitTest($phpunit)
    {
        $this->upc = '4011';
        $phpunit->assertNotEquals(0, strlen($this->get_upc_view()));
    }
}

FannieDispatch::conditionalExec();
