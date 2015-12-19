<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

/**
  @class SigImage

*/
class SigImage 
{
    public function setConfig()
    {
    }

    public function setLogger()
    {
    }

    public function setConnection()
    {
    }


    public function draw_page()
    {
        include(dirname(__FILE__).'/../../config.php');
        $dbc = FannieDB::getReadOnly($FANNIE_TRANS_DB);

        $id = FormLib::get('id', 0);
        $prep = $dbc->prepare('SELECT filetype, filecontents FROM CapturedSignature WHERE capturedSignatureID=?');
        $result = $dbc->execute($prep, array($id));
        if ($dbc->num_rows($result) > 0) {
            $row = $dbc->fetch_row($result);
            switch(strtoupper($row['filetype'])) {
                case 'BMP':
                    header('Content-type: image/bmp');
                    break;
                case 'PNG':
                    header('Content-type: image/png');
                    break;
                case 'JPG':
                    header('Content-type: image/jpeg');
                    break;
                case 'GIF':
                    header('Content-type: image/gif');
                    break;
                default:
                    // Content-type: application/octet-stream
                    // may be helpful in this scenario but appears
                    // to be technically incorrect. in any event
                    // it really should not occur
                    break;
            }

            echo $row['filecontents'];
        }
    }

    public function unitTest($phpunit)
    {
        $this->setConfig();
        $this->setLogger();
        $this->setConnection();
        ob_start();
        $this->draw_page();
        ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

