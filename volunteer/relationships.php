<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: relationships.php,v 1.5 2003/11/07 16:59:19 andrewziem Exp $
 *
 */

if (preg_match('/relationships.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function show_relationship_leaf($vid, $row, $remaining_depth, $ignore_vids)
{    
    global $db;
    

    if ($remaining_depth < 1)
    {
        return FALSE;
    }
        
    $ignore_vids[] = $vid;
    	
    $volunteer2_row = volunteer_get($row['volunteer2_id']);
    
    if ($volunteer2_row)
    {
        $row['volunteer2_name'] = make_volunteer_name($volunteer2_row);
	
	echo ("<LI>".$row['volunteer2_name']." [<A href=\"?vid=".$row['volunteer2_id']."\">account</A>, <A href=\"?vid=".$row['volunteer2_id']."&menu=relationships\">relationships</A>], ".$row['rname']."\n");
	echo ("<INPUT type=\"submit\" name=\"delete_relationship_".$vid."_".$row['volunteer2_id']."\" value=\""._("Delete")."\">\n");
	echo ("</LI>\n");
	
        $sql = "SELECT relationships.relationship_id AS relationship_id, ".
	    "relationships.volunteer2_id AS volunteer2_id, ".
	    "relationship_types.name AS rname  ".
	    "FROM relationships ".
	    "LEFT JOIN relationship_types ".
	    "ON relationships.relationship_type_id = relationship_types.relationship_type_id ".
	    "WHERE relationships.volunteer1_id = ".$row['volunteer2_id']." AND relationships.volunteer2_id != $vid";
    
        $result = $db->query($sql);
	
	if (!$result)
	{
	    process_system_error(_("Database error."), 	array('debug' => $db->get_error()));
	}
	else if ($db->num_rows($result) > 0)
        {
	    echo ("<UL>\n");
            while (FALSE != ($row2 = $db->fetch_array($result)))
    	    {
		if (!in_array($row2['volunteer2_id'], $ignore_vids))
		{
		    show_relationship_leaf($row['volunteer2_id'], $row2, $remaining_depth - 1, $ignore_vids);
		}
	    }
	    echo ("</UL>\n");	    
	}
    }	
} /* show_relationship_leaf() */


function relationships_view()
{
    global $db;
    global $ignore_relationships;

    $vid = intval($_REQUEST['vid']);
    
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

    echo ("<H2>"._("Relationships")."</H2>\n");

    // query primary relationships
    
    $sql = "SELECT relationships.relationship_id AS relationship_id, ".
    "relationships.volunteer2_id AS volunteer2_id, ".
    "relationship_types.name AS rname  ".
    "FROM relationships ".
    "LEFT JOIN relationship_types ".
    "ON relationships.relationship_type_id = relationship_types.relationship_type_id ".
    "WHERE relationships.volunteer1_id = $vid ";
    
    $result = $db->query($sql);
    
    if (!$result)
    {
	process_system_error(_("Database error."), array('debug' => $db->get_error()));
    }
    else if ($db->num_rows($result) > 0)
    {

	echo ("<FORM action=\".\" method=\"post\">\n");
	echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
	echo ("<UL>\n");
	echo ("<LH>".make_volunteer_name(volunteer_get($vid))."</LH>\n");
    
	while (FALSE != ($row = $db->fetch_array($result)))
	{
	    $c++;
	    show_relationship_leaf($vid, $row, $max_depth - 1, array($vid));
	}
	
	echo ("</UL>\n");
	echo ("</FORM>\n");

    }

    if (0 == $c)
    {
	echo ("<P>"._("None found.")."</P>\n");
    }
    
    // button for changing maximum depth
    
    echo ("<FORM method=\"\" action=\".\">\n");    
    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
    echo ("<INPUT type=\"hidden\" name=\"menu\" value=\"relationships\">\n");    
    echo (_("Maximum depth")."\n");    
    echo ("<SELECT name=\"max_depth\">\n");
    // to do: remember, default=3
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
    
    echo ("<H3>"._("Add relationship")."</H3>\n");

    // remember relationship types for multiple uses
    $result = $db->query("SELECT * FROM relationship_types");
    $rtypes = array();
    while (FALSE != ($row = $db->fetch_array($result)))
    {
	$rtypes[] = $row;
    }
    
    // quickly add a relationship if volunteer ID is known

    echo ("<FIELDSET>\n");
    echo ("<LEGEND>"._("Quick add")."</LEGEND>\n");
    echo ("<FORM method=\"post\" action=\".\">\n");
    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
    echo (_("Volunteer ID")." <INPUT type=\"text\" name=\"volunteer2_id\" size=\"5\">\n");
    echo ("<BR>"._("Relationship")." <SELECT name=\"rtype\">\n");
    foreach ($rtypes as $rt)
    {
	echo ("<OPTION value=\"".$rt['relationship_type_id']."\">".$rt['name']."</OPTION>\n");
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
    echo ("</SELECT>\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");
    
    // display search results, if applicable
    
    if (array_key_exists('volunteer2_name', $_GET))
    {
	echo ("<FIELDSET>\n");
	echo ("<LEGEND>Search results</LEGEND>\n");
	echo ("<FORM method=\"post\" action=\".\">\n");
	echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
	$needle = $db->escape_string($_GET['volunteer2_name']);
	$result = $db->query("SELECT volunteer_id FROM volunteers WHERE concat(first, middle, last, organization) like '%$needle%'");
	$c = 0;
	while (FALSE != ($row = $db->fetch_array($result)))
	{
	    $c++;
	    $vid2 = $row['volunteer_id'];
	    $v2 = volunteer_get(intval($vid2));
	    $name = make_volunteer_name($v2);
	    echo ("<SELECT name=\"rtype_$vid2\">\n");
	    foreach ($rtypes as $rt)
	    {
		echo ("<OPTION value=\"".$rt['relationship_type_id']."\">".$rt['name']."</OPTION>\n");
	    }
    	    echo ("</SELECT>\n");
	    echo ("<INPUT type=\"submit\" name=\"add_relationship_$vid2\" value=\"Add\">\n");
	    echo (" $name ($vid2)\n");
	    echo ("<BR>\n");
	}
	echo ("</FORM>\n");
    	echo ("</FIELDSET>\n");
	if (0 == $c)
	{
	    echo ("<P>No volunteers found for ".strip_tags($needle)."</P>\n");
	}
    }
}

function relationship_add()
{
    global $db;

    if (array_key_exists('add_relationship', $_POST))
    {
	$vid2 = intval($_POST['volunteer2_id']);
	$rtype = intval($_POST['rtype']);
    }    
    else
    {
	foreach ($_POST as $pk => $pv)
	{
	    if (preg_match('/add_relationship_(\d+)/', $pk, $matches))
	    {
		$vid2 = $matches[1];
		$rtype = intval($_POST['rtype_'.$vid2]);
	    }
	}
    }
    
    if (!$vid2)
    {
	process_system_error(_("Input missing."));
    }
    
    $vid = intval($_POST['vid']);
    
    $sql1 = "INSERT INTO relationships (volunteer1_id, volunteer2_id, relationship_type_id) VALUES ($vid, $vid2, $rtype)";
    $sql2 = "INSERT INTO relationships (volunteer1_id, volunteer2_id, relationship_type_id) VALUES ($vid2, $vid, $rtype)";
    $result1 = $db->query($sql1);    
    $result2 = $db->query($sql2);
    
    if (!$result1 or !$result2)
    {
	process_system_error(_("Error adding data to database."), array('debug' => $db->get_error()));
    }
    else
    {
	process_user_notice(_("Added relationship."));
    }
    
    relationships_view();
    
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
    
    if (!$vid1 or !$vid2)
    {
	process_system_error(_("Input missing."));
    }
    
    $sql1 = "DELETE FROM relationships WHERE volunteer1_id = $vid1 and volunteer2_id = $vid2";
    $sql2 = "DELETE FROM relationships WHERE volunteer1_id = $vid2 and volunteer2_id = $vid1";    
    $result1 = $db->query($sql1);
    $result2 = $db->query($sql2);
    
    if (!$result1 or !$result2)
    {
	process_system_error(_("Error deleting data from database."));
    }
    else
    {
	process_user_notice(_("Deleted."));
    }
    
    relationships_view();
    

} /* relationship_delete() */

?>
