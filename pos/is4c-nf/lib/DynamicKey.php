<?php

namespace COREPOS\pos\lib;
use \JsonSerializable;

class DynamicKey
{
    private $label = '??';
    private $entry = '';
    private $append = true;
    private $submit = true;
    
    public function __construct($label, $entry, $append=true, $submit=true)
    {
        $this->label = $label;
        $this->entry = $entry;
        $this->append = $append;
        $this->submit = $submit;
    }

    public function jsonSerialize()
    {
        return array(
            'label' => $this->label,
            'entry' => $this->entry,
            'append' => $this->append ? true : false,
            'submit' => $this->submit ? true : false,
        );
    }
}

