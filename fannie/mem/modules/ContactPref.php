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

class ContactPref extends \COREPOS\Fannie\API\member\MemberModule {

    public function width()
    {
        return parent::META_WIDTH_HALF;
    }

    // Return a form segment to display or edit the Contact Preference.
    function showEditForm($memNum, $country="US"){

        global $FANNIE_URL;

        $dbc = $this->db();

        // Select the preference for this member and all of the options.
        $infoQ = $dbc->prepare("SELECT n.pref, p.pref_id, p.pref_description
                FROM memContact AS n,
                memContactPrefs AS p
                WHERE n.card_no=?
                ORDER BY p.pref_id");
        $infoR = $dbc->execute($infoQ,array($memNum));

        // If no preference exists get the options and force a default in pref.
        if ( $dbc->num_rows($infoR) == 0 ) {
            $infoQ = $dbc->prepare("SELECT IF(pref_id=2,2,-1) pref, pref_id, pref_description
                    FROM memContactPrefs
                    ORDER BY pref_id");
            $infoR = $dbc->execute($infoQ);
        }

        // Compose the display/edit block.
        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">Member Contact Preference</div>
            <div class=\"panel-body\">";

        $ret .= '<div class="form-group form-inline">
            <span class="label primaryBackground">Preference</span>';
        $ret .= ' <select name="MemContactPref" class="form-control">';
        while ($infoW = $dbc->fetch_row($infoR)) {
            $ret .= sprintf("<option value=%d %s>%s</option>",
                $infoW['pref_id'],
                (($infoW['pref']==$infoW['pref_id'])?'selected':''),
                $infoW['pref_description']);
        }
        $ret .= "</select></div>";

        $ret .= "</div>";
        $ret .= "</div>";

        return $ret;

    // showEditForm
    }

    // Update or insert the Contact Preference.
    // Return "" on success or an error message.
    public function saveFormData($memNum, $json=array())
    {
        $dbc = $this->db();

        $formPref = FormLib::get_form_value('MemContactPref',-1);

        // Does a preference for this member exist?
        $infoQ = $dbc->prepare("SELECT pref
                FROM memContact
                WHERE card_no=?");
        $infoR = $dbc->execute($infoQ,array($memNum));

        // If no preference exists, add one if one was chosen.
        if ( $dbc->num_rows($infoR) == 0 ) {
            if ( $formPref > -1 ) {
                $upQ = $dbc->prepare("INSERT INTO memContact (card_no, pref)
                    VALUES (?, ?)");
                $upR = $dbc->execute($upQ,array($memNum, $formPref));
                if ( $upR === False )
                    return "Error: problem adding Contact Preference.";
                else
                    return "";
            }
        }
        // If one exists, update it unless there was no change.
        else {
            $row = $dbc->fetch_row($infoR);
            $dbPref = $row['pref'];
            if ( $formPref != $dbPref ) {
                $upQ = $dbc->prepare("UPDATE memContact SET pref = ?
                    WHERE card_no = ?");
                $upR = $dbc->execute($upQ,array($formPref, $memNum));
                if ( $upR === False )
                    return "Error: problem updating Contact Preference.";
                else
                    return "";
            }
        }

        return "";

    // saveFormData
    }

// ContactPref
}

