<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: relationships.php,v 1.2 2003/11/01 17:24:55 andrewziem Exp $
 *
 */

if (preg_match('/relationships.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

function relationships_view()
{
    global $db;
    

    $vid = intval($_REQUEST['vid']);
    
    $max_depth = 3;

    echo ("<H2>Relationships</H2>\n");

    $sql = "SELECT relationships.relationship_id as relatipnship_id, ".
    "relationships.volunteer2_id as volunteer2_id, ".
    "relationship_types.name as rname  ".
    "FROM relationships ".
    "LEFT JOIN relationship_types ".
    "ON relationships.rtype = relationship_types.relationship_type_id ".
    "WHERE relationships.volunteer1_id = $vid ";
    
    $result = $db->query($sql);
    
    $depth = 1;
    
    $ignore_relationships = array (); // could this be improved?
    
    echo ("<UL>\n");
    echo ("<LH>".make_volunteer_name($vid)."</LH>\n");
    
    function show_relationship_leaf($row, $cdepth)
    {
	if ($cdepth > $max_depth)
	    return FALSE;
	    
	if (array_search($row['relationship_id'], $ignore_relationships))
	    return FALSE;
	
	$volunteer2_row = volunteer_get($row['volunteer2_id']);
	
	if ($volunteer2_row)
	{
	    $row['volunteer2_name'] = make_volunteer_name($volunteer2_row);
	}	
	
	echo ("<LI>".$row['voluteer2_name'].", ".$row['rname']."</LI>\n");
    }

    $c = 0;    
    
    while (FALSE != ($row = $db->fetch_array($result)))
    {
	$c++;
	show_relationship_leaf($row, $depth + 1);
	$ignore_relationships[] = $row['relationship_id'];
    }
    
    if (0 == $c)
    {
	echo ("<P>No relationships found.</P>\n");
    }
    
    echo ("</UL>\n");

    echo ("<FORM method=\"\" action=\".\">\n");    
    echo ("<INPUT type=\"hidden\" name=\"menu\" value=\"relationships\">\n");
    echo ("<SELECT name=\"max_depth\">\n");
    for ($i = 1; $i < 10; $i++)
    {
	echo ("<OPTION>$i</OPTION>\n");
    }
    echo ("</SELECT>\n");
    echo ("Maximum depth\n");
    echo ("<INPUT type=\"submit\" value=\""._("Go")."\">\n");
    echo ("</FORM>\n");
    
    echo ("<H3>Add relationship</H3>\n");

    // memory relationship types for multiple uses
    $result = $db->query("SELECT * FROM relationship_types");
    $rtypes = array();
    while (FALSE != ($row = $db->fetch_array($result)))
    {
//	echo ("<OPTION value=\"".$row['relationship_type_id']."\">".$row['name']."</OPTION>\n");
	$rtypes[] = $row;
    }

    echo ("<FIELDSET>\n");
    echo ("<LEGEND>Quick add</LEGEND>\n");
    echo ("<FORM method=\"post\" action=\".\">\n");
    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
    echo ("Volunteer ID <INPUT type=\"text\" name=\"volunteer2_id\" size=\"5\">\n");
    echo ("Relationship <SELECT name=\"rtype\">\n");
    foreach ($rtypes as $rt)
    {
	echo ("<OPTION value=\"".$rt['relationship_type_id']."\">".$rt['name']."</OPTION>\n");
    }
    echo ("</SELECT>\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");
    
    echo ("<FIELDSET>\n");
    echo ("<LEGEND>Lookup</LEGEND>\n");
    echo ("<FORM method=\"get\" action=\".\">\n");
    echo ("<INPUT type=\"hidden\" name=\"menu\" value=\"relationships\">\n");
    echo ("<INPUT type=\"hidden\" name=\"vid\" value=\"$vid\">\n");
    echo ("Volunteer search <INPUT type=\"text\" name=\"volunteer2_name\">\n");
    echo ("</SELECT>\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");
    
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
	    $name = make_volunteer_name($vid2);
//	    echo ("<INPUT type=\"hidden\" name=\"volunteer2_id\" value=\"$vid2\">\n");
	    echo ("$name ($vid2)\n");
	    echo ("<SELECT name=\"rtype\">\n");
	    foreach ($rtypes as $rt)
	    {
		echo ("<OPTION value=\"".$rt['relationship_type_id']."\">".$rt['name']."</OPTION>\n");
	    }
    	    echo ("</SELECT>\n");
	    echo ("<INPUT type=\"submit\" name=\"relationship_add_$vid2\" value=\"Add\">\n");
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

?>
