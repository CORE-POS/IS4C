<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class GumPromissoryPage extends FannieRESTfulPage 
{
    public function preprocess()
    {
        $acct = FormLib::get('id');
        $this->header = 'Promissory Note' . ' : ' . $acct;
        $this->title = 'Promissory Note' . ' : ' . $acct;

        return parent::preprocess();
    }

    public function get_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $this->loan = new GumLoanAccountsModel($dbc);
        $this->loan->accountNumber($this->id);
        if (!$this->loan->load()) {
            echo _('Error: account') . ' ' . $this->id . ' ' . _('does not exist');
            return false;
        }

        $this->custdata = new CustdataModel($dbc);
        $this->custdata->whichDB($FANNIE_OP_DB);
        $this->custdata->CardNo($this->loan->card_no());
        $this->custdata->personNum(1);
        $this->custdata->load();

        $this->meminfo = new MeminfoModel($dbc);
        $this->meminfo->whichDB($FANNIE_OP_DB);
        $this->meminfo->card_no($this->loan->card_no());
        $this->meminfo->load();

        $this->taxid = new GumTaxIdentifiersModel($dbc);
        $this->taxid->card_no($this->loan->card_no());

        $this->settings = new GumSettingsModel($dbc);

        return true;
    }

    public function css_content()
    {
        return '
            table#infoTable td {
                text-align: center;
            }
            table#infoTable td.header {
                font-weight: bold;
            }
            table#infoTable td.top {
                border-top: solid 1px black;
            }
            table#infoTable td.left {
                border-left: solid 1px black;
            }
            table#infoTable td.right {
                border-right: solid 1px black;
            }
            table#infoTable td.bottom {
                border-bottom: solid 1px black;
            }
            table#infoTable td.noborder {
                border: 0;
                line-height: 2px;
            }
            table#infoTable td.paragraph {
                border: 0;
                text-align: left;
            }
        ';
    }

    public function get_id_view()
    {
        global $FANNIE_URL;
        $ret = '';

        $ret .= '<table id="infoTable" cellspacing="0" cellpadding="4">';
        $ret .= '<tr>';
        $ret .= '<td class="header top left right">Lender</td>';
        $ret .= '<td class="header top right">Borrower</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left right">'. $this->custdata->FirstName() . ' ' . $this->custdata->LastName() . '</td>';
        $ret .= '<td class="right">' . 'Whole Foods Community Co-op, Inc.' .'</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left right">'. $this->meminfo->street() . '</td>';
        $ret .= '<td class="right">' . '610 E 4th St' .'</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left right">'. $this->meminfo->city() . ', ' . $this->meminfo->state() . ' ' . $this->meminfo->zip() . '</td>';
        $ret .= '<td class="right">' . 'Duluth, MN 55805' .'</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ssn = 'Unknown';
        if ($this->taxid->load()) {
            $ssn = 'xxx-xx-' . $this->taxid->maskedTaxIdentifier();
        }
        $ret .= '<td class="left right bottom">' . $ssn . '</td>';
        $tax_id = 'xx-xxxxxxx';
        $ret .= '<td class="right bottom">' . $tax_id . '</td>';
        $ret .= '</tr>';
        $ret .= '<tr><td class="noborder" colspan="2">&nbsp;</td></tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left top">Loan Date: ' . date('m/d/Y', strtotime($this->loan->loanDate())) . '</td>';
        $ret .= '<td class="right top">Interest Rate: ' . number_format($this->loan->interestRate() * 100, 2) . '%</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left bottom">Principal Sum: $' . number_format($this->loan->principal(), 2) . '</td>';
        $ld = strtotime($this->loan->loanDate());
        $ret .= '<td class="right bottom">Maturity Date: ' . date('m/d/Y', mktime(0, 0, 0, date('n', $ld)+$this->loan->termInMonths(), date('j', $ld), date('Y', $ld))) . '</td>';
        $ret .= '</tr>';

        $ret .= '<tr>';
        $ret .= '<td class="paragraph" colspan="2">
        For value received, the Borrower indicated above, a Minnesota cooperative corporation (hereinafter “Borrower”) hereby promises to pay the lender indicated above (hereinafter “Lender”), a current owner of the Borrower, whose address is indicated above, or his or her successors, the principal sum indicated above together with interest thereon at the interest rate indicated above .  Interest shall be calculated and compounded annually.   Upon maturity of this Note on the date set forth above, interest and principal shall be paid in full.  There shall be no penalty for prepayment or early payment of this Note by the Borrower."
            </td>';
        $ret .= '</tr>';

        $ret .= '<tr>';
        $ret .= '<td class="paragraph" colspan="2">
        All payments shall be made to the address of the Lender set forth above.  It is the responsibility of the Lender to inform the Borrower of any change in address.
            </td>';
        $ret .= '</tr>';

        $ret .= '<tr>';
        $ret .= '<td class="paragraph" colspan="2">
            Lender understands that there are other loans made to the Borrower that have a security interest in the assets of the cooperative and that are superior to the Note of the Lender.  Lender understands that there are unsecured creditors and other lenders to the cooperative that have interests that may be superior to that of the Lender.
            </td>';
        $ret .= '</tr>';

        $ret .= '<tr>';
        $ret .= '<td class="paragraph" colspan="2">
            Borrower shall be in default if it fails to make prompt payment of this Note and the compound interest thereon as of the above maturity date.  The Lender may proceed to enforce payment of the indebtedness and to exercise any or all rights afforded to the Lender under the law.
            </td>';
        $ret .= '</tr>';

        $ret .= '<tr>';
        $ret .= '<td class="top left right header">Lender Signature</td>';
        $ret .= '<td class="top right header">Borrower Signature</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="left right bottom">&nbsp;<br />&nbsp;</td>';
        $ret .= '<td class="right bottom">&nbsp;<br />&nbsp;</td>';
        $ret .= '</tr>';

        $ret .= '</table>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

