<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class GumLib
{
    static public function loanSchedule($loan)
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $settings = new GumSettingsModel($dbc);

        // get end of fiscal year
        $settings->key('FYendMonth');
        $settings->load();
        $fyM = $settings->value();
        $settings->key('FYendDay');
        $settings->load();
        $fyD = $settings->value();

        // find end of fiscal year following
        // loan start date
        $ld = strtotime($loan->loanDate());
        $startYear = date('Y', $ld);
        if ($ld > mktime(0, 0, 0, $fyM, $fyD, date('Y', $ld))) {
            $startYear++;
        }

        // end of loan timestamp
        $ed = mktime(0, 0, 0, date('n', $ld)+$loan->termInMonths(), date('j', $ld), date('Y', $ld));
        /*
        $today = strtotime('today');
        if ($today < $ed) {
            $ed = strtotime('180 days ago');
        }
        */
        // end of next fiscal year
        $fy = mktime(0, 0, 0, $fyM, $fyD, $startYear);

        $prevDT = new DateTime(date('Y-m-d', $ld));
        $fyDT = new DateTime(date('Y-m-d', $fy));
        $limit = 0;
        $last = false;
        $loan_value = $loan->principal();
        $rate = $loan->interestRate();
        $sumInt = 0.0;
        $schedule = array();
        while($fy <= $ed) {
            $entry = array();
            $entry['end_date'] = date('m/d/Y', $fy);
            $entry['days'] = $fyDT->diff($prevDT)->format('%a');
            $new_value = $loan_value * pow(1.0 + $rate, $entry['days']/365.25);
            $interest = $new_value - $loan_value;
            $loan_value = $new_value;
            $sumInt += $interest;
            $entry['interest'] = $interest;
            $entry['balance'] = $new_value;
            $schedule[] = $entry;

            $fy = mktime(0, 0, 0, $fyM, $fyD, date('Y', $fy)+1);
            if ($fy > $ed && !$last) {
                $fy = $ed;
                $last = true;
            } else if ($last) {
                break;
            }
            $prevDT = $fyDT;
            $fyDT = new DateTime(date('Y-m-d', $fy));
            if ($limit++ > 50) break; // something weird is going on
        }

        return array(
                'schedule' => $schedule,
                'total_interest' => $sumInt,
                'balance' => $loan->principal() + $sumInt,
        );
    }

    /**
      Create an entry in GumPayoffs representing a new check
      @param $map_model a model for a mapping table (e.g., GumLoanPayoffMapModel)
        where the new gumPayoffID can be saved
      @param $mapped [boolean, default true] save a reference in the $map_model
        Set to false if simply allocating a check number for use from
        outside the plugin. Turning off mapping will return the check
        number rather than the gumPayoffID
      @return [int] gumPayoffID or [boolean] false on failure
    */
    public static function allocateCheck($map_model, $mapped=true, $unmap_reason='', $unmap_key='')
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $settings = new GumSettingsModel($dbc);

        $cn = 0;
        $result = $dbc->query('SELECT MAX(checkNumber) as max_cn FROM GumPayoffs');
        if ($dbc->num_rows($result) > 0) {
            $row = $dbc->fetch_row($result);
            if ($row['max_cn'] != '') {
                $cn = $row['max_cn'];
            }
        }

        if ($cn == 0) { // first ever check
            $settings->key('firstCheckNumber');
            if ($settings->load()) {
                $cn = $settings->value();
            } else {
                $cn = 1;
            }
        } else { // go to next
            $cn++;
        }

        /**
          Create a new GumPayoffs entry
          for the check then save its
          ID in the provided map. If the
          2nd step fails somehow, delete
          the GumPayoffs record so the
          check number is wasted.
        */
        $payoff = new GumPayoffsModel($dbc);
        $payoff->checkNumber($cn);
        $payoff->reason($unmap_reason);
        $payoff->alternateKey($unmap_key);
        $id = $payoff->save();
        if (!$mapped) {
            return $cn;
        }
        if ($id !== false) {
            $map_model->gumPayoffID($id);
            $finish = $map_model->save();
            if ($finish === false) {
                $payoff->gumPayoffID($id);
                $payoff->delete();
                return false;
            } else {
                return $id;
            }
        } else {
            return false;
        }
    }

    public static function getCheck($map_model)
    {
        global $FANNIE_PLUGIN_SETTINGS;
        if ($map_model->gumPayoffID() == '') {
            return false;
        }

        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $payoff = new GumPayoffsModel($dbc);
        $payoff->gumPayoffID($map_model->gumPayoffID());

        if ($payoff->load()) {
            return $payoff;
        } else {
            return false;
        }
    }

    /**
      Get value for a given setting
      @param $key [string] setting key
      @param $default [string] value to use if setting is missing
      @return [string] setting value or $default
    */
    public static function getSetting($key, $default='')
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $s = new GumSettingsModel($dbc);
        $s->key($key);
        if ($s->load()) {
            return $s->value();
        } else {
            return $default;
        }
    }

}

