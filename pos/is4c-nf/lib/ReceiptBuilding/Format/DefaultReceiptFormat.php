<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op.

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

namespace COREPOS\pos\lib\ReceiptBuilding\Format;
use COREPOS\pos\lib\PrintHandlers\PrintHandler;

/**
  @class DefaultReceiptFormat
  Module for print-formatting 
  receipt records. Subclasses can
  override the format() method
*/
class DefaultReceiptFormat 
{
    protected $print_handler;
    protected $line_width = 56;

    public function setPrintHandler(PrintHandler $ph)
    {
        $this->print_handler = $ph;
    }

    public function setWidth($w)
    {
        $this->line_width = is_numeric($w) ? ((int)$w) : 56;
    }
    
    /*
      boolean. 
    */
    public $is_bold;

    /**
      constructor. disables bolding by default
    */
    public function __construct(PrintHandler $ph=null, $w=56)
    {
        $is_bold = false;
        $this->print_handler = $ph;
        $this->line_width = is_numeric($w) ? ((int)$w) : 56;
    }

    /**
      Formatting function
      @param $row a single receipt record
      @return a formatted string
    */
    public function format(array $row)
    {
        return '';
    }
}

