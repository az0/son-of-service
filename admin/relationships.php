<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: relationships.php,v 1.1 2003/11/03 05:12:58 andrewziem Exp $
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
    
	$result = $db->query("INSERT INTO relationship_types (name) VALUES ('$relationship_type_name')");

	if ($result)
	{
	    process_user_notice(_("Added succesfully."));	
	}
	else
	{
	    process_system_error(_("Error adding."));
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
    

    $sql = "SELECT relationship_types.relationship_type_id as relationship_type_id, relationship_types.name AS name, floor(count(*)/2) AS count ".
	"FROM relationship_types ".
	"LEFT JOIN relationships ".
	"ON relationships.relationship_type_id = relationship_types.relationship_type_id ".
	"GROUP BY relationship_types.relationship_type_id";

    $result = $db->query($sql);
    
    if (!$result)
    {
	process_system_error(_("Database error."), array('debug' => mysql_error()));
    }
    else if (0 == $db->num_rows($result))
    {
	process_user_error("No relationship types registered.");
	relationship_add_form();
    }
    else
    {
	echo ("<H2>List of relationship types</H2>\n");
	echo ("<P style=\"instructions\">To edit or delete a relationship type, select the radio button by it.  Then click edit or delete (respectively).</P>\n");
    
	echo ("<FORM method=\"post\" action=\".\">\n");
    
	echo ("<TABLE border=\"1\">\n");
	echo ("<TR>\n");
	echo ("<TH>Select</TH>\n");
	echo ("<TH>Relationship type</TH>\n");
	echo ("<TH>Quantity in use</TH>\n");	
	echo ("</TR>\n");
	while (FALSE != ($row = ($db->fetch_array($result))))
	{
	    echo ("<TR>\n");
	    echo ("<TD><INPUT type=\"radio\" name=\"relationship_type_id\" value=\"".$row['relationship_type_id']."\"></TD>\n");
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
    
    
    $relationship_type_id = intval($_POST['relationship_type_id']);
    
    // currently in use?
    $result = $db->query("SELECT relationship_id FROM relationships WHERE relationship_type_id = $relationship_type_id");
    
    if ($db->num_rows($result) > 0)
    {
	process_user_error("Relationship currently in use.");
	
    }
    else
    {
	$result = $db->query("DELETE FROM relationship_types WHERE relationship_type_id = $relationship_type_id LIMIT 1");	
	
	if ($result)
	{
	    process_user_notice(_("Succesfully removed."));
	}
	else
	{
	    process_user_notice(_("Error removing."));	
	}
    }
} /* relationship_type_delete() */

?>