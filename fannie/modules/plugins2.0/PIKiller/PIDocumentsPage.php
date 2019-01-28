<?php 
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('PIKillerPage')) {
    include('lib/PIKillerPage.php');
}

class PIDocumentsPage extends PIKillerPage {

    protected function get_id_handler(){
        global $FANNIE_OP_DB;
        $this->card_no = $this->id;

        $this->title = 'Documents : Member '.$this->card_no;

        return True;
    }

    protected function get_id_view()
    {
        ob_start();
        echo '<tr><td>';

        $dir = opendir(__DIR__ . '/noauto/docfile/' . $this->id);
        echo '<ul style="font-size: 145%">';
        while ($dir && ($file=readdir($dir)) !== false) {
            if ($file[0] == '.') continue;
            echo '<li><a href="noauto/docfile/' . $this->id . '/' . $file . '">' . $file . '</li>';
        }
        echo '</ul>';
        echo '</td></tr>';
        echo '<tr><td style="padding-left: 20px;">
            <button type="button" onclick="window.location=\'PISignaturePage.php?id=' . $this->id . '\';">Sign</button>
            </td></tr>';
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

