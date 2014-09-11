<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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
include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
include_once('../../classlib2.0/InstallPage.php');
include('../util.php');

/**
    @class LaneTextStringPage
    Class for the Global Lane define Text Strings page.
    Strings that appear in receipt headers and footers and in the PoS interface.
*/
class LaneTextStringPage extends InstallPage {

    protected $title = 'CORE:PoS Global Lane Configuration: Text Strings';
    protected $header = 'CORE:PoS Global Lane Configuration: Text Strings';

    public $description = "
    Class for the Global Lane define Text Strings page.
    Strings that appear in receipt headers and footers and in the PoS interface.
    ";

    // This replaces the __construct() in the parent.
    public function __construct() {

        // To set authentication.
        FanniePage::__construct();

        $SRC = '../../src';
        // Link to a file of CSS by using a function.
        $this->add_css_file("$SRC/style.css");
        $this->add_css_file("$SRC/javascript/jquery-ui.css");
        $this->add_css_file("$SRC/css/install.css");

        // Link to a file of JS by using a function.
        $this->add_script("$SRC/javascript/jquery.js");
        $this->add_script("$SRC/javascript/jquery-ui.js");

    // __construct()
    }

    // If chunks of CSS are going to be added the function has to be
    //  redefined to return them.
    // If this is to override x.css draw_page() needs to load it after the add_css_file
    /**
      Define any CSS needed
      @return A CSS string
    function css_content(){
        $css ="";
        return $css;
    //css_content()
    }
    */

    // If chunks of JS are going to be added the function has to be
    //  redefined to return them.
    /**
      Define any javascript needed
      @return A javascript string
    function javascript_content(){
        $js ="";
        return $js;
    }
    */

    function body_content(){
        global $FANNIE_OP_DB; //, $TRANSLATE;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        // keys are customReceipt.type values.
        $TRANSLATE = array(
            'receiptHeader'=>'Receipt Header',
            'receiptFooter'=>'Receipt Footer',
            'ckEndorse'=>'Check Endorsement',
            'welcomeMsg'=>'Welcome On-screen Message',
            'farewellMsg'=>'Goodbye On-screen Message',
            'trainingMsg'=>'Training On-screen Message',
            'chargeSlip'=>'Store Charge Slip'
        );

        if (isset($_REQUEST['new_submit'])){
            $chkQ = $dbc->prepare_statement("SELECT MAX(seq) FROM customReceipt WHERE type=?");
            $chkR = $dbc->exec_statement($chkQ, array($_REQUEST['new_type']));
            $seq = 0;
            if ($dbc->num_rows($chkR) > 0){
                $max = array_pop($dbc->fetch_row($chkR));
                if ($max != null) $seq=$max+1;
            }
            if (!empty($_REQUEST['new_content'])){
                $insQ = $dbc->prepare_statement("INSERT INTO customReceipt (type,text,seq) VALUES (?,?,?)");
                $dbc->exec_statement($insQ,array($_REQUEST['new_type'],$_REQUEST['new_content'],$seq));
            }
        }
        else if (isset($_REQUEST['old_submit'])){
            $cont = $_REQUEST['old_content'];
            $type = $_REQUEST['old_type'];
            $seq=0;
            $prev_type='';
            $trun = $dbc->prepare_statement("TRUNCATE TABLE customReceipt");
            $dbc->exec_statement($trun);
            $insP = $dbc->prepare_statement("INSERT INTO customReceipt (type,text,seq) VALUES (?,?,?)");
            for($i=0;$i<count($cont);$i++){
                if ($prev_type != $type[$i])
                    $seq = 0; // new type, reset sequence
                if (empty($cont[$i])) 
                    continue; // empty means delete that line
                $dbc->exec_statement($insP, array($type[$i],$cont[$i],$seq));
                $prev_type=$type[$i];
                $seq++;
            }
        }

        ob_start();

        echo showLinkToFannie();
        echo showInstallTabsLane("Text Strings", '');

?>

<form action=LaneTextStringPage.php method=post>
<h1 class="install"><?php echo $this->header; ?></h1>

<?php
if (is_writable('../../config.php')){
    echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
    echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>
<p class="ichunk">Use this utility to enter and edit the lines of text that appear on
receipts, the lane Welcome screen, and elsewhere.
<br />If your receipts have no headers or footers or they are wrong this is the place to fix that.
<br />The upper form is for adding lines.
<br />The lower form is for editing existing lines.
<br />Make changes in only one of the forms for each submit, i.e. Add and Edit separately.
</p>
<hr />

<h4 class="install">Add lines</h4>
<p class="ichunk2 ichunk3">Select a type of text string, enter the line of text for it, and click "Add".
<br />Existing lines of the type are displayed in the "Edit existing text string lines" form, below.
<br />All types of text strings may initially have no lines, i.e. be empty.
<br />The maximum length of a line is 55 characters.
</p>
<select name="new_type" size="5">
<?php
$tcount = 0;
foreach($TRANSLATE as $short=>$long){
    $tcount++;
    if (isset($_REQUEST['new_type'])) {
        $selected=($_REQUEST['new_type']==$short)?'selected':'';
    } else {
        $selected = ($tcount==1)?'selected':'';
    }
    printf('<option value="%s" %s>%s</option>',
        $short, $selected, $long);
}
?>
</select>
<input type="text" name="new_content" size="55" maxlength="55" />
<input type="submit" name="new_submit" value="Add a line of the selected type" />
</form>
<hr />

<h4 class="install">Edit existing text string lines</h4>
<p class="ichunk2 ichunk3">Existing lines of text of different types are displayed below and can be edited there.
<br />All types may initially have no lines in which case the heading will not appear and no line boxes will appear.
<br />To delete a line erase all the text from it.
<br />The maximum length of a line is 55 characters.
</p>
<form method="post" action="LaneTextStringPage.php">
<?php
$q = $dbc->prepare_statement("SELECT type,text FROM customReceipt ORDER BY type,seq");
$r = $dbc->exec_statement($q);
$header="";
$i=1;
while($w = $dbc->fetch_row($r)){
	if ($header != $w['type']){
		echo '<h3>'.$TRANSLATE[$w['type']].'</h3>';
		$header = $w['type'];	
		$i=1;
	}
	printf('<p>%d:<input type="text" size="55" maxlength="55" name="old_content[]" value="%s" />
		<input type="hidden" name="old_type[]" value="%s" /></p>',
		$i++,$w['text'],$w['type']);
}
?>
<input type="submit" name="old_submit" value="Save Changes" />
</form>

<?php

        return ob_get_clean();

    // body_content
    }

// LaneTextStringPage  
}

FannieDispatch::conditionalExec(false);

?>
