<?php

define('DOCUMENT_BASE', __DIR__ . "/DokuWiki.docset/Contents/Resources/Documents");
global $db;

function prepare() {
    global $db;
    exec("rm -rf DokuWiki.docset/Contents/Resources/");
    exec("mkdir -p DokuWiki.docset/Contents/Resources/");
    exec("cp -a " . __DIR__ . "/output " . DOCUMENT_BASE);
    exec("find " . DOCUMENT_BASE . " -name \"*[A-Z]*\" -exec rename -fv 'y/A-Z/a-z/' '{}' \;");
    
    file_put_contents(__DIR__ . "/DokuWiki.docset/Contents/Info.plist", <<<ENDE
    <?xml version="1.0" encoding="UTF-8"?>
    <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
    <plist version="1.0">
    <dict>
    	<key>CFBundleIdentifier</key>
    	<string>org.dokuwiki</string>
    	<key>CFBundleName</key>
    	<string>DokuWiki</string>
    	<key>DocSetPlatformFamily</key>
    	<string>dokuwiki</string>
    	<key>isDashDocset</key>
    	<true/>
    	<key>dashIndexFilePath</key>
    	<string>index.html</string>
    </dict>
    </plist>
ENDE

//        <key>isJavaScriptEnabled</key>
//        <true/>


    );
    copy(__DIR__ . "/icon.png", __DIR__ . "/DokuWiki.docset/icon.png");
    
    $db = new sqlite3(__DIR__ . "/DokuWiki.docset/Contents/Resources/docSet.dsidx");
    $db->query("CREATE TABLE searchIndex(id INTEGER PRIMARY KEY, name TEXT, type TEXT, path TEXT)");
    $db->query("CREATE UNIQUE INDEX anchor ON searchIndex (name, type, path)");
}

function functionReference() {
    global $db;
    foreach( array( 'Class' => '_classes',
                    'Constant' => '_constants',
                    'Function' => '_functions',
                    'Variable' => '_variables' ) as $type => $location ) {
    
        $links = array();
        $dom = new DomDocument;
        @$dom->loadHTMLFile(DOCUMENT_BASE . $location . "/index.html");
        
        // add links from the table of contents
        $stmt = $db->prepare('INSERT OR IGNORE INTO searchIndex(name, type, path) VALUES (:name,:type,:href)');
        foreach ($dom->getElementsByTagName("a") as $a) {
        	$href = $a->getAttribute("href");
        	$name = $a->getAttribute("name");
        	
        	if ( empty($href) || empty($name) ) {
            	continue;
        	}
        	
        	$href = $location . '/' . strtolower( $href );
        	if ( array_key_exists($href, $links)  ) { continue; }
        	
        	// print "Found '$type': '$name'\n";
        	$links[$href] = true;
    
            $stmt->clear();
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':type', $type, SQLITE3_TEXT);
            $stmt->bindValue(':href', $href, SQLITE3_TEXT);
            $stmt->execute();
        }
        
        $stmt->close();
        print "\nFound " . count($links) . " of type: " . $type;
    }
}


function events() {
    global $db;
    $events = array();
    $funcList = array( "Doku_Event", "register_hook", "trigger_event");
    $prefixes = implode('|', $funcList);
    exec('grep -RE -e "('.$prefixes.')[^\'\"].*?\([\'\"]" --include "*.html" --exclude-dir "_test" ' . substr(DOCUMENT_BASE, 0, -1), $events);
    foreach( $events as $line ) {
        
        list($file, $command) = explode( ':', $line, 2);
        
        $file = str_replace(DOCUMENT_BASE, '', $file); $matches = array();
        if ( !preg_match("/($prefixes)[^'\"].*?\([\'\"](.*?)[\'\"]\s?[,\)]/", $command, $matches) ) {
            continue;
        }
        
        $func = $matches[1];
        $evnt = $matches[2];
        
        if ( !array_key_exists($evnt, $events) || array_search( $events[$evnt]['func'], $funcList ) > array_search( $func, $funcList ) ) {
            $events[$evnt] = array(
                'file' => strtolower( $file ),
                'func' => $func
            );
        }
    }

    $stmt = $db->prepare('INSERT OR IGNORE INTO searchIndex(name, type, path) VALUES (:name,"Event",:href)');
    foreach( $events as $event => $data ) {
        
            if ( !is_array($data) ) { continue; }
            $stmt->clear();
            $stmt->bindValue(':name', $event, SQLITE3_TEXT);
            $stmt->bindValue(':href', strtolower( $data['file'] ), SQLITE3_TEXT);
            $stmt->execute();
    }
    
    $stmt->close();
}

function files() {
    global $db;
    
    $files = array();
    exec('find '.substr(DOCUMENT_BASE, 0, -1).' -type f -name "*.source.html"', $files);

    $stmt = $db->prepare('INSERT OR IGNORE INTO searchIndex(name, type, path) VALUES (:name,"File",:href)');
    foreach( $files as $href ) {
        $href = str_replace(DOCUMENT_BASE, '', $href);
        $file = str_replace('.source.html', '', $href);
        $matches = array();

        $stmt->clear();
        $stmt->bindValue(':name', $file, SQLITE3_TEXT);
        $stmt->bindValue(':href', strtolower( $href ), SQLITE3_TEXT);
        $stmt->execute();
    }

    $stmt->close();
}

prepare();
functionReference();
events();
files();
print "\n\n";
