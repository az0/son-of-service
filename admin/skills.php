<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: skills.php,v 1.2 2003/11/03 05:12:58 andrewziem Exp $
 *
 */

if (preg_match('/skills.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

function skill_add()
{
    global $db;
    
    echo ("debug: skill_add()\n");

    $errors_found = 0;

    if (strlen($_POST['skill_name']) > 100)
    {
	process_user_error('Skill name too long.');
	$errors_found++;
    }
    
    if (strlen($_POST['skill_name']) < 2)
    {
	process_user_error('Skill name too short.');
	//print_r($_POST);
	$errors_found++;	
    }
    
    if ($errors_found)
    {
	echo ("<P>Try again.</P>\n");
	
	skill_add_form();
    }
    else
    {
	$skill_name = $db->escape_string($_POST['skill_name']);
    
    $result = $db->query("INSERT INTO skills (name) VALUES ('$skill_name')");

    if ($result)
    {
	echo ("<P>Skill $skill_name added succesfully.</P>\n");
	
	skill_list();
    }
    else
    {
	process_system_error("Unable to add skill to database.");
    }
    }
    
    
}

function skill_add_form()
{
    
    //echo ("<H2>Add a volunteer skill</H2>\n");
    echo ("<FIELDSET>\n");
    echo ("<LEGEND>Add a volunteer skill</LEGEND>\n");
    
    echo ("<P class=\"instructionstext\">Add a skill (or interest) here so volunteers can register under it.</P>\n");

    echo ("<FORM method=\"POST\" action=\"./\">\n");
    echo ("<TABLE border=\"1\" style=\"margin-top:2em\">\n");
    echo ("<TR>\n");
    echo ("<TD>Name of skill</TD>\n");
    echo ("<TD>\n");
    echo ("<INPUT type=\"type\" name=\"skill_name\" maxlength=\"100\"></TD>\n");
    echo ("</TD>\n");
    echo ("</TR>\n");
    echo ("</TABLE>\n");
    echo ("<INPUT type=\"submit\" name=\"button_skill_add\" value=\"Add skill\">\n");    
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");
}

function skill_list()
{
    global $db;
    
    // to do: add how many volunteers attached to each skill
    $result = $db->query ("SELECT * from skills");
    
    if (0 == $db->num_rows($result))
    {
	process_user_error("No skills registered.");
	skill_add_form();
    }
    else
    {
	echo ("<H2>List of volunteer skills</H2>\n");
	echo ("<P style=\"instructions\">To edit or delete a skill, select the radio button by it.  Then, click edit or delete.</P>\n");
    
	echo ("<FORM method=\"post\" action=\".\">\n");
    
	echo ("<TABLE border=\"1\">\n");
	echo ("<TR>\n");
	echo ("<TH>Select</TH>\n");
	echo ("<TH>Skill name</TH>\n");
	echo ("</TR>\n");
	while (FALSE != ($row = ($db->fetch_array($result))))
	{
	    echo ("<TR>\n");
	    echo ("<TD><INPUT type=\"radio\" name=\"skill_id\" value=\"".$row['skill_id']."\"></TD>\n");
	    echo ("<TD>".$row['name']."</TD>\n");
	    echo ("</TR>\n");
	}
	echo ("</TABLE>\n");
	echo ("<INPUT type=\"submit\" name=\"button_skill_delete\" value=\"Delete\">\n");
	echo ("<INPUT type=\"submit\" name=\"button_skill_edit\" value=\"Edit\">\n");
	echo ("</FORM>\n");
    }
} /* skill_list() */


?>