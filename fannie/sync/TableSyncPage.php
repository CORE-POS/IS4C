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
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TableSyncPage extends FanniePage 
{
    protected $title = "Fannie : Sync Data";
    protected $header = "Syncing data";
    public $themed = true;

    private $errors = '';
    private $results = '';

    function preprocess()
    {  
        $table = FormLib::get('tablename','');
        $othertable = FormLib::get('othertable','');

        if ($table === '' && $othertable !== '') {
            $table = $othertable;
        }

        $sync = \COREPOS\Fannie\API\data\SyncLanes::pushTable($table);

        $this->results = "<p>Syncing table $table <br />";
        if ($sync['sending'] === true) {
            $this->results .= $sync['messages'];
        } else {
            $this->errors .= $sync['messages'];
        }
        $this->results .= '</p>';
        
        return true;
    }

    function body_content()
    {
        $ret = '';
        if (strlen($this->errors) > 0) {
            $ret .= '<blockquote>';
            $ret .= $this->errors;
            $ret .= '<a href="SyncIndexPage.php">Try Again</a></blockquote>';
        }
        $ret .= $this->results;

        return $ret;
    }
}

FannieDispatch::conditionalExec();

