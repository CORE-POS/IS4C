<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 03Apr13 AT Added prepared statements. Used SQLManger::identifer_escape
             rather than direct backticks for datestamp field variable
    * 15Feb13 EL + For trans_type D approximate cost as (total - (total*dept_margin)).
    * 27Jan13 Eric Lee Based on GeneralCosts.
    *         N.B. For trans_type D approximate cost as (total / dept markup).
    *         To exclude Cancelled transactions (X). What are D and Z?
  *                     AND t.trans_status not in ('D','X','Z')
    *         To exclude Dummy/Training transactions
    *                       AND t.emp_no not in (7000, 9999)
    *         Display: Costs, Sales, Tax1 (HST), Tax2 (GST) in same table.
    *         Might want to try to generate tax-related code from taxNames[]
    *          so the program could be more portable.

    * 25Jan13 EL Add today, yesterday, this week, last week, this month, last month options.
    *  2Jan13 Eric Lee Report of Costs, based on GeneralSales/index.php
    * + Base on a dtrans table
    * + Use variable for name of datestamp field.
    * + Exclude what the dlog view excludes
    * + For trans_type D approximate cost as (total / dept markup).
    * + Page heading.
    * + Format report as a single table. Other HTML adjustments.
    * I'm not sure the dept==1 flavour gets markup right when department has changed
    *  since the transaction.

*/
header("Location: StoreSummaryReport.php");

