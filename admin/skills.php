<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: skills.php,v 1.4 2003/11/12 16:12:23 andrewziem Exp $
 *
 */

if (preg_match('/skills.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

function skill_add()
{
    global $db;
    

    $errors_found = 0;

    if (strlen($_POST['skill_name']) > 100)
    {
	process_user_error(_("Too long: "). _("Skill name"));
	$errors_found++;
    }
    
    if (strlen($_POST['skill_name']) < 2)
    {
	process_user_error('Skill name too short.');
	process_user_error(_("Too short: "). _("Skill name"));	
	$errors_found++;	
    }
    
    if ($errors_found)
    {
	echo ("<P>Try again.</P>\n");
    }
    else
    {
	$skill_name = $db->escape_string(htmlentities($_POST['skill_name']));
    
	$result = $db->query("INSERT INTO strings (s,type) VALUES ('$skill_name', 'skill')");

	if ($result)
	{
	    echo ("<P>Skill $skill_name added succesfully.</P>\n");	
	}
	else
	{
	    process_system_error(_("Error adding data to database."), array('debug' => $db->get_error()));
	}
    }
} /* skill_add() */

function skill_add_form()
{
    
    //echo ("<H2>Add a volunteer skill</H2>\n");
    echo ("<FIELDSET>\n");
    echo ("<LEGEND>Add a volunteer skill</LEGEND>\n");
    
    echo ("<P class=\"instructionstext\">Add a skill (or interest) here so volunteers can register under it.</P>\n");

    echo ("<FORM method=\"POST\" action=\"./\">\n");
    echo ("Name of skill\n");
    echo ("<INPUT type=\"type\" name=\"skill_name\" maxlength=\"100\">\n");
    echo ("<BR><INPUT type=\"submit\" name=\"button_skill_add\" value=\""._("Add")."\">\n");    
    echo ("</FORM>\n");
    
    echo ("</FIELDSET>\n");
}

function skill_list()
{
    global $db;
    
    // to do: add how many volunteers attached to each skill
    $result = $db->query ("SELECT * FROM strings WHERE type = 'skill'");
    
    if (!$result)
    {
	process_system_error(_("Error querying database."), array('debug'=> $db->get_error()));	
    }
    elseif (0 == $db->num_rows($result))
    {
	process_user_error(_("No skills."));
    }
    else
    {
	echo ("<H2>"._("Skills, interests")."</H2>\n");
	echo ("<P style=\"instructions\">To edit or delete a skill, select the radio button by it.  Then, click edit or delete.</P>\n");
    
	echo ("<FORM method=\"post\" action=\".\">\n");
    
	echo ("<TABLE border=\"1\">\n");
	echo ("<TR>\n");
	echo ("<TH>"._("Select")."</TH>\n");
	echo ("<TH>"._("Skill name")."</TH>\n");
	echo ("</TR>\n");
	while (FALSE != ($row = ($db->fetch_array($result))))
	{
	    echo ("<TR>\n");
	    echo ("<TD><INPUT type=\"radio\" name=\"string_id\" value=\"".$row['string_id']."\"></TD>\n");
	    echo ("<TD>".$row['s']."</TD>\n");
	    echo ("</TR>\n");
	}
	echo ("</TABLE>\n");
	echo ("<INPUT type=\"submit\" name=\"button_skill_delete\" value=\""._("Delete")."\">\n");
	echo ("<INPUT type=\"submit\" name=\"button_skill_edit\" value=\""._("Edit")."\">\n");
	echo ("</FORM>\n");
    }
} /* skill_list() */

function skill_delete()
{
    global $db;
    
    
    $string_id = intval($_POST['string_id']);
    
    // currently in use?
    $result = $db->query("SELECT string_id FROM volunteer_skills WHERE string_id = $string_id");
    
    if ($db->num_rows($result) > 0)
    {
	process_user_error(_("Skill currently in use."));
	
    }
    else
    {
	$result = $db->query("DELETE FROM strings WHERE string_id = $string_id LIMIT 1");	
	
	if ($result)
	{
	    process_user_notice(_("Removed."));
	}
	else
	{
	    process_user_notice(_("Error deleting data from database."));	
	}
    }
} /* skill_delete() */


?>