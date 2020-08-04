#!/usr/bin/sh
#
#    This script will download phpXref and the master Branch of DokuWiki
#    from which it will create the documentation and the DocSet
#    @author: Gerry Weißbach <https://github.com/gamma>

echo "> LOADING PHPXRef"
git clone --depth=1 https://github.com/gamma/phpxref.git ./phpxref
cp -a ./phpxref.cfg ./phpxref

# checkout DokuWiki into current directory (no clone because dir isn't empty)
# the branch is specified in the $DOKUWIKI environment variable
echo "> CLONING DOKUWIKI: ${DOKUWIKI:-master}"
mkdir -p dokuwiki && cd dokuwiki && git init
git pull https://github.com/splitbrain/dokuwiki.git "${DOKUWIKI:-master}"

# remove not needed language files
echo "> Removing not needed language files"
find . -name "lang" -type d -exec find {} -type d -not -name "en" \; | xargs rm -rf
cd -

# Create Output Directory for PHPXref
mkdir -p ./output
