<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\lib\TransRecord;

/**
  @class DeptLib
*/
class DeptLib 
{
    private $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
      Add an open ring to a department
      @param $price amount in cents (100 = $1)
      @param $dept POS department
      @ret an array of return values
      @returns An array. See Parser::default_json()
       for format explanation.
    */
    // @hintable
    public function deptkey($price, $dept,$ret=array()) 
    {
        if ($this->session->get("quantity") == 0 && $this->session->get("multiple") == 0) {
            $this->session->set("quantity",1);
        }

        $ringAsCoupon = false;
        if (substr($price,0,2) == 'MC') {
            $ringAsCoupon = true;
            $price = substr($price,2);
        }
            
        if (!is_numeric($dept) || !is_numeric($price) || strlen($price) < 1 || strlen($dept) < 2) {
            $ret['output'] = DisplayLib::inputUnknown();
            $this->session->set("quantity",1);
            $ret['udpmsg'] = 'errorBeep';
            return $ret;
        }

        $price = $price/100;
        $dept = $dept/10;
        $discount = 0;

        $dbc = Database::pDataConnect();
        $row = $this->getDepartment($dbc, $dept);

        if ($row['line_item_discount'] && $this->session->get('itemPD') > 0 && $this->session->get('SecurityLineItemDiscount') == 30 && $this->session->get('msgrepeat')==0){
            $ret['main_frame'] = MiscLib::baseURL() . "gui-modules/adminlogin.php?class=COREPOS-pos-lib-adminlogin-LineItemDiscountAdminLogin";
            return $ret;
        } elseif ($row['line_item_discount'] && $this->session->get('itemPD') > 0) {
            $discount = MiscLib::truncate2($price * ($this->session->get('itemPD')/100.00));
            $price -= $discount;
        }
        $discount = $discount * $this->session->get('quantity');

        if ($row === false) {
            $ret['output'] = DisplayLib::boxMsg(
                _("department unknown"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
            $ret['udpmsg'] = 'errorBeep';
            $this->session->set("quantity",1);
        } elseif ($ringAsCoupon) {
            $ret = $this->deptCouponRing($row, $price, $ret);
        } else {
            if ($row['dept_see_id'] > 0) {
                list($badAge, $ret) = PrehLib::ageCheck($row['dept_see_id'], $ret);
                if ($badAge === true) {
                    return $ret;
                }
            }

            $ret = $this->deptOpenRing($row, $price, $discount, $ret);
        }

        $this->session->set("quantity",0);
        $this->session->set("itemPD",0);

        return $ret;
    }

    // @hintable
    private function getDepartment($dbc, $dept)
    {
        $query = "SELECT dept_no,
            dept_name,
            dept_tax,
            dept_fs,
            dept_limit,
            dept_minimum,
            dept_discount,";
        if ($this->session->get('NoCompat') == 1) {
            $query .= 'dept_see_id, memberOnly, line_item_discount';
        } else {
            $table = $dbc->tableDefinition('departments');
            if (isset($table['dept_see_id'])) {
                $query .= 'dept_see_id,';
            } else {
                $query .= '0 as dept_see_id,';
            }
            if (isset($table['memberOnly'])) {
                $query .= 'memberOnly,';
            } else {
                $query .= '0 AS memberOnly,';
            }
            if (isset($table['line_item_discount'])) {
                $query .= 'line_item_discount';
            } else {
                $query .= '1 AS line_item_discount';
            }
        }
        $query .= " FROM departments 
                    WHERE dept_no = " . ((int)$dept);
        $result = $dbc->query($query);

        return $dbc->numRows($result) === 0 ? false : $dbc->fetchRow($result);
    }

    // @hintable
    private function deptCouponRing($dept, $price, $ret)
    {
        $query2 = "select department, sum(total) as total from localtemptrans where department = "
            .$dept['dept_no']." group by department";

        $db2 = Database::tDataConnect();
        $result2 = $db2->query($query2);

        $numRows2 = $db2->numRows($result2);
        if ($numRows2 == 0) {
            $ret['output'] = DisplayLib::boxMsg(
                _("no item found in")."<br />".$dept["dept_name"],
                '',
                false,
                DisplayLib::standardClearButton()
            );
            $ret['udpmsg'] = 'errorBeep';
        } else {
            $row2 = $db2->fetchRow($result2);
            if ($price > $row2["total"]) {
                $ret['output'] = DisplayLib::boxMsg(
                    _("coupon amount greater than department total"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
                $ret['udpmsg'] = 'errorBeep';
            } else {
                TransRecord::addRecord(array(
                    'description' => $dept['dept_name'] . ' Coupon',
                    'trans_type' => 'I',
                    'trans_subtype' => 'CP',
                    'trans_status' => 'C',
                    'department' => $dept['dept_no'],
                    'quantity' => 1,
                    'ItemQtty' => 1,
                    'unitPrice' => -1 * $price,
                    'total' => -1 * $price,
                    'regPrice' => -1 * $price,
                    'voided' => 0,
                ));
                $this->session->set("ttlflag",0);
                $ret['output'] = DisplayLib::lastpage();
                $ret['redraw_footer'] = True;
                $ret['udpmsg'] = 'goodBeep';
            }
        }

        return $ret;
    }

    // @hintable
    private function memberOnlyDept($dept, $ret)
    {
        /**
          Enforce memberOnly flag
        */
        $modified = false;
        if ($dept['memberOnly'] > 0) {
            switch ($dept['memberOnly']) {
                case 1: // member only, no override
                    if ($this->session->get('isMember') == 0) {
                        $ret['output'] = DisplayLib::boxMsg(_(
                                            _('Department is member-only'),
                                            _('Enter member number first'),
                                            false,
                                            array(_('Member Search [ID]') => 'parseWrapper(\'ID\');', _('Dismiss [clear]') => 'parseWrapper(\'CL\');')
                                        ));
                        $modified = true;
                    }
                    break; 
                case 2: // member only, can override
                    if ($this->session->get('isMember') == 0) {
                        if ($this->session->get('msgrepeat') == 0 || $this->session->get('lastRepeat') != 'memberOnlyDept') {
                            $this->session->set('boxMsg', _(
                                _('Department is member-only<br />') .
                                _('[enter] to continue, [clear] to cancel')
                            ));
                            $this->session->set('lastRepeat', 'memberOnlyDept');
                            $ret['main_frame'] = MiscLib::baseURL() . 'gui-modules/boxMsg2.php';
                            $modified = true;
                        } else if ($this->session->get('lastRepeat') == 'memberOnlyDept') {
                            $this->session->set('lastRepeat', '');
                        }
                    }
                    break;
                case 3: // anyone but default non-member
                    if ($this->session->get('memberID') == '0') {
                        $ret['output'] = DisplayLib::boxMsg(_(
                                            _('Department is member-only'),
                                            _('Enter member number first'),
                                            false,
                                            array(_('Member Search [ID]') => 'parseWrapper(\'ID\');', _('Dismiss [clear]') => 'parseWrapper(\'CL\');')
                                        ));
                        $modified = true;
                    } else if ($this->session->get('memberID') == $this->session->get('defaultNonMem')) {
                        $ret['output'] = DisplayLib::boxMsg(_(
                                            _('Department not allowed with this member'),
                                            '',
                                            false,
                                            DisplayLib::standardClearButton()
                                        ));
                        $modified = true;
                    }
                    break;
            }
        }

        return array($ret, $modified);
    }

    // @hintable
    private function deptOpenRing($dept, $price, $discount, $ret)
    {
        list($ret, $memberOnly) = $this->memberOnlyDept($dept, $ret);
        if ($memberOnly === true) {
            return $ret;
        }

        $deptmax = $dept['dept_limit'] ? $dept['dept_limit'] : 0;
        $deptmin = $dept['dept_minimum'] ? $dept['dept_minimum'] : 0;

        $tax = $dept["dept_tax"];
        $foodstamp = $dept['dept_fs'] != 0 ? 1 : 0;
        $deptDiscount = $dept["dept_discount"];
        list($tax, $foodstamp, $deptDiscount) = PrehLib::applyToggles($tax, $foodstamp, $deptDiscount);

        $minMaxButtons = array(
            _('Confirm [enter]') => '$(\'#reginput\').val(\'\');submitWrapper();',
            _('Cancel [clear]') => '$(\'#reginput\').val(\'CL\');submitWrapper();',
        );
        // remove Confirm button/text if hard limits enforced
        if ($this->session->get('OpenRingHardMinMax')) {
            array_shift($minMaxButtons);
        }

        if ($price > $deptmax && ($this->session->get('OpenRingHardMinMax') || $this->session->get("msgrepeat") == 0)) {
            $this->session->set("boxMsg","$".$price." "._("is greater than department limit"));
            $this->session->set('boxMsgButtons', $minMaxButtons);
            $ret['main_frame'] = MiscLib::baseURL().'gui-modules/boxMsg2.php';
        } elseif ($price < $deptmin && ($this->session->get('OpenRingHardMinMax') || $this->session->get("msgrepeat") == 0)) {
            $this->session->set("boxMsg","$".$price." "._("is lower than department minimum"));
            $this->session->set('boxMsgButtons', $minMaxButtons);
            $ret['main_frame'] = MiscLib::baseURL().'gui-modules/boxMsg2.php';
        } else {
            
            TransRecord::addRecord(array(
                'upc' => $price . 'DP' . $dept['dept_no'],
                'description' => $dept['dept_name'],
                'trans_type' => 'D',
                'department' => $dept['dept_no'],
                'quantity' => $this->session->get('quantity'),
                'ItemQtty' => $this->session->get('quantity'),
                'unitPrice' => $price,
                'total' => $price * $this->session->get('quantity'),
                'regPrice' => $price,
                'tax' => $tax,
                'foodstamp' => $foodstamp,
                'discountable' => $deptDiscount,
                'voided' => 0,
                'discount' => $discount,
            ));
            $this->session->set("ttlflag",0);
            $this->session->set("msgrepeat",0);

            if ($this->session->get("itemPD") > 0) {
                TransRecord::adddiscount($discount, $dept);
            }

            $ret['output'] = DisplayLib::lastpage();
            $ret['redraw_footer'] = true;
            $ret['udpmsg'] = 'goodBeep';
        }

        return $ret;
    }

}

