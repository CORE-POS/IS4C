<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

global $FANNIE_ROOT;
if (!class_exists('FannieAPI'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class PIKillerPage extends FannieRESTfulPage {

    protected $card_no = False;

    protected $must_authenticate = true;
    
    function getHeader() 
    {
        global $FANNIE_URL;
        $this->add_css_file('css/styles.css');
        $this->add_css_file($FANNIE_URL . 'src/javascript/jquery-ui.css');
        $this->add_script($FANNIE_URL.'src/javascript/jquery.js');
        $this->add_script($FANNIE_URL.'src/javascript/jquery-ui.js');
        return '<!DOCTYPE html>
            <html lang="en">
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
            <title>'.$this->title.'</title>
            </head>
            <body bgcolor="#66CC99" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
            <table width="660" height="111" border="0" cellpadding="0" cellspacing="0" bgcolor="#66cc99">
            <tr>
            <td colspan="2"><img src="images/newLogo_small1.gif" /></td>
            </tr>
            <tr>
            <td colspan="11" bgcolor="#006633">
            <div style="width:100%;height:4px;"></div><!-- spacer for vertical-align -->
            <div style="width:100%;height:1.8em;font-size:115%;">
            <a style="margin-left:15px;margin-right:15px;vertical-align: top; color:#6c9;" 
                href="'.($this->card_no?'PIMemberPage.php?id='.$this->card_no:'').'">General</a>
            <a style="margin-left:15px;margin-right:15px;vertical-align: top; color:#6c9;" 
                href="'.($this->card_no?'PIEquityPage.php?id='.$this->card_no:'').'">Equity</a>
            <a style="margin-left:15px;margin-right:15px;vertical-align: top; color:#6c9;" 
                href="'.($this->card_no?'PIArPage.php?id='.$this->card_no:'').'">AR</a>
            <a style="margin-left:15px;margin-right:15px;vertical-align: top; color:#6c9;" 
                href="'.($this->card_no?$FANNIE_URL.'mem/prefs.php?memID='.$this->card_no:'').'">Control</a>
            <a style="margin-left:15px;margin-right:15px;vertical-align: top; color:#6c9;" 
                href="'.($this->card_no?'PIPurchasesPage.php?id='.$this->card_no:'').'">Detail</a>
            <a style="margin-left:15px;margin-right:15px;vertical-align: top; color:#6c9;" 
                href="'.($this->card_no?'PIPatronagePage.php?id='.$this->card_no:'').'">Patronage</a>
            </div>
            </td>
            </tr>
            <tr>
            <td colspan="9">
            <a href="PISearchPage.php">
            <img src="images/memDown.gif" alt="" name="Members" border="0" id="Members"  /></a>
            <a href="">
            <img src="images/repUp.gif" alt="" name="Reports" width="81" height="62" border="0" id="Reports" /></a>
            <a href="" target="_top">
            <img name="Items" src="images/itemsUp.gif" border="0" alt="Items"  /></a>
            <a href="'.($this->card_no?'PIDocumentsPage.php?id='.$this->card_no:'').'">
            <img name="Reference" src="images/refUp.gif" border="0" alt="Reference"  /></a></td>
            <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
            <td colspan="2" align="center" valign="top">&nbsp;</td>
            <td width="60" align="center" valign="top">&nbsp;</td>
            <td colspan="6" align="center" valign="top">&nbsp;</td>
            <td colspan="2" align="center" valign="top" bgcolor="#66CC99">&nbsp;</td>
            </tr>';
    }

    function getFooter()
    {
        global $FANNIE_URL;
        $ret = '</table>';
        if (FannieAuth::checkLogin() !== false) {
            $ret .= '<p><span id="logininfo" style="top:50px;">';
            $ret .= 'Logged in as: '.FannieAuth::checkLogin();
            $ret .= '&nbsp;&nbsp;&nbsp;[';
            $ret .= ' <a href="'.$FANNIE_URL.'auth/ui/loginform.php?logout=yes">Logout</a> ]';
            $ret .= '</span></p>';
        } else {
            $ret .= FannieAuth::checkLogin();
        }
        $ret .= '</body></html>';

        return $ret;
    }
}
