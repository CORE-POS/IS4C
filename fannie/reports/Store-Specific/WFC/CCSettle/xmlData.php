<?php

class xmlData {

    var $parser;
    var $DATA;
    var $curTag;
    var $valid;

    function xmlData($str){
        $this->valid = False;
        $this->parser = xml_parser_create();
        xml_set_object($this->parser,$this);
        xml_set_element_handler($this->parser,"startTag","endTag");
        xml_set_character_data_handler($this->parser,"tagData");
        xml_parse($this->parser,$str,True);
        xml_parser_free($this->parser);
    }

    function startTag($parser,$name,$attr){
        $name = strtoupper($name);
        if (isset($attr["KEY"]))
            $name = strtoupper($attr["KEY"]);
        $this->curTag = array("tag"=>$name,"attributes"=>$attr,"chardata"=>"");
    }

    function endTag($parser,$name){
        $name = $this->curTag["tag"];
        if (!isset($this->DATA["$name"]))
            $this->DATA["$name"] = array();
        array_push($this->DATA["$name"],$this->curTag); 
        $this->valid = True;
    }

    function tagData($parser,$data){
        $this->curTag["chardata"] = $data;
    }

    function get($tagname){
        $tagname = strtoupper($tagname);
        if (!isset($this->DATA["$tagname"]))
            return False;
        if (count($this->DATA["$tagname"]) == 1){
            if (isset($this->DATA["$tagname"][0]["chardata"]))
                return $this->DATA["$tagname"][0]["chardata"];
            else
                return False;
            
        }
        else {
            $ret = array();
            foreach ($this->DATA["$tagname"] as $d)
                array_push($ret,$d["chardata"]);
            return $ret;
        }
    }

    function isValid(){ return $this->valid; }

    function get_first($tagname){
        $tagname = strtoupper($tagname);
        if (!isset($this->DATA["$tagname"]))
            return False;
        else {
            if (isset($this->DATA["$tagname"][0]["chardata"]))
                return $this->DATA["$tagname"][0]["chardata"];
            else
                return False;
        }
    }

    function array_dump(){
        $ret = array();
        foreach ($this->DATA as $field=>$value){
            if (isset($value[0]) &&
                isset($value[0]["chardata"]))
                $ret[$field] = $value[0]["chardata"];
        }
        return $ret;
    }
}

?>
