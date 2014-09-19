<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
include_once('../classlib2.0/FannieAPI.php');
include('util.php');

class InstallThemePage extends InstallPage
{
    protected $title = 'Fannie: Theme Settings';
    protected $header = 'Fannie: Theme Settings';

    public function __construct()
    {

        // To set authentication.
        FanniePage::__construct();

        // Link to a file of CSS by using a function.
        $this->add_css_file("../src/style.css");
        $this->add_css_file("../src/css/configurable.php");
        $this->add_css_file("../src/javascript/jquery-ui.css");
        $this->add_css_file("../src/css/install.css");

        // Link to a file of JS by using a function.
        $this->add_script("../src/javascript/jquery.js");
        $this->add_script("../src/javascript/jquery-ui.js");

        // __construct()
    }

    function body_content()
    {
        include('../config.php');
        ob_start();
        echo showInstallTabs('Theming');
        ?>

        <form action="InstallThemePage.php" method="post">
        <h1 class="install"><?php echo $this->header; ?></h1>
        <?php
        if (is_writable('../config.php')) {
            echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
        } else {
            echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
        }

        echo '<h4 class="install">Colors</h4>';
        echo '<table id="colorsConfTable">'; 

        echo '<tr><td>Background Color</td>'
            . '<td>' . installTextField('FANNIE_CSS_BG_COLOR', $FANNIE_CSS_BG_COLOR, '#FFFFFF') . '</td>'
            . '<td><div style="background-color: ' . $FANNIE_CSS_BG_COLOR . '; width: 20px; margin:3px; '
            . ' height: 20px; border:solid 1px black; "></div></td>'
            . '</tr>';

        echo '<tr><td>Text Color</td>'
            . '<td>' . installTextField('FANNIE_CSS_FG_COLOR', $FANNIE_CSS_FG_COLOR, '#222222') . '</td>'
            . '<td><div style="background-color: ' . $FANNIE_CSS_FG_COLOR . '; width: 20px; margin:3px; '
            . ' height: 20px; border:solid 1px black; "></div></td>'
            . '</tr>';

        echo '<tr><td>Primary Highlight Color</td>'
            . '<td>' . installTextField('FANNIE_CSS_PRIMARY_COLOR', $FANNIE_CSS_PRIMARY_COLOR, '#330066') . '</td>'
            . '<td><div style="background-color: ' . $FANNIE_CSS_PRIMARY_COLOR . '; width: 20px; margin:3px; '
            . ' height: 20px; border:solid 1px black; "></div></td>'
            . '</tr>';

        echo '<tr><td>Secondary Highlight Color</td>'
            . '<td>' . installTextField('FANNIE_CSS_SECONDARY_COLOR', $FANNIE_CSS_SECONDARY_COLOR, '#444444') . '</td>'
            . '<td><div style="background-color: ' . $FANNIE_CSS_SECONDARY_COLOR . '; width: 20px; margin:3px; '
            . ' height: 20px; border:solid 1px black; "></div></td>'
            . '</tr>';

        echo '</table>';

        echo '<h4 class="install">Other</h4>';

        echo '<table id="otherConfTable">'; 

        echo '<tr><td>Custom Title</td>'
            . '<td>' . installTextField('FANNIE_CUSTOM_TITLE', $FANNIE_CUSTOM_TITLE, '') . '</td>';

        echo '<tr><td>Font</td>'
            . '<td>' . installTextField('FANNIE_CSS_FONT', $FANNIE_CSS_FONT, '') . '</td>';
        $family = str_replace(';', '', $FANNIE_CSS_FONT);
        $family = str_replace('\'', '"', $family);
        $family = rtrim($family, ',');
        $family .= ', arial, sans-serif';
        echo '<td><div style=\'font-family: ' . $family . '; margin:3px; padding:5px;'
            . ' border:solid 1px black; \'>Lorem Ipsum</div></td>'
            . '</tr>';

        echo '<tr><td>Character Set</td>'
            . '<td>' . installTextField('FANNIE_CHARSET', $FANNIE_CHARSET, 'ISO-8859-1') . '</td>'
            . '</tr>';

        echo '<tr><td>Logo</td>'
            . '<td>' . installTextField('FANNIE_CSS_LOGO', $FANNIE_CSS_LOGO, '') . '</td>'
            . '<td><img src="' . $FANNIE_CSS_LOGO . '" alt="logo preview" /></td>'
            . '</tr>';

        echo '</table>';
        echo '<hr />
            <input type=submit value="Update" />
            </form>';

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

