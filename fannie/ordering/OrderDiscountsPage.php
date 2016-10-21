<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}

class OrderDiscountsPage extends FannieRESTfulPage
{
    protected $must_authenticate = true;
    protected $header = 'Set Special Order Discounts';
    protected $title = 'Set Special Order Discounts';
    public $description = '[Special Order Discounts] assigns markups (from wholesale) or markdown
    (from retail) based on member type.';
    public $page_set = 'Special Orders';

    /**
      Generate a default record for every
      member type
    */
    private function init()
    {
        $res = $this->connection->query('
            SELECT memtype
            FROM memtype
            WHERE memtype NOT IN (
                SELECT memType
                FROM ' . $this->config->get('TRANS_DB') . $this->connection->sep() . 'SpecialOrderMemDiscounts
            )
        ');
        $prep = $this->connection->prepare('
            INSERT INTO ' . $this->config->get('TRANS_DB') . $this->connection->sep() . 'SpecialOrderMemDiscounts
                (memType)
            VALUES
                (?)
        ');
        while ($row = $this->connection->fetchRow($res)) {
            $this->connection->execute($prep, array($row['memtype']));
        }
    }

    public function preprocess()
    {
        $this->init();
        return parent::preprocess();
    }

    public function post_id_handler()
    {
        $model = new SpecialOrderMemDiscountsModel($this->connection);
        $model->whichDB($this->config->get('TRANS_DB'));
        try {
            for ($i=0; $i<count($this->id); $i++) {
                $model->memType($this->id[$i]);
                $model->type($this->form->type[$i]);
                $model->amount($this->form->amt[$i]/100.00);
                $model->save();
            }
        } catch (Exception $ex) {
        }

        return filter_input(INPUT_SERVER, 'PHP_SELF');
    }

    protected function get_view()
    {
        $ret = '<form method="post">
            <table class="table table-bordered">
            <tr>
                <th>Member Type</th>
                <th>Pricing Style</th>
                <th>% Mark Up/Down</th>
            </tr>';
        $prep = $this->connection->prepare('
            SELECT m.memDesc,
                s.memType,
                s.type,
                s.amount
            FROM ' . $this->config->get('TRANS_DB') . $this->connection->sep() . 'SpecialOrderMemDiscounts AS s
                INNER JOIN memtype AS m ON s.memType=m.memtype
            ORDER BY m.memDesc
        ');
        $res = $this->connection->execute($prep);
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr>
                <td>%s<input type="hidden" name="id[]" value="%d" /></td>
                <td>%s</td>
                <td class="col-sm-1">
                    <div class="input-group">
                        <input type="text" class="form-control price-field" name="amt[]" value="%.2f" />
                        <span class="input-group-addon">%%</span>
                    </div>
                </td>
                </tr>',
                $row['memDesc'], $row['memType'],
                $this->markUpDown($row['type']),
                $row['amount']*100
            );
        }
        $ret .= '</table>
            <p><button type="submit" class="btn btn-default btn-core">Save</button></p>
            </form>';

        return $ret;
    }

    private function markUpDown($val)
    {
        $opts = array(
            'markdown' => 'Mark Down From Retail',
            'markup' => 'Mark Up From Cost',
        );
        $ret = '<select name="type[]" class="form-control">';
        foreach ($opts as $opt => $label) {
            $ret .= sprintf('<option %s value="%s">%s</option>',
                ($opt == $val ? 'selected' : ''),
                $opt, $label);
        }
        $ret .= '</select>';

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $this->init();
        $body = $this->get_view();
        $phpunit->assertNotEquals(0, strlen($body));
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->id = array(1);
        $this->id = array(1);
        $form->type = array('markdown');
        $form->amt = array(10);
        $this->setForm($form);
        $this->post_id_handler();
        $body2 = $this->get_view();
        $phpunit->assertNotEquals($body, $body2);
    }
}

FannieDispatch::conditionalExec();


