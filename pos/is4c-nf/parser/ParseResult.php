<?php

namespace COREPOS\pos\parser;
use COREPOS\pos\lib\ReceiptLib;
use \ArrayAccess;
use \InvalidArgumentException;
use \LogicException;

class ParseResult implements ArrayAccess
{
    private $value = array();

    public function __construct()
    {
        $this->value = array(
            'main_frame'=>false,
            'target'=>'.baseHeight',
            'output'=>false,
            'redraw_footer'=>false,
            'receipt'=>false,
            'trans_num'=>ReceiptLib::receiptNumber(),
            'scale'=>false,
            'udpmsg'=>false,
            'retry'=>false,
        );
    }

    /**
      Use magic method to create fluent interface
    */
    public function __call($name, $args)
    {
        if (!$this->offsetExists($name)) {
            throw new LogicException("ParseResult doesn't include '{$name}'");
        } elseif (count($args) !== 1) {
            throw new LogicException("ParseResult setters take exactly one argument");
        }

        $this->offsetSet($name, $args[0]);

        return $this;
    }

    public function offsetExists($offset)
    {
        return isset($this->value[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException("ParseResult does not include '{$offset}'");
        }

        return $this->value[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException("ParseResult does not include '{$offset}'");
        }

        $this->value[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        throw new LogicException("Cannot unset ParseResult values");
    }
}

