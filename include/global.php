<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: global.php,v 1.1 2003/10/05 16:14:13 andrewziem Exp $
 *
 */

if (preg_match('/global.php/i', $_SERVER['PHP_SELF']))
{
    process_system_error('Do not access this page directly.', array('fatal'=>TRUE));
}
 
define('SOS_PATH', '../');

require_once(SOS_PATH . 'include/config.php');
require_once(SOS_PATH . 'functions/auth.php');
require_once(SOS_PATH . 'functions/db.php');

if (!empty($_POST) and !headers_sent())
{
    // do not allow caching of POST
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");                                                        // always modified   header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0", FALSE);
    header('Pragma: no-cache');    
}

//print_r($_POST);

error_reporting(E_ALL); // for debugging

if (version_compare(phpversion(),'4.1.0','<'))
{
    // try to be compatiable with old PHP
    $_POST &= $HTTP_POST_VARS;
    $_GET &= $HTTP_GET_VARS;
    $_SERVER &= $HTTP_SERVER_VARS;
    $_SESSION &= $HTTP_SESSION_VARS;
    
    // to do; define array_key_exists
}

// to do: define _ and gettext for those without

if (!extension_loaded('gettext'))
{
    $success = FALSE;

/*
    if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN'))
    {
	$success = dl('gettext.dll');
    }    
    else
    {
	$success = dl('gettext.so');
    }
*/    
    if (!$success)
    {
	// cheap replacements
	// to do: implement full replacement
    
	function _($s)
	{
	    return $s;
	}

	function gettext($s)
	{
	    echo $s;
	}
	
    }
}

function process_user_error($text)
{
    // to do: css
   echo ("<P><FONT color=\"red\">$text</FONT></P>\n");
}


function process_user_warning($text)
{
    // to do: css
   echo ("<P>Warning: $text</P>\n");
}


function process_user_notice($text)
{
    // to do: css
   echo ("<P>$text</P>\n");
}


function process_system_error($text, $options = NULL)
{

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

function sanitize_date($date)
{
    $errors_found = 0;

    if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[-\/](\d{2,4})$/', $date, $matches))
	{// MM/DD/YY[YY]
	    if (checkdate($matches[1], $matches[2],$matches[3]))
	    {
		$date = $matches[3].'-'.$matches[1].'-'.$matches[2];
	    }
	    else
	    {
	       process_user_error("Please enter a date in the format YYYY-MM-DD or MM/DD/YYYY.");
    	       $errors_found++;       
	    }
	}
	else
	if (preg_match('/^(\d{2,4})-(\d{1,2})-(\d{1,2})$/', $date, $matches))	
	{ // [YY]YY-MM-DD
	    if (checkdate($matches[2], $matches[3],$matches[1]))
	    {
		$date = $date;
	    }
	    else
	    {
	       process_user_error("Please enter a date in the format YYYY-MM-DD or MM/DD/YYYY.");
    	       $errors_found++;       
	    }
	
	}
	else
	    $errors_found++;
	    
    if ($errors_found)
	return FALSE;
    return $date;
}

function validate_email ($email)
{
    return (preg_match('/^\w+@\w+$/', $email));
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