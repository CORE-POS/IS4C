<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class PaycardCleanTask extends FannieTask
{

    public $name = 'Paycard Cleanup';

    public $description = 'Prunes old data about integrated card transactions.
    Expird one-time-use tokens are cleared.';

    public $default_schedule = array(
        'min' => 17,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get(FannieConfig::config('TRANS_DB'));

        /**
          Change year-old tokens to 'EXP'
          This saves a few bytes since actual tokens are longer
          and complies with processor recommendations
        */
        $expires = new DateTime();
        $expires->sub(new DateInterval('P1Y'));
        $tokenP = $dbc->prepare("
            UPDATE PaycardTransactions
            SET xToken='EXP'
            WHERE dateID <= ?
                AND xToken IS NOT NULL
                AND xToken <> ''
        ");
        $dbc->execute($tokenP, array($expires->format('Ymd')));

        if (class_exists('Itgalaxy\\Bmp2Image')) {
            /**
              Convert signature images from BMPs to PNGs
              This makes the data stored about 5x smaller
            */
            $saveP = $dbc->prepare("UPDATE CapturedSignature SET filetype='PNG',filecontents=? WHERE capturedSignatureID=?");
            $cutoff = new DateTime();
            $cutoff->sub(new DateInterval('P1D'));
            $all = false;
            if (in_array('all', $this->arguments)) {
                $all = true;
                $cutoff->sub(new DateInterval('P25Y'));
                echo "ALL MODE triggered. This will probably take awhile" . PHP_EOL;
            }
            $sigP = $dbc->prepare("
                SELECT capturedSignatureID, filecontents
                FROM CapturedSignature
                WHERE filetype='BMP'
                    AND tdate >= ?
                    AND tdate < " . $dbc->curdate()
            ); 
            $sigR = $dbc->execute($sigP, array($cutoff->format('Y-m-d')));
            while ($sigW = $dbc->fetchRow($sigR)) {
                $tmpBMP = tempnam(sys_get_temp_dir(), 'bmp');
                $tmpPNG = tempnam(sys_get_temp_dir(), 'png');
                if (!file_put_contents($tmpBMP, $sigW['filecontents'])) {
                    continue;
                }

                $img = \Itgalaxy\Bmp2Image::make($tmpBMP);
                $saved = imagepng($img, $tmpPNG);
                if ($saved) {
                    $png = file_get_contents($tmpPNG);
                    $dbc->execute($saveP, array($png, $sigW['capturedSignatureID']));
                    unlink($tmpPNG);
                    if ($all) {
                        echo $sigW['capturedSignatureID'] . PHP_EOL;
                    }
                }
                unlink($tmpBMP);
            }
        }
    }
}

