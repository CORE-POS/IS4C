use is4c_log;
insert into dlog_2008_pr select * from dlog_2008 where date(datetime) = DATE_SUB(curdate(), INTERVAL 1 DAY);
