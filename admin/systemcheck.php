<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: systemcheck.php,v 1.10 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

if (preg_match('/systemcheck.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function is_ip_private($ip)
// Tests whether an IP address is Class A, B, or C private.
// Only works for IP v4.
// Reference: RFC 1918
{
    $b = FALSE;

    $b = (preg_match('/^172.(\d{2})./', $ip, $matches) and 16 <= $matches[1] and $matches[1] <= 31);

    return (preg_match('/^127./',$ip) or preg_match('/^192.168.0./', $ip) or $b  or preg_match('/^10./', $ip));
}

function bool_to_text($b)
{
    assert(is_bool($b));
    
    return ($b ? _("True") : _("False"));
}


function system_check()
{
    global $system_check_data;
    
    echo ("<H2>System check</H2>\n");
    
    echo ("<P>This simplistic check detects some common errors.  For more information, see the SOS administrator's manual.</P>\n");
    
    $system_check_data = array();
    
    if (!has_permission(PC_ADMIN, PT_READ, NULL, NULL))
    {
	die_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    
    
    // todo: do better
    
    // todo: same for Windows
    $c['title'] = 'Security: /etc/shadow readable';
    $c['pass'] = !(is_file('/etc/shadow') and is_readable('/etc/shadow'));
    $c['result'] = bool_to_text(!$c['pass']);
    $system_check_data[] = $c;
    
    $c['title'] = 'Security: SOS world-writeable files/directories';
    $files = '';
    $dir = opendir(SOS_PATH);
    while (FALSE != ($fname = readdir($dir)))
    {
	// todo: recursive
	//echo ("$dir $fname<BR>\n");
	$perm = fileperms(SOS_PATH.$fname);
	if ($perm & 2)
	    $files .= $fname."\n";
    }
    closedir($dir);
    $c['result'] = empty($files)  ? _("None") :  $files ;
    $c['pass'] = empty($files);
    $system_check_data[] = $c;
    unset($files);
    
    // check PHP
    $c['title'] = 'PHP setting: register_globals';
    $c['result'] = bool_to_text(ini_get('register_globals') == 1);
    $c['pass'] = ini_get('register_globals') != 1;
    $system_check_data[] = $c;

    $c['title'] = 'PHP setting: magic_quotes_gpc';
    $c['result'] = bool_to_text(ini_get('magic_quotes_gpc') == 1);
    $c['pass'] = ini_get('magic_quotes_gpc') != 1;
    $system_check_data[] = $c;

    $c['title'] = 'PHP setting: safe_mode';
    $c['result'] = bool_to_text(ini_get('safe_mode') == 1);
    $c['pass'] = ini_get('safe_mode') != 1;
    $system_check_data[] = $c;
    
    $c['title'] = 'PHP: gettext module';
    $c['result'] = bool_to_text(extension_loaded('gettext'));
    $c['pass'] = extension_loaded('gettext');
    $system_check_data[] = $c;
    
    $c['title'] = 'PHP version';
    $c['result'] = phpversion();
    $c['pass'] = version_compare(phpversion(),'4.3.1','>=');
    $system_check_data[] = $c;
    
    
    // check database
    
    // check client
    
    $c['title'] = 'Client: Supports JavaScript';
    if (ini_get('browscap'))
    {
	$browser = get_browser(NULL, TRUE);
	$c['result'] = $browser['javascript'];
	$c['pass'] = $browser['javascript'] == TRUE;
    }
    else
    {
	$c['result'] = 'Unknown';
	$c['pass'] = FALSE;    
    }    
    $system_check_data[] = $c;
    
    // check connection
    
    $c['title'] = 'Connection: SSL missing';

    // fails if SSL inactive and client uses public IP address
    // todo: better SSL checking
    $c['pass'] = ($_SERVER['SERVER_PORT'] == 443) or is_ip_private($_SERVER['REMOTE_ADDR']);
    $c['result'] = "SSL: ". bool_to_text($_SERVER['SERVER_PORT'] == 443). ", Remote IP public: ".bool_to_text(!is_ip_private($_SERVER['REMOTE_ADDR']));
    $system_check_data[] = $c;
    
    // check database for orphans
    
    // check database for invalid values
    
    // todo: check latest version against SOS web site
    
    // display test results
    echo ("<TABLE border=\"1\">\n");
    echo ("<TR>\n");
    echo ("<TH>"._("Test name")."</TH>\n");
    echo ("<TH>"._("Result")."</TH>\n");    
    echo ("</TR>\n");
    
    foreach ($system_check_data as $sc)
    {
	echo ("<TR>\n");
	echo ("<TD>".$sc['title']."</TD>\n");
	echo ("<TD>".($sc['pass'] ? '' : "<SPAN class=\"errortext\">").$sc['result'].($sc['pass'] ? '' : "</SPAN>")."</TD>\n");
	echo ("</TR>\n");	
    }
    echo ("</TABLE>\n");
}


?>
