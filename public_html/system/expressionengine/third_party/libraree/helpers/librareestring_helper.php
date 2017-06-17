<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	function substring_between($haystack,$start,$end) {
		if (strpos($haystack,$start) === false || strpos($haystack,$end) === false) {
			return false;
		} else {
			$start_position = strpos($haystack,$start)+strlen($start);
			$end_position = strpos($haystack,$end);
			return substr($haystack,$start_position,$end_position-$start_position);
		}
	}
	
	function relative_to_absolute($content, $baseUrl) { 
		//error_reporting(E_ALL ^ E_NOTICE);
		
		$text = $content;

		$urls = extract_html_urls( $text );
		$pageurls = Array();
		foreach ( $urls as $element_entry ){
		    foreach ( $element_entry as $attribute_entry ){
		        $pageurls = array_merge( $pageurls, $attribute_entry );
		 	}
		}	
		$pageurls = array_unique($pageurls);
		// Convert each URL to absolute
		$n = count( $pageurls );
		for ( $i = 0; $i < $n; $i++ ){

	    	$content = str_replace($pageurls[$i], url_to_absolute( $baseUrl, $pageurls[$i]), $content );
	    }
	    

		return $content; 
	} 

function url_to_absolute( $baseUrl, $relativeUrl )
{

    // If relative URL has a scheme, clean path and return.
    $r = split_url( $relativeUrl );
    if ( $r === FALSE )
        return FALSE;
    if ( !empty( $r['scheme'] ) )
    {
        if ( !empty( $r['path'] ) && $r['path'][0] == '/' )
            $r['path'] = url_remove_dot_segments( $r['path'] );
        return join_url( $r );
    }
 
    // Make sure the base URL is absolute.
    $b = split_url( $baseUrl );
    if ( $b === FALSE || empty( $b['scheme'] ) || empty( $b['host'] ) )
        return FALSE;
    $r['scheme'] = $b['scheme'];
 
    // If relative URL has an authority, clean path and return.
    if ( isset( $r['host'] ) )
    {
        if ( !empty( $r['path'] ) )
            $r['path'] = url_remove_dot_segments( $r['path'] );
        return join_url( $r );
    }
    unset( $r['port'] );
    unset( $r['user'] );
    unset( $r['pass'] );
 
    // Copy base authority.
    $r['host'] = $b['host'];
    if ( isset( $b['port'] ) ) $r['port'] = $b['port'];
    if ( isset( $b['user'] ) ) $r['user'] = $b['user'];
    if ( isset( $b['pass'] ) ) $r['pass'] = $b['pass'];
 
    // If relative URL has no path, use base path
    if ( empty( $r['path'] ) )
    {
        if ( !empty( $b['path'] ) )
            $r['path'] = $b['path'];
        if ( !isset( $r['query'] ) && isset( $b['query'] ) )
            $r['query'] = $b['query'];
        return join_url( $r );
    }
 
    // If relative URL path doesn't start with /, merge with base path
    if ( $r['path'][0] != '/' )
    {
        $base = mb_strrchr( $b['path'], '/', TRUE, 'UTF-8' );
        if ( $base === FALSE ) $base = '';
        $r['path'] = $base . '/' . $r['path'];
    }
    $r['path'] = url_remove_dot_segments( $r['path'] );
    return join_url( $r );
}

function url_remove_dot_segments( $path )
{
    // multi-byte character explode
    $inSegs  = preg_split( '!/!u', $path );
    $outSegs = array( );
    foreach ( $inSegs as $seg )
    {
        if ( $seg == '' || $seg == '.')
            continue;
        if ( $seg == '..' )
            array_pop( $outSegs );
        else
            array_push( $outSegs, $seg );
    }
    $outPath = implode( '/', $outSegs );
    if ( $path[0] == '/' )
        $outPath = '/' . $outPath;
    // compare last multi-byte character against '/'
    if ( $outPath != '/' &&
        (mb_strlen($path)-1) == mb_strrpos( $path, '/', 'UTF-8' ) )
        $outPath .= '/';
    return $outPath;
}


/**
 * Extract URLs from a web page.
 */
function extract_html_urls( $text )
{
    $match_elements = array(
        // HTML
        array('element'=>'a',       'attribute'=>'href'),       // 2.0
        array('element'=>'a',       'attribute'=>'urn'),        // 2.0
        array('element'=>'base',    'attribute'=>'href'),       // 2.0
        array('element'=>'form',    'attribute'=>'action'),     // 2.0
        array('element'=>'img',     'attribute'=>'src'),        // 2.0
        array('element'=>'link',    'attribute'=>'href'),       // 2.0
 
        array('element'=>'applet',  'attribute'=>'code'),       // 3.2
        array('element'=>'applet',  'attribute'=>'codebase'),   // 3.2
        array('element'=>'area',    'attribute'=>'href'),       // 3.2
        array('element'=>'body',    'attribute'=>'background'), // 3.2
        array('element'=>'img',     'attribute'=>'usemap'),     // 3.2
        array('element'=>'input',   'attribute'=>'src'),        // 3.2
 
        array('element'=>'applet',  'attribute'=>'archive'),    // 4.01
        array('element'=>'applet',  'attribute'=>'object'),     // 4.01
        array('element'=>'blockquote','attribute'=>'cite'),     // 4.01
        array('element'=>'del',     'attribute'=>'cite'),       // 4.01
        array('element'=>'frame',   'attribute'=>'longdesc'),   // 4.01
        array('element'=>'frame',   'attribute'=>'src'),        // 4.01
        array('element'=>'head',    'attribute'=>'profile'),    // 4.01
        array('element'=>'iframe',  'attribute'=>'longdesc'),   // 4.01
        array('element'=>'iframe',  'attribute'=>'src'),        // 4.01
        array('element'=>'img',     'attribute'=>'longdesc'),   // 4.01
        array('element'=>'input',   'attribute'=>'usemap'),     // 4.01
        array('element'=>'ins',     'attribute'=>'cite'),       // 4.01
        array('element'=>'object',  'attribute'=>'archive'),    // 4.01
        array('element'=>'object',  'attribute'=>'classid'),    // 4.01
        array('element'=>'object',  'attribute'=>'codebase'),   // 4.01
        array('element'=>'object',  'attribute'=>'data'),       // 4.01
        array('element'=>'object',  'attribute'=>'usemap'),     // 4.01
        array('element'=>'q',       'attribute'=>'cite'),       // 4.01
        array('element'=>'script',  'attribute'=>'src'),        // 4.01
 
        array('element'=>'audio',   'attribute'=>'src'),        // 5.0
        array('element'=>'command', 'attribute'=>'icon'),       // 5.0
        array('element'=>'embed',   'attribute'=>'src'),        // 5.0
        array('element'=>'event-source','attribute'=>'src'),    // 5.0
        array('element'=>'html',    'attribute'=>'manifest'),   // 5.0
        array('element'=>'source',  'attribute'=>'src'),        // 5.0
        array('element'=>'video',   'attribute'=>'src'),        // 5.0
        array('element'=>'video',   'attribute'=>'poster'),     // 5.0
 
        array('element'=>'bgsound', 'attribute'=>'src'),        // Extension
        array('element'=>'body',    'attribute'=>'credits'),    // Extension
        array('element'=>'body',    'attribute'=>'instructions'),//Extension
        array('element'=>'body',    'attribute'=>'logo'),       // Extension
        array('element'=>'div',     'attribute'=>'href'),       // Extension
        array('element'=>'div',     'attribute'=>'src'),        // Extension
        array('element'=>'embed',   'attribute'=>'code'),       // Extension
        array('element'=>'embed',   'attribute'=>'pluginspage'),// Extension
        array('element'=>'html',    'attribute'=>'background'), // Extension
        array('element'=>'ilayer',  'attribute'=>'src'),        // Extension
        array('element'=>'img',     'attribute'=>'dynsrc'),     // Extension
        array('element'=>'img',     'attribute'=>'lowsrc'),     // Extension
        array('element'=>'input',   'attribute'=>'dynsrc'),     // Extension
        array('element'=>'input',   'attribute'=>'lowsrc'),     // Extension
        array('element'=>'table',   'attribute'=>'background'), // Extension
        array('element'=>'td',      'attribute'=>'background'), // Extension
        array('element'=>'th',      'attribute'=>'background'), // Extension
        array('element'=>'layer',   'attribute'=>'src'),        // Extension
        array('element'=>'xml',     'attribute'=>'src'),        // Extension
 
        array('element'=>'button',  'attribute'=>'action'),     // Forms 2.0
        array('element'=>'datalist','attribute'=>'data'),       // Forms 2.0
        array('element'=>'form',    'attribute'=>'data'),       // Forms 2.0
        array('element'=>'input',   'attribute'=>'action'),     // Forms 2.0
        array('element'=>'select',  'attribute'=>'data'),       // Forms 2.0
 
        // XHTML
        array('element'=>'html',    'attribute'=>'xmlns'),
 
        // WML
        array('element'=>'access',  'attribute'=>'path'),       // 1.3
        array('element'=>'card',    'attribute'=>'onenterforward'),// 1.3
        array('element'=>'card',    'attribute'=>'onenterbackward'),// 1.3
        array('element'=>'card',    'attribute'=>'ontimer'),    // 1.3
        array('element'=>'go',      'attribute'=>'href'),       // 1.3
        array('element'=>'option',  'attribute'=>'onpick'),     // 1.3
        array('element'=>'template','attribute'=>'onenterforward'),// 1.3
        array('element'=>'template','attribute'=>'onenterbackward'),// 1.3
        array('element'=>'template','attribute'=>'ontimer'),    // 1.3
        array('element'=>'wml',     'attribute'=>'xmlns'),      // 2.0
    );
 
    $match_metas = array(
        'content-base',
        'content-location',
        'referer',
        'location',
        'refresh',
    );
 
    // Extract all elements
    if ( !preg_match_all( '/<([a-z][^>]*)>/iu', $text, $matches ) )
        return array( );
    $elements = $matches[1];
    $value_pattern = '=(("([^"]*)")|([^\s]*))';
 
    // Match elements and attributes
    foreach ( $match_elements as $match_element )
    {
        $name = $match_element['element'];
        $attr = $match_element['attribute'];
        $pattern = '/^' . $name . '\s.*' . $attr . $value_pattern . '/iu';
        if ( $name == 'object' )
            $split_pattern = '/\s*/u';  // Space-separated URL list
        else if ( $name == 'archive' )
            $split_pattern = '/,\s*/u'; // Comma-separated URL list
        else
            unset( $split_pattern );    // Single URL
        foreach ( $elements as $element )
        {
            if ( !preg_match( $pattern, $element, $match ) )
                continue;
            $m = empty($match[3]) ? $match[4] : $match[3];
            if ( !isset( $split_pattern ) )
                $urls[$name][$attr][] = $m;
            else
            {
                $msplit = preg_split( $split_pattern, $m );
                foreach ( $msplit as $ms )
                    $urls[$name][$attr][] = $ms;
            }
        }
    }
 
    // Match meta http-equiv elements
    foreach ( $match_metas as $match_meta )
    {
        $attr_pattern    = '/http-equiv="?' . $match_meta . '"?/iu';
        $content_pattern = '/content'  . $value_pattern . '/iu';
        $refresh_pattern = '/\d*;\s*(url=)?(.*)$/iu';
        foreach ( $elements as $element )
        {
            if ( !preg_match( '/^meta/iu', $element ) ||
                !preg_match( $attr_pattern, $element ) ||
                !preg_match( $content_pattern, $element, $match ) )
                continue;
            $m = empty($match[3]) ? $match[4] : $match[3];
            if ( $match_meta != 'refresh' )
                $urls['meta']['http-equiv'][] = $m;
            else if ( preg_match( $refresh_pattern, $m, $match ) )
                $urls['meta']['http-equiv'][] = $match[2];
        }
    }
 
    // Match style attributes
    $urls['style'] = array( );
    $style_pattern = '/style' . $value_pattern . '/iu';
    foreach ( $elements as $element )
    {
        if ( !preg_match( $style_pattern, $element, $match ) )
            continue;
        $m = empty($match[3]) ? $match[4] : $match[3];
        $style_urls = extract_html_urls( $m );
        if ( !empty( $style_urls ) )
            $urls['style'] = array_merge_recursive(
                $urls['style'], $style_urls );
    }
 
    // Match style bodies
    if ( preg_match_all( '/<style[^>]*>(.*?)<\/style>/siu', $text, $style_bodies ) )
    {
        foreach ( $style_bodies[1] as $style_body )
        {
            $style_urls = extract_html_urls( $style_body );
            if ( !empty( $style_urls ) )
                $urls['style'] = array_merge_recursive(
                    $urls['style'], $style_urls );
        }
    }
    if ( empty($urls['style']) )
        unset( $urls['style'] );
 
    return $urls;
}

function split_url( $url, $decode=TRUE )
{
    $xunressub     = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
    $xpchar        = $xunressub . ':@%';

    $xscheme       = '([a-zA-Z][a-zA-Z\d+-.]*)';

    $xuserinfo     = '((['  . $xunressub . '%]*)' .
                     '(:([' . $xunressub . ':%]*))?)';

    $xipv4         = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

    $xipv6         = '(\[([a-fA-F\d.:]+)\])';

    $xhost_name    = '([a-zA-Z\d-.%]+)';

    $xhost         = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
    $xport         = '(\d*)';
    $xauthority    = '((' . $xuserinfo . '@)?' . $xhost .
                     '?(:' . $xport . ')?)';

    $xslash_seg    = '(/[' . $xpchar . ']*)';
    $xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
    $xpath_rel     = '([' . $xpchar . ']+' . $xslash_seg . '*)';
    $xpath_abs     = '(/(' . $xpath_rel . ')?)';
    $xapath        = '(' . $xpath_authabs . '|' . $xpath_abs .
                     '|' . $xpath_rel . ')';

    $xqueryfrag    = '([' . $xpchar . '/?' . ']*)';

    $xurl          = '^(' . $xscheme . ':)?' .  $xapath . '?' .
                     '(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';
 
 
    // Split the URL into components.
    if ( !preg_match( '!' . $xurl . '!', $url, $m ) )
        return FALSE;
 
    if ( !empty($m[2]) )        $parts['scheme']  = strtolower($m[2]);
 
    if ( !empty($m[7]) ) {
        if ( isset( $m[9] ) )   $parts['user']    = $m[9];
        else            $parts['user']    = '';
    }
    if ( !empty($m[10]) )       $parts['pass']    = $m[11];
 
    if ( !empty($m[13]) )       $h=$parts['host'] = $m[13];
    else if ( !empty($m[14]) )  $parts['host']    = $m[14];
    else if ( !empty($m[16]) )  $parts['host']    = $m[16];
    else if ( !empty( $m[5] ) ) $parts['host']    = '';
    if ( !empty($m[17]) )       $parts['port']    = $m[18];
 
    if ( !empty($m[19]) )       $parts['path']    = $m[19];
    else if ( !empty($m[21]) )  $parts['path']    = $m[21];
    else if ( !empty($m[25]) )  $parts['path']    = $m[25];
 
    if ( !empty($m[27]) )       $parts['query']   = $m[28];
    if ( !empty($m[29]) )       $parts['fragment']= $m[30];
 
    if ( !$decode )
        return $parts;
    if ( !empty($parts['user']) )
        $parts['user']     = rawurldecode( $parts['user'] );
    if ( !empty($parts['pass']) )
        $parts['pass']     = rawurldecode( $parts['pass'] );
    if ( !empty($parts['path']) )
        $parts['path']     = rawurldecode( $parts['path'] );
    if ( isset($h) )
        $parts['host']     = rawurldecode( $parts['host'] );
    if ( !empty($parts['query']) )
        $parts['query']    = rawurldecode( $parts['query'] );
    if ( !empty($parts['fragment']) )
        $parts['fragment'] = rawurldecode( $parts['fragment'] );
    return $parts;
}

function join_url( $parts, $encode=TRUE )
{
    if ( $encode )
    {
        if ( isset( $parts['user'] ) )
            $parts['user']     = rawurlencode( $parts['user'] );
        if ( isset( $parts['pass'] ) )
            $parts['pass']     = rawurlencode( $parts['pass'] );
        if ( isset( $parts['host'] ) &&
            !preg_match( '!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'] ) )
            $parts['host']     = rawurlencode( $parts['host'] );
        if ( !empty( $parts['path'] ) )
            $parts['path']     = preg_replace( '!%2F!ui', '/',
                rawurlencode( $parts['path'] ) );
        if ( isset( $parts['query'] ) )
            $parts['query']    = rawurlencode( $parts['query'] );
        if ( isset( $parts['fragment'] ) )
            $parts['fragment'] = rawurlencode( $parts['fragment'] );
    }
 
    $url = '';
    if ( !empty( $parts['scheme'] ) )
        $url .= $parts['scheme'] . ':';
    if ( isset( $parts['host'] ) )
    {
        $url .= '//';
        if ( isset( $parts['user'] ) )
        {
            $url .= $parts['user'];
            if ( isset( $parts['pass'] ) )
                $url .= ':' . $parts['pass'];
            $url .= '@';
        }
        if ( preg_match( '!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'] ) )
            $url .= '[' . $parts['host'] . ']'; // IPv6
        else
            $url .= $parts['host'];             // IPv4 or name
        if ( isset( $parts['port'] ) )
            $url .= ':' . $parts['port'];
        if ( !empty( $parts['path'] ) && $parts['path'][0] != '/' )
            $url .= '/';
    }
    if ( !empty( $parts['path'] ) )
        $url .= $parts['path'];
    if ( isset( $parts['query'] ) )
        $url .= '?' . $parts['query'];
    if ( isset( $parts['fragment'] ) )
        $url .= '#' . $parts['fragment'];
    return $url;
}

function isValidLicense($key){

	if(substr_count($key, '-') == 4 && strlen($key) == 36){
		return TRUE;
	}else{
		return FALSE;
	}
}
?>