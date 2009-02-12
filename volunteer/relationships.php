<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: relationships.php,v 1.26 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

if (preg_match('/relationships.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function show_relationship_leaf($vid, $row, $remaining_depth, $ignore_vids, $brief)
{    
    global $db;
    

    if ($remaining_depth < 1)
    {
        return FALSE;
    }
        
    $ignore_vids[] = $vid;
    	
    $volunteer2_row = volunteer_get($row['volunteer2_id'], $errstr);
    
    if ($volunteer2_row)
    {
        $row['volunteer2_name'] = make_volunteer_name($volunteer2_row);
	
	echo "<li>" . $row['volunteer2_name'] . " [<a href=\"?vid=" . 
		$row['volunteer2_id'] . "\">". _("account") ."</a>, <a href=\"?vid=" .
		$row['volunteer2_id'] . "&amp;menu=relationships\">" . _("relationships") .
		"</a>], " . $row['rname']."\n";
	if (!$brief and has_permission(PC_VOLUNTEER, PT_WRITE, $row['volunteer2_id'], NULL) and has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
	{
	    echo ("<INPUT type=\"submit\" name=\"delete_relationship_".$vid."_".$row['volunteer2_id']."\" value=\""._("Delete")."\">\n");
	}
	echo ("</LI>\n");
	
        $sql = "SELECT relationships.relationship_id AS relationship_id, ".
	    "relationships.volunteer2_id AS volunteer2_id, ".
	    "strings.s AS rname  ".
	    "FROM relationships ".
	    "LEFT JOIN strings ".
	    "ON relationships.string_id = strings.string_id ".
	    "WHERE relationships.volunteer1_id = ".$row['volunteer2_id']." AND relationships.volunteer2_id != $vid AND strings.type = 'relationship'";
    
        $result = $db->Execute($sql);
	
	if (!$result)
	{
	    die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
	}
	else if ($result->RecordCount() > 0)
        {
	    echo ("<UL>\n");
            while (!$result->EOF)
    	    {
		$row2 = $result->fields;
		if (!in_array($row2['volunteer2_id'], $ignore_vids))
		{
		    show_relationship_leaf($row['volunteer2_id'], $row2, $remaining_depth - 1, $ignore_vids, $brief);
		}
		$result->MoveNext();
	    }
	    echo ("</UL>\n");	    
	}
    }	
    else
    {
        echo ("<LI>volunteer_get(): $errstr\n");
	if (!$brief and has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL) and has_permission(PC_VOLUNTEER, PT_WRITE, $row['volunteer2_id'], NULL))
	{
    	    echo ("<INPUT type=\"submit\" name=\"delete_relationship_".$vid."_".$row['volunteer2_id']."\" value=\""._("Delete")."\">\n");
	}
    }
    
} /* show_relationship_leaf() */


function relationships_view($brief = FALSE)
{
    global $db;
    global $ignore_relationships;
    

    display_messages();
    
    $vid = intval($_GET['vid']);
    
    if (array_key_exists('max_depth', $_GET) and is_numeric($_GET['max_depth']))
    {	
	// set new value via GET
	$max_depth = intval($_GET['max_depth']);
	$_SESSION['relationships']['max_depth'] = $max_depth;
    }
    elseif (array_key_exists('relationships', $_SESSION) and array_key_exists('max_depth', $_SESSION['relationships']))
    {
	// remember previous value from session
	$max_depth = intval($_SESSION['relationships']['max_depth']);
    }
    else
    {
	// use default value
        $max_depth = 5;
    }
    
    $ignore_vids = array($vid);
    
    $c = 0;            

    // query primary relationships
    
    $sql = "SELECT relationships.relationship_id AS relationship_id, ".
    "relationships.volunteer2_id AS volunteer2_id, ".
    "strings.s AS rname  ".
    "FROM relationships ".
    "LEFT JOIN strings ".
    "ON relationships.string_id = strings.string_id ".
    "WHERE relationships.volunteer1_id = $vid AND strings.type = 'relationship'";
    
    $result = $db->Execute($sql);
    
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else if ($result->RecordCount() > 0)
    {

	echo ("<H2>"._("Relationships")."</H2>\n");
    
	echo ("<FORM action=\".\" method=\"post\">\n");
	echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
	echo ("<UL>\n");
	$volunteer = volunteer_get($vid, $errstr);
	echo ("<lh>".make_volunteer_name($volunteer)."</lh>\n");
		
	while (!$result->EOF)
	{
	    $row = $result->fields;
	    $c++;
	    show_relationship_leaf($vid, $row, $max_depth - 1, array($vid), $brief);
	    $result->MoveNext();
	}
	
	echo ("</UL>\n");
	echo ("</FORM>\n");
    }

    if (0 == $c and !$brief)
    {
	echo ("<P>"._("None found.")."</P>\n");
    }
    else
    // button for changing maximum depth
    
    if (!$brief)
    {
    
    echo ("<FORM method=\"\" action=\".\">\n");    
    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
    echo ("<INPUT type=\"hidden\" name=\"menu\" value=\"relationships\">\n");    
    echo (_("Maximum depth")."\n");    
    echo ("<SELECT name=\"max_depth\">\n");
    for ($i = 1; $i < 10; $i++)
    {
	$selected = "";
	if ($i == $max_depth)
	{
	    $selected = " SELECTED";
	}
	echo ("<OPTION".$selected.">$i</OPTION>\n");
    }
    echo ("</SELECT>\n");
    echo ("<INPUT type=\"submit\" value=\""._("Go")."\">\n");
    echo ("</FORM>\n");
    
    }
}
    
function relationships_add_form()
{    
    global $db;
    global $db_cache_timeout;


    $vid = intval($_REQUEST['vid']);
    
    if (!has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL))
    {
	return FALSE;
    }
    
    echo ("<H3>"._("Add relationship")."</H3>\n");

    // remember relationship types for multiple uses
    $sql = "SELECT s AS name, string_id FROM strings WHERE type = 'relationship'";
    $result = $db->CacheExecute($db_cache_timeout, $sql);
    $rtypes = array();
    while (!$result->EOF)
    {
	$rtypes[] = $result->fields;
	$result->MoveNext();
    }
    
    // quickly add a relationship if volunteer ID is known

    echo ("<FIELDSET>\n");
    echo ("<LEGEND>"._("Quick add")."</LEGEND>\n");
    echo ("<FORM method=\"post\" action=\".\">\n");
    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
    echo (_("Volunteer ID")." <INPUT type=\"text\" name=\"volunteer2_id\" size=\"5\">\n");
    echo ("<BR>"._("Relationship")." <SELECT name=\"string_id\">\n");
    foreach ($rtypes as $rt)
    {
	echo ("<OPTION value=\"".$rt['string_id']."\">".$rt['name']."</OPTION>\n");
    }
    echo ("</SELECT>\n");
    echo ("<INPUT type=\"submit\" name=\"add_relationship\" value=\""._("Add")."\">\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");
    
    // search for a volunteer by name
    
    echo ("<FIELDSET>\n");
    echo ("<LEGEND>Lookup</LEGEND>\n");
    echo ("<FORM method=\"get\" action=\".\">\n");
    echo ("<INPUT type=\"hidden\" name=\"menu\" value=\"relationships\">\n");
    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
    echo (_("Name")." <INPUT type=\"text\" name=\"volunteer2_name\">\n");
    echo ("<INPUT type=\"submit\" value=\""._("Search")."\">\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");
    
    // display search results, if applicable
    
    if (array_key_exists('volunteer2_name', $_GET))
    {
	echo ("<FIELDSET>\n");
	echo ("<LEGEND>" . _("Search results"). "</LEGEND>\n");
	echo ("<FORM method=\"post\" action=\".\">\n");
	echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
	$needle = $db->qstr('%'.$_GET['volunteer2_name'].'%', get_magic_quotes_gpc());
	// todo: portable concat
	$sql = "SELECT volunteer_id FROM volunteers WHERE concat(first, middle, last, organization) like $needle";
	$result = $db->Execute($sql);
	$c = 0;
	while ($result and !$result->EOF)
	{
	    $row = $result->fields;
	    $c++;
	    $vid2 = $row['volunteer_id'];
	    $v2 = volunteer_get(intval($vid2), $errstr);
	    $name = make_volunteer_name($v2);
	    echo ("<SELECT name=\"string_id_$vid2\">\n");
	    foreach ($rtypes as $rt)
	    {
		echo ("<OPTION value=\"".$rt['string_id']."\">".$rt['name']."</OPTION>\n");
	    }
    	    echo ("</SELECT>\n");
	    echo ("<INPUT type=\"submit\" name=\"add_relationship_$vid2\" value=\"Add\">\n");
	    echo (" $name ($vid2)\n");
	    echo ("<BR>\n");
	    $result->MoveNext();
	}
	echo ("</FORM>\n");
    	echo ("</FIELDSET>\n");
	if (0 == $c)
	{
	    echo ("<P>No volunteers found for ".htmlentities(strip_tags($needle))."</P>\n");
	}
    }
}

function relationship_add()
{
    global $db;


    if (array_key_exists('add_relationship', $_POST))
    {
	$vid2 = intval($_POST['volunteer2_id']);
	$string_id = intval($_POST['string_id']);
    }    
    else
    {
	foreach ($_POST as $pk => $pv)
	{
	    if (preg_match('/add_relationship_(\d+)/', $pk, $matches))
	    {
		$vid2 = $matches[1];
		$string_id = intval($_POST['string_id_'.$vid2]);
	    }
	}
    }
    
    $errors_found = 0;
    
    if (0 == $string_id)
    {
	save_message(MSG_SYSTEM_ERROR, _("Bad form input:").' string_id', __FILE__, __LINE__);    
	$errors_found++;
    }
    
    if (!isset($vid2) or 0 == $vid2)
    {
	save_message(MSG_SYSTEM_ERROR, _("Bad form input:"). ' vid2', __FILE__, __LINE__);
	$errors_found++;	
    }
    
    $vid = intval($_POST['vid']);
    
    if (!volunteer_get($vid, $errstr))
    {
	save_message(MSG_USER_ERROR, _("volunteer_get(): ") . $errstr);
	$errors_found++;	
    }
    
    if (!volunteer_get($vid2, $errstr))
    {
	save_message(MSG_USER_ERROR, _("volunteer_get(): ") . $errstr);
	$errors_found++;	
    }
    
    if (!(has_permission(PC_VOLUNTEER, PT_WRITE, $vid) and has_permission(PC_VOLUNTEER, PT_WRITE, $vid2)))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }
    
    if ($errors_found)
    {
	redirect("?vid=$vid&amp;menu=relationships");
	return FALSE;
    }    
    
    $sql1 = "INSERT INTO relationships (volunteer1_id, volunteer2_id, string_id) VALUES ($vid, $vid2, $string_id)";
    $sql2 = "INSERT INTO relationships (volunteer1_id, volunteer2_id, string_id) VALUES ($vid2, $vid, $string_id)";
    $result1 = $db->Execute($sql1);    
    
    if (!$result1)
    {
	save_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql1);	
    }
    else
    {
        $result2 = $db->Execute($sql2);
	if (!$result2)
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error adding data to database."), __FILE__, __LINE__, $sql2);	
	}
	else
	{
	    save_message(MSG_USER_NOTICE, _("Added relationship."));
	}
    }
    
    // redirect client to non-POST page
    redirect("?vid=$vid&amp;menu=relationships");
    
} /* relationship_add() */


function relationship_delete()
{
    global $db;
    

    $vid1 = $vid2 = FALSE;

    foreach ($_POST as $pk => $pv)
    {
        if (preg_match('/delete_relationship_(\d+)_(\d+)/', $pk, $matches))
        {
	    $vid1 = intval($matches[1]);
	    $vid2 = intval($matches[2]);	    
	}
    }
    
    $errors_found = 0;
    
    if (!$vid1 or !$vid2)
    {
	save_message(MSG_SYSTEM_ERROR, _("Input missing."), __FILE__, __LINE__);
	$errors_found ++;
    }

    if (!(has_permission(PC_VOLUNTEER, PT_WRITE, $vid, NULL) and has_permission(PC_VOLUNTEER, PT_WRITE, $vid2, NULL)))
    {
	$errors_found++;
	save_message(MSG_SYSTEM_ERROR, _("Insufficient permissions."), __FILE__, __LINE__);
    }    
    
    if ($errors_found)
    {
        redirect("?vid=$vid&amp;menu=relationships");
    }
    
    $sql1 = "DELETE FROM relationships WHERE volunteer1_id = $vid1 and volunteer2_id = $vid2";
    $sql2 = "DELETE FROM relationships WHERE volunteer1_id = $vid2 and volunteer2_id = $vid1";    
    $result1 = $db->Execute($sql1);

    
    if (!$result1)
    {
	save_message(MSG_SYSTEM_ERROR, _("Error deleting data from database."), __FILE__, __LINE__, $sql1);
    }
    else
    {
        $result2 = $db->Execute($sql2);
	if (!$result2)
	{
	    save_message(MSG_SYSTEM_ERROR, _("Error deleting data from database."), __FILE__, __LINE__, $sql2);
	}
	else
	{
	    save_message(MSG_USER_NOTICE, _("Deleted."));
	}
    }
    
    redirect("?vid=$vid1&amp;menu=relationships");
} /* relationship_delete() */

?>
