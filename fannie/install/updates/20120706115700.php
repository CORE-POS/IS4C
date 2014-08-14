<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class update_20120706115700 extends UpdateObj {

    protected $timestamp = '20120706115700';

    protected $description = 'This update revises
TenderTapeGeneric using grouping. This makes cash total
correct.'; 


    protected $author = 'Andy Theuninck (WFC)';

    protected $queries = array(
        'op' => array(),
        'trans' => array(
        "ALTER VIEW TenderTapeGeneric AS
        select
        max(tdate) as tdate,
        emp_no,
        register_no,
        trans_no,
        CASE WHEN trans_subtype = 'CP' AND upc LIKE '%MAD%' THEN ''
        WHEN trans_subtype IN ('EF','EC','TA') THEN 'EF'
        ELSE trans_subtype
        END AS tender_code,
        -1 * sum(total) as tender
        from dlog
        WHERE datediff(curdate(),tdate)=0
        and trans_subtype not in ('0','')
        GROUP BY emp_no, register_no, trans_no,
        tender_code",
        ),
        'archive' => array()
    );
}

?>
