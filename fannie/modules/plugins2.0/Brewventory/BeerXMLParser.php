<?php
/**
  @class BeerXMLParser
  Class to read BeerXML files
*/
class BeerXMLParser {
    
    private $data = array('Hops'=>array(),
            'Fermentables'=>array(),
            'Yeast'=>array(),
            'Misc'=>array()
    );

    private $hop;
    private $ferm;
    private $yeast;
    private $misc;
    private $outer_element = "";
    private $current_element = array();

    public function BeerXMLParser($filename){
        $file = file_get_contents($filename);
        if (!$file) $this->data = False;
        else {
            // beersmith bad data correction
                        $file = str_replace(chr(0x01),"",$file);

            $xml_parser = xml_parser_create();
            xml_set_object($xml_parser,$this);
            xml_set_element_handler($xml_parser, "startElement", "endElement");
            xml_set_character_data_handler($xml_parser, "charData");
            xml_parse($xml_parser, $file, True);
            xml_parser_free($xml_parser);
        }
    }

    public function get_data(){
        return $this->data;
    }

    private function startElement($parser,$name,$attrs){
        switch(strtolower($name)){
        case 'hop':
            $this->outer_element = "hop";
            $this->hop = array();
            break;
        case 'fermentable':
            $this->outer_element = "fermentable";
            $this->ferm = array();
            break;
        case 'yeast':
            $this->outer_element = "yeast";
            $this->yeast = array();
            break;
        case 'misc':
            $this->outer_element = "misc";
            $this->misc = array();
            break;
        }
        array_unshift($this->current_element,strtolower($name));
    }

    private function endElement($parser,$name){
        switch(strtolower($name)){
        case 'hop':
            $this->data['Hops'][] = $this->hop;
            break;
        case 'fermentable':
            $this->data['Fermentables'][] = $this->ferm;
            break;
        case 'yeast':
            $this->data['Yeast'][] = $this->yeast;
            break;
        case 'misc':
            $this->data['Misc'][] = $this->misc;
            break;
        }
        array_shift($this->current_element);
    }

    private function charData($parser,$data){
        switch($this->outer_element){
        case 'hop':
            if (!isset($this->hop[$this->current_element[0]]))
                $this->hop[$this->current_element[0]] = "";
            $this->hop[$this->current_element[0]] .= $data;
            break;
        case 'fermentable':
            if (!isset($this->ferm[$this->current_element[0]]))
                $this->ferm[$this->current_element[0]] = "";
            $this->ferm[$this->current_element[0]] .= $data;
            break;
        case 'yeast':
            if (!isset($this->yeast[$this->current_element[0]]))
                $this->yeast[$this->current_element[0]] = "";
            $this->yeast[$this->current_element[0]] .= $data;
            break;
        case 'misc':
            if (!isset($this->misc[$this->current_element[0]]))
                $this->misc[$this->current_element[0]] = "";
            $this->misc[$this->current_element[0]] .= $data;
            break;
        }
    }

}

