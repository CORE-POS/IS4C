<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

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

class Notes extends \COREPOS\Fannie\API\member\MemberModule {

    public function width()
    {
        return parent::META_WIDTH_HALF;
    }

    function showEditForm($memNum, $country="US"){
        global $FANNIE_URL;

        $dbc = $this->db();
        
        $infoQ = $dbc->prepare("SELECT note,stamp FROM memberNotes
                WHERE cardno=? ORDER BY stamp DESC");
        $infoR = $dbc->execute($infoQ,array($memNum));

        $recentNote = "";
        $recentDate = "";
        /*
          Always show the most recent note
        */
        if ($dbc->num_rows($infoR) > 0){
            $temp = $dbc->fetch_row($infoR);    
            $recentNote = str_replace("<br />","\n",$temp['note']);
            $recentDate = $temp['stamp'];
        }

        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">Notes</div>
            <div class=\"panel-body\">";

        $ret .= '<div class="form-group">';
        $ret .= "<span class=\"label primaryBackground\">Additional Notes</span>";
        if ($dbc->num_rows($infoR) > 1) {
            $ret .= ' <button type="button" onclick="$(\'#noteHistory\').toggle();"
                        class="btn btn-default">Details</button>';
        }
        $ret .= '</div>';

        $ret .= "<textarea name=\"Notes_text\" class=\"form-control\">";
        $ret .= $recentNote;
        $ret .= "</textarea>";
        $ret .= '<input type="hidden" name="Notes_current" value="'.base64_encode($recentNote).'" />';

        $ret .= "<table id=\"noteHistory\" class=\"MemFormTable table collapse\">";
        while ( $infoW = $dbc->fetch_row($infoR) ) {
            // converting br tags to newlines is only necessary
            // when displaying in a textarea
            $note = $infoW['note'];
            $date = $infoW['stamp'];
            $ret .= "<tr><td>$date</td><td>$note</td></tr>\n";
        }
        $ret .= "</table>\n";

        $ret .= "</div>\n";
        $ret .= "</div>\n";

        return $ret;
    }

    public function saveFormData($memNum, $json=array())
    {

        /* entry blank. do not save */
        $note = FormLib::get_form_value('Notes_text');
        if ( $note == "" ) {
            return "";
        }
        
        /* entry has note changed. this means it's already
           in memberNotes as the most recent entry */
        $current = FormLib::get_form_value('Notes_current');
        if ($note == base64_decode($current)){
            return "";
        }

        $dbc = $this->db();

        $insertNote = $dbc->prepare("INSERT into memberNotes
                (cardno, note, stamp, username)
                VALUES (?, ?, ".$dbc->now().", 'Admin')");

        // convert newlines back to br tags
        // so displayed notes have user's
        // paragraph formatting
        $note = str_replace("\n",'<br />',$note);
        $test1 = $dbc->execute($insertNote,array($memNum,$note));

        if ($test1 === False )
            return "Error: problem saving Notes<br />";
        else
            return "";
    }

// Notes
}

