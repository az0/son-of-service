<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: strings.php,v 1.1 2003/11/14 07:10:56 andrewziem Exp $
 *
 */

if (preg_match('/strings.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


$category_map = array('relationship' => _("Relationship"), 'work' => _("Work category"), 'skill' => _("Skill"));

function strings_add()
{
    global $db;
    global $category_map;
    

    $errors_found = 0;

    if (strlen($_POST['string_name']) > 100)
    {
	process_user_error(_("Too long:"). ' '. _("String name"));
	$errors_found++;
    }
    
    if (strlen($_POST['string_name']) < 2)
    {
	process_user_error(_("Too short:"). ' '. _("String name"));
	$errors_found++;	
    }
    
    if (!array_key_exists($_POST['string_category'], $category_map))
    {
	process_user_error(_("Choose a category from the list."));
	$errors_found++;
    }

    if ($errors_found)
    {
	echo ("<P>Try again.</P>\n");	
	return FALSE;
    }
    else
    {
	$string_name = $db->escape_string(htmlentities($_POST['string_name']));
	$string_category = $db->escape_string(htmlentities($_POST['string_category']));    
	
	$result = $db->query("INSERT INTO strings (s, type) VALUES ('$string_name', '$string_category')");

	if ($result)
	{
	    process_user_notice(_("Added succesfully."));	
	}
	else
	{
	    process_system_error(_("Error adding data to database."));
	}
    }
} /* strings_add() */


function strings_add_form()
{
    global $category_map;
    
    
    echo ("<FIELDSET>\n");
    echo ("<LEGEND>Add a string</LEGEND>\n");
    
    echo ("<FORM method=\"POST\" action=\".\">\n");
    $i = 0;
    foreach ($category_map as $key => $value)
    {
	if ($i > 0)
	{
	    echo ("<BR>\n");
	}
	echo ("<INPUT type=\"radio\" name=\"string_category\" value=\"$key\">$value\n");
	$i++;
    }
    echo ("<BR>"._("Name") . " <INPUT type=\"type\" name=\"string_name\" maxlength=\"100\">\n");
    echo ("<BR><INPUT type=\"submit\" name=\"button_string_add\" value=\""._("Add")."\">\n");    
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");
} /* strings_add_form() */


function strings_list()
{
    global $db;
    global $category_map;
    
    
    $sql = "SELECT strings.string_id AS string_id, strings.s AS name, strings.type AS type, count(*) AS count ".
	"FROM strings ".
	"LEFT JOIN work ".
	"ON strings.string_id = work.category_id ".
	"LEFT JOIN relationships ".
	"ON strings.string_id = relationships.string_id ".
	"LEFT JOIN volunteer_skills ".
	"ON strings.string_id = volunteer_skills.string_id ".
	"GROUP BY strings.string_id ".
	"ORDER BY type, name, count ";

    $result = $db->query($sql);
    
    if (!$result)
    {
	process_system_error(_("Error querying database."), array('debug' => $db->get_error()));
    }
    else if (0 == $db->num_rows($result))
    {
	process_user_error(_("No work categories exist."));
    }
    else
    {
	echo ("<H2>"._("Strings")."</H2>\n");
	echo ("<P style=\"instructions\">To edit or delete a string, select the radio button by it.  Then click edit or delete (respectively).</P>\n");
    
	echo ("<FORM method=\"post\" action=\".\">\n");
    
	echo ("<TABLE border=\"1\">\n");
	echo ("<TR>\n");
	echo ("<TH>"._("Select")."</TH>\n");
	echo ("<TH>"._("Category")."</TH>\n");	
        echo ("<TH>"._("String")."</TH>\n");	
// to do: fixme: quantity never zero
//	echo ("<TH>"._("Quantity in use")."</TH>\n");	
	echo ("</TR>\n");
	while (FALSE != ($row = ($db->fetch_array($result))))
	{
	    echo ("<TR>\n");
	    echo ("<TD><INPUT type=\"radio\" name=\"string_id\" value=\"".$row['string_id']."\"></TD>\n");
	    echo ("<TD>".$category_map[$row['type']]."</TD>\n");	    
	    echo ("<TD>".$row['name']."</TD>\n");
//	    echo ("<TD>".$row['count']."</TD>\n");	    
	    echo ("</TR>\n");
	}
	echo ("</TABLE>\n");
	echo ("<INPUT type=\"submit\" name=\"button_string_delete\" value=\""._("Delete")."\">\n");
	echo ("<INPUT type=\"submit\" name=\"button_string_edit\" value=\""._("Edit")."\">\n");
	echo ("</FORM>\n");
    }
} /* string_list() */


function strings_delete()
{
    global $db;
    

    if (!array_key_exists('string_id', $_POST))
    {
	process_user_error(_("You must make a selection."));
	return FALSE;
    }    
    
    $string_id = intval($_POST['string_id']);
    
    // Exists?  What type?
    $result = $db->query("SELECT type FROM strings WHERE string_id = $string_id");
    
    if (!$result)
    {
	process_system_error(_("Error querying database."), array('debug' => $db->get_error()));
	return FALSE;
    }
    else if (0 == $db->num_rows($result))
    {
	process_user_error(_("Cannot find string."));
	return FALSE;	
    }
    
    $row = $db->fetch_array($result);
    
    switch ($row['type'])
    {
	case 'relationship':
	    $sql = "SELECT * FROM relationships WHERE string_id = $string_id";
	    break;
	    
	case 'work':
	    $sql = "SELECT * FROM work WHERE category_id = $string_id";
	    break;	    

	case 'skill':
	    $sql = "SELECT * FROM volunteer_skills WHERE string_id = $string_id";
	    break;	    
    
	default:
	    process_system_error(_("Unexpected type from database."));
	    $sql = "SELECT * FROM strings WHERE 0 = 1"; // find nothing
	    break;
    }
    
    $result = $db->query($sql);
    
    
    if (!$result)
    {
	process_system_error(_("Error querying database."), array('debug' => $db->get_error()));	
    } 
    else if ($db->num_rows($result) > 0)
    {
	process_user_error(_("Currently in use."));	
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
} /* strings_delete() */

?>