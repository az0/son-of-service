<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: availability.php,v 1.7 2003/12/17 17:11:03 andrewziem Exp $
 *
 */
 
if (preg_match('/availability.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function volunteer_delete_availability()
{
    global $db;
    
    
    // todo: check permissions
    
    $vid = intval($_POST['vid']);
    $availability_id  = intval($_POST['availability_id']);
    
    $sql = "DELETE FROM availability WHERE availability_id = $availability_id AND volunteer_id = $vid";
    
    $result = $db->Execute($sql);

    if (!$result)
    {
	save_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else
    {
	save_message(MSG_USER_NOTICE, _("Deleted."));
    }
    
    // relocate client to non-POST page
    header("Location: ./?vid=$vid&menu=availability");

}

 
function volunteer_availability_add()
{
    global $db;
      

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
    else
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
    
    header ("Location: ./?vid=$vid&menu=availability");
    
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
    
	echo ("<H3>Availability</H3>\n");
	
	echo ("<FORM method=\"post\" action=\".\">\n");
	echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
    }
    
    $sql = "SELECT * FROM availability WHERE volunteer_id = $vid ORDER BY day_of_week";

    $result = $db->Execute($sql);
    
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
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
?>
<TABLE border="1">
<TR>
<?php
    if (!$brief)
    {
	echo ("<TH>" . _("Select") . "</TH>\n");
    }
?>
 <TH><?php echo _("Day of week");?></TH>
 <TH><?php echo _("Start");?></TH>
 <TH><?php echo _("End");?></TH>
</TR>
<?php

	while (!$result->EOF)
        {
	    $availability = $result->fields;
	    echo ("<TR>\n");
	    if (!$brief)
	    {
		echo ("<TD><INPUT type=\"radio\" name=\"availability_id\" value=\"".$availability['availability_id']."\"></TD>\n");
	    }
	    echo ("<TD>".(0< $availability['day_of_week'] ? $daysofweek[$availability['day_of_week']]:"bad value")."</TD>\n");
	    echo ("<TD>".$availability['start_time']."</TD>\n");	
	    echo ("<TD>".$availability['end_time']."</TD>\n");		
	    echo ("</TR>\n");	
	    $result->MoveNext();
	}

	echo ("</TABLE>\n");
	if (!$brief)
	{
	    echo ("<INPUT type=\"submit\" name=\"button_delete_availability\" value=\""._("Delete")."\">\n");
	}
    }

    if (!$brief)
    {
	echo ("<H4>Add new availability</H4>\n");
	echo ("<SELECT name=\"day_of_week\">\n");
        for ($i = 1; $i <= 7; $i++)
	{
	    echo ("<OPTION value=\"$i\">".$daysofweek[$i]."</OPTION>\n");
	}
	echo ("</SELECT>\n");
	echo (" From ");
	echo ("<SELECT name=\"start_time\">\n");
	echo ("<OPTION>"._("Morning")."</OPTION>\n");
	echo ("<OPTION>"._("Afternoon")."</OPTION>\n");
	echo ("<OPTION>"._("Evening")."</OPTION>\n");
	echo ("<OPTION>"._("Night")."</OPTION>\n");
	echo ("</SELECT>\n");
	echo (" To ");
	
	echo ("<SELECT name=\"end_time\">\n");
	echo ("<OPTION>"._("Morning")."</OPTION>\n");
	echo ("<OPTION>"._("Afternoon")."</OPTION>\n");
	echo ("<OPTION>"._("Evening")."</OPTION>\n");
	echo ("<OPTION>"._("Night")."</OPTION>\n");
	echo ("</SELECT>\n");

	echo ("<INPUT type=\"submit\" name=\"availability_add\" value=\""._("Add")."\">\n");

	echo ("</FORM>\n");
    }

} /* volunteer_view_availability() */


?>