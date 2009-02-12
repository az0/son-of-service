<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Functions for cleaning and validating source input.
 *
 * $Id: forminput.php,v 1.7 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

if (preg_match('/forminput.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

/**
 * sanitize_date($date)
 *
 * Given a string from the user, return a date in the format YYYY-MM-DD.
 *
 * @param string date in the format MM/DD/[YY]YY or YYYY-MM-DD. 
 * @return string date in the format YYYY-MM-DD
 */

function sanitize_date($date)
{     $errors_found = 0;

    if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[-\/](\d{2,4})$/', $date, $matches))
	{
	    // MM/DD/[YY]YY
	    if (checkdate($matches[1], $matches[2],$matches[3]))
	    {
		if ($matches[3] < 100)
		{
		    // short year
		    $matches[3] += 1900;
		
		}
		$date = sprintf("%04d-%02d-%02d", $matches[3], $matches[1], $matches[2]);
	    }
	    else
	    {
    	       $errors_found++;       
	    }
	}
	else
	if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $date, $matches))	
	{ 
	    // YYYY-MM-DD
	    if (checkdate($matches[2], $matches[3],$matches[1]))
	    {
		$date = sprintf("%04d-%02d-%02d", $matches[1], $matches[2], $matches[3]);
	    }
	    else
	    {
    	       $errors_found++;       
	    }
	
	}
	else
	{
	    $errors_found++;
	}
	    
    if ($errors_found)
    {
	return FALSE;
    }
    
    return $date;
}


/**
 * validate_email ($email)
 *
 * Validates an e-mail address.
 *
 * @param string email An e-mail address.
 * @return boolean whether valid
 */

function validate_email ($email)
{
    return (preg_match('/^[\w\d.]+@[\w\d.]+$/', $email));    
}

function sos_strip_tags($s)
// Conditionally strips HTML tags from string.
// Depends on html_security_level and access_admin.
{
    global $html_security_level;
    
    if (3 == $html_security_level or (!$_SESSION['sos_user']['access_admin'] and 2 == $html_security_level))
	return strip_tags($s);
	
    if (1 == $html_security_level or ($_SESSION['sos_user']['access_admin'] and 2 == $html_security_level))
	return strip_tags($s, '<br><b><i><hr><u>');
	
    assert(FALSE);	
}


?>
