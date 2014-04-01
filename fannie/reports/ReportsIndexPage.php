<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class ReportsIndexPage extends FanniePage {

    protected $title = "Fannie : Reports";
    protected $header = "Reports";

    public function body_content()
    {
        global $FANNIE_ROOT, $FANNIE_URL;
        $report_sets = array();
        $other_reports = array();

        $reports = FannieAPI::listModules('FannieReportPage');
        foreach($reports as $class) {
            $obj = new $class();
            if (!$obj->discoverable) {
                continue;
            }
            $reflect = new ReflectionClass($obj);
            $url = $FANNIE_URL . str_replace($FANNIE_ROOT, '', $reflect->getFileName());
            if ($obj->report_set != '') {
                if (!isset($report_sets[$obj->report_set])) {
                    $report_sets[$obj->report_set] = array();
                }
                $report_sets[$obj->report_set][] = array(
                    'url' => $url,
                    'info' => $obj->description,
                );
            } else {
                $other_reports[] = array(
                    'url' => $url,
                    'info' => $obj->description,
                );
            }
        }
        $tools = FannieAPI::listModules('FannieReportTool');
        foreach($tools as $class) {
            $obj = new $class();
            if (!$obj->discoverable) {
                continue;
            }
            $reflect = new ReflectionClass($obj);
            $url = $FANNIE_URL . str_replace($FANNIE_ROOT, '', $reflect->getFileName());
            if ($obj->report_set != '') {
                if (!isset($report_sets[$obj->report_set])) {
                    $report_sets[$obj->report_set] = array();
                }
                $report_sets[$obj->report_set][] = array(
                    'url' => $url,
                    'info' => $obj->description,
                );
            } else {
                $other_reports[] = array(
                    'url' => $url,
                    'info' => $obj->description,
                );
            }
        }
        echo '<ul>';
        $keys = array_keys($report_sets);
        sort($keys);
        foreach($keys as $set_name) {
            echo '<li>' . $set_name;
            echo '<ul>';
            $reports = $report_sets[$set_name];
            usort($reports, array('ReportsIndexPage', 'reportAlphabetize'));
            foreach($reports as $report) {
                $description = $report['info'];
                $url = $report['url'];
                $linked = preg_replace('/\[(.+)\]/', '<a href="' . $url . '">\1</a>', $description);
                if ($linked === $description) {
                    $linked .= ' (<a href="' . $url . '">Link</a>)';
                }
                echo '<li>' . $linked . '</li>';
            }
            echo '</ul></li>';
        }
        usort($other_reports, array('ReportsIndexPage', 'reportAlphabetize'));
        foreach($other_reports as $report) {
            $description = $report['info'];
            $url = $report['url'];
            $linked = preg_replace('/\[(.+)\]/', '<a href="' . $url . '">\1</a>', $description);
            if ($linked === $description) {
                $linked .= ' (<a href="' . $url . '">Link</a>)';
            }
            echo '<li>' . $linked . '</li>';
        }
        echo '</ul>';

        return ob_get_clean();
    }

    static private function reportAlphabetize($a, $b)
    {
        if ($a['info'] < $b['info']) {
            return -1;
        } else if ($a['info'] > $b['info']) {
            return 1;
        } else {
            return 0;
        }
    }

}

FannieDispatch::conditionalExec();

