<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
if(!class_exists("FannieAPI")) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if(!class_exists("CalendarPluginDB")) {
    include(dirname(__FILE__).'/CalendarPluginDB.php');
}

class CalendarSubscriptionsTask extends FannieTask
{
    public function run()
    {
        $dbc = CalendarPluginDB::get();

        /**
          Use prepare statements instead of models
          for efficiency. Could be issuing a large number
          of queries with many subscriptions
        */
        $uidP = $dbc->prepare('
            SELECT eventID
            FROM monthview_events
            WHERE calendarID=?
                AND subscriptionUID=?');
        $insertP = $dbc->prepare('
            INSERT INTO monthview_events
            (calendarID, eventDate, eventText, uid, subscriptionUID)
            VALUES
            (?, ?, ?, 0, ?)');
        $updateP = $dbc->prepare('
            UPDATE monthview_events
            SET eventDate=?,
                eventText=?
            WHERE eventID=?');

        $calendarsQ = '
            SELECT c.calendarID,
                s.url
            FROM calendars AS c
                INNER JOIN CalendarSubscriptions AS s 
                    ON c.calendarSubscriptionID=s.calendarSubscriptionID';
        $calendarsR = $dbc->query($calendarsQ);
        $our_tz = new DateTimeZone(date_default_timezone_get());
        /**
          For each subscribed calendar:
          * Download the feed URL to temporary storage
          * Parse the feed data and extract VEvents
          * Loop through the events and add/update them
          * Delete any events in the calendar that
            a) do not match one of the event unique IDs
            b) fall within the same timespan as the
               parsed events
            These two conditions *probably* indicate
            the event was deleted in the source calendar
        */
        while ($calendarsW = $dbc->fetchRow($calendarsR)) {
            $calendarID = $calendarsW['calendarID'];
            $file = $this->downloadFeed($calendarsW['url']);
            if ($file === false) {
                // error downloading feed
                continue;
            }

            $fp = fopen($file, 'r');
            $document = Sabre\VObject\Reader::read($fp, Sabre\VObject\Reader::OPTION_FORGIVING);
            $events = $document->getBaseComponents('VEvent');
            
            $subscribedIDs = array();
            $earliest = new DateTime('today');
            $latest = new DateTime('today');
            foreach ($events as $event) {
                if (!isset($event->DTSTART) || !isset($event->UID)) {
                    // malformed event
                    continue;
                }

                $summary = false;
                if (isset($event->SUMMARY)) {
                    $summary = $event->SUMMARY->getValue();
                }
                $description = false;
                if (isset($event->DESCRIPTION)) {
                    $description = $event->DESCRIPTION->getValue();
                }

                if (!$summary && !$description) {
                    // event has no useful content
                    continue;
                }

                $uniqueID = $event->UID;

                $start = $event->DTSTART->getDateTime();
                $start->setTimezone($our_tz);
                $hours = false;
                if ($event->DTEND) {
                    $end = $event->DTEND->getDateTime();    
                    $end->setTimezone($our_tz);
                    if ($start->format('Y-m-d') == $end->format('Y-m-d')) {
                        $t1 = $start->format('H:ia');
                        $t2 = $end->format('H:ia');
                        if ($t1 != $t2) {
                            $hours = $t1 . ' - ' . $t2;
                        }
                    }
                }

                $eventText = '';
                if ($hours) {
                    $eventText .= $hours . "\n";
                }
                if ($summary) {
                    $eventText .= $summary . "\n";
                }
                if ($description) {
                    $eventText .= $description . "\n";
                }

                $uidR = $dbc->execute($uidP, array($calendarID, $uniqueID));
                if ($dbc->numRows($uidR) == 0) {
                    $dbc->execute($insertP, array(
                        $calendarID,
                        $start->format('Y-m-d'),
                        nl2br($eventText),
                        $uniqueID,
                    ));
                } else {
                    $uidW = $dbc->fetchRow($uidR);
                    $dbc->execute($updateP, array(
                        $start->format('Y-m-d'),
                        nl2br($eventText),
                        $uidW['eventID'],
                    ));
                }
                $subscribedIDs[] = $uniqueID;
                if ($start < $earliest) {
                    $earliest = $start;
                }
                if ($start > $latest) {
                    $latest = $start;
                }
            }

            if (count($subscribedIDs) > 0) {
                $cleanQ = '
                    DELETE FROM monthview_events
                    WHERE calendarID=?
                        AND eventDate BETWEEN ? AND ?
                        AND subscriptionUID NOT IN (';
                $cleanArgs = array($calendarID, $earliest->format('Y-m-d'), $latest->format('Y-m-d'));
                foreach ($subscribedIDs as $sID) {
                    $cleanQ .= '?,';
                    $cleanArgs[] = $sID;
                }
                $cleanQ = substr($cleanQ, 0, strlen($cleanQ)-1);
                $cleanQ .= ')';
                $cleanP = $dbc->prepare($cleanQ);
                $cleanR = $dbc->execute($cleanP, $cleanArgs);
            }

            fclose($fp);
            unlink($file);
        }
    }

    private function downloadFeed($url)
    {
        $tempfile = tempnam(sys_get_temp_dir(), 'CST');
        $fp = fopen($tempfile, 'w');
        if (substr($url, 0, 9) == 'webcal://') {
            $url = str_replace('webcal://', 'http://', $url);
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $success = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($success) {
            return $tempfile;
        } else {
            unlink($tempfile);

            return false;
        }
    }
}

