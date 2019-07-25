#!/bin/sh

sudo apt-get update
sudo apt-get install -y puppet
sudo puppet module install puppetlabs-vcsrepo

