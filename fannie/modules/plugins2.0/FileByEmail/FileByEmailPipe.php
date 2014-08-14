<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of Fannie.

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

if (!class_exists('AttachmentEmailPipe')) {
    include_once(dirname(__FILE__).'/../../../classlib2.0/data/pipes/AttachmentEmailPipe.php');
}
/**
*/
class FileByEmailPipe extends AttachmentEmailPipe
{
    public function processMail($msg)
    {
        include(dirname(__FILE__) . '/../../../config.php');
        /** extract valid mime types **/
        $mimes = array();
        if(isset($FANNIE_PLUGIN_SETTINGS['FbeMimeTypes'])) {
            $mimes = preg_split('/[^\w\/]+/', $FANNIE_PLUGIN_SETTINGS['FbeMimeTypes'], 0, PREG_SPLIT_NO_EMPTY); 
        }

        $info = $this->parseEmail($msg);
        
        $boundary = $this->hasAttachments($info['headers']);
        $burst = $FANNIE_PLUGIN_SETTINGS['FbeBurst'];

        if ($boundary) {
            $pieces = $this->extractAttachments($info['body'], $boundary);
            echo "Attachments: " . count($pieces['attachments']) . "\n";
            foreach($pieces['attachments'] as $a) {
                echo "File: {$a['name']}\n";
                echo "Mime-type: {$a['type']}\n";
                if (!in_array($a['type'], $mimes)) {
                    echo "Skipping (based on type)\n";
                    continue;
                }
                $fp = fopen(dirname(__FILE__) . '/noauto/queue/' . $a['name'], 'w');
                if ($fp === false) {
                    echo 'File open failed' . "\n";
                    continue;
                }
                fwrite($fp, $a['content']);
                fclose($fp);
                echo 'Wrote file ' . dirname(__FILE__) . '/noauto/queue/' . $a['name'] . "\n";
                chmod(dirname(__FILE__) . '/noauto/queue/' . $a['name'], 0666);
                if (!empty($burst)) {
                    $this->burstPDF($a['name'], $burst);
                }
            }
        }
    }

    protected function burstPDF($filename, $program)
    {
        if (substr($program, -5) == 'pdftk') {
            $cmd = escapeshellcmd($program) . ' ' . escapeshellarg($filename) . ' burst output ' . escapeshellarg($filename.'-%02d.pdf');
        } else if (substr($program, -7) == 'convert') {
            $cmd = escapeshellcmd($program) . ' ' . escapeshellarg($filename) . ' ' . escapeshellarg($filename.'-%02d.pdf'); 
        } else {
            return false;
        }
        chdir(dirname(__FILE__).'/noauto/queue/');
        if (!file_exists($filename)) {
            return false;
        }
        exec($cmd, $output, $returncode);
        echo $cmd."\n";

        if ($returncode == 0) {
            unlink($filename);
            if (substr($program, -5) == 'pdftk') {
                unlink('doc_data.txt');
            }
            foreach(scandir('.') as $file) {
                if (substr($file, 0, strlen($filename)) == $filename) {
                    chmod($file, 0666);
                }
            }
        }
    }

}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new FileByEmailPipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 
