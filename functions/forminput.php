<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Functions for cleaning and validating source input.
 *
 * $Id: forminput.php,v 1.1 2003/10/06 00:33:32 andrewziem Exp $
 *
 */

if (preg_match('/forminput.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
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

function sos_strip_tags($s)
// Conditionally strips HTML tags from string.
// Depends on html_security_level and access_admin.
{
    global $html_security_level;
    
    if (3 == $html_security_level or (!$_SESSION['user']['access_admin'] and 2 == $html_security_level))
	return strip_tags($s);
	
    if (1 == $html_security_level or ($_SESSION['user']['access_admin'] and 2 == $html_security_level))
	return strip_tags($s, '<br><b><i><hr><u>');
	
    assert(FALSE);	
}


?>