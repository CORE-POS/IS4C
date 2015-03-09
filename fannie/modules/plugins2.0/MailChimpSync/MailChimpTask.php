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

include_once(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

class MailChimpTask extends FannieTask
{
    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS, $argv;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $custdata = new CustdataModel($dbc);
        $meminfo = new MeminfoModel($dbc);

        $APIKEY='a92f83d3e5f7fe52d4579e7902c6491d-us8';
        $LISTID='54100d18af';
        $APIKEY = $FANNIE_PLUGIN_SETTINGS['MailChimpApiKey'];
        $LISTID = $FANNIE_PLUGIN_SETTINGS['MailChimpListID'];
        if (empty($APIKEY) || empty($LISTID)) {
            $this->cronMsg('Missing API key or List ID', FannieLogger::NOTICE);
            return false;
        }

        if (!class_exists('MailChimp')) {
            include(dirname(__FILE__) . '/noauto/mailchimp-api-php/src/Mailchimp.php');
        }
        $mc = new MailChimp($APIKEY);

        if ($FANNIE_PLUGIN_SETTINGS['MailChimpMergeVarField'] != 1) {
            $vars = $mc->lists->mergeVars(array($LISTID));
            $field_id = false;
            if ($vars['data']) {
                $current = array_pop($vars['data']);
                foreach ($current['merge_vars'] as $index => $info) {
                    if ($info['tag'] == 'CARDNO') {
                        $field_id = $info['id'];
                        break;
                    }
                }

                if ($field_id !== false) {
                    echo 'Found member# field' . "\n";
                } else {
                    echo 'Adding member# field' . "\n";
                    $new = $mc->lists->mergeVarAdd($LISTID, 'CARDNO', 'Owner Number', array('field_type'=>'number','public'=>false));
                    $field_id = $new['id'];
                }
            }

            if ($field_id === false) {
                $this->cronMsg('Error: could not locate / create owner number field!', FannieLogger::NOTICE);
                return false;
            }
        } // end create owner number field if needed

        $statuses = array('subscribed', 'unsubscribed', 'cleaned');
        $cleans = array();
        $memlist = '';
        /**
          Examine all list members
        */
        foreach ($statuses as $status) {

            $this->cronMsg('==== Checking ' . $status . ' emails ====', FannieLogger::INFO);

            $full_list = $mc->lists->export($LISTID, $status);
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
                $card_no = $record[$columns['OWNER NUMBER']];
                $email = $record[$columns['EMAIL ADDRESS']];
                $fn = $record[$columns['FIRST NAME']];
                $ln = $record[$columns['LAST NAME']];
                $changed = isset($columns['LAST_CHANGED']) && isset($record[$columns['LAST_CHANGED']]) ? $record[$columns['LAST_CHANGED']] : 0;

                /** MailChimp has a POS member number tag **/
                if (!empty($card_no)) {
                    switch ($status) {
                        /**
                          If subscribed list member has been tagged with a POS member number, compare
                          MailChimp fields to POS fields. If name disagrees, use POS value
                          for both. If email disagrees, use MailChimp value for both.
                        */
                        case 'subscribed':
                            $memlist .= sprintf('%d,', $card_no);
                            $custdata->reset();
                            $custdata->CardNo($card_no);
                            $custdata->personNum(1);
                            $custdata->load();
                            $update = array();
                            $meminfo->reset();
                            $meminfo->card_no($card_no);
                            $meminfo->load();
                            if ($meminfo->email_1() != $email && strtotime($changed) > strtotime($meminfo->modified())) {
                                $this->cronMsg(sprintf("MISMATCH: POS says %s, MailChimp says %s, Mailchimp is newer",
                                $meminfo->email_1(), $email), FannieLogger::INFO);
                                $meminfo->email_1($email);
                                $meminfo->save();
                            } elseif ($meminfo->email_1() != $email) {
                                $update['EMAIL'] = $meminfo->email_1();
                                $this->cronMsg(sprintf("MISMATCH: POS says %s, MailChimp says %s, POS is newer",
                                $meminfo->email_1(), $email), FannieLogger::INFO);
                            }
                            if (strtoupper(trim($custdata->FirstName())) != strtoupper($fn)) {
                                $this->cronMsg(sprintf("MISMATCH: POS says %s, MailChimp says %s",
                                    $custdata->FirstName(), $fn), FannieLogger::INFO);
                                $update['FNAME'] = trim($custdata->FirstName());
                            }
                            if (strtoupper(trim($custdata->LastName())) != strtoupper($ln)) {
                                $this->cronMsg(sprintf("MISMATCH: POS says %s, MailChimp says %s",
                                    $custdata->LastName(), $ln), FannieLogger::INFO);
                                $update['LNAME'] = trim($custdata->LastName());
                            }
                            if (count($update) > 0) {
                                $email_struct = array(
                                    'euid' => $record[$columns['EUID']],
                                    'leid' => $record[$columns['LEID']],
                                );
                                $this->cronMsg(sprintf("Updating name field(s) for member #%d", $card_no), FannieLogger::INFO);
                                $mc->lists->updateMember($LISTID, $email_struct, $update, '', false);
                                sleep(1);
                            }
                            break;
                        /**
                          Just track the number to avoid re-adding them to the list
                        */
                        case 'unsubscribed':
                            $memlist .= sprintf('%d,', $card_no);
                            break;
                        /**
                          Cleaned in MailChimp means the address isn't deliverable
                          In this situation, remove the bad address from POS
                          and delete the address from MailChimp. The member can be
                          re-added when a correct email is entered into POS.
                        */
                        case 'cleaned':
                            $meminfo->reset();
                            $meminfo->card_no($card_no);
                            $meminfo->email_1('');
                            $meminfo->save();
                            $this->cronMsg(sprintf('CLEANING Member %d, email %s', $card_no, $email), FannieLogger::INFO);
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
                            $update = array();
                            $meminfo->reset();
                            $meminfo->email_1($email);
                            $matches = $meminfo->find();
                            if (count($matches) == 1) {
                                $update['CARDNO'] = $matches[0]->card_no();
                            } else {
                                $custdata->reset();
                                $custdata->FirstName($fn);
                                $custdata->LastName($ln);
                                $custdata->personNum(1);
                                $custdata->Type('PC');
                                $matches = $custdata->find();
                                if (count($matches) == 1) {
                                    $update['CARDNO'] = $matches[0]->CardNo();
                                }
                            }

                            if (isset($update['CARDNO'])) {
                                $email_struct = array(
                                    'email' => $email,
                                    'euid' => $record[$columns['EUID']],
                                    'leid' => $record[$columns['LEID']],
                                );
                                $this->cronMsg("Assigning member # to account " . $email, FannieLogger::INFO);
                                $mc->lists->updateMember($LISTID, $email_struct, $update, '', false);
                                sleep(1);
                                $memlist .= sprintf('%d,', $update['CARDNO']);
                            }
                            break;
                        /**
                          Unsubscribed are currently ignored. The can't be updated as is.
                          They could be deleted entirely via unsubscribe, resubscribed with
                          an owner number, and then unsubscribed again. That's not currently
                          implemented. It does check for the email address on the POS side
                          to prevent re-adding it.
                        */
                        case 'unsubscribed':
                            $meminfo->reset();
                            $this->cronMsg('Checking unsubscribed ' . $email, FannieLogger::INFO);
                            $meminfo->email_1($email);
                            $matches = $meminfo->find();
                            foreach ($matches as $opted_out) {
                                $memlist .= sprintf('%d,', $opted_out->card_no());
                                $this->cronMsg('Excluding member ' . $opted_out->card_no(), FannieLogger::INFO);
                            }
                            break;
                        /**
                          Cleaned are bad addresses. Delete them from POS database
                          then from Mail Chimp.
                        */
                        case 'cleaned':
                            $meminfo->reset();
                            $meminfo->email_1($email);
                            foreach ($meminfo->find() as $bad_address) {
                                $bad_address->email_1('');
                                $bad_address->save();
                                $this->cronMsg(sprintf('CLEANING untagged member %d, email %s', $bad_address->card_no(), $email), FannieLogger::INFO);
                            }
                            $cleans[] = $record;
                            break;
                    }
                }
            } // foreach list member record

        } // foreach list status

        /**
          Removed bounced from the MailChimp list now that
          POS has been updated
        */
        $removal_batch = array();
        foreach ($cleans as $record) {
            if (empty($record[$columns['EMAIL ADDRESS']])) {
                continue;
            }
            $email_struct = array(
                'email' => $record[$columns['EMAIL ADDRESS']],
                'euid' => $record[$columns['EUID']],
                'leid' => $record[$columns['LEID']],
            );
            $removal_batch[] = $email_struct;
        }
        if (count($removal_batch) > 0) {
            $this->cronMsg(sprintf('Removing %d addresses with status "cleaned"', count($removal_batch)), FannieLogger::INFO);
            $result = $mc->lists->batchUnsubscribe($LISTID, $removal_batch, true, false, false);
        }

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
        $add_batch = array();
        while ($row = $dbc->fetch_row($result)) {
            if (!filter_var($row['email_1'], FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $new = array(
                'email' => array(
                    'email' => $row['email_1'],
                ),
                'email_type' => 'html',
                'merge_vars' => array(
                    'FNAME' => $row['FirstName'],
                    'LNAME' => $row['LastName'],
                    'CARDNO' => $row['card_no'],
                ),
            );
            $add_batch[] = $new;
        }
        if (count($add_batch) > 0) {
            $this->cronMsg(sprintf('Adding %d new members', count($add_batch)), FannieLogger::INFO);
            $added = $mc->lists->batchSubscribe($LISTID, $add_batch, false, true);

        }

        return true;
    }

}

