<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
    @class LaneTextStringPage
    Class for the Global Lane define Text Strings page.
    Strings that appear in receipt headers and footers and in the PoS interface.
*/
class LaneTextStringPage extends FannieRESTfulPage 
{
    protected $title = 'Lane Configuration: Text Strings';
    protected $header = 'Lane Configuration: Text Strings';

    public $description = "[Lane Text Editor] manages strings that appear in receipt headers and footers 
    as well as some sections of the POS interface.";

    private $TRANSLATE = array(
        'receiptHeader'=>'Receipt Header',
        'receiptFooter'=>'Receipt Footer',
        'ckEndorse'=>'Check Endorsement',
        'welcomeMsg'=>'Welcome On-screen Message',
        'farewellMsg'=>'Goodbye On-screen Message',
        'trainingMsg'=>'Training On-screen Message',
        'chargeSlip'=>'Store Charge Slip',
    );

    public $has_unit_tests = true;

    public function preprocess()
    {
        $this->addRoute('get<type>');
        $this->addRoute('post<id><line><type>');
        $this->addRoute('post<type><newLine>');
        return parent::preprocess();
    }

    protected function get_type_handler()
    {
        if ($this->type == '') {
            echo '';
            return false;
        }
        $this->connection->selectDB($this->config->get('OP_DB'));
        $model = new CustomReceiptModel($this->connection);
        $model->type($this->type);
        
        $ret = '<table class="table table-bordered">
            <tr>
                <th>Line #</th>
                <th>Text</th>
                <th>' . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . '</th>
            </tr>';
        foreach ($model->find('seq') as $obj) {
            $ret .= sprintf('<tr>
                <td>%d<input type="hidden" name="id[]" value="%d" /></td>
                <td><input type="text" maxlength="55" name="line[]" class="form-control" value="%s" /></td>
                <td><input type="checkbox" name="del[]" value="%d" /></td>
                </tr>',
                $obj->seq(), $obj->seq(),
                $obj->text(),
                $obj->seq()
            );
        }
        $ret .= '<tr><td>NEW</td><td><input type="text" name="newLine" class="form-control" /></td>
            <td>&nbsp;</td></tr>';
        $ret .= '</table>';
        echo $ret;

        return false;
    }

    // with a blank set of lines there will be no ID yet
    protected function post_type_newLine_handler()
    {
        $this->id = array();
        return $this->post_id_line_type_handler();
    }

    protected function post_id_line_type_handler()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $model = new CustomReceiptModel($this->connection);
        $model->type($this->type);
        try {
            $delete = $this->form->del;
        } catch (Exception $ex) {
            $delete = array();
        }

        $currentID = 0;
        for ($i=0; $i<count($this->id); $i++) {
            $postedID = $this->id[$i];
            $line = $this->line[$i];
            if (!in_array($postedID, $delete)) {
                $model->seq($currentID);
                $model->text($line);
                $model->save();
                $currentID++; 
            }
        }
        try {
            $new = $this->form->newLine;
            if (!empty($new)) {
                $model->seq($currentID);
                $model->text($new);
                $model->save();
                $currentID++;
            }
        } catch (Exception $ex) {}

        $trimP = $this->connection->prepare('
            DELETE 
            FROM customReceipt
            WHERE type=?
                AND seq >= ?
        ');
        $this->connection->execute($trimP, array($this->type, $currentID));

        return $this->get_type_handler();
    }

    protected function get_view()
    {
        $this->addScript('lane-text.js');
        ob_start();
?>
<form action=LaneTextStringPage.php onsubmit="laneText.saveString(this); return false;">

<p class="ichunk">Use this utility to enter and edit the lines of text that appear on
receipts, the lane Welcome screen, and elsewhere.
<br />If your receipts have no headers or footers or they are wrong this is the place to fix that.
</p>
<hr />
<h4 class="install">Select Type of Text</h4>
<p class="ichunk2 ichunk3">Select a type of text string to view and edit the current entries.
</p>
<div class="form-group">
    <select class="form-control" name="type" class="form-control" 
        onchange="laneText.loadStrings(this.value);">
    <option value="">Choose...</option>
<?php
foreach ($this->TRANSLATE as $short=>$long) {
    printf('<option value="%s">%s</option>', $short, $long);
}
?>
</select>
</div>
<p id="instructions-p">
<br />To add an additional line fill out the last row marked NEW.
<br />To delete a line check the box in the right-hand column (trash).
<br />The maximum length of a line is 55 characters.
</p>
<div id="line-div">
</div>
<p>
    <button type="submit" class="btn btn-default">Save Changes</button>
</p>
</form>

<?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '
            <p>
            This editor manages strings of text that appear
            on lane receipts as well as certain sections of the screen.
            First select the type of text you wish to view or edit, then
            adjust the line(s) of text.
            </p>
            <ul>
            <li>Receipt Header lines appear at the beginnning of a receipt.
            Values ending in ".bmp" are special and will print the specified
            bitmap image rather than a line of text.</li>
            <li>Receipt Footer lines appear at the end of a receipt.</li>
            <li>Check Endorsement lines are printed on the back of paper checks
            when the receipt printer is configured for endorsing.</li>
            <li>Welcome On-screen Message is displayed when the cashier first
            logs in and has not yet rung any items.</li>
            <li>Goodbye On-screen Message is displayed at the conclusion of a
            transaction.</li>
            <li>Training On-screen Message is displayed when a cashier first
            logs into training mode.</li>
            <li>Store Charge Slip is printed on paper signature slips used
            for store charge (AR) accounts.</li>
            </ul>
        ';
    }

    public function unitTest($phpunit)
    {
        if (!class_exists('LaneTextTests', false)) {
            include(dirname(__FILE__) . '/LaneTextTests.php');
        }
        $tester = new LaneTextTests($this->connection, $this->config, $this->logger);
        $tester->testHtml($this, $phpunit);
        $tester->testAddLine($this, $phpunit);
        $tester->testEditLine($this, $phpunit);
        $tester->testDeleteLine($this, $phpunit);
    }

// LaneTextStringPage  
}

FannieDispatch::conditionalExec();

