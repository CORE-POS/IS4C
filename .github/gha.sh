#/usr/bin/env sh

phpunit -v --debug -d max_execution_time=0 -c phpunit.xml
exit_code=$?

cat fannie/logs/*.log
cat pos/is4c-nf/*.log

exit "$exit_code"

