<?php

namespace COREPOS\pos\parser;
use COREPOS\pos\lib\ReceiptLib;
use \ArrayAccess;
use \Countable;
use \InvalidArgumentException;
use \Iterator;
use \LogicException;
use \Serializable;
use \JsonSerializable;
use \UnexpectedValueException;

class ParseResult implements ArrayAccess, Countable, Iterator, Serializable, JsonSerializable
{
    private $value = array();
    private $position = null;

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
            'term'=>false,
            'udpmsg'=>false,
            'retry'=>false,
        );
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->value;
    }

    public function toArray()
    {
        return $this->value;
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

    /**
        Countable method(s)
    */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->value);
    }

    /**
        ArrayAccess method(s)
    */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->value[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException("ParseResult does not include '{$offset}'");
        }

        return $this->value[$offset];
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException("ParseResult does not include '{$offset}'");
        }

        $this->value[$offset] = $value;

        return $this;
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new LogicException("Cannot unset ParseResult values");
    }

    /**
        Iterator method(s)
    */
    #[\ReturnTypeWillChange]
    public function current()
    {
        $key = $this->key();
        return $key === null ? null : $this->value[$key];
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        $keys = array_keys($this->value);
        return $this->valid() ? $keys[$this->position] : null;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->position++;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        $keys = array_keys($this->value);
        return isset($keys[$this->position]);
    }

    /**
      Serializable method(s)
    */
    public function serialize()
    {
        $packed = array($this->value, $this->position);
        return serialize($packed);
    }

    public function __serialize()
    {
        return $this->serialize();
    }

    public function unserialize($data)
    {
        $packed = unserialize($data);
        if (count($packed) !==2 || !isset($packed[0]) || !isset($packed[1])) {
            throw new UnexpectedValueException('Serialized data is malformed');
        }

        $this->value = $packed[0];
        $this->position = $packed[1];
    }

    public function __unserialize($data)
    {
        return $this->unserialize($data);
    }
}

