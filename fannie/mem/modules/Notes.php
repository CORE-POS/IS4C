<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

class Notes extends MemberModule {

    function showEditForm($memNum, $country="US"){
        global $FANNIE_URL;

        $dbc = $this->db();
        
        $infoQ = $dbc->prepare_statement("SELECT note,stamp FROM memberNotes
                WHERE cardno=? ORDER BY stamp DESC");
        $infoR = $dbc->exec_statement($infoQ,array($memNum));

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

        $ret = "<fieldset><legend>Notes</legend>";

        $ret .= "<table class=\"MemFormTable\" border=\"0\">";
        $ret .= "<tr><th>Additional Notes</th>";
//      $ret .= "<td><a href=\"\">History</a></td></tr>";
        $ret .= "<td> ";
        if ($dbc->num_rows($infoR) > 1){
            $ret .= "<input type=\"button\" value=\"History\" id=\"historyButton\"
                style=\"display:block;\"
                onclick=\"
                    tb = document.getElementById('noteHistory'); tb.style.display='block';
                    nhb = document.getElementById('noHistoryButton'); nhb.style.display='block';
                    hb = document.getElementById('historyButton'); hb.style.display='none';
                    \"
                />";
            $ret .= "<input type=\"button\" value=\"NoHistory\" id=\"noHistoryButton\"
                style=\"display:none;\"
                onclick=\"
                    tb = document.getElementById('noteHistory'); tb.style.display='none';
                    hb = document.getElementById('historyButton'); hb.style.display='block';
                    nhb = document.getElementById('noHistoryButton'); nhb.style.display='none';
                    \"
                />";
        }
        $ret .= "</td></tr>\n";
        $ret .= "<tr><td colspan=\"2\"><textarea name=\"Notes_text\" rows=\"4\" cols=\"25\">";
        $ret .= $recentNote;
        $ret .= "</textarea></td></tr>";
        $ret .= '<input type="hidden" name="Notes_current" value="'.base64_encode($recentNote).'" />';
        $ret .= "</table>\n";

        $ret .= "<table id=\"noteHistory\" class=\"MemFormTable\" border=\"0\" style=\"display:none;\">";
        while ( $infoW = $dbc->fetch_row($infoR) ) {
            // converting br tags to newlines is only necessary
            // when displaying in a textarea
            $note = $infoW['note'];
            $date = $infoW['stamp'];
            $ret .= "<tr><td>$date</td><td>$note</td></tr>\n";
        }
        $ret .= "</table>\n";

        $ret .= "</fieldset>\n";
        return $ret;
    }

    function saveFormData($memNum){

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

        $insertNote = $dbc->prepare_statement("INSERT into memberNotes
                (cardno, note, stamp, username)
                VALUES (?, ?, ".$dbc->now().", 'Admin')");

        // convert newlines back to br tags
        // so displayed notes have user's
        // paragraph formatting
        $note = str_replace("\n",'<br />',$note);
        $test1 = $dbc->exec_statement($insertNote,array($memNum,$note));

        if ($test1 === False )
            return "Error: problem saving Notes<br />";
        else
            return "";
    }

// Notes
}

?>
