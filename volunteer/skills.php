<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: skills.php,v 1.17 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

if (preg_match('/skills.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

global $skill_levels;
$skill_levels = array (1=> _("None"), _("Amateur"), _("Some"), ("Professional"), _("Expert"));


function volunteer_delete_skill()
{
    global $db;
    
    
    $vid = intval($_POST['vid']);
    $volunteer_skill_id  = intval($_POST['volunteer_skill_id']);
    
    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    
    
    if ($errors_found)
    {
        redirect("?vid=$vid&menu=skills");    
	return FALSE;
    }

    // to do: portable LIMIT    
    $sql = "DELETE FROM volunteer_skills WHERE volunteer_skill_id = $volunteer_skill_id AND volunteer_id = $vid LIMIT 1";
    
    $result = $db->Execute($sql);

    if (!$result)
    {
	save_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else
    {
	save_message(MSG_USER_NOTICE, _("Deleted."));
    }

    // redirect client to non-POST page
    redirect("?vid=$vid&menu=skills");
}

function volunteer_view_skills($brief = FALSE)
// use brief for summary
{
    global $db, $skill_levels, $db_cache_timeout;
    
    
    $errors_found = 0;
    
    $vid = intval($_GET['vid']);    
    
    if (!has_permission(PC_VOLUNTEER, PT_READ, $vid, NULL))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    
    
    if (!$brief)
    {
	display_messages();
    }
    
    if ($errors_found)
    {
	return FALSE;
    }
    
    if (!$brief)
    {
	echo ("<FORM action=\".\" method=\"post\">\n");
	echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
        echo ("<INPUT type=\"hidden\" name=\"menu\" value=\"skills\">\n");
    }

    $sql = "SELECT volunteer_skills.string_id, volunteer_skills.volunteer_skill_id, strings.s, volunteer_skills.skill_level FROM volunteer_skills LEFT JOIN strings on strings.string_id = volunteer_skills.string_id WHERE volunteer_id = $vid AND strings.type = 'skill'";
    
    $vskills_result = $db->Execute($sql);

    if (!$vskills_result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    
    if ($vskills_result->RecordCount() > 0 or !$brief)
    {
        echo ("<H3>"._("Skills, interests")."</H3>\n");	    
    }

    if (0 == $vskills_result->RecordCount() and !$brief)
    {
	process_user_notice(_("None found."));
    }
    
    if ($vskills_result->RecordCount() > 0)
    {
	echo ("<TABLE border=\"1\">\n");
	echo ("<TR>\n");
	if (!$brief and has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
	{
	    echo ("<TH>"._("Select")."</TH>\n");
	}
	echo ("<TH>"._("Skill")."</TH>\n");
	echo ("<TH>"._("Level")."</TH>\n");
	echo ("</TR>\n");
    
	while (!$vskills_result->EOF)
	{
	    $vskill = $vskills_result->fields;
	    echo ("<TR>\n");
	    if (!$brief and has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
	    {
		echo ("<TD><INPUT type=\"radio\" name=\"volunteer_skill_id\" value=\"".$vskill['volunteer_skill_id']."\"></TD>\n");
	    }
	    echo ("<TD>".$vskill['s']."</TD>\n");
    	    echo ("<TD>".$skill_levels[$vskill['skill_level']]."</TD>\n");	
	    echo ("</TR>\n");
	    $vskills_result->MoveNext();
	}
	echo ("</TABLE>\n");
	
	if (!$brief and has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
	{    
	    echo ("<INPUT type=\"submit\" name=\"button_delete_volunteer_skill\" value=\""._("Delete")."\">\n");
	}
    }    

    // add skill form
    if (!$brief and has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	$sql = "SELECT * FROM strings WHERE type = 'skill' ORDER BY s";
	$skills_list_result = $db->CacheExecute($db_cache_timeout, $sql);

	if (!$skills_list_result)
	{
	    die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
	}
	elseif (1 > $skills_list_result->RecordCount())
	{
	    process_user_error(_("None found."));
	}
	else
	{
	    echo ("<H4>"._("Add new skill")."</H4>\n");

	    echo ("<SELECT name=\"string_id\">\n");
	    while (!$skills_list_result->EOF)
	    {
		$skill = $skills_list_result->fields;
		echo ("<OPTION value=\"".$skill['string_id']."\">".$skill['s']."</OPTION>\n");
    		$skills_list_result->MoveNext();
    	    }
	    echo ("</SELECT>\n");
	    echo ("<SELECT name=\"skill_level\">\n");
	    for ($i = 2 ; $i <= 5; $i++)
	    {
		echo ("<OPTION value=\"$i\">".$skill_levels[$i]."</OPTION>\n");
	    }
	    echo ("</SELECT>\n");
	    echo ("<INPUT type=\"submit\" name=\"add_skill\" value=\""._("Add")."\">\n");
	}

	echo ("</FORM>\n");
    }
} /* volunteer_view_skills() */


function volunteer_skill_add()
{
    global $db;
      
      
    $vid = intval($_POST['vid']);
    $string_id = intval($_POST['string_id']);
    $skill_level = intval($_POST['skill_level']);
    
    $errors_found = 0;
    
    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }      

    if (!(preg_match("/^[0-9]+$/", $_POST['string_id']) and preg_match("/^[0-9]+$/",$_POST['skill_level'])))
    {
	$errors_found++;    
	save_message(MSG_SYSTEM_ERROR, _("Bad form input:").' string_id, skill_level', __FILE__, __LINE__);
    }
    
    if (0 === $errors_found)
    {  
	$sql = "INSERT INTO volunteer_skills (volunteer_id, string_id, skill_level) VALUES ($vid, $string_id, $skill_level)";
    
        $result = $db->Execute($sql);	
    
        if (!$result)
        {
    	    save_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql);
        }      
    }

    redirect("?vid=$vid&menu=skills");
    
} /* volunteer_skill_add() */


?>
