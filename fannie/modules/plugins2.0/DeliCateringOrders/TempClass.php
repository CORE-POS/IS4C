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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TempClass extends FannieRESTfulPage 
{

    protected $header = 'Temp Test Class';
    protected $title = 'Temp Test Class';
    protected $results = array();
    protected $orders = array();

    function preprocess(){
        
        $this->__routes[] = 'get<id><confirm>';
        $this->__routes[] = 'get<id>';
        $this->__routes[] = 'get<review>';
        $this->__routes[] = 'get<complete>';
        return parent::preprocess();
    }
    
    public function get_view()
    {
        $ret = '';
        echo '<button class="btn btn-default" onClick="window.location.reload();">Reload Page</button>';
        
        $croisCnt = 123;
        $this->print_form($croisCnt);
        
        $ret .= '<br><button type="button" class="btn btn-default" onClick="addInput(' . $croisCnt .', 2); return false;">
            Press me to complete AJAX request</button>';
        $ret .= '<div id="ajax-resp"><br>Ajax respoinse:</div>';
        
        ?>
        <script type="text/javascript">
            addInput(1, 2);
        </script>
        <?php
        
        return $ret;
    }
    
    public function print_form($i)
    {
        print  '
            <form method="get">
                <input type="text" name="test' . $i . '">
                <input type="submit">
                <br>The above form is called from print_form()
            </form>
        ';
        
        return $i;
    }
    
        public function javascriptContent()
    {
        ob_start();
        ?>
function addInput(i, plu)
{
    $.ajax({
        type: 'post',
        url: 'TempUpdate.php',
        dataType: 'json',
        data: 'i='+i+'&plu='+plu,
        success: function(resp) 
        {
             $('#ajax-resp').html(resp);
        }
    });
    
}

        <?php
        return ob_get_clean();
    }
   
}

FannieDispatch::conditionalExec();


