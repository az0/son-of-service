<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: global.php,v 1.3 2003/11/28 16:25:48 andrewziem Exp $
 *
 */

if (preg_match('/global.php/i', $_SERVER['PHP_SELF']))
{
    process_system_error('Do not access this page directly.', array('fatal'=>TRUE));
}
 
define('SOS_PATH', '../');

require_once(SOS_PATH . 'include/config.php');
require_once(SOS_PATH . 'functions/access.php');
require_once(SOS_PATH . 'functions/db.php');

if (!empty($_POST) and !headers_sent())
{
    // do not allow caching of POST
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");                                                        // always modified   header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0", FALSE);
    header('Pragma: no-cache');    
}

error_reporting(E_ALL); // for debugging

if (version_compare(phpversion(),'4.1.0','<'))
{
    // try to be compatiable with old PHP
    $_POST &= $HTTP_POST_VARS;
    $_GET &= $HTTP_GET_VARS;
    $_SERVER &= $HTTP_SERVER_VARS;
    $_SESSION &= $HTTP_SESSION_VARS;
    
    // todo: define array_key_exists for old PHP compatibility
}

// todo: define _ and gettext for those without

if (!extension_loaded('gettext'))
{
    // cheap replacements
    // todo: implement full replacement
    
    function _($s)
    {
        return $s;
    }

    function gettext($s)
    {
        echo $s;
    }
}

function process_user_error($text)
{
    // todo: css
   echo ("<P><FONT color=\"red\">$text</FONT></P>\n");
}


function process_user_warning($text)
{
    // todo: css
   echo ("<P>Warning: $text</P>\n");
}


function process_user_notice($text)
{
    // todo: css
   echo ("<P>$text</P>\n");
}


function process_system_error($text, $options = NULL)
{
    // todo: logging
   echo ("<P><FONT color=\"red\">$text</FONT></P>\n");
   if (is_array($options) and array_key_exists('debug', $options))
   {
   echo ("<P><FONT color=\"red\">debug:".$options['debug']."</FONT></P>\n");      
   }
   if (is_array($options) and array_key_exists('fatal', $options))
   {
	die();
   }

}

function make_volunteer_name($row)
// $row: an array containing first, middle, last, organization
// return: name, e.g. John Smith (Smith Inc.)
{
    if (!is_array($row))
	return FALSE;
    $name = trim($row['first'].' '.$row['middle'].' '.$row['last']);
    if (!empty($row['organization']))
	$name .= ' ('.$row['organization'].')';
    return $name;	
}


$daysofweek = array(1 => 'Sunday' , 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');	

?>