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
if (!class_exists('FannieAPI'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!class_exists('PIKillerPage')) {
    include('lib/PIKillerPage.php');
}

class PINoteHistoryPage extends PIKillerPage {

    protected function get_id_handler(){
        global $FANNIE_OP_DB;
        $this->card_no = $this->id;

        $this->title = 'Notes History : Member '.$this->card_no;

        $this->__models['notes'] = $this->get_model(FannieDB::get($FANNIE_OP_DB), 'MemberNotesModel',
                        array('cardno'=>$this->id),'stamp');
        $this->__models['notes'] = array_reverse($this->__models['notes']);
    
        return True;
    }

    protected function get_id_view(){
        global $FANNIE_URL;
        ob_start();
        echo '<tr><td>';
        foreach($this->__models['notes'] as $note){
            if(trim($note->note()) == '') continue;
            echo '<b>'.$note->stamp().' - note added by '.$note->username().'</b><br />';
            echo $note->note().'<br /><hr />';
        }
        echo '</td></tr>';
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

?>
