<?php

/*
 * Son of Service
 * Copyright (C) 2003-2004 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Delivers an e-mail message via SMTP.
 *
 * $Id: email_smtp.php,v 1.2 2004/02/22 00:26:50 andrewziem Exp $
 *
 */

if (preg_match('/email_smtp.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


// warning: experimental code
function send_email_smtp($from, $to, $cc, $bcc, $subject, $message)
{
    global $smtp_hostname;

    
    //debug
    ob_end_flush();
    
    if (empty($smtp_hostname))
    {
	process_system_error("Setting smtp_hostname not configured.");
	return FALSE;    
    }

    $fp = @fsockopen($smtp_hostname, 25, $errno, $errstr, 45);
    
    if (!$fp)
    {
	process_system_error(_("Unable to connect to SMTP server."), array('debug' => "$errstr ($errno)"));
	return FALSE;
    }
    
    // listen for HELO
    
    while (!feof($fp))
    {
	$line = fgets($fp);
	if (preg_match('/^(\d+) (\w+) ESTMP/', $line, $matches))
	{
	    $rhostname = $matches[2];
	}
	else
	{
	    process_system_error("Do not understand ".htmlentities($line)." from STMP\n");
	}
    }

    // send HELO request
    
    fwrite($fp, "HELO ".$_SERVER['SERVER_NAME']."\n");
    
    // send MAIL request
    
    fwrite($fp, "MAIL FROM: $from\n");
    
    // send RCPT requests
    
    $addresses = explode(',', $to);
    foreach ($addresses as $a)
    {
	fwrite("RCTP TO: $a\n");
    }
    
    // send DATA request
    
    fwrite ($fp, "DATA\n");
    
    // send encoded message
    
    fwrite($fp, "Date: ".date('j M Y H:i:s O')."\n");
    fwrite($fp, "From: $from\n");
    fwrite($fp, "To: $to\n");
    if (!empty($cc))
    {
        fwrite($fp, "CC: $cc\n");    
    }
    fwrite($fp, "Subject: $subject\n");
    fwrite($fp, "\n");
    fwrite($fp, "$message\n");
    fwrite($fp, ".\n");
    
    // QUIT
    
    fwrite($fp, "QUIT\n");
    
    fclose($fp);

}

?>
