<?php
/*******************************************************************************

    Copyright 2011,2013 Whole Foods Co-op

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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TaxRateEditor extends FannieRESTfulPage 
{
    protected $title = "Fannie : Tax Rates";
    protected $header = "Tax Rates";

    public $description = '[Tax Rates] defines applicable sales tax rates.';
    public $has_unit_tests = true;

    function post_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        try {
            $desc = $this->form->desc;
            $rate = $this->form->rate;
            $account = $this->form->account;
            $delete_flag = isset($this->form->del) ? $this->form->del : array();
            $tax_id = 1;
            $trun = $dbc->query("TRUNCATE TABLE taxrates");
            $model = new TaxRatesModel($dbc);
            for ($j=0;$j<count($desc);$j++) {
                if (empty($desc[$j]) || empty($rate[$j])) {
                    continue;
                }
                if (in_array($tax_id, $delete_flag)) {
                    continue;
                }

                $model->id($tax_id);
                $model->rate($rate[$j]);
                $model->description($desc[$j]);
                $model->salesCode($account[$j]);
                $saved = $model->save();
                if ($saved) {
                    $this->add_onload_command("showBootstrapAlert('#alert-area', 'success', 'Saved {$desc[$j]}');");
                } else {
                    $this->add_onload_command("showBootstrapAlert('#alert-area', 'success', 'Error saving {$desc[$j]}');");
                }
                $tax_id++;
            }
        } catch (Exception $e) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'Invalid values submitted');");
        }

        return true;
    }

    function post_view()
    {
        return $this->get_view();
    }

    function get_view()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $model = new TaxRatesModel($dbc);

        $ret = '<div id="alert-area"></div>';
        $ret .= '<form action="TaxRateEditor.php" method="post">';
        $ret .= '<table class="table">';
        $ret .= '<tr><th>Description</th><th>Rate</th><th>Account #</th><th>Delete</th></tr>';
        $ret .= '<tr><td>NoTax</th><td>0.00</td><td colspan="2">&nbsp;</td></tr>';
        foreach ($model->find('id') as $tax) {
            $ret .= sprintf('
                <tr>
                    <td><input type="text" name="desc[]" value="%s" class="form-control" /></td>
                    <td><input type="text" name="rate[]" value="%f" class="form-control" /></td>
                    <td><input type="text" name="account[]" value="%s" class="form-control" /></td>
                    <td><input type="checkbox" name="del[]" value="%d" /></td>
                </tr>',
                $tax->description(), $tax->rate(), $tax->salesCode(), $tax->id()
            );
        }
        $ret .= '<tr>
            <td><input type="text" name="desc[]" class="form-control" /></td>
            <td><input type="text" name="rate[]" class="form-control" /></td>
            <td><input type="text" name="account[]" class="form-control" /></td>
            <td>NEW</td></tr>';
        $ret .= "</table>";
        $ret .= '<p><button type="submit" value="1" name="sub"
                        class="btn btn-default">Save Tax Rates</button></p>';
        $ret .= '</form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Manage sales tax rates. Rates should be 
            specified as decimals - for example, 0.05 means 5%.
            Entries should be effective tax rates as opposed to 
            invdividual taxes. If, for example, there is a state
            sales tax as well as city sales tax that applies to
            taxable items, the <em>effective</em> rate is both
            rates added together.
            </p>
            <p>
            The account number field is provided for mapping sales
            tax collected to chart of accounts numbers.
            </p> 
            ';
    }

    /**
      Create a tax rate, update it, delete it.
    */
    public function unitTest($phpunit)
    {
        $get = $this->get_view();
        $phpunit->assertNotEquals(0, strlen($get)); 

        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->desc = array('test rate');
        $form->rate = array('0.05');
        $form->account = array('101');
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $rate = new TaxRatesModel($dbc);

        $this->testInsert($phpunit, $rate, $form);

        $this->testUpdate($phpunit, $rate, $form);

        $this->testDelete($phpunit, $rate, $form);
    }

    private function testInsert($phpunit, $rate, $form)
    {
        $this->setForm($form);
        $post = $this->post_handler();
        $phpunit->assertInternalType('bool', $post);
        $rate->id(1);
        $phpunit->assertEquals(true, $rate->load());
        $phpunit->assertEquals('test rate', $rate->description());
        $phpunit->assertEquals(0.05, $rate->rate());
        $phpunit->assertEquals('101', $rate->salesCode());
    }

    private function testUpdate($phpunit, $rate, $form)
    {
        $form->rate = array('0.15');
        $this->setForm($form);
        $post = $this->post_handler();
        $rate->reset();
        $rate->id(1);
        $phpunit->assertEquals(true, $rate->load());
        $phpunit->assertEquals(0.15, $rate->rate());
    }

    private function testDelete($phpunit, $rate, $form)
    {
        $form->del = array(1);
        $this->setForm($form);
        $post = $this->post_handler();
        $rate->reset();
        $rate->id(1);
        $phpunit->assertEquals(false, $rate->load());
    }
}

FannieDispatch::conditionalExec();

