<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Probes user's system for acceptance of cookie.
 *
 * $Id: cookie_probe.php,v 1.3 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

ob_start();
session_start();

define('SOS_PATH', '../');

require_once (SOS_PATH . 'include/global.php');


if (array_key_exists('phase', $_GET) and 1 == $_GET['phase'])
{
    // was cookie accepted?
    
    if (array_key_exists('probe', $_COOKIE) and 'SOS-1' == $_COOKIE['probe'])
    {
	// try to modify cookie
	setcookie('probe', 'SOS-2');
	header("Location: cookie_probe.php?phase=2");
    }
    else
    {
	process_user_error(_("Your system rejected the cookie."));
	echo ("<P>Change your settings and <A href=\"cookie_probe.php\">try again</A>.</P>\n");
    }
}
else if (array_key_exists('phase', $_GET) and 2 == $_GET['phase'])
{
    if ('SOS-2' == $_COOKIE['probe'])
    {
	process_user_notice(_("Your system's cookies are normal."));    
    }
    else
    {
	process_user_error(_("Your system rejected modification of existing cookie."));
	echo ("<P>Change your settings and <A href=\"cookie_probe.php\">try again</A>.</P>\n");	
    }
    
}
else
{
    setcookie('probe', 'SOS-1');
    header("Location: cookie_probe.php?phase=1");
}




?>

