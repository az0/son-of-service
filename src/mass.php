<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: mass.php,v 1.2 2003/11/22 05:16:14 andrewziem Exp $
 *
 */

//ob_start();
session_start();

define('SOS_PATH', '../');

require_once(SOS_PATH . 'include/config.php');
require_once(SOS_PATH . 'include/global.php');
require_once(SOS_PATH . 'functions/auth.php');
require_once(SOS_PATH . 'functions/db.php');
require_once(SOS_PATH . 'functions/forminput.php');
require_once(SOS_PATH . 'functions/html.php');

make_html_begin('Mass volunteer action', array());

make_nav_begin();

$db = new voldbMySql();

if ($db->get_error())
{
    process_system_error(_("Unable to establish database connection: "), array('debug' => $db->get_error()));    
    die();	
}

if (array_key_exists('button_email_volunteers', $_POST))
{
    email_volunteers_form();
}
else if (array_key_exists('send_email', $_POST))
{
    $from = $_SESSION['user']['email'];
    
    if (!validate_email($from))
    {
	process_user_warning(_("Your e-mail address appears invalid: "). htmlentities($from));
    }
    
    send_email_smtp($from, $_POST['mailto'], "", "", $_POST['mailre'], $_POST['message']);
}
else
{
    die("You have reached this page incorrectly.");
}


function send_email_smtp($from, $to, $cc, $bcc, $subject, $message)
{
    global $smtp_hostname;
    
    if (empty($smtp_hostname))
    {
	process_system_error("Setting smtp_hostname not configured.");
	return FALSE;    
    }

    $fp = @fsockopen($cfg['email_smtp'], 25, $errno, $errstr, 30);
    
    if (!$fp)
    {
	process_system_error(_("Unable to connect to SMTP server."), array('debug' => "$errstr ($errno)"));
	return FALSE;
    }
    
    // listen for HELO
    
    
    while (!feof($fp))
    {
	$line = fgets($fp);
	if (preg_match('/^(\d+) (\w+) ESTMP', $line, $matches))
	{
	    $rhostname = $matches[2];
	}
	else
	{
	    process_system_warning("Do not understand ".htmlentities($line)." from STMP\n");
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
    fwrite($fp, "Subject: $subject\n");
    fwrite($fp, "\n");
    fwrite($fp, "$message\n");
    fwrite($fp, ".\n");
    
    // QUIT
    
    fwrite($fp, "QUIT\n");
    
    fclose($fp);

}

function email_volunteers_form()
{

    // collect volunteer IDs from form
    $vids = array();

    foreach ($_POST as $k => $v)
    {
//	print_r($k);
    
	if (preg_match('/^volunteer_id_(\d+)/', $k, $matches))
	{
	    //print_r($matches);
	    $vids[intval($matches[1])] = intval($matches[1]);
	}
    }    
    
    // get volunteers' email addresses and names
    
    $mailto = "";
    
    foreach ($vids as $k => $vid)
    {
	$volunteer = volunteer_get($vid);
	$name = make_volunteer_name($volunteer);
	//print_r($volunteer);
	if (empty($volunteer['email_address']))
	{
	    process_user_warning(_("Volunteer does not have an e-mail address: ").$name);
	    unset($vids[$k]);
	    break;
	}
	elseif (validate_email($volunteer['email_address']))
	{
	    process_user_warning(_("Volunteer's e-mail address appears invalid: ").$name);
	}
	$vids[$k] = array('name' => $name, 'email' => $volunteer['email_address']);
	if (strlen($mailto)>0)
	    $mailto .= ',';
	$mailto .= $vids[$k]['email'];
    }
    
    // to do: SquirrelMail, IMP, Hotmail, Yahoo e-mail
    
    echo ("<P><A href=\"mailto:".htmlentities(urlencode($mailto))."\">Use my e-mail client</A></P>\n");
    
    process_user_warning("Built-in e-mail is experimental.");
    
    echo ("<FORM method=\"post\" action=\"mass.php\">\n");
    echo ("<TABLE border=\"1\" width=\"100%\">\n");
    echo ("<TR>\n");
    echo ("<TH class=\"vert\">To</TH>\n");
    echo ("<TD width=\"100%\"><TEXTAREA name=\"mailto\" rows=\"5\" cols=\"80\">$mailto</TEXTAREA></TD>\n");
    echo ("</TR>\n");
    echo ("<TR>\n");
    echo ("<TH class=\"vert\">From</TH>\n");
    print_r($_SESSION);
    echo ("<TD><INPUT type=\"text\" name=\"mailfrom\" value=\"".$_SESSION['user']['email']."\" DISABLED></TD>\n");
    echo ("</TR>\n");
    echo ("<TR>\n");
    echo ("<TH class=\"vert\">Subject</TH>\n");
    echo ("<TD><INPUT type=\"text\" name=\"mailre\" value=\"Volunteering\" size=\"80\"></TD>\n");
    echo ("</TR>\n");
    echo ("<TR>\n");
    echo ("<TH class=\"vert\">Message</TH>\n");
    echo ("<TD><TEXTAREA name=\"message\" rows=\"20\" cols=\"80\"></TEXTAREA></TD>\n");
    echo ("</TR>\n");
    echo ("</TABLE>\n");
    
    echo ("<INPUT type=\"submit\" name=\"send_email\" value=\""._("Send")."\">\n");
    echo ("</FORM>\n");
    
    
}

make_html_end();

?>

