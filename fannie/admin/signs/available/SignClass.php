<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class SignClass {

    function SignClass(){
        switch($_REQUEST['action']){
        case 'start':
            echo $this->start_form();
            break;
        case 'edit':
            echo $this->edit_form();
            break;
        case 'preview':
            echo $this->preview();
            break;
        case 'pdf':
            $this->sign_pdf();
            break;
        default:
            echo 'Unknown action error!';
            break;
        }
    }

    function start_form(){

    }

    function edit_form(){

    }

    function preview(){

    }

    function sign_pdf(){

    }

}
