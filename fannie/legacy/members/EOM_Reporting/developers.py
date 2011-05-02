import datetime

today = datetime.datetime.today()
year = today.year
month = today.month

dlog = "("
for i in xrange(3):
	month -= 1
	if month == 0:
		month = 12
		year -= 1
	dlog += "SELECT * FROM trans_archive.dbo.dlog%d%s" % (year, str(month).zfill(2))
	if i != 2: dlog += " UNION ALL "
	else: dlog += ")"

################################################################
# SQL Note:
# The last three columns in Defectors are named fiveMonthsAgo
# fourMonthsAgo, and threeMonthsAgo. That's correct for
# defectors but wrong for developers. I'm sticking more recent
# data in those columns here.
#
# The two were merged into one table so that total mailing
# per period time could be limited. The column names are
# an artificat of earlier separation
################################################################
query = """
INSERT INTO Defectors 
select d.card_no,'DEVELOPER',getdate(),
sum(case when datediff(mm,getdate(),tdate)=-3
	and trans_type in ('D','I') then d.total else 0 end) as month1,
sum(case when datediff(mm,getdate(),tdate)=-2
	and trans_type in ('D','I') then d.total else 0 end) as month2,
sum(case when datediff(mm,getdate(),tdate)=-1
	and trans_type in ('D','I') then d.total else 0 end) as month3
from
%s as d 
LEFT JOIN DefectionNotices as n
on d.card_no = n.card_no
LEFT JOIN custdata as c
on d.card_no=c.cardno and c.personnum=1
where noticesThisYear <= 1 and noticesThisQuarter = 0
and c.type <> 'TERM' and c.memType not in (3,9)
group by d.card_no
having 
sum(case when datediff(mm,getdate(),tdate)=-3 
	and trans_type in ('D','I') then d.total else 0 end)
	BETWEEN 0.01 and 50.00 AND
sum(case when datediff(mm,getdate(),tdate)=-2 
	and trans_type in ('D','I') then d.total else 0 end)
	BETWEEN 0.01 and 50.00 AND
sum(case when datediff(mm,getdate(),tdate)=-1 
	and trans_type in ('D','I') then d.total else 0 end)
	BETWEEN 0.01 and 50.00 """ % dlog

print query

import Sybase
conn = Sybase.connect()
db = conn.cursor()
db.execute("use WedgePOS")
db.execute(query)
conn.commit()
