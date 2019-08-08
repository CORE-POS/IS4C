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

use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\Undo;
use COREPOS\pos\lib\TransRecord;

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class TransList extends NoInputCorePage 
{
    function preprocess()
    {
        if ($this->form->tryGet('selectlist', false) !== false) {
            if (!empty($this->form->selectlist)) {
                list($emp, $reg, $trans) = explode('::', $this->form->selectlist);
                try {
                    Undo::reverseTransaction($emp, $reg, $trans);
                    TransRecord::addcomment("RO FOR $emp-$reg-$trans");
                    TransRecord::finalizeTransaction();
                    $dbc = Database::tDataConnect();
                    $againP = $dbc->prepare("INSERT INTO localtemptrans
                        SELECT * FROM localtranstoday
                        WHERE upc NOT IN ('TAX','DISCOUNT','TAXLINEITEM','DISCLINEITEM')
                            AND emp_no=?
                            AND register_no=?
                            AND trans_no=?");
                    $againR = $dbc->execute($againP, array($emp, $reg, $trans));
                    $upP = $dbc->prepare("UPDATE localtemptrans SET trans_no=?, datetime=?");
                    $upR = $dbc->execute($upP, array(CoreLocal::get('transno'), date('Y-m-d H:i:s')));
                } catch (Exception $ex) {
                }
            }
            $this->change_page($this->page_url."gui-modules/pos2.php");

            return false;
        } elseif ($this->form->tryGet('preview') !== '') {
            echo $this->previewTrans($this->form->preview);
            return false;
        }

        return true;
    }

    function head_content()
    {
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js?date=20180611"></script>
        <script type="text/javascript">
        function updatePreview(trans) {
            $.ajax({
                data: 'preview='+trans
            }).done(function(resp) {
                $('#receipt-preview').html(resp);
            });
        }
        </script>
        <?php
        $this->addOnloadCommand("selectSubmit('#selectlist', '#selectform', false, false)\n");
        $this->addOnloadCommand("\$('#selectlist').focus();\n");
    }

    private function getTransactions()
    {
        $dbc = Database::tDataConnect();
        $filterQ = "SELECT description, emp_no, register_no, trans_no
            FROM localtranstoday
            WHERE trans_subtype='CM'
                AND description LIKE 'RO FOR%'
                AND datetime >= " . $dbc->curdate();
        $filterR = $dbc->query($filterQ);
        $filtered = array();
        while ($row = $dbc->fetchRow($filterR)) {
            list(,,$original) = explode(' ', $row['description']);
            $filtered[$original] = true;
            $filtered[$row['emp_no'] . '-' . $row['register_no'] . '-' . $row['trans_no']] = true;
        }
        $query = "
            SELECT register_no, 
                emp_no, 
                trans_no, 
                SUM(CASE 
                    WHEN trans_type='T' AND department=0 THEN -1 * total 
                    ELSE 0 
                END) AS total 
            FROM localtranstoday 
            WHERE register_no = ?
                AND emp_no = ?
                AND datetime >= " . $dbc->curdate() . "
            GROUP BY register_no, 
                emp_no, 
                trans_no 
            ORDER BY trans_no DESC";
        $args = array($this->session->get('laneno'), $this->session->get('CashierNo')); 
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);
        $ret = array();
        while ($row = $dbc->fetchRow($result)) {
            $transNum = $row['emp_no'] . '-' . $row['register_no'] . '-' . $row['trans_no'];
            if (isset($filtered[$transNum])) {
                continue;
            }
            $ret[] = $row;
        }

        return $ret;
    }

    private function previewTrans($trans)
    {
        list($emp, $reg, $tID) = explode('::', $trans);
        $dbc = Database::tDataConnect();
        $previewP = $dbc->prepare("
            SELECT description
            FROM localtranstoday
            WHERE emp_no=?
                AND register_no=?
                AND trans_no=?
                AND trans_type <> 'L'
                AND trans_status NOT IN ('X', 'Z')
            ORDER BY trans_id");
        $previewR = $dbc->execute($previewP, array($emp, $reg, $tID));
        $ret = '';
        $count = 0;
        while ($row = $dbc->fetchRow($previewR)) {
            $ret .= $row['description'] . '<br />';
            $count++;
            if ($count > 10) {
                break;
            }
        }

        return $ret;
    }
    
    function body_content()
    {
        ?>
        <div class="baseHeight">
        <div class="listbox">
        <form name="selectform" method="post" id="selectform" 
            action="<?php echo AutoLoader::ownURL(); ?>" >
        <b>Re-open Transaction</b><br />
        <select name="selectlist" size="15" id="selectlist"
            onblur="$('#selectlist').focus()" onchange="updatePreview(this.value);" >

        <?php
        $selected = "selected";
        $first = false;
        foreach ($this->getTransactions() as $row) {
            echo "<option value='".$row["emp_no"]."::".$row["register_no"]."::".$row["trans_no"]."'";
            echo $selected;
            echo ">lane ".substr(100 + $row["register_no"], -2)." Cashier ".substr(100 + $row["emp_no"], -2)
                ." #".$row["trans_no"]." -- $".
                sprintf('%.2f',$row["total"]);
            $selected = "";
            if (!$first) {
                $first = $row['emp_no'] . '::' . $row['register_no'] . '::' . $row['trans_no'];
            }
        }
        ?>
        </select>
        </div>
        <div class="listbox" id="receipt-preview" style="height: 15; font-size: 85%;">
            <?php echo ($first) ? $this->previewTrans($first) : ''; ?>
        </div>
        <?php
        if ($this->session->get('touchscreen')) {
            echo '<div class="listbox listboxText">'
                . DisplayLib::touchScreenScrollButtons('#selectlist')
                . '</div>';
        }
        ?>
        <div class="listboxText coloredText centerOffset">
        <?php echo _("use arrow keys to navigate"); ?><br />
        <p>
            <button type="submit" class="pos-button wide-button coloredArea">
            <?php echo _('Re-Open'); ?> <span class="smaller"><?php echo _('[enter]'); ?></span>
            </button>
        </p>
        <p>
            <button type="submit" class="pos-button wide-button errorColoredArea"
            onclick="$('#selectlist').append($('<option>').val(''));$('#selectlist').val('');">
            <?php echo _('Cancel'); ?> <span class="smaller"><?php echo _('[clear]'); ?></span>
        </button></p>
        </div>
        </form>
        <div class="clear"></div>
        </div>

        <?php
    } // END body_content() FUNCTION

    public function unitTest($phpunit)
    {
        $this->form = new COREPOS\common\mvc\ValueContainer();
        $phpunit->assertEquals(true, $this->preprocess());
        $this->form->selectlist = '';
        $phpunit->assertEquals(false, $this->preprocess());
    }
}

AutoLoader::dispatch();

