<?php

namespace COREPOS\Fannie\API\data;

/**
  @class DocumentImport

  A DocumentImport is a class that knows how to extract
  information from a certain kind of document - an invoice,
  a product catalog, etc. Use a DocumentImport to decouple
  processing the file from the mechanism by which the file
  is fed into the system.

  Child implementations for a specific file format will usually
  just need to override the process method.
*/
class DocumentImport
{
    /**
      Generic data holder to provide extra information to the implementing class
    */
    protected $meta = array();

    public function __construct($meta=array())
    {
        $this->meta = $meta;
    }

    public function setMeta($meta)
    {
        $this->meta;
    }

    public function import($filename)
    {
        $lines = FileData::fileToArray($filename);    
        if ($lines === false || !is_array($lines)) {
            return array('error '=> 'Could not process file');
        }

        return $this->process($lines);
    }

    /**
      Process the file's data. This is the method
      child classes should implement

      @param $lines [array of strings]
      @return [array] result information
    */
    protected function process($lines)
    {
        return array('success'=>true, 'lines_processed'=>0);
    }
}

