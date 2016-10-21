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
    include($FANNIE_ROOT.'/classlib2.0/FannieAPI.php');
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

    protected function get_id_view(){
        ob_start();
        echo '<tr><td>';

        echo '<iframe width="90%" height="300"
            src="http://key:8888/cgi-bin/docfile/index.cgi?memID='.$this->id.'"
            style="border: 0px;">
        </iframe>';

        echo '</td></tr>';
        echo '<tr><td style="padding-left: 20px;">
            <button type="button" onclick="window.location=\'PISignaturePage.php?id=' . $this->id . '\';">Sign</button>
            </td></tr>';
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

