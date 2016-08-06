<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('confset')) {
    include(dirname(__FILE__) . '/util.php');
}

class InstallLabelsPage extends \COREPOS\Fannie\API\InstallPage
{
    protected $title = 'Fannie: Label Settings';
    protected $header = 'Fannie: Label Settings';

    function body_content()
    {
        include(dirname(__FILE__) . '/../config.php');
        ob_start();
        echo showInstallTabs('Labels');
        ?>

        <form action="InstallLabelsPage.php" method="post">
        <?php
        echo $this->writeCheck(dirname(__FILE__) . '/../config.php');
        echo '<h4 class="install">Labels</h4>';


        $printers = array();
        $printer_options = array();
        exec("lpstat -a", $printers);
        foreach($printers as $printer) {
          $name = explode(" ", $printer, 2);
          $printer_options[$name[0]] = $name[0];
        }
        echo 'Printer for instant label: '.installSelectField('FANNIE_SINGLE_LABEL_PRINTER', $FANNIE_SINGLE_LABEL_PRINTER, $printer_options);

        $layouts = array();
        $dh = scandir(dirname(__FILE__).'/../admin/labels/pdf_layouts/');
        foreach($dh as $filename) {
          if($filename != "." && $filename != "..") {
            $file = substr($filename, 0, strlen($filename)-4);
            $layouts[$file] =  str_replace("_", " ", $file);
          }
        }

        echo 'Layout for instant label: '.installSelectField('FANNIE_SINGLE_LABEL_LAYOUT', $FANNIE_SINGLE_LABEL_LAYOUT, $layouts, 'Zebra_Single_Label');

        echo '<hr />
            <p>
                <button type="submit" name="psubmit" value="1" class="btn btn-default">Save Configuration</button>
            </p>
            </form>';

        return ob_get_clean();
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();
