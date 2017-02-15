<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

class ParseException extends Exception
{
    public function __construct($message=null, $code=0, Exception $previous=null)
    {
        parent::__construct('parseexception', $code, $previous);
        $this->message = $this->validateMessage($message);
    }

    /**
      Ensure the message is a valid Parser return array
    */
    private function validateMessage($message)
    {
        if (!is_array($message)) {
            throw new Exception('Invalid Parse Exception: ' . print_r($message, true));
        }
        $parse = new Parser();
        $valid = $parse->default_json();
        foreach ($message as $key => $val) {
            $valid[$key] = $val;
        }

        return $valid;
    }

    public function __getString()
    {
        return print_r($this->message, true);
    }
}

