#!/usr/bin/python

""" usage: trends.py [start date] [end date]
    date format is yyyy-mm-dd
    returns a comma separated list of dates between the
    start and end dates
"""

import datetime
import sys
import string

""" read command line args """
start = sys.argv[1]
end = sys.argv[2]

""" make dates out of the strings """
temp = string.split(start,'-')
start_date = datetime.date(int(temp[0]),int(temp[1]),int(temp[2]))
temp = string.split(end,'-')
end_date = datetime.date(int(temp[0]),int(temp[1]),int(temp[2]))

""" difference between dates """
diff = end_date - start_date

""" one day object for incrementing """
one = datetime.timedelta(days=1)

""" append """
ret = start_date.isoformat()+","
for i in xrange(diff.days - 1):
    start_date += one
    ret += start_date.isoformat()+","
ret += end_date.isoformat()

""" output """
print ret