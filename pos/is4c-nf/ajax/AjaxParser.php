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

namespace COREPOS\pos\ajax;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\AjaxCallback;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\LocalStorage\LaneCache;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;
use \CoreLocal;
use COREPOS\pos\parser\Parser;
use COREPOS\pos\parser\PostParser;
use COREPOS\pos\parser\PreParser;
use \paycardEntered;

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
        $preItem = LaneCache::get('preparse_chain');
        $preChain = $preItem->get();
        if (!is_array($preChain)) {
            $preChain = PreParser::get_preparse_chain();
            $preItem->set($preChain);
            LaneCache::set($preItem);
        }

        $session = new WrappedStorage();
        foreach ($preChain as $cn){
            if (!class_exists($cn)) continue;
            $pre = new $cn($session);
            if ($pre->check($entered)) {
                $entered = $pre->parse($entered);
                if (!$entered || $entered == "")
                    break;
            }
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
        $parseItem = LaneCache::get('parse_chain');
        $parseChain = $parseItem->get();
        if (!is_array($parseChain)) {
            $parseChain = Parser::get_parse_chain();
            $parseItem->set($parseChain);
            LaneCache::set($parseItem);
        }

        $result = False;
        $session = new WrappedStorage();
        foreach ($parseChain as $cn){
            if (!class_exists($cn)) continue;
            $parse = new $cn($session);
            if ($parse->check($entered)){
                $result = $parse->parse($entered);
                break;
            }
        }

        return $result;
    }

    // @hintable
    private function runPostParsers($result)
    {
        // postparse chain: modify result
        $postItem = LaneCache::get('postparse_chain');
        $postChain = $postItem->get();
        if (!is_array($postChain)) {
            $postChain = PostParser::getPostParseChain();
            $postItem->set($postChain);
            LaneCache::set($postItem);
        }
        foreach ($postChain as $class) {
            if (!class_exists($class)) {
                continue;
            }
            $obj = new $class();
            $result = $obj->parse($result);
        }

        return $result;
    }

    // @hintable
    private function handlePaycards($entered, $json)
    {
        /* this breaks the model a bit, but I'm putting
         * putting the CC parser first manually to minimize
         * code that potentially handles the PAN */
        if ($entered != '' && in_array("Paycards",CoreLocal::get("PluginList"))) {
            /* this breaks the model a bit, but I'm putting
             * putting the CC parser first manually to minimize
             * code that potentially handles the PAN */
            if (CoreLocal::get("PaycardsCashierFacing")=="1" && substr($entered,0,9) == "PANCACHE:") {
                /* cashier-facing device behavior; run card immediately */
                $entered = substr($entered,9);
                CoreLocal::set("CachePanEncBlock",$entered);
            }

            $pce = new paycardEntered();
            if ($pce->check($entered)){
                $json = $pce->parse($entered);
                $entered = "PAYCARD";
                CoreLocal::set("strEntered","");
            }
        }

        return array($entered, $json);
    }

    // @hintable
    private function readInput($input)
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

        return $entered;
    }

    public function ajax(array $input=array())
    {
        if (CoreLocal::get('CashierNo') === '') { // session is missing/invalid
            return array(
                'main_frame' => MiscLib::baseURL() . 'login.php',
            );
        }

        $entered = $this->readInput($input);
        CoreLocal::set("strEntered",$entered);

        $json = array();
        $sdObj = MiscLib::scaleObject();
        list($entered, $json) = $this->handlePaycards($entered, $json);

        CoreLocal::set("quantity",0);
        CoreLocal::set("multiple",0);

        $entered = $this->runPreParsers($entered);

        if ($entered != "" && $entered != "PAYCARD") {
            $result = $this->runParsers($entered);
            if ($result) {
                $json = $this->runPostParsers($result);

                if (isset($json['udpmsg']) && $json['udpmsg'] !== False && is_object($sdObj)){
                    $sdObj->WriteToScale($json['udpmsg']);
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
            if (isset($json['redraw_footer']) && $json['redraw_footer'] !== false){
                $json['redraw_footer'] = DisplayLib::printfooter();
            }
            if (isset($json['scale']) && $json['scale'] !== False){
                $display = DisplayLib::scaledisplaymsg($json['scale']);
                $json['scale'] = is_array($display) ? $display['display'] : $display;
                $term_display = DisplayLib::drawNotifications();
                if (!empty($term_display)) {
                    $json['term'] = $term_display;
                }
            }
        }

        return $json;
    }
}

AjaxParser::run();

