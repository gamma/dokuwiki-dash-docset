#!/usr/bin/sh
#
#    This script will download phpXref and the master Branch of DokuWiki
#    from which it will create the documentation and the DocSet
#    @author: Gerry Weißbach <https://github.com/gamma>

if [ -z "$DOKUWIKI" ]; then
    DOKUWIKI=$(cat ./.travis.yml | grep "DOKUWIKI=" | awk -F= '{print $2}')
fi

BRANCH=git rev-parse --abbrev-ref HEAD
if [ -e "./dokuwiki/VERSION" ]; then
    BRANCH=$(cat ./dokuwiki/VERSION)
fi

if [ -z "BRANCH" ]; then
    BRANCH=$DOKUWIKI
fi

VERSION_NAME=$(echo "Dokuwiki $BRANCH" | xargs)
VERSION_PATH=$(echo "$BRANCH" | tr -cd '[:alnum:]_-')

DASH_CONTRIBUTIONS_PATH="../Dash-User-Contributions"
DASH_DW_CONTRIBUTIONS_PATH="${DASH_CONTRIBUTIONS_PATH}/docsets/DokuWiki"

# Create PHPXref of DokuWiki
cd ./phpxref/ && perl ./phpxref.pl && cd ..

# Prepare
DOCUMENT_BASE="${VERSION_NAME}.docset/Contents/Resources"

# Delete create
rm -rf "$DOCUMENT_BASE"
mkdir -p "$DOCUMENT_BASE"
cp -a ./output "$DOCUMENT_BASE/Documents"

# Modify Path
export PATH=`echo $PATH | sed -e 's/:\.\/[^:]*//g'`

# Generate the Docset
php generate-dokuwiki.php "${VERSION_NAME}"

# Package the Docset
tar --exclude='.DS_Store' -czf dokuwiki-docset.tgz "${VERSION_NAME}.docset"

if [ -d "${DASH_CONTRIBUTIONS_PATH}" ]; then
    echo "Will copy docset file to Dash-User-Contributions"
    cp dokuwiki-docset.tgz "${DASH_DW_CONTRIBUTIONS_PATH}/"
    
    mkdir -p "${DASH_DW_CONTRIBUTIONS_PATH}/versions/${VERSION_PATH}/"
    cp dokuwiki-docset.tgz "${DASH_DW_CONTRIBUTIONS_PATH}/versions/${VERSION_PATH}/"
fi

# Clean Up
rm -rf "./phpxref/" output dokuwiki

# Show everything
ls -altr *
du -sh *
