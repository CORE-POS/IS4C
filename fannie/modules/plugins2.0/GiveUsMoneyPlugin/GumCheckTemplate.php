<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class GumCheckTemplate 
{
    private $check_number;
    private $amount;
    private $amount_as_words;
    private $check_date;
    private $my_address = array();
    private $their_address = array();
    private $bank_address = array();
    
    public function __construct($custdata, $meminfo, $amount, $check_number=false, $date=false)
    {
        if (!$date) {
            $this->check_date = date('m/d/Y');
        } else {
            $this->check_date = $date;
        }

        $this->check_number = $check_number;

        $this->amount = number_format($amount, 2);
        $dollars = floor($amount);
        $cents = round(($amount - $dollars) * 100);
        $nf = new NumberFormatter('en_US', NumberFormatter::SPELLOUT);
        $this->amount_as_words = ucwords($nf->format($dollars)) . ' And ' . $cents . '/100';

        $their_address[] = $custdata->FirstName() . ' ' . $custdata->LastName();
        $their_address[] = $meminfo->street();
        $their_address[] = $meminfo->city() . ', ' . $meminfo->state() . ' ' . $meminfo->zip();
    }
}

