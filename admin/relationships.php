<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: relationships.php,v 1.3 2003/11/12 16:12:23 andrewziem Exp $
 *
 */

if (preg_match('/relationships.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

function relationship_type_add()
{
    global $db;
    

    $errors_found = 0;

    if (strlen($_POST['relationship_type_name']) > 100)
    {
	process_user_error('Relationship type name too long.');
	$errors_found++;
    }
    
    if (strlen($_POST['relationship_type_name']) < 2)
    {
	process_user_error('Relationship type name too short.');
	$errors_found++;	
    }
    
    if ($errors_found)
    {
	echo ("<P>Try again.</P>\n");	
	relationship_add_form();
    }
    else
    {
	$relationship_type_name = htmlentities($db->escape_string($_POST['relationship_type_name']));
    
	$result = $db->query("INSERT INTO strings (s, type) VALUES ('$relationship_type_name', 'relationship')");

	if ($result)
	{
	    process_user_notice(_("Added succesfully."));	
	}
	else
	{
	    process_system_error(_("Error adding data to database."));
	}
    }
} /* relationship_add() */


function relationship_type_add_form()
{
    
    echo ("<FIELDSET>\n");
    echo ("<LEGEND>Add a relationship type</LEGEND>\n");
    
    echo ("<P class=\"instructionstext\">Add a relationship type here so you can define this type of relationship among volunteers.</P>\n");

    echo ("<FORM method=\"POST\" action=\"./\">\n");
    echo ("<TABLE border=\"1\" style=\"margin-top:2em\">\n");
    echo ("<TR>\n");
    echo ("<TD>Relationship type</TD>\n");
    echo ("<TD>\n");
    echo ("<INPUT type=\"type\" name=\"relationship_type_name\" maxlength=\"100\"></TD>\n");
    echo ("</TD>\n");
    echo ("</TR>\n");
    echo ("</TABLE>\n");
    echo ("<INPUT type=\"submit\" name=\"button_relationship_type_add\" value=\""._("Add")."\">\n");    
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");
} /* relationship_add_form() */


function relationship_type_list()
{
    global $db;
    

    $sql = "SELECT strings.string_id AS string_id, strings.s AS name, floor(count(*)/2) AS count ".
	"FROM strings ".
	"LEFT JOIN relationships ".
	"ON relationships.string_id = strings.string_id ".
	"WHERE strings.type = 'relationship' ".
	"GROUP BY strings.string_id";

    $result = $db->query($sql);
    
    if (!$result)
    {
	process_system_error(_("Database error."), array('debug' => $db->get_error()));
    }
    else if (0 == $db->num_rows($result))
    {
	process_user_error("No relationship types registered.");
	relationship_add_form();
    }
    else
    {
	echo ("<H2>"._("Relationship types")."</H2>\n");
	echo ("<P style=\"instructions\">To edit or delete a relationship type, select the radio button by it.  Then click edit or delete (respectively).</P>\n");
    
	echo ("<FORM method=\"post\" action=\".\">\n");
    
	echo ("<TABLE border=\"1\">\n");
	echo ("<TR>\n");
	echo ("<TH>"._("Select")."</TH>\n");
	echo ("<TH>"._("Relationship type")."</TH>\n");
	echo ("<TH>"._("Quantity in use")."</TH>\n");	
	echo ("</TR>\n");
	while (FALSE != ($row = ($db->fetch_array($result))))
	{
	    echo ("<TR>\n");
	    echo ("<TD><INPUT type=\"radio\" name=\"string_id\" value=\"".$row['string_id']."\"></TD>\n");
	    echo ("<TD>".$row['name']."</TD>\n");
	    echo ("<TD>".$row['count']."</TD>\n");	    
	    echo ("</TR>\n");
	}
	echo ("</TABLE>\n");
	echo ("<INPUT type=\"submit\" name=\"button_relationship_type_delete\" value=\""._("Delete")."\">\n");
	echo ("<INPUT type=\"submit\" name=\"button_relationship_type_edit\" value=\""._("Edit")."\">\n");
	echo ("</FORM>\n");
    }
} /* relationship_type_list() */


function relationship_type_delete()
{
    global $db;
    
    
    $string_id = intval($_POST['string_id']);
    
    // currently in use?
    $result = $db->query("SELECT relationship_id FROM relationships WHERE string_id = $string_id");
    
    if ($db->num_rows($result) > 0)
    {
	process_user_error(_("Relationship currently in use."));
	
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
} /* relationship_type_delete() */

?>