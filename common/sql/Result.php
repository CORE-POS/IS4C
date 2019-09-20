<?php

namespace COREPOS\common\sql;

/**
  @class Result

  A wrapper class for SQL results. This treats keys case-insensitvely
  but otherwise should behave like a normal array. The existing code
  base assumes the database will return column name in the same case
  that they're specified in the query but Postgres is really resistant
  to doing this. This seems like the easiest way to let code keep
  refering to values like "fooID". The alternative would be a massive
  increase in identifierEscape() calls
*/
class Result implements \ArrayAccess, \Serializable, \Countable, \Iterator
{
    private $data = array();
    private $position = 0;

    public function __construct(array $arr)
    {
        foreach ($arr as $k => $v) {
            $this->data[strtolower($k)] = $v;
        }
    }

    /*
      ArrayAccess
    */

    public function offsetExists($offset)
    {
        return isset($this->data[strtolower($offset)]);
    }

    public function offsetGet($offset)
    {
        return $this->data[strtolower($offset)];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[strtolower($offset)] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[strtolower($offset)]);
    }

    /*
      Serializable
    */

    public function serialize()
    {
        return serialize($this->data);
    }

    public function unserialize($serialized)
    {
        $this->data = unserialize($serialized);
    }

    /*
      Countable
    */

    public function count()
    {
        return count($this->data);
    }

    /*
      Iterator
    */

    public function key()
    {
        $keys = array_keys($this->data);
        return $keys[$this->position];
    }

    public function current()
    {
        return $this->data[$this->key()];
    }

    public function next()
    {
        $this->position++;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        $keys = array_keys($this->data);
        return isset($keys[$this->position]);
    }

    /*
      Convert a 2D array of SQL results to an array of Result objects
      @param $rowset array of arrays
      @return [Result, Result, ...]
    */
    public static function many($rowset)
    {
        return array_map(function($i) { new Result($i); }, $rowset);
    }
}

