#!/bin/sh

find ./pos/is4c-nf -type f -name '*.js' | while read file
do
    emsg=`node_modules/.bin/acorn --silent "$file"`;
    exit=$?
    if [ "$exit" != "0" ]; then
        echo "Error in $file: $emsg"
        exit $exit;
    fi
done

find ./fannie -type f -name '*.js' | while read file
do
    # don't rescan vendor'd files
    if [ "${file#*node_modules}" != "$file" ]; then
        continue;
    fi
    # EndCapper has jsx that will fail
    if [ "${file#*EndCapper}" != "$file" ]; then
        continue;
    fi
    echo $file;
    emsg=`node_modules/.bin/acorn --silent "$file"`;
    exit=$?
    if [ "$exit" != "0" ]; then
        echo "Error in $file: $emsg"
        exit $exit;
    fi
done
