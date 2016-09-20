<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

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

include(dirname(__FILE__) . '/../../../../config.php');
if (!class_exists('\COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe')) {
    include_once(dirname(__FILE__).'/../../../../classlib2.0/data/pipes/AttachmentEmailPipe.php');
}
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__).'/../../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('PIApply')) {
    include_once(dirname(__FILE__) . '/../PIApply.php');
}

/**
  Extract JSON attachments from email and feed them
  into the PIApply page to trigger updates.
*/
class PIEmailPipe extends \COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe
{
    public function processMail($msg)
    {
        /** extract valid mime types **/
        $mimes = array('application/json');
        $info = $this->parseEmail($msg);
        $fp = fopen('/tmp/pik.out', 'a');
        fwrite($fp, date('r') . ": Got message\n");
        
        $boundary = $this->hasAttachments($info['headers']);
        if ($boundary) {
            $pieces = $this->extractAttachments($info['body'], $boundary);
            fwrite($fp,"Attachments: " . count($pieces['attachments']) . "\n");
            foreach ($pieces['attachments'] as $a) {
                fwrite($fp, "File: {$a['name']}\n");
                fwrite($fp, "Mime-type: {$a['type']}\n");
                if (!in_array($a['type'], $mimes)) {
                    fwrite($fp, "Skipping (based on type)\n");
                    continue;
                }
                $json = base64_encode($a['content']);
                fwrite($fp, $json . "\n");
                $page = new PIApply();
                $page->setJson($json);
                $page->get_json_handler();
            }
        }
        fclose($fp);
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    ini_set('error_log', '/tmp/pik.err');
    $obj = new PIEmailPipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 

