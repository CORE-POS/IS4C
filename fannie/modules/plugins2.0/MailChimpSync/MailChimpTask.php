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

use DrewM\MailChimp\MailChimp as MailChimpV3;

include_once(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

class MailChimpTask extends FannieTask
{
    private $requiredMergeFields = array();
    private $ownerField = 'OWNER NUMBER';

    protected function getSettings()
    {
        $FANNIE_PLUGIN_SETTINGS = $this->config->get('PLUGIN_SETTINGS');
        $APIKEY='a92f83d3e5f7fe52d4579e7902c6491d-us8';
        $LISTID='54100d18af';
        $APIKEY = $FANNIE_PLUGIN_SETTINGS['MailChimpApiKey'];
        $LISTID = $FANNIE_PLUGIN_SETTINGS['MailChimpListID'];
        if ($FANNIE_PLUGIN_SETTINGS['MailChimpMergeVarName']) {
            $this->ownerField =$FANNIE_PLUGIN_SETTINGS['MailChimpMergeVarName'];
        }

        return array($APIKEY, $LISTID);
    }

    protected function readMergeFields($chimp, $LISTID)
    {
        $vars = $chimp->get("lists/{$LISTID}/merge-fields");
        $field_id = false;
        foreach ($vars['merge_fields'] as $mf) {
            if ($mf['tag'] == 'CARDNO') {
                $field_id = $mf['merge_id'];
            }
            if ($mf['required']) {
                $this->requiredMergeFields[$mf['tag']] = $mf['default_value'];
            }
        }

        if ($field_id === false) {
            echo 'Adding member# field' . "\n";
            /** doesn't worK?
            $new = $chimp->post("lists/{$LISTID}/merge_fields", array(
                'tag' => 'CARDNO',
                'name' => 'Owner Number',
                'type' => 'number',
                'required' => false,
                'public' => false,
            ));
            $field_id = $new['merge_id'];
             */
        }

        if ($field_id === false) {
            $this->cronMsg('Error: could not locate / create owner number field!', FannieLogger::NOTICE);
            return false;
        }

        return true;
    }

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS, $argv;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $this->custdata = new CustdataModel($dbc);
        $this->meminfo = new MeminfoModel($dbc);

        list($APIKEY, $LISTID) = $this->getSettings();
        if (empty($APIKEY) || empty($LISTID)) {
            $this->cronMsg('Missing API key or List ID', FannieLogger::NOTICE);
            return false;
        }

        if (!class_exists('Mailchimp')) {
            $this->cronMsg('MailChimp library is not installed', FannieLogger::NOTICE);
            return false;
        } elseif (!class_exists('DrewM\\MailChimp\\MailChimp')) {
            $this->cronMsg('MailChimp API v3 library is not installed', FannieLogger::NOTICE);
            return false;
        }
        $chimpEX = new MailChimpEx($APIKEY);
        $chimp3 = new MailChimpV3($APIKEY);

        if ($this->readMergeFields($chimp3, $LISTID) === false) {
            return false;
        } // end create owner number field if needed

        $statuses = array('subscribed', 'unsubscribed', 'cleaned');
        $cleans = array();
        $memlist = '';
        /**
          Examine all list members
        */
        foreach ($statuses as $status) {

            $this->cronMsg('==== Checking ' . $status . ' emails ====', FannieLogger::INFO);

            $full_list = $chimpEX->lists->export($LISTID, $status);
            $headers = array_shift($full_list);
            $columns = array();
            foreach ($headers as $index => $name) {
                $columns[strtoupper($name)] = $index;
            }
            $line_count = 1;
            foreach ($full_list as $record) {
                /**
                  Print progress meter in verbose mode
                */
                if (isset($argv[2]) && ($argv[2] == '-v' || $argv[2] == '--verbose')) {
                    printf("Processing %d/%d\r", $line_count, count($full_list));
                }
                $line_count++;
                list($card_no, $email, $fname, $lname, $changed) = $this->unpackRecord($record, $columns);

                /** MailChimp has a POS member number tag **/
                if ($card_no !== false && !empty($card_no)) {
                    switch ($status) {
                        /**
                          If subscribed list member has been tagged with a POS member number, compare
                          MailChimp fields to POS fields. If name disagrees, use POS value
                          for both. If email disagrees, use MailChimp value for both.
                        */
                        case 'subscribed':
                            $memlist = $this->isSubscribed($record, $columns, $chimp3, $LISTID, $memlist);
                            break;
                        /**
                          Just track the number to avoid re-adding them to the list
                        */
                        case 'unsubscribed':
                            $memlist = $this->isUnsubscribed($record, $columns, $memlist);
                            break;
                        /**
                          Cleaned in MailChimp means the address isn't deliverable
                          In this situation, remove the bad address from POS
                          and delete the address from MailChimp. The member can be
                          re-added when a correct email is entered into POS.
                        */
                        case 'cleaned':
                            $memlist = $this->isCleaned($record, $columns, $memlist);
                            $cleans[] = $record;
                            break;
                    }
                /**
                  If list member is not tagged with a POS member number, try to
                  locate them in POS by name and/or email address. If found,
                  tag them in MailChimp with the POS member number. This whole
                  situation only occurs if the initial list is imported without
                  member numbers.
                */
                } else {
                    switch ($status) {
                        // subscribed users can be updated easily
                        case 'subscribed':
                            $memlist = $this->unknownSubscribed($record, $columns, $chimp3, $LISTID, $memlist);
                            break;
                        /**
                          Unsubscribed are currently ignored. The can't be updated as is.
                          They could be deleted entirely via unsubscribe, resubscribed with
                          an owner number, and then unsubscribed again. That's not currently
                          implemented. It does check for the email address on the POS side
                          to prevent re-adding it.
                        */
                        case 'unsubscribed':
                            $memlist = $this->unknownUnsubscribed($record, $columns, $memlist);
                            break;
                        /**
                          Cleaned are bad addresses. Delete them from POS database
                          then from Mail Chimp.
                        */
                        case 'cleaned':
                            $memlist = $this->unknownClean($record, $columns, $memlist);
                            $cleans[] = $record;
                            break;
                    }
                }
            } // foreach list member record

        } // foreach list status

        $this->removeBounces($chimp3, $LISTID, $cleans, $columns);

        $this->addNew($chimp3, $LISTID, $dbc, $memlist);

        return true;
    }

    protected function removeBounces($chimp, $LISTID, $cleans, $columns)
    {
        $this->cronMsg(sprintf('Removing %d addresses with status "cleaned"', count($cleans)), FannieLogger::INFO);
        $delID = 0;
        $batch = $chimp->new_batch();
        foreach ($cleans as $record) {
            if (empty($record[$columns['EMAIL ADDRESS']])) {
                continue;
            }
            $email = $record[$columns['EMAIL ADDRESS']];
            $hash = $chimp->subscriberHash($email);
            $batch->delete("del{$delID}", "lists/{$LISTID}/members/{$hash}");
            $delID++;
            $this->cronMsg(sprintf('  => %s', $email), FannieLogger::INFO);
        }
        if ($delID) {
            $res = $batch->execute();
        }
    }

    protected function addNew($chimp, $LISTID, $dbc, $memlist)
    {
        /**
          Finally, find new members and add them to MailChimp
        */
        if ($memlist === '') {
            $memlist = '-1,';
        }
        $memlist = substr($memlist, 0, strlen($memlist)-1);
        $query = 'SELECT m.card_no,
                    m.email_1,
                    c.FirstName,
                    c.LastName
                  FROM meminfo AS m
                    INNER JOIN custdata AS c ON c.CardNo=m.card_no AND c.personNum=1
                  WHERE c.Type = \'PC\'
                    AND m.email_1 IS NOT NULL
                    AND m.email_1 <> \'\'
                    AND m.card_no NOT IN (' . $memlist . ')';
        $result = $dbc->query($query);
        $this->cronMsg(sprintf('Adding %d new members', $dbc->numRows($result)), FannieLogger::INFO);
        $batch = $chimp->new_batch();
        $addID = 0;
        while ($row = $dbc->fetch_row($result)) {
            if (!filter_var($row['email_1'], FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $this->cronMsg(sprintf(' => %s (%s)', $row['email_1'], $row['card_no']), FannieLogger::INFO);
            $req = array(
                'email_address' => $row['email_1'],
                'status' => 'subscribed',
                'merge_fields' => array(
                    'CARDNO' => $row['card_no'],
                    'FNAME' => $row['FirstName'],
                    'LNAME' => $row['LastName'],
                ),
            );
            foreach ($this->requiredMergeFields as $tag => $default) {
                if (!isset($req['merge_fields'][$tag])) {
                    $req['merge_fields'][$tag] = $default;
                }
            }
            $batch->post("add{$addID}", "lists/{$LISTID}/members", $req);
            $addID++;
        }
        if ($addID) {
            $res = $batch->execute();
        }
    }

    protected function unpackRecord($record, $columns)
    {
        $card_no = isset($columns[$this->ownerField]) ? $record[$columns[$this->ownerField]] : false;
        $email = $record[$columns['EMAIL ADDRESS']];
        $fname = $record[$columns['FIRST NAME']];
        $lname = $record[$columns['LAST NAME']];
        $changed = isset($columns['LAST_CHANGED']) && isset($record[$columns['LAST_CHANGED']]) ? $record[$columns['LAST_CHANGED']] : 0;

        return array($card_no, $email, $fname, $lname, $changed);
    }

    /**
      Callback when list includes an subuscribed entry
      with a member number
    */
    protected function isSubscribed($record, $columns, $chimp, $LISTID, $memlist)
    {
        list($card_no, $email, $fname, $lname, $changed) = $this->unpackRecord($record, $columns);
        $memlist .= sprintf('%d,', $card_no);
        $this->custdata->reset();
        $this->custdata->CardNo($card_no);
        $this->custdata->personNum(1);
        $this->custdata->load();
        $update = array();
        $this->meminfo->reset();
        $this->meminfo->card_no($card_no);
        $this->meminfo->load();
        $email = strtolower($email);
        if (strtolower($this->meminfo->email_1()) != $email && (strtotime($changed) > strtotime($this->meminfo->modified()) || $this->meminfo->email_1() == '')) {
            $this->cronMsg(sprintf("MISMATCH: POS says %s, MailChimp says %s, Mailchimp is newer",
            $this->meminfo->email_1(), $email), FannieLogger::INFO);
            $this->meminfo->email_1($email);
            $this->meminfo->save();
        } elseif (strtolower($this->meminfo->email_1()) != $email) {
            $update['EMAIL'] = strtolower($this->meminfo->email_1());
            $this->cronMsg(sprintf("MISMATCH: POS says %s, MailChimp says %s, POS is newer",
                $this->meminfo->email_1(), $email), FannieLogger::ALERT);
        }
        if (strtoupper(trim($this->custdata->FirstName())) != strtoupper($fname)) {
            $this->cronMsg(sprintf("MISMATCH: POS says %s, MailChimp says %s",
                $this->custdata->FirstName(), $fname), FannieLogger::INFO);
            $update['FNAME'] = trim($this->custdata->FirstName());
        }
        if (strtoupper(trim($this->custdata->LastName())) != strtoupper($lname)) {
            $this->cronMsg(sprintf("MISMATCH: POS says %s, MailChimp says %s",
                $this->custdata->LastName(), $lname), FannieLogger::INFO);
            $update['LNAME'] = trim($this->custdata->LastName());
        }
        if (count($update) > 0) {
            $email_struct = array(
                'euid' => $record[$columns['EUID']],
                'leid' => $record[$columns['LEID']],
            );
            $this->cronMsg(sprintf("Updating name field(s) for member #%d", $card_no), FannieLogger::INFO);
            try {
                $hash = $chimp->subscriberHash($email);
                $req = array();
                if (isset($update['EMAIL'])) {
                    $req['email'] = $update['EMAIL'];
                }
                if (isset($update['FNAME']) || isset($update['LNAME'])) {
                    $req['merge_fields'] = array();
                    if (isset($update['FNAME'])) {
                        $req['merge_fields']['FNAME'] = $update['FNAME'];
                    }
                    if (isset($update['LNAME'])) {
                        $req['merge_fields']['LNAME'] = $update['LNAME'];
                    }
                }
                $chimp->patch("list/{$LISTID}/members/{$hash}", $req);
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }
            sleep(1);
        }

        return $memlist;
    }

    /**
      Callback when list includes an unsubuscribed entry
      with a member number
    */
    protected function isUnsubscribed($record, $columns, $memlist)
    {
        list($card_no, $email, $fname, $lname, $changed) = $this->unpackRecord($record, $columns);
        $memlist .= sprintf('%d,', $card_no);

        return $memlist;
    }

    /**
      Callback when list includes a cleaned [invalid address] entry
      with a member number
    */
    protected function isCleaned($record, $columns, $memlist)
    {
        list($card_no, $email, $fname, $lname, $changed) = $this->unpackRecord($record, $columns);
        $this->meminfo->reset();
        $this->meminfo->card_no($card_no);
        $this->meminfo->email_1('');
        $this->meminfo->save();
        $this->cronMsg(sprintf('CLEANING Member %d, email %s', $card_no, $email), FannieLogger::INFO);

        return $memlist;
    }

    /**
      Callback when list includes a subscribed entry
      without a member number
    */
    protected function unknownSubscribed($record, $columns, $chimp, $LISTID, $memlist)
    {
        list($card_no, $email, $fname, $lname, $changed) = $this->unpackRecord($record, $columns);
        if ($card_no === false) {
            return $memlist;
        }
        $update = array();
        $this->meminfo->reset();
        $this->meminfo->email_1($email);
        $matches = $this->meminfo->find();
        if (count($matches) == 1) {
            $update['CARDNO'] = $matches[0]->card_no();
        } else {
            $this->custdata->reset();
            $this->custdata->FirstName($fname);
            $this->custdata->LastName($lname);
            $this->custdata->personNum(1);
            $this->custdata->Type('PC');
            $matches = $this->custdata->find();
            if (count($matches) == 1) {
                $update['CARDNO'] = $matches[0]->CardNo();
            }
        }

        if (isset($update['CARDNO'])) {
            $this->cronMsg("Assigning member # to account " . $email, FannieLogger::INFO);
            $hash = $chimp->subscriberHash($email);
            $chimp->patch("lists/{$LISTID}/members/{$hash}", array(
                'merge_fields' => array('CARDNO' => $update['CARDNO']),
            ));
            sleep(1);
            $memlist .= sprintf('%d,', $update['CARDNO']);
        }

        return $memlist;
    }

    /**
      Callback when list includes an unsubscribed entry
      without a member number
    */
    protected function unknownUnsubscribed($record, $columns, $memlist)
    {
        list($card_no, $email, $fname, $lname, $changed) = $this->unpackRecord($record, $columns);
        if ($memlist === false) {
            return $memlist;
        }
        $this->meminfo->reset();
        $this->cronMsg('Checking unsubscribed ' . $email, FannieLogger::INFO);
        $this->meminfo->email_1($email);
        $matches = $this->meminfo->find();
        foreach ($matches as $opted_out) {
            $memlist .= sprintf('%d,', $opted_out->card_no());
            $this->cronMsg('Excluding member ' . $opted_out->card_no(), FannieLogger::INFO);
        }

        return $memlist;
    }

    /**
      Callback when list includes a cleaned [invalid address] entry
      without a member number
    */
    protected function unknownClean($record, $columns, $memlist)
    {
        list($card_no, $email, $fname, $lname, $changed) = $this->unpackRecord($record, $columns);
        $this->meminfo->reset();
        $this->meminfo->email_1($email);
        foreach ($this->meminfo->find() as $bad_address) {
            $bad_address->email_1('');
            $bad_address->save();
            $this->cronMsg(sprintf('CLEANING untagged member %d, email %s', $bad_address->card_no(), $email), FannieLogger::INFO);
        }

        return $memlist;
    }

}

