#!/usr/bin/sh
#
#    This script will download phpXref and the master Branch of DokuWiki
#    from which it will create the documentation and the DocSet
#    @author: Gerry Weißbach <https://github.com/gamma>

# Create PHPXref of DokuWiki
cd phpxref-0.7.1 && perl ./phpxref.pl && cd ..

# Prepare
DOCUMENT_BASE="DokuWiki.docset/Contents/Resources"

# Delete create
rm -rf $DOCUMENT_BASE
mkdir -p $DOCUMENT_BASE
cp -a ./output "$DOCUMENT_BASE/Documents"

# Modify Path
export PATH=`echo $PATH | sed -e 's/:\.\/[^:]*//g'`

echo "Renaming to lowercase"
sh --version
shopt -s globstar
rename -fv "y/A-Z/a-z/" "$DOCUMENT_BASE/Documents/**"

find "$DOCUMENT_BASE/Documents/" -type f -name "*[A-Z]*" -execdir rename -fv "y/A-Z/a-z/" "{}" \; 2>&1 > /dev/null
find "$DOCUMENT_BASE/Documents/" -type d -name "*[A-Z]*" -execdir rename -fv "y/A-Z/a-z/" "{}" \; 2>&1 > /dev/null
echo "DONE. (Renaming to lowercase)"

# Generate the Docset
php generate-dokuwiki.php

# Package the Docset
tar --exclude='.DS_Store' -czf dokuwiki-docset.tgz DokuWiki.docset

# Clean Up
rm -rf phpxref-0.7.1* output dokuwiki

# Show everything
ls -altr *
du -sh *