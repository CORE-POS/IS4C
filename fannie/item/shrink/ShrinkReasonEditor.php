<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

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

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ShrinkReasonEditor extends FannieRESTfulPage
{
    protected $header = 'Shrink Reasons Editor';
    protected $title = 'Shrink Reasons Editor';
    public $themed = true;
    public $description = '[Shrink Reasons] edits the list of reasons attached to items entered as shrink.';

    public function preprocess()
    {
        $this->__routes[] = 'get<new>';
        $this->__routes[] = 'post<id><desc>';

        return parent::preprocess();
    }

    public function get_new_handler()
    {
        global $FANNIE_OP_DB;
        $reasons = new ShrinkReasonsModel(FannieDB::get($FANNIE_OP_DB));
        $reasons->description('NEW REASON');
        $reasons->save();

        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function delete_id_handler()
    {
        global $FANNIE_OP_DB;
        $reasons = new ShrinkReasonsModel(FannieDB::get($FANNIE_OP_DB));
        $reasons->shrinkReasonID($this->id);
        $reasons->delete();

        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function post_id_desc_handler()
    {
        global $FANNIE_OP_DB;
        $reasons = new ShrinkReasonsModel(FannieDB::get($FANNIE_OP_DB));
        for ($i=0; $i<count($this->id); $i++) {
            $reasons->reset();
            $reasons->shrinkReasonID($this->id[$i]);
            $reasons->description($this->desc[$i]);
            $reasons->save();    
        }
        $this->add_onload_command("showBootstrapAlert('#alert-area', 'success', 'Saved');\n");

        return true;
    }

    public function post_id_desc_view()
    {
        return $this->get_view();
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $reasons = new ShrinkReasonsModel(FannieDB::get($FANNIE_OP_DB));
        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">
            <div id="alert-area"></div>
            <table class="table">'; 
        foreach ($reasons->find('shrinkReasonID') as $reason) {
            $ret .= sprintf('<tr>
                <td><input type="text" class="form-control" name="desc[]" value="%s" /></td>
                <td><a href="%s?_method=delete&id=%d">%s</a></td>
                <input type="hidden" name="id[]" value="%d" />
                </tr>',
                $reason->description(),
                $_SERVER['PHP_SELF'],
                $reason->shrinkReasonID(),
                \COREPOS\Fannie\API\lib\FannieUI::deleteIcon(),
                $reason->shrinkReasonID()
            );
        }
        $ret .= '</table>';
        $ret .= '<p>
            <button type="submit" class="btn btn-default">Save Reasons</button>
            <a href="' . $_SERVER['PHP_SELF'] . '?new=1" class="btn btn-default">Add New Reason</a>
            </p>
            </form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Maintain a list of reasons for "shrinking" products.
            In this context, shrink means entering loss quanties
            from breakage, spoilage, etc. When entering shrink,
            the user can select a specific reason.
            </p>
            <p>
            Reasons are not strictly necessary unless the store
            wants to track why different losses take place. For
            the sake of quantity on hand, loss is loss.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->post_id_desc_view()));
    }
}

FannieDispatch::conditionalExec();

