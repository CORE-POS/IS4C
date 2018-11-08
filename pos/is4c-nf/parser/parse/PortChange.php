<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

namespace COREPOS\pos\parser\parse;
use COREPOS\pos\parser\Parser;

class PortChange extends Parser 
{
    public function check($str)
    {
        $prefix = substr($str, 0, 6);
        return $prefix == 'SETTCP' || $prefix == 'SETUDP';
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        $prefix = substr($str, 0, 6);
        $port = substr($str, 6);
        $overrides = array($this->session->get('portOverrides'));
        if ($prefix == 'SETTCP' && is_numeric($port)) {
            $overrides['t8999'] = $port; 
        } else {
            $overrides['u9450'] = $port; 
        }
        $this->session->set('portOverrides', $overrides);

        return $ret;
    }
}

