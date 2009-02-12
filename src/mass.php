<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: mass.php,v 1.14 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

//ob_start();
session_start();

define('SOS_PATH', '../');

require_once(SOS_PATH . 'include/config.php');
require_once(SOS_PATH . 'include/global.php');
require_once(SOS_PATH . 'functions/access.php');
require_once(SOS_PATH . 'functions/db.php');
require_once(SOS_PATH . 'functions/forminput.php');
require_once(SOS_PATH . 'functions/formmaker.php');
require_once(SOS_PATH . 'functions/html.php');

make_html_begin('Mass volunteer action', array());

make_nav_begin();

$db = connect_db();

if (!$db)
{
    die_message(MSG_SYSTEM_ERROR, _("Error establishing database connection."), __FILE__, __LINE__);
}

if (array_key_exists('button_email_volunteers', $_POST))
{
    email_volunteers_form();
}
else if (array_key_exists('send_email', $_POST))
{

    if (!validate_email($_SESSION['sos_user']['email']))
    {
	save_message(MSG_USER_WARNING, _("Your e-mail address appears invalid: "). htmlentities($_SESSION['sos_user']['email']));
    }
    
    if (0 == strlen(trim($_POST['mailto'])))
    {
	save_message(MSG_USER_ERROR, _("At least one recipient required."));
    }
        
    require(SOS_PATH . 'functions/class.phpmailer.php');
    
    $mail = new PHPMailer();
    
    $mail->IsSMTP();
    $mail->Host = $smtp_hostname;
    $mail->From = $_SESSION['sos_user']['email'];
    $mail->FromName = $_SESSION['sos_user']['personalname'];

    if (strchr($_POST['mailto'], ','))
    {    
        foreach(explode($_POST['mailto'], ',') as $address)
        {
	    $mail->AddAddress($address);
        }
    }
    else
    {
	$mail->AddAddress($_POST['mailto']);    
    }

    if (array_key_exists('cc_myself', $_POST) and 1 == $_POST['cc_myself'])
    {
	$mail->AddCC ($_SESSION['sos_user']['email']);
    }

    
    $mail->Subject = $_POST['mailre'];
    $mail->Body = $_POST['message'];
    
    if (!$mail->Send())
    {
	save_message(MSG_SYSTEM_ERROR, _("Error sending e-mail message."), __FILE__, __LINE__);
    }
    else
    {
	save_message(MSG_USER_NOTICE, _("Message sent successfully."));
    }
    
    redirect(SOS_PATH . 'src/welcome.php');

    //send_email_smtp($from, $_POST['mailto'], $cc, "", $_POST['mailre'], $_POST['message']);
}
else if (array_key_exists('button_delete_volunteers', $_POST))
{

    // collect volunteer IDs from form
    $vids = array();

    foreach ($_POST as $k => $v)
    {
	if (preg_match('/^volunteer_id_(\d+)/', $k, $matches))
	{
	    $vids[intval($matches[1])] = intval($matches[1]);
	}
    }    
    
    if (0 == count($vids))
    {
	process_user_error(_("Select one or more volunteers from the list."));
    }
    else if (array_key_exists('delete_confirm', $_POST) and 'on' == $_POST['delete_confirm'])
    {
	include (SOS_PATH . 'functions/delete_volunteer.php');
	foreach ($vids as $vid)
	{
	    delete_volunteer($vid);
	}
    }
    else
    {
    
        // ask for delete confirmation
	echo ("<P>"._("Are you sure you want to permanently delete these volunteers?")."</P>\n");
    

        echo ("<UL>\n");    
	foreach ($vids as $k => $vid)
        {
	    $volunteer = volunteer_get($vid, $errstr);
	    $name = make_volunteer_name($volunteer);
	    
	    echo ("<LI>$name ($vid)\n");
        }
	echo ("</UL>\n");
	
	
	$form = new formMaker();
	$form->open(FALSE, 'POST', 'mass.php', FS_PLAIN);
	foreach ($vids as $vid)
	{
	    $form->addHiddenField('volunteer_id_'.$vid, 1);
	}

	$form->addButton('button_delete_volunteers', _("Delete"));
	echo (_("Confirm")."<input type=\"checkbox\" name=\"delete_confirm\"><BR>\n"); 
	$form->close();
	
    }
}
else
{
    die("You have reached this page incorrectly.");
}


function email_volunteers_form()
{

    if (0 == strlen($_SESSION['sos_user']['email']))
    {
	process_user_error(_("Your e-mail address must be set in user settings."));
	return FALSE;
    }

    if (!validate_email($_SESSION['sos_user']['email']))
    {
	process_user_warning(_("Your e-mail address appears invalid: "). $_SESSION['sos_user']['email']);
    }

    // collect volunteer IDs from form
    $vids = array();

    foreach ($_POST as $k => $v)
    {
	if (preg_match('/^volunteer_id_(\d+)/', $k, $matches))
	{
	    $vids[intval($matches[1])] = intval($matches[1]);
	}
    }    
    
    // get volunteers' email addresses and names
    
    $mailto = "";
    
    foreach ($vids as $k => $vid)
    {
	$volunteer = volunteer_get($vid, $errstr);
	$name = make_volunteer_name($volunteer);
	if (!$volunteer)
	{
	    process_user_warning(_("Error in volunteer_get: ") . $errstr);
	    continue;
	} else
	if (empty($volunteer['email_address']))
	{
	    process_user_warning(_("Volunteer does not have an e-mail address: ")."<A href=\"" . SOS_PATH . "volunteer/?vid=$k\">$name</A>");
	    unset($vids[$k]);
	    continue;
	}
	elseif (!validate_email($volunteer['email_address']))
	{
	    process_user_warning(_("Volunteer's e-mail address appears invalid: ").$name);
	}

        $vids[$k] = array('name' => $name, 'email' => $volunteer['email_address']);
        if (strlen($mailto)>0)
		$mailto .= ',';
    	$mailto .= $vids[$k]['email'];
    }
    
    // todo: SquirrelMail, IMP, Hotmail, Yahoo e-mail
    
    echo ("<P><A href=\"mailto:".htmlentities(urlencode($mailto))."\">Use my e-mail client</A></P>\n");
    
    process_user_warning("Built-in e-mail is experimental, so you may want to use your own email client: click above.");
    
    echo ("<FORM method=\"post\" action=\"mass.php\">\n");
    echo ("<TABLE border=\"1\" width=\"100%\">\n");
    echo ("<TR>\n");
    echo ("<TH class=\"vert\">To</TH>\n");
    echo ("<TD width=\"100%\"><TEXTAREA name=\"mailto\" rows=\"5\" cols=\"80\">$mailto</TEXTAREA></TD>\n");
    echo ("</TR>\n");
    echo ("<TR>\n");    
    echo ("<TH class=\"vert\">From</TH>\n");
    echo ("<TD><INPUT type=\"text\" name=\"mailfrom\" value=\"".$_SESSION['sos_user']['email']."\" DISABLED></TD>\n");
    echo ("</TR>\n");
    echo ("<TR>\n");
    echo ("<TH class=\"vert\">Subject</TH>\n");
    echo ("<TD><INPUT type=\"text\" name=\"mailre\" value=\"Volunteering\" size=\"80\"></TD>\n");
    echo ("</TR>\n");
    echo ("<TR>\n");
    echo ("<TH class=\"vert\">Message</TH>\n");
    echo ("<TD><TEXTAREA name=\"message\" rows=\"20\" cols=\"80\"></TEXTAREA></TD>\n");
    echo ("</TR>\n");
    echo ("<TR>\n");
    echo ("<TH class=\"vert\">Options</TH>\n");
    echo ("<TD><INPUT type=\"checkbox\" name=\"cc_myself\" value=\"1\" checked> Send myself a copy</TD>\n");
    echo ("</TR>\n");
    echo ("</TABLE>\n");    
    
    echo ("<INPUT type=\"submit\" name=\"send_email\" value=\""._("Send")."\">\n");
    echo ("</FORM>\n");
    
    
}

make_html_end();

?>

