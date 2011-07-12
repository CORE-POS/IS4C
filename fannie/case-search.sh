#!/bin/sh

find . -name '*.php' | xargs grep -i $1 | grep -v $1
