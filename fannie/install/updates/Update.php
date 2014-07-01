<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class UpdateObj {
    protected $timestamp = 'YYYYMMDDHHMMSS';
    protected $description = 'Describe what your update does';
    protected $author = 'Say who you are';
    protected $queries = array(
        'op' => array(),
        'trans' => array(),
        'archive' => array()
    );

    public function HtmlInfo(){
        $ret = "<p>";
        $ret .= "<b>Update</b>: ".$this->timestamp."<br />";
        $ret .= "<b>Author</b>: ".$this->author."<br />";
        $ret .= "<blockquote>".$this->description."</blockquote>";
        $ret .= "</p>";
        return $ret;    
    }

    public function HtmlQueries(){
        $ret = "<pre>";
        $ret .= "Op changes\n";
        foreach($this->queries['op'] as $q){
            $ret .= "\t$q\n\n";
        }
        $ret .= "Trans changes\n";
        foreach($this->queries['trans'] as $q){
            $ret .= "\t$q\n\n";
        }
        $ret .= "Archive changes\n";
        foreach($this->queries['archive'] as $q){
            $ret .= "\t$q\n\n";
        }
        $ret .= "</pre>";
        return $ret;
    }

    public function ApplyUpdates(){
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_ARCHIVE_DB;
        $passed = True;
        $i = 0;
        $ret = "<ul>";
        ob_start();

        $db = $this->db();
        foreach ($this->queries['op'] as $q){
            $try = $db->query($q);
            if ($try){
                $ret .= sprintf('<li><span style="color:green;" onclick="$(\'#qs%d\').toggle();">Query succeeded</span>
                        <ul style="display:none;" id="qs%d"><li>%s</li></ul>
                        </li>',$i,$i,$q);
            }
            else {
                $ret .= sprintf('<li><span style="color:red;" onclick="$(\'#qs%d\').toggle();">Query failed</span>
                        <ul id="qs%d"><li>%s</li><li>%s</li></ul>
                        </li>',$i,$i,$q,$db->error());
                $passed = False;
            }
            $i++;
        }
        
        $db->query("USE $FANNIE_TRANS_DB");
        foreach ($this->queries['trans'] as $q){
            $try = $db->query($q);
            if ($try){
                $ret .= sprintf('<li><span style="color:green;" onclick="$(\'#qs%d\').toggle();">Query succeeded</span>
                        <ul style="display:none;" id="qs%d"><li>%s</li></ul>
                        </li>',$i,$i,$q);
            }
            else {
                $ret .= sprintf('<li><span style="color:red;" onclick="$(\'#qs%d\').toggle();">Query failed</span>
                        <ul id="qs%d"><li>%s</li><li>%s</li></ul>
                        </li>',$i,$i,$q,$db->error());
                $passed = False;
            }
            $i++;
        }

        $db->query("USE $FANNIE_ARCHIVE_DB");
        foreach ($this->queries['archive'] as $q){
            $try = $db->query($q);
            if ($try){
                $ret .= sprintf('<li><span style="color:green;" onclick="$(\'#qs%d\').toggle();">Query succeeded</span>
                        <ul style="display:none;" id="qs%d"><li>%s</li></ul>
                        </li>',$i,$i,$q);
            }
            else {
                $ret .= sprintf('<li><span style="color:red;" onclick="$(\'#qs%d\').toggle();">Query failed</span>
                        <ul id="qs%d"><li>%s</li><li>%s</li></ul>
                        </li>',$i,$i,$q,$db->error());
                $passed = False;
            }
            $i++;
        }

        $ret .= "</ul>";

        $suppressedOutput = ob_end_clean();
        
        $db->close();
        $this->SetStatus($passed);
        return $ret;
    }

    public function CheckStatus(){
        $db = $this->db();
        $p = $db->prepare_statement("SELECT status FROM UpdateLog WHERE id=?");
        $r = $db->exec_statement($p,array($this->timestamp));
        $ret = False;
        if ($db->num_rows($r) > 0){
            $st = array_pop($db->fetch_row($r));
            $ret = ($st == 1) ? True : False;
        }
        $db->close();
        return $ret;
    }

    public function SetStatus($st){
        $st = ($st==True) ? 1 : 0;
        $db = $this->db();
        $p = $db->prepare_statement("DELETE FROM UpdateLog WHERE id=?");
        $r = $db->exec_statement($p,array($this->timestamp));
        $p = $db->prepare_statement("INSERT INTO UpdateLog (id,status,tdate) VALUES (?,?,".$db->now().")");
        $r = $db->exec_statement($p,array($this->timestamp,$st));
        $db->close();
    }

    private function db(){
        global $FANNIE_ROOT, $FANNIE_OP_DB;
        if (!class_exists('FannieAPI')) {
            include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
        }
        $dbc = FannieDB::get($FANNIE_OP_DB);

        return $dbc;
    }
}

?>
