<?php

?>

<table cellpadding=4 cellspacing=0 border=1>
<tr>
<th>Report</th><th>Description</th>
</tr>
<tr>
	<td valign=top><b>Aged Trial Balances</b><br />
	<a href=agedTrialBalances.php>Regular</a><br />
	<a href=agedTrialBalances.php?excel=xls>Excel</a>
	</td>
	<td>Summary of AR last month, two months ago, and three months ago
	for all members except:<ul>
	<li>Terminated memberships</li>
	<li>Staff Non-members</li>
	<li>Members with no AR activity in the time span</li>
	</ul></td>
</tr>
<!--
<tr>
	<td valign=top><b>AR Statements</b><br />
	<a href=../statements/makeStatement.php>PDF</a>
	</td>
	<td>
	AR Billing statements for members who had a balance two months
	ago and have not made any payments last month.
	</td>
</tr>
<tr>
	<td valign=top><b>Business AR Statements</b><br />
	<a href=../statements/makeStatementBusiness.php>PDF</a>
	</td>
	<td>
	AR Statements for businesses with activity last month
	</td>
-->
<tr>
	<td valign=top><b>Inactivations for AR</b><br />
	<a href=inactivationsAR.php>Regular</a><br />
	<a href=inactivationsAR.php?excel=xls>Excel</a>
	</td>
	<td>Lists members with a balance > $1 three months ago
	and no payments. Members who meet these conditions but
	are already inactive are currently exluded. Again,
	terminated members and staff non-members are also excluded.
	</td>
</tr>
<tr>
	<td valign=top><b>New Members Last Month</b><br />
	<a href=newMembersEOM.php>Regular</a><br />
	<a href=newMembersEOM.php?excel=xls>Excel</a>
	</td>
	<td>
	All members joining last month with contact information,
	start &amp; end dates, and first stock purchase amount.
	This report also includes potential matches against existing
	memberships.
	</td>
</tr>
<tr>
	<td valign=top><b>New Members Year to Date</b><br />
	<a href=newMembersYTD.php>Regular</a><br />
	<a href=newMembersYTD.php?excel=xls>Excel</a>
	</td>
	<td>
	All members joining this yearwith contact information,
	start &amp; end dates, and first stock purchase amount.
	</td>
</tr>
<tr>
	<td valign=top><b>First Equity Due</b><br />
	<a href=equityDue1.php>Regular</a><br />
	<a href=equityDue1.php?excel=xls>Excel</a>
	</td>
	<td>
	Members whose final equity payment is due next month
	</td>
</tr>
<tr>
	<td valign=top><b>Second Equity Due</b><br />
	<a href=equityDue2.php>Regular</a><br />
	<a href=equityDue2.php?excel=xls>Excel</a>
	</td>
	<td>
	Members whose final equity payment is due this month
	</td>
</tr>
<tr>
	<td valign=top><b>Inactivations for Equity</b><br />
	<a href=inactivationsStock.php>Regular</a><br />
	<a href=inactivationsStock.php?excel=xls>Excel</a>
	</td>
	<td>
	Members whose final equity payment was due last month
	and have less than $100 equity	
	</td>
</tr>
<tr>
	<td valign=top><b>Inactivations for Equity YTD</b><br />
	<a href=inactiveStockYTD.php>Regular</a><br />
	<a href=inactiveStockYTD.php?excel=xls>Excel</a>
	</td>
	<td>
	Members who have been inactivated or termed with less
	than $100 equity and a final due date this year.
	</td>
</tr>
<tr>
	<td valign=top><b>Aged Patronage Report</b><br />
	<a href=agedPatronage.php>Regular</a><br />
	<a href=agedPatronage.php?excel=xls>Excel</a>
	</td>
	<td>
	Listing of members who made no purchases in the previous month
	but visited the store at some point in the four months before that.
	There's very small chance that number of visits is occasionally underestimated,
	but I don't want to slow the report down even more to fix it.
	</td>
</tr>
<tr>
	<td valign=top><b>Shopper statistics</b><br />
	<a href="../../../modules/plugins2.0/CoreWarehouse/reports/CWDemographicsReport.php">Regular</a><br />
	<a href=demographics.php?excel=xls>Excel</a>
	</td>
	<td>
	Active members, shopping frequency, and spending
	</td>
</tr>
<tr>
	<td valign=top><b>Patronage Report (defectors)</b><br />
	<a href=defectors.php>Regular</a><br />
	<a href=defectors.php?excel=xls>Excel</a>
	</td>
	<td>
	List of members who visited the store five, four, and three months ago
	but made no visits in the previous two months. Members are selected for this
	list at most once quarterly and twice yearly 
	(In combination with the developers report below).
	Terminated memberships and
	staff are suppressed.
	</td>
</tr>
<tr>
	<td valign=top><b>Patronage Report (developers)</b><br />
	<a href=developers.php>Regular</a><br />
	<a href=developers.php?excel=xls>Excel</a>
	</td>
	<td>
	List of members who consistently shopped here for the last three months
	and spent between $0.01 and $50.00 per month.
	Members are selected for this list at most once quarterly and twice yearly 
	(In combination with the defectors report above).
	Terminated memberships and staff are suppressed.
	</td>
</tr>
<tr>
	<td valign=top><b>Inactivations Last Month</b><br />
	<a href=inactiveAll.php>Regular</a><br />
	<a href=inactiveAll.php?excel=xls.php>Excel</a>
	</td>
	<td>
	Members who were made inactive last month &amp; the reason given.
	This differs from the above AR and Equity above. Members in this report
	<i>are already inactive</i>. Members in the above reports <i>should be
	made inactive</i>.
	</td>
</tr>
<tr>
	<td valign=top><b>Current Inactivations</b><br />
	<a href=inactiveCurrent.php>Regular</a><br />
	<a href=inactiveCurrent.php?excel=xls>Excel</a>
	</td>
	<td>
	All members who are currently inactive.
	</td>
</tr>
</table>
