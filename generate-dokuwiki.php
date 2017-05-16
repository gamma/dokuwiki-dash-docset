<?php

define('DOCUMENT_BASE', __DIR__ . "/DokuWiki.docset/Contents/Resources/Documents");
global $db;


function prepare() {
    global $db;
    
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

global $existing;
function existingFile( $type, $key, $href) {
    global $existing;

    if ( !is_array($existing) ) $existing = array();
    if ( !array_key_exists($type, $existing) ) $existing[$type] = array();

    if ( array_key_exists($key, $existing[$type]) ) return $existing[$type][$key];
    if ( !file_exists(DOCUMENT_BASE."/".$href) ) {
        print "Skipping: '$type':'$key' -> '$href'";
        return null;
    }
    
    $existing[$type][$key] = $href;
    return $href;
}

function functionReference() {
    global $db;
    foreach( array( 'Class' => '_classes',
                    'Constant' => '_constants',
                    'Function' => '_functions',
                    'Variable' => '_variables' ) as $type => $location ) {
    
        $links = array();
        $dom = new DomDocument;
        @$dom->loadHTMLFile(DOCUMENT_BASE . '/' . $location . "/index.html");
        
        // add links from the table of contents
        $stmt = $db->prepare('INSERT OR IGNORE INTO searchIndex(name, type, path) VALUES (:name,:type,:href)');
        foreach ($dom->getElementsByTagName("a") as $a) {
        	$href = $a->getAttribute("href");
        	$name = $a->getAttribute("name");
        	
        	if ( empty($href) || empty($name) ) {
            	continue;
        	}
        	
        	$href = existingFile($type, $name, $location . '/' . $href);
        	if ( is_null( $href )  ) { continue; }
        	// if ( array_key_exists($href, $links)  ) { continue; }
        	
        	#print "Found '$type': '$name' - '$href'\n";
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
    exec('grep -RE -e "('.$prefixes.')[^\'\"].*?\([\'\"]" --include "*.html" --exclude-dir "_test" ' . DOCUMENT_BASE, $events);
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
                'file' => $file,
                'func' => $func
            );
        }
    }

    $stmt = $db->prepare('INSERT OR IGNORE INTO searchIndex(name, type, path) VALUES (:name,"Event",:href)');
    foreach( $events as $event => $data ) {
        
            if ( !is_array($data) ) { continue; }
            $stmt->clear();
            $stmt->bindValue(':name', $event, SQLITE3_TEXT);
            $stmt->bindValue(':href', $data['file'], SQLITE3_TEXT);
            $stmt->execute();
    }
    
    $stmt->close();
}

function files() {
    global $db;
    
    $files = array();
    exec('find '.DOCUMENT_BASE.' -type f -name "*.source.html"', $files);

    $stmt = $db->prepare('INSERT OR IGNORE INTO searchIndex(name, type, path) VALUES (:name,"File",:href)');
    foreach( $files as $href ) {
        $href = str_replace(DOCUMENT_BASE, '', $href);
        $file = str_replace('.source.html', '', $href);
        $matches = array();

        $stmt->clear();
        $stmt->bindValue(':name', $file, SQLITE3_TEXT);
        $stmt->bindValue(':href', $href, SQLITE3_TEXT);
        $stmt->execute();
    }

    $stmt->close();
}

prepare();
functionReference();
events();
files();
print "\n\n";
