<?php

namespace COREPOS\pos\plugins\Paycards\xml;

/**
  A very generic parser class for XML data
*/
class XmlData {

    var $parser;
    var $DATA;
    var $curTag;
    var $valid;

    /**
      Constructor
      Create parser from XML
      @param $str an XML string
    */
    function __construct($str){
        $this->valid = False;
        $this->parser = xml_parser_create();
        xml_set_object($this->parser,$this);
        xml_set_element_handler($this->parser,"startTag","endTag");
        xml_set_character_data_handler($this->parser,"tagData");
        xml_parse($this->parser,$str,True);
        xml_parser_free($this->parser);
    }

    /**
      xml_parser callback for an opening tag
    */
    function startTag($parser,$name,$attr){
        $name = strtoupper($name);
        if (isset($attr["KEY"]))
            $name = strtoupper($attr["KEY"]);
        $this->curTag = array("tag"=>$name,"attributes"=>$attr,"chardata"=>"");
    }

    /**
      xml_parser callback for a closing tag
    */
    function endTag($parser,$name){
        $name = $this->curTag["tag"];
        if (!isset($this->DATA["$name"]))
            $this->DATA["$name"] = array();
        array_push($this->DATA["$name"],$this->curTag);    
        $this->valid = True;
    }

    /**
      xml_parser callback for data between tags
    */
    function tagData($parser,$data){
        $this->curTag["chardata"] = $data;
    }

    /**
      Get a value by tag name
      @param $tagname the tag name
      @return
       - string value if one tag has the given name
       - an array if multiple tags have the given name
       - False if no such tag
    */
    function get($tagname){
        $tagname = strtoupper($tagname);
        if (!isset($this->DATA["$tagname"]))
            return False;
        if (count($this->DATA["$tagname"]) == 1) {
            if (isset($this->DATA["$tagname"][0]["chardata"])) {
                return $this->DATA["$tagname"][0]["chardata"];
            }
            return False;
        }
        $ret = array();
        foreach ($this->DATA["$tagname"] as $d) {
            array_push($ret,$d["chardata"]);
        }
        return $ret;
    }

    /**
      Check if at least some xml parsed successfully.
      @return True or False
    */
    function isValid(){ return $this->valid; }

    /**
      Get a value by tag name
      @param $tagname the tag name
      @return
       - String value if the tag exists
       - False if the tag doesn't exist

      This method works a tad more reliably
      than get().
    */
    function get_first($tagname){
        $tagname = strtoupper($tagname);
        if (!isset($this->DATA["$tagname"])) {
            return false;
        }
        if (isset($this->DATA["$tagname"][0]["chardata"])) {
            return $this->DATA["$tagname"][0]["chardata"];
        }
        return false;
    }

    /**
      Export XML content to an array
      @return an array
    
      Debugging method
    */
    function arrayDump(){
        $ret = array();
        foreach ($this->DATA as $field=>$value){
            if (isset($value[0]) &&
                isset($value[0]["chardata"]))
                $ret[$field] = $value[0]["chardata"];
        }
        return $ret;
    }
}

