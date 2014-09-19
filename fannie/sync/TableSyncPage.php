<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/*
 * NOTE: SQLManager's transfer method is not the fastest way of pulling
 * this off. I'm using it so I can mix & match MySQL and SQL Server
 * without errors.
 *
 * Rewriting the loop to use mysql commandline programs would be good
 * if everything's on the same dbms. Using the global settings in
 * $FANNIE_LANES is the important part. Rough sketch of this
 * is in comments below.
 * Using fannie/sync/special/* is one way to effect this.
 *
 */
include('../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class TableSyncPage extends FanniePage {

    protected $title = "Fannie : Sync Data";
    protected $header = "Syncing data";

    private $errors = array();
    private $results = '';

    function preprocess(){  
        global $FANNIE_OP_DB, $FANNIE_LANES;
        $table = FormLib::get_form_value('tablename','');
        $othertable = FormLib::get_form_value('othertable','');

        if ($table === '' && $othertable !== '')
            $table = $othertable;

        if (empty($table)){
            $this->errors[] = "Error: no table was specified";
            return True;
        }
        elseif (ereg("[^A-Za-z0-9_]",$table)){
            $this->errors[] = "Error: \"$table\" contains illegal characters";
            return True;
        }

        $dbc = FannieDB::get($FANNIE_OP_DB);

        $this->results = "<p style='font-family:Arial; font-size:1.0em;'>Syncing table $table <ul>";

        if (file_exists("special/$table.php")){
            ob_start();
            include("special/$table.php");
            $this->results .= ob_get_clean();
        }
        else {
            $i = 1;
            foreach ($FANNIE_LANES as $lane){
                $dbc->add_connection($lane['host'],$lane['type'],
                    $lane['op'],$lane['user'],$lane['pw']);

                if ($dbc->connections[$lane['op']]){
                    $dbc->query("TRUNCATE TABLE $table",$lane['op']);
                    $success = $dbc->transfer($FANNIE_OP_DB,
                               "SELECT * FROM $table",
                               $lane['op'],
                               "INSERT INTO $table");
                    $dbc->close($lane['op']);
                    if ($success){
                        $this->results .= "<li>Lane ".$i." ({$lane['host']}) completed successfully</li>";
                    }
                    else {
                        $this->errors[] = "Lane ".$i." ({$lane['host']}) completed but with some errors";
                    }
                }
                else {
                    $this->errors[] = "Lane ".$i." ({$lane['host']}) couldn't connect to lane";
                }
                $i++;
            }
        }

        $this->results .= "</ul></p>";
        
        return True;
    }

    function body_content(){
        $ret = '';
        if (count($this->errors) > 0){
            $ret .= '<blockquote style="border: solid 1px red; padding: 4px;"><ul>';    
            foreach($this->errors as $e)
                $ret .= '<li>'.$e.'</li>';  
            $ret .= '</ul><a href="SyncIndexPage.php">Try Again</a></blockquote>';
        }
        $ret .= $this->results;
        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

?>
