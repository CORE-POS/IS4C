<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

use COREPOS\pos\lib\gui\BasicCorePage;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\parser\parse\ScrollItems;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

/* wraps around an undone transaction to limit editing options
   CL cancels the attempt (wraps to input "CN")
   {Enter} finishes the transaction (wraps to input "0CA")
*/
class undo_confirm extends BasicCorePage 
{
    private $msg;

    function body_content()
    {
        echo $this->input_header();
        ?>
        <div class="baseHeight">
        <?php 
            echo empty($this->msg) ? DisplayLib::lastPage() : $this->msg;
        ?>
        </div>
        <?php
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";
        $this->addOnloadCommand("\$('#reginput').keyup(function(ev){
                    switch(ev.keyCode){
                    case 33:
                        \$('#reginput').val('U11');
                        \$('#formlocal').submit();
                        break;
                    case 38:
                        \$('#reginput').val('U');
                        \$('#formlocal').submit();
                        break;
                    case 34:
                        \$('#reginput').val('D11');
                        \$('#formlocal').submit();
                        break;
                    case 40:
                        \$('#reginput').val('D');
                        \$('#formlocal').submit();
                        break;
                    }
                });\n");
        $this->addOnloadCommand("undoInstructions();");
    }

    function head_content(){
        ?>
        <script type="text/javascript">
        function undoInstructions(){
            var str = '<span style="padding:3px;background:#fff;"><b>[Enter] to accept, [Clear] to reject</b></span> ';
            var cur = $('.notices').html();
            $('.notices').html(str+cur);
        }
        </script>
        <?php
    }

    function preprocess()
    {
        $this->msg = "";
        try {
            $input = strtoupper($this->form->reginput);
            switch($input) {
                case 'CL':
                    return $this->cancel();
                case '':
                    return $this->confirm();
                case 'U':
                case 'U11':
                case 'D':
                case 'D11':
                    $this->msg = $this->scroll($input);
                    break;
                default:
                    break;
            }
        } catch (Exception $ex) {}

        return true;
    }

    private function assignTransaction()
    {
        $dbc = Database::tDataConnect();
        $emp = $this->session->get('CashierNo');
        $trans = $this->session->get('transno');
        $dbc->query('UPDATE localtemptrans SET
                    emp_no='.((int)$emp).',
                    trans_no='.((int)$trans).'
                    WHERE
                    emp_no<>'.((int)$emp).' OR
                    trans_no<>'.((int)$trans));
    }

    private function cancel()
    {
        // cancel the transaction instead

        /**
          Unify emp_no & trans_no records in the
          database. Logging records from authentication
          may have different values. This step normalizes
          the transaction. In this case I'm restoring
          the logged in cashier's info immediately
          and assigning the entire transaction to that
          cashier. This is simpler than the case below
          and since it's canceled it doesn't matter if
          the tender records are assigned to the original
          cashier or the current cashier.
        */
        Database::loadglobalvalues();
        $this->assignTransaction();
        $this->change_page($this->page_url."gui-modules/pos2.php?reginput=CN&repeat=1");

        return false;
    }

    private function confirm()
    {
        // use zero cash to finish transaction

        /**
          Unify emp_no & trans_no records in the
          database. Logging records from authentication
          may have different values. This step
          normalizes the transaction. When AjaxEnd.php
          runs to close the transaction, the actual
          logged in cashier's values will be restored
          via Database::loadglobalvalues().
        */
        $this->assignTransaction();
        $this->change_page($this->page_url."gui-modules/pos2.php?reginput=0CA&repeat=1");

        return false;
    }

    private function scroll($dir)
    {
        // just use the parser module here
        // for simplicity; all its really
        // doing is updating a couple session vars
        $scroll = new ScrollItems($this->session);
        $json = $scroll->parse($dir);
        return $json['output'];
    }

    public function unitTest($phpunit)
    {
        $scrolled = $this->scroll('D2');
        ob_start();
        $this->body_content();
        $body = ob_get_clean();
        $phpunit->assertNotEquals(0, strlen($scrolled));
        $phpunit->assertNotEquals(0, strlen($body));
        ob_start();
        $phpunit->assertEquals(false, $this->confirm());
        $phpunit->assertEquals(false, $this->cancel());
        ob_get_clean();
    }
}

AutoLoader::dispatch();

