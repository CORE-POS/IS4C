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
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('PIKillerPage')) {
    include('lib/PIKillerPage.php');
}

class PISuspensionPage extends PIKillerPage {

    /**
      Route: get<id>
      Show suspension history for member <id>
      
      Route: get<id><edit>
      Show current suspension status for member <id>
      as editable form

      Route: post<id>
      Update suspension status for member <id> then
      redirect to PIMemberPage.php

      Route get<id><fixaddress>
      Special case clear suspension for bad address only

      Route get<id><fixpaperwork>
      Special case clear suspension for missing paperwork only
    */

    function preprocess(){
        global $FANNIE_OP_DB;
        $rc = new ReasoncodesModel(FannieDB::get($FANNIE_OP_DB));
        $this->__models['codes'] = $rc->find('mask');

        if (FormLib::get_form_value('id',False) !== False){
            $this->card_no = FormLib::get_form_value('id');
            $susp = $this->get_model(FannieDB::get($FANNIE_OP_DB),'SuspensionsModel',array('cardno'=>$this->card_no));
            if ($susp->load()) $this->__models['suspended'] = $susp;
        }

        $this->__routes[] = 'get<id><edit>';
        $this->__routes[] = 'get<id><fixaddress>';
        $this->__routes[] = 'get<id><fixpaperwork>';
        $this->__routes[] = 'get<id><fixequity>';
        $this->__routes[] = 'get<id><setpaperwork>';
        return parent::preprocess();
    }

    protected function get_id_edit_handler()
    {
        $this->card_no = $this->id;
        if (!FannieAuth::validateUserQuiet('editmembers')) {
            return $this->unknown_request_handler();
        }

        $this->title = 'Suspension Status : Member '.$this->card_no;

        $this->account = \COREPOS\Fannie\API\member\MemberREST::get($this->id);

        return true;
    }

    protected function get_id_fixaddress_handler(){
        global $FANNIE_OP_DB;
        $susp = new SuspensionsModel(FannieDB::get($FANNIE_OP_DB));
        $susp->cardno($this->id);
        if (!$susp->load()){
            // not currently suspended
            header('Location: PIMemberPage.php?id='.$this->id);
            return False;
        }
        else if ($susp->reasoncode() == 16){
            // clear suspension for bad address
            return $this->post_id_handler();
        }
        else
            return $this->unknown_request_handler();
    }

    protected function get_id_fixpaperwork_handler(){
        global $FANNIE_OP_DB;
        $susp = new SuspensionsModel(FannieDB::get($FANNIE_OP_DB));
        $susp->cardno($this->id);
        if (!$susp->load()){
            // not currently suspended
            header('Location: PIMemberPage.php?id='.$this->id);
            return False;
        }
        else if ($susp->reasoncode() == 256){
            // clear suspension for bad address
            return $this->post_id_handler();
        }
        else
            return $this->unknown_request_handler();
    }

    protected function get_id_fixequity_handler(){
        global $FANNIE_OP_DB;
        $susp = new SuspensionsModel(FannieDB::get($FANNIE_OP_DB));
        $susp->cardno($this->id);
        if (!$susp->load()){
            // not currently suspended
            header('Location: PIMemberPage.php?id='.$this->id);
            return False;
        }
        else if ($susp->reasoncode() == 4 || $susp->reasoncode() == 2){
            // clear suspension for bad address
            return $this->post_id_handler();
        }
        else
            return $this->unknown_request_handler();
    }

    protected function get_id_setpaperwork_handler()
    {
        global $FANNIE_OP_DB;
        if (!FannieAuth::validateUserQuiet('editmembers') && !FannieAuth::validateUserQuiet('editmembers_csc'))
            return $this->unknown_request_handler();
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $status = 'INACT';
        $code = 256;
        $cas_model = new CustomerAccountSuspensionsModel($dbc);
        $cas_model->card_no($this->id);
        $current_id = 0;
        $account = \COREPOS\Fannie\API\member\MemberREST::get($this->id);
        // suspend active account
        // create suspensions and log/history records
        // set custdata & meminfo to inactive
        $discount = 0;
        foreach ($account['customers'] as $c) {
            if ($c['accountHolder']) {
                $discount = $c['discount'];
                break;
            }
        }
        
        $susp = new SuspensionsModel($dbc);
        $susp->cardno($this->id);
        $susp->type( $status == 'TERM' ? 'T' : 'I' );           
        $susp->memtype1($account['customerTypeID']);
        $susp->memtype2($account['memberStatus']);
        $susp->suspDate(date('Y-m-d H:i:s'));
        $susp->reason('');
        $susp->mailflag($account['contactAllowed']);
        $susp->discount($discount);
        $susp->chargelimit($account['chargeLimit']);
        $susp->reasoncode($code);
        $susp->save();

        $cas_model->savedType($account['memberStatus']);
        $cas_model->savedMemType($account['customerTypeID']);
        $cas_model->savedDiscount($discount);
        $cas_model->savedChargeLimit($account['chargeLimit']);
        $cas_model->savedMailFlag($account['contactAllowed']);
        $cas_model->suspensionTypeID( $status == 'TERM' ? 2 : 1 );
        $cas_model->tdate(date('Y-m-d H:i:s'));
        $cas_model->username($this->current_user);
        $cas_model->reasonCode($code);
        $cas_model->active(1);
        $current_id = $cas_model->save();

        $history = new SuspensionHistoryModel($dbc);
        $history->username($this->current_user);
        $history->cardno($this->id);
        $history->reasoncode($code);
        $history->postdate(date('Y-m-d H:i:s'));
        $history->save();

        $json = array(
            'cardNo' => $this->id,
            'chargeLimit' => 0,
            'activeStatus' => $status,
            'customerTypeID' => 0,
            'contactAllowed' => 0,
            'customers' => array(),
        );
        foreach ($account['customers'] as $c) {
            $c['discount'] = 0;
            $json['customers'][] = $c;
        }
        \COREPOS\Fannie\API\member\MemberREST::post($this->id, $json);

        $callbacks = FannieConfig::config('MEMBER_CALLBACKS');
        foreach ($callbacks as $cb) {
            $obj = new $cb();
            $obj->run($this->id);
        }

        header('Location: PIMemberPage.php?id='.$this->id);
        return False;
    }

    protected function get_id_handler(){
        global $FANNIE_OP_DB;
        $this->card_no = $this->id;

        $this->title = 'Suspension History : Member '.$this->card_no;

        $this->__models['history'] = $this->get_model(FannieDB::get($FANNIE_OP_DB), 'SuspensionHistoryModel',
                        array('cardno'=>$this->id),'postdate');
        $this->__models['history'] = array_reverse($this->__models['history']);
    
        return True;
    }

    protected function get_id_view(){
        global $FANNIE_URL;
        ob_start();
        echo '<tr><td>';
        foreach($this->__models['history'] as $obj){
            echo '<b>'.$obj->postdate().' - status changed by '.$obj->username().'</b><br />';
            if ($obj->reasoncode() == -1)
                echo $obj->post().'<br /><hr />';
            else {
                $code = (int)$obj->reasoncode();
                foreach($this->__models['codes'] as $reason){
                    $mask = (int)$reason->mask();
                    if (($code & $mask) != 0) {
                        echo $reason->textStr().'<br />';
                    }
                }
                echo '<hr />';
            }
        }
        echo '</td></tr>';
        return ob_get_clean();
    }

    function get_id_edit_view(){
        ob_start();
        echo '<tr><td>';
        echo '<form action="PISuspensionPage.php" method="post">';
        echo '<input type="hidden" name="id" value="'.$this->id.'" />';
        echo "&nbsp;&nbsp;&nbsp;Reason for suspending membership ".$this->id.'<br />';
        echo "<select name=status>";
        $stats = array('INACT'=>'Inactive','TERM'=>'Termed','INACT2'=>'Term pending');
        foreach ($stats as $k=>$v){
            echo "<option value=".$k;
            if ($k == $this->account['activeStatus']) echo " selected";
            echo ">".$v."</option>";
        }
        echo "</select>";
        echo '<table>';
        $i = 0;
        $code = isset($this->__models['suspended']) ? (int)$this->__models['suspended']->reasoncode() : 0;
        foreach($this->__models['codes'] as $reason){
            echo '<tr><td>';
            echo '<input type="checkbox" id="pi_rc_'.$i.'" name="reasoncodes[]" value="'.$reason->mask().'"';
            $mask = (int)$reason->mask();
            if (($code & $mask) != 0) {
                echo ' checked';
            }
            echo ' /></td><td><label for="pi_rc_'.$i.'">'.$reason->textStr().'</label></td></tr>';
            $i++;
        }
        echo "</table>";
        echo "<input type=submit name=submit value=Update />";
        echo "</form>";
        echo '</td></tr>';

        return ob_get_clean();
    }

    function post_id_handler()
    {
        global $FANNIE_OP_DB;
        if (!FannieAuth::validateUserQuiet('editmembers') && !FannieAuth::validateUserQuiet('editmembers_csc'))
            return $this->unknown_request_handler();
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $status = FormLib::get_form_value('status','INACT');
        $codes = FormLib::get_form_value('reasoncodes',array());
        $code = 0;
        foreach($codes as $selected_code){
            $code = $code | ((int)$selected_code);
        }

        $cas_model = new CustomerAccountSuspensionsModel($dbc);
        $cas_model->card_no($this->id);
        $current_id = 0;
        $account = \COREPOS\Fannie\API\member\MemberREST::get($this->id);

        if ($code == 0) {
            // reactivate account
            // add history/log record, restore settings, delete suspensions record
            $history = new SuspensionHistoryModel($dbc);
            $history->username($this->current_user);
            $history->cardno($this->id);
            $history->reasoncode(-1);
            $history->post('Account reactivated');
            $history->postdate(date('Y-m-d H:i:s'));
            $history->save();

            $cas_model->reasonCode(0);
            $cas_model->suspensionTypeID(0);
            $cas_model->active(0);
            $cas_model->username($this->current_user);
            $cas_model->tdate(date('Y-m-d H:i:s'));
            $cas_model->save();

            if (isset($this->__models['suspended'])) {

                $json = array(
                    'cardNo' => $this->id,
                    'activeStatus' => '',
                    'memberStatus' => $this->__models['suspended']->memtype2(),
                    'customerTypeID' => $this->__models['suspended']->memtype1(),
                    'chargeLimit' => $this->__models['suspended']->chargelimit(),
                    'contactAllowed' => $this->__models['suspended']->mailflag(),
                    'customers' => array()
                );
                foreach ($account['customers'] as $c) {
                    $c['discount'] = $this->__models['suspended']->discount();
                    $c['chargeAllowed'] = 1;
                    $json['customers'][] = $c;
                }
                \COREPOS\Fannie\API\member\MemberREST::post($this->id, $json);

                $cust = new CustdataModel($dbc);
                $cust->CardNo($this->id);
                foreach ($cust->find() as $obj) {
                    $obj->pushToLanes();
                }
                $this->__models['suspended']->delete();
            }
        } elseif (isset($this->__models['suspended'])) {
            // account already suspended
            // add history/log record, update suspended record
            $m_status = 0;
            if ($status == 'TERM') {
                $this->__models['suspended']->type('T');
                $m_status = 2;
            } else {
                $this->__models['suspended']->type('I');
                $m_status = 1;
            }
            $this->__models['suspended']->reasoncode($code);
            $this->__models['suspended']->suspDate(date('Y-m-d H:i:s'));
            $this->__models['suspended']->save();

            $history = new SuspensionHistoryModel($dbc);
            $history->username($this->current_user);
            $history->cardno($this->id);
            $history->reasoncode($code);
            $history->postdate(date('Y-m-d H:i:s'));
            $history->save();

            $changed = false;
            $cas_model->active(1);
            // find most recent active record
            $current = $cas_model->find('tdate', true);
            foreach($current as $obj) {
                if ($obj->reasonCode() != $code || $obj->suspensionTypeID() != $m_status) {
                    $changed = true;
                }
                $cas_model->savedType($obj->savedType());
                $cas_model->savedMemType($obj->savedMemType());
                $cas_model->savedDiscount($obj->savedDiscount());
                $cas_model->savedChargeLimit($obj->savedChargeLimit());
                $cas_model->savedMailFlag($obj->savedMailFlag());
                // copy "saved" values from current active
                // suspension record. should only be one
                break;
            }

            // only add a record if something changed.
            // count($current) of zero means there is no
            // record. once the migration to the new data
            // structure is complete, that check won't
            // be necessary
            if ($changed || count($current) == 0) {
                $cas_model->reasonCode($code);
                $cas_model->username($this->current_user);
                $cas_model->tdate(date('Y-m-d H:i:s'));
                $cas_model->suspensionTypeID($m_status);

                $current_id = $cas_model->save();
            }

            $json = array(
                'cardNo' => $this->id,
                'activeStatus' => $status,
            );
            \COREPOS\Fannie\API\member\MemberREST::post($this->id, $json);
        } else {
            // suspend active account
            // create suspensions and log/history records
            // set custdata & meminfo to inactive
            $discount = 0;
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $discount = $c['discount'];
                    break;
                }
            }
            
            $susp = new SuspensionsModel($dbc);
            $susp->cardno($this->id);
            $susp->type( $status == 'TERM' ? 'T' : 'I' );           
            $susp->memtype1($account['customerTypeID']);
            $susp->memtype2($account['memberStatus']);
            $susp->suspDate(date('Y-m-d H:i:s'));
            $susp->reason('');
            $susp->mailflag($account['contactAllowed']);
            $susp->discount($discount);
            $susp->chargelimit($account['chargeLimit']);
            $susp->reasoncode($code);
            $susp->save();

            $cas_model->savedType($account['memberStatus']);
            $cas_model->savedMemType($account['customerTypeID']);
            $cas_model->savedDiscount($discount);
            $cas_model->savedChargeLimit($account['chargeLimit']);
            $cas_model->savedMailFlag($account['contactAllowed']);
            $cas_model->suspensionTypeID( $status == 'TERM' ? 2 : 1 );
            $cas_model->tdate(date('Y-m-d H:i:s'));
            $cas_model->username($this->current_user);
            $cas_model->reasonCode($code);
            $cas_model->active(1);
            $current_id = $cas_model->save();

            $history = new SuspensionHistoryModel($dbc);
            $history->username($this->current_user);
            $history->cardno($this->id);
            $history->reasoncode($code);
            $history->postdate(date('Y-m-d H:i:s'));
            $history->save();

            $json = array(
                'cardNo' => $this->id,
                'chargeLimit' => 0,
                'activeStatus' => $status,
                'customerTypeID' => 0,
                'contactAllowed' => 0,
                'customers' => array(),
            );
            foreach ($account['customers'] as $c) {
                $c['discount'] = 0;
                $json['customers'][] = $c;
            }
            \COREPOS\Fannie\API\member\MemberREST::post($this->id, $json);
        }

        // only one CustomerAccountSuspensions record should be active
        if ($current_id != 0) {
            $cas_model->reset();
            $cas_model->card_no($this->id);
            $cas_model->active(1);
            foreach($cas_model->find() as $obj) {
                if ($obj->customerAccountSuspensionID() != $current_id) {
                    $obj->active(0);
                    $obj->save();
                }
            }
        }
        
        $callbacks = FannieConfig::config('MEMBER_CALLBACKS');
        foreach ($callbacks as $cb) {
            $obj = new $cb();
            $obj->run($this->id);
        }

        header('Location: PIMemberPage.php?id='.$this->id);
        return False;
    }
}

FannieDispatch::conditionalExec();

