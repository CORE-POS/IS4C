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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class FbeProcessQueuePage extends FannieRESTfulPage
{

    protected $title = 'File Queued Documents';
    protected $header = 'File Queued Documents';

    public $page_set = 'Plugin: File By Email';
    public $description = '[File By Email] shows pending documents so the user can rename them and
    file them in an appropriate directory.';

    protected $window_dressing = false;

    public function preprocess()
    {
        $this->__routes[] = 'post<path><current><new>';

        return parent::preprocess();
    }

    public function post_path_current_new_handler()
    {
        $current = dirname(__FILE__) . '/noauto/queue/' . base64_decode($this->current);
        $save_path = dirname(__FILE__) . '/noauto/save-paths/' . base64_decode($this->path);
        $new = $save_path . '/' . $this->new;

        $ret = array();
        if (!file_exists($current)) {
            $ret['error'] = 'Error: Cannot find queued file';
        } else if (!is_dir($save_path)) {
            $ret['error'] = 'Error: Cannot find directory ' . $this->path;
        } else if (!is_writable($save_path)) {
            $ret['error'] = 'Error: Cannot find save to ' . $this->path;
        } else {
            // preserve file extension
            $old_info = pathinfo($current);
            $new_info = pathinfo($new);
            if (isset($old_info['extension'])) {
                if (!isset($new_info['extension']) || $new_info['extension'] != $old_info['extension']) {
                    $new .= '.' . $old_info['extension'];
                }
            }
            $try = rename($current, $new);
            if (!$try) {
                $ret['error'] = 'Error: Failed to rename file'; 
            } else {
                $next = $this->queueNext();        
                if ($next) {
                    $ret['next'] = $next;
                    $ret['encoded'] = base64_encode($next);
                } else {
                    $ret['all_done'] = true;
                }
            }
        }

        echo json_encode($ret);

        return false;
    }

    public function get_view()
    {
        global $FANNIE_URL;
        $ret = '';

        $base = dirname(__FILE__).'/noauto/save-paths/';
        $ret .= '<form onsubmit="processFile(); return false;">';
        $ret .= '<b>File To</b>: <select id="savePath">';
        $dh = opendir($base);
        while( ($d = readdir($dh)) !== false) {
            if ($d[0] == '.') continue;
            if (is_dir($base . $d)) {
                $ret .= sprintf('<option value="%s">%s</option>',
                                base64_encode($d), basename($d));
            }
        }
        $ret .= '</select><br />';
        $ret .= '<b>File As</b>: <input type="text" id="saveFilename" />';
        $ret .= ' <input type="submit" value="Save" />';
        $ret .= '</form>';

        $ret .= '<hr />';

        $next = $this->queueNext();
        if ($next === false) {
            $ret .= 'No files in queue!';
        } else {
            $ret .= '<object style="width:100%;min-height:500px;" id="preview" 
                    type="application/pdf" data="noauto/queue/' . $next . '#page=1&view=Fit&toolbar=0">
                    <param name="page" value="1" />
                    <param name="toolbar" value="0" />
                    <param name="view" value="Fit" />
                    </object>'; 
            $ret .= '<input type="hidden" id="curName" value="' . base64_encode($next) . '" />';
        }

        $this->add_script($FANNIE_URL.'src/javascript/jquery.js');
        $this->add_script('js/process.js');

        return $ret;
    }

    private function queueNext()
    {
        $path = dirname(__FILE__).'/noauto/queue/';
        foreach(scandir($path) as $file) {
            if ($file[0] == '.') continue;
            if (is_dir($path . $file)) continue;
            return $file;
        }

        return false;
    }
}

FannieDispatch::conditionalExec();

