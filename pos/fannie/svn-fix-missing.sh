#!/bin/sh

# Remove references to "missing files" - 
# usually generated images or other temporary stuff
# that got into the working copy

svn rm $( svn status | sed -e '/^!/!d' -e 's/^!//')
