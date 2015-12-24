<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

use COREPOS\pos\lib\FormLib;

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

/**
  @class AjaxDecision
*/
class AjaxParser extends AjaxCallback
{
    protected $encoding = 'json';

    private $draw_page_parts = true;
    public function enablePageDrawing($p)
    {
        $this->draw_page_parts = $p;
    }

    private function runPreParsers($entered)
    {
        /* FIRST PARSE CHAIN:
         * Objects belong in the first parse chain if they
         * modify the entered string, but do not process it
         * This chain should be used for checking prefixes/suffixes
         * to set up appropriate session variables.
         */
        $parser_lib_path = MiscLib::base_url()."parser-class-lib/";
        if (!is_array(CoreLocal::get("preparse_chain")))
            CoreLocal::set("preparse_chain",PreParser::get_preparse_chain());

        foreach (CoreLocal::get("preparse_chain") as $cn){
            if (!class_exists($cn)) continue;
            $pre = new $cn();
            if ($pre->check($entered))
                $entered = $pre->parse($entered);
                if (!$entered || $entered == "")
                    break;
        }

        return $entered;
    }

    private function runParsers($entered)
    {
        /* 
         * SECOND PARSE CHAIN
         * these parser objects should process any input
         * completely. The return value of parse() determines
         * whether to call lastpage() [list the items on screen]
         */
        if (!is_array(CoreLocal::get("parse_chain")))
            CoreLocal::set("parse_chain",Parser::get_parse_chain());

        $result = False;
        foreach (CoreLocal::get("parse_chain") as $cn){
            if (!class_exists($cn)) continue;
            $parse = new $cn();
            if ($parse->check($entered)){
                $result = $parse->parse($entered);
                break;
            }
        }

        return $result;
    }

    private function runPostParsers($result)
    {
        // postparse chain: modify result
        if (!is_array(CoreLocal::get("postparse_chain"))) {
            CoreLocal::set("postparse_chain",PostParser::getPostParseChain());
        }
        foreach (CoreLocal::get('postparse_chain') as $class) {
            if (!class_exists($class)) {
                continue;
            }
            $obj = new $class();
            $result = $obj->parse($result);
        }

        return $result;
    }

    private function handlePaycards($entered, $json)
    {
        if ($entered != "") {
            /* this breaks the model a bit, but I'm putting
             * putting the CC parser first manually to minimize
             * code that potentially handles the PAN */
            if (in_array("Paycards",CoreLocal::get("PluginList"))){
                /* this breaks the model a bit, but I'm putting
                 * putting the CC parser first manually to minimize
                 * code that potentially handles the PAN */
                if(CoreLocal::get("PaycardsCashierFacing")=="1" && substr($entered,0,9) == "PANCACHE:"){
                    /* cashier-facing device behavior; run card immediately */
                    $entered = substr($entered,9);
                    CoreLocal::set("CachePanEncBlock",$entered);
                }

                $pce = new paycardEntered();
                if ($pce->check($entered)){
                    $valid = $pce->parse($entered);
                    $entered = "PAYCARD";
                    CoreLocal::set("strEntered","");
                    $json = $valid;
                }
            }
        }

        return array($entered, $json);
    }

    public function ajax($input=array())
    {
        $in_field = 'input';
        if (isset($input['field'])) {
            $in_field = $input['field'];
        }
        $entered = strtoupper(trim(FormLib::get($in_field)));
        if (substr($entered, -2) == "CL") $entered = "CL";

        if ($entered == "RI") $entered = CoreLocal::get("strEntered");

        if (FormLib::get('repeat')) {
            CoreLocal::set('msgrepeat', 1);
        } elseif (CoreLocal::get("msgrepeat") == 1 && $entered != "CL") {
            $entered = CoreLocal::get("strRemembered");
        }
        CoreLocal::set("strEntered",$entered);

        $json = array();
        $sdObj = MiscLib::scaleObject();
        list($entered, $json) = $this->handlePaycards($entered, $json);

        CoreLocal::set("quantity",0);
        CoreLocal::set("multiple",0);

        $entered = $this->runPreParsers($entered);

        if ($entered != "" && $entered != "PAYCARD") {
            $result = $this->runParsers($entered);
            if ($result && is_array($result)) {
                $result = $this->runPostParsers($result);

                $json = $result;
                if (isset($result['udpmsg']) && $result['udpmsg'] !== False && is_object($sdObj)){
                    $sdObj->WriteToScale($result['udpmsg']);
                }
            } else {
                $arr = array(
                    'main_frame'=>false,
                    'target'=>'.baseHeight',
                    'output'=>DisplayLib::inputUnknown());
                $json = $arr;
                if (is_object($sdObj))
                    $sdObj->WriteToScale('errorBeep');
            }
        }

        CoreLocal::set("msgrepeat",0);

        if (!empty($json) && $this->draw_page_parts) {
            if (isset($json['redraw_footer']) && $json['redraw_footer'] !== False){
                $json['redraw_footer'] = DisplayLib::printfooter();
            }
            if (isset($json['scale']) && $json['scale'] !== False){
                $display = DisplayLib::scaledisplaymsg($json['scale']);
                if (is_array($display))
                    $json['scale'] = $display['display'];
                else
                    $json['scale'] = $display;
                $term_display = DisplayLib::drawNotifications();
                if (!empty($term_display))
                    $json['term'] = $term_display;
            }
        }

        return $json;
    }
}

AjaxParser::run();

