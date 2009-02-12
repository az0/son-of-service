<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: availability.php,v 1.18 2009/02/12 04:11:20 andrewziem Exp $
 *
 */
 
if (preg_match('/availability.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}




function volunteer_delete_availability()
{
    global $db;
    
    
    $errors_found = 0;
    
    $vid = intval($_POST['vid']);
    
    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    
        
    $availability_ids  = find_values_in_request($_POST, 'availability_id');
    
    if (0 == count($availability_ids))    
    {
	save_message(MSG_USER_ERROR, _("You must make a selection."));    
	$errors_found++;
    }
    
    if (0 === $errors_found)
    {
    
	foreach ($availability_ids as $availability_id)
	{
	    // todo portable LIMIT
	    $sql = "DELETE FROM availability WHERE availability_id = $availability_id AND volunteer_id = $vid";
    
	    $result = $db->Execute($sql);

	    if (!$result)
	    {
		$errors_found++;
		save_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
	    }
	}    
	
	if (0 === $errors_found)
	{
	    save_message(MSG_USER_NOTICE, _("Deleted."));
	}
    }
    
    // relocate client to non-POST page
    redirect("?vid=$vid&menu=availability");
}

 
function volunteer_availability_add()
{
    global $db;
      

    $errors_found = 0;
    $vid = intval($_POST['vid']);      
     
    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    
    
    $day_of_week = intval($_POST['day_of_week']);
    // should just be char      
    $start_time = $db->qstr($_POST['start_time'], get_magic_quotes_gpc()); 
    // should just be char
    $end_time = $db->qstr($_POST['end_time'], get_magic_quotes_gpc()); 
  
    // always validate form input first
    if (!(preg_match("/^[0-9]+$/", $day_of_week) and preg_match("/^[0-9]+$/",$day_of_week)))
    {
	save_message(MSG_SYSTEM_ERROR, _("Bad form input:"). ' day_of_week', __FILE__, __LINE__);
    }
    else if (0 === $errors_found)
    {  
	$sql = "INSERT INTO availability ".
	    "(volunteer_id, day_of_week, start_time, end_time, dt_added, uid_added, dt_modified,uid_modified) ".
	    "VALUES ($vid, $day_of_week, $start_time, $end_time, now(), ".get_user_id().", dt_added, uid_added)";
      
	$result = $db->Execute($sql);	
    
        if (!$result)
        {
	    save_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql);
        }      
    }
    
    redirect("./?vid=$vid&menu=availability");
    
} /* volunteer_availability_add() */



function volunteer_view_availability($brief = FALSE)
// Use brief for summary: supresses headers and forms.
{
    global $db;
    global $user;
    global $daysofweek;
        
    
    $vid = intval($_REQUEST['vid']);
    
    if (!has_permission(PC_VOLUNTEER, PT_READ, $vid, NULL))
    {
	die_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }
    
    if (!$brief)
    {    
        display_messages();    
    }
    
    $sql = "SELECT * FROM availability WHERE volunteer_id = $vid ORDER BY day_of_week";

    $result = $db->Execute($sql);
    
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }

    if (!$brief or $result->RecordCount() > 0)
    {
	echo ("<h3>". _("Availability") ."</h3>\n");
    }	
    
    if (0 == $result->RecordCount())
    {
	if (!$brief)
	{	
	    process_user_notice(_("None found."));
	}
    }
    else
    {
    
	require_once(SOS_PATH . 'functions/table.php');
    
	$dtp = new DataTablePager();
    
	if ($brief)
	{
	    // show last ten	    
	    $dtp->setPrintable(TRUE);
	    $dtp->setPagination(10, $result->RecordCount() > 10 ? $result->RecordCount() - 10 : 0);
	}
	else
	{
	    $dtp->setPagination(10);
	    echo ("<FORM method=\"post\" action=\".\">\n");
	    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
	}

	$headers = array();
        if (!$brief and has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
	{
	    $headers['availability_id'] = array('checkbox' => TRUE, 'label' => _("Select"));
	}
	
	$int_to_timeofday = array(1 => _("Morning"), _("Afternoon"), _("Evening"), _("Night"));
	
	$headers['day_of_week'] = array('label' => _("Day of week"), 'map' => $daysofweek);
	$headers['start_time'] = array('label' => _("Start"), 'map' => $int_to_timeofday);
	$headers['end_time'] = array('label' => _("End"), 'map' => $int_to_timeofday);
	
	$dtp->setHeaders($headers);
	$dtp->setDatabase($db, $result);
	$dtp->render();

	if (!$brief and has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
	{
	    // todo: allow multiple delete
	    echo ("<INPUT type=\"submit\" name=\"button_delete_availability\" value=\""._("Delete")."\">\n");
	    echo ("</FORM>\n");
	}
    }
} /* volunteer_view_availability() */    


function volunteer_availability_add_form()
{
    global $daysofweek;

    
    $vid = intval($_REQUEST['vid']);
    
    if (has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	echo ("<h4>" . _("Add new availability") ."</h4>\n");
	echo ("<FORM method=\"post\" action=\".\">\n");
	echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
	echo ("<SELECT name=\"day_of_week\">\n");
        for ($i = 1; $i <= 7; $i++)
	{
	    echo ("<OPTION value=\"$i\">".$daysofweek[$i]."</OPTION>\n");
	}
	echo ("</SELECT>\n");
	echo (" From ");
	echo ("<SELECT name=\"start_time\">\n");
	echo ("<OPTION value=\"1\">"._("Morning")."</OPTION>\n");
	echo ("<OPTION value=\"2\">"._("Afternoon")."</OPTION>\n");
	echo ("<OPTION value=\"3\">"._("Evening")."</OPTION>\n");
	echo ("<OPTION value=\"4\">"._("Night")."</OPTION>\n");
	echo ("</SELECT>\n");
	echo (" To ");
	
	echo ("<SELECT name=\"end_time\">\n");
	echo ("<OPTION value=\"1\">"._("Morning")."</OPTION>\n");
	echo ("<OPTION value=\"2\">"._("Afternoon")."</OPTION>\n");
	echo ("<OPTION value=\"3\">"._("Evening")."</OPTION>\n");
	echo ("<OPTION value=\"4\">"._("Night")."</OPTION>\n");
	echo ("</SELECT>\n");

	echo ("<INPUT type=\"submit\" name=\"availability_add\" value=\""._("Add")."\">\n");

	echo ("</FORM>\n");
    }
} /* volunteer_availability_add_form() */


?>
