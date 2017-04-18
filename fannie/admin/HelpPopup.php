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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}

class HelpPopup extends FanniePage 
{
    public $discoverable = false;

    public function preprocess()
    {
        if (trim(FormLib::get('comments')) !== '') {

            $info = FormLib::get('comments');
            $email = FormLib::get('email');
            $page = FormLib::get('page');
            $msg = "Email: {$email}\nPage: {$page}\n\n$info\n";
            mail('feedback@techsupport.coop', 'CORE Help Feedback', $msg);

            return false;
        }

        return true;
    }

    public function drawPage()
    {
        $url = $this->config->URL;
        if ($this->preprocess() === false) {
            return;
        }
        ?>
        <!doctype html>
        <html>
        <head>
            <title>CORE Help</title>
            <?php if (file_exists(dirname(__FILE__) . '/../src/javascript/composer-components')) { ?>
            <link rel="stylesheet" type="text/css" 
                href="<?php echo $url; ?>src/javascript/composer-components/bootstrap/css/bootstrap.min.css">
            <link rel="stylesheet" type="text/css" 
                href="<?php echo $url; ?>src/javascript/composer-components/bootstrap-default/css/bootstrap.min.css">
            <link rel="stylesheet" type="text/css" 
                href="<?php echo $url; ?>src/javascript/composer-components/bootstrap-default/css/bootstrap-theme.min.css">
            <script type="text/javascript"
                src="<?php echo $url; ?>src/javascript/composer-components/jquery/jquery.min.js"></script>
            <script type="text/javascript"
                src="<?php echo $url; ?>src/javascript/composer-components/bootstrap/js/bootstrap.min.js"></script>
            <?php } else { ?>
            <link rel="stylesheet" type="text/css" 
                href="<?php echo $url; ?>src/javascript/bootstrap/css/bootstrap.min.css">
            <link rel="stylesheet" type="text/css" 
                href="<?php echo $url; ?>src/javascript/bootstrap-default/css/bootstrap.min.css">
            <link rel="stylesheet" type="text/css" 
                href="<?php echo $url; ?>src/javascript/bootstrap-default/css/bootstrap-theme.min.css">
            <script type="text/javascript"
                src="<?php echo $url; ?>src/javascript/jquery.js"></script>
            <script type="text/javascript"
                src="<?php echo $url; ?>src/javascript/bootstrap/js/bootstrap.min.js"></script>
            <?php } ?>
            <link rel="stylesheet" type="text/css" 
                href="<?php echo $url; ?>src/javascript/jquery-ui.css">
            <script type="text/javascript"
                src="<?php echo $url; ?>src/javascript/jquery-ui.js"></script>
            <link rel="stylesheet" type="text/css" 
                href="<?php echo $url; ?>src/css/configurable.php">
            <link rel="stylesheet" type="text/css" 
                href="<?php echo $url; ?>src/css/core.css">
            <link rel="stylesheet" type="text/css" 
                href="<?php echo $url; ?>src/css/print.css">
            <script type="text/javascript">
            $(document).ready(function(){
                var opener = window.opener;
                if (opener) {
                    var help = opener.$('#help-modal');
                    $('body').append(help.html());
                    $('#popout-btn').remove();
                    $('.close-btn').removeAttr('data-dismiss');
                    $('.close-btn').click(function(){ close(); });
                }
            });
            </script>
        </head>
        <body>
        </body>
        </html>
        <?php
    }

    public function unitTest($phpunit)
    {
        ob_start();
        $this->drawPage();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
    }
}

FannieDispatch::conditionalExec();

