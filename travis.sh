#!/usr/bin/sh
#
#    This script will download phpXref and the master Branch of DokuWiki
#    from which it will create the documentation and the DocSet
#    @author: Gerry Weißbach <https://github.com/gamma>

echo "> LOADING PHPXRef"
wget −O "phpxref.tgz" "https://github.com/gamma/phpxref/tarball/master"
tar -xzf "phpxref.tgz"
cp -a ./phpxref.cfg ./phpxref-0.7.1

# checkout DokuWiki into current directory (no clone because dir isn't empty)
# the branch is specified in the $DOKUWIKI environment variable
echo "> CLONING DOKUWIKI: Master"
mkdir -p dokuwiki
git clone --depth=1 https://github.com/splitbrain/dokuwiki.git ./dokuwiki

# Create Output Directory for PHPXref
mkdir -p ./output
