<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: relationships.php,v 1.1 2003/10/31 17:10:53 andrewziem Exp $
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
    
    while (FALSE != ($row = $db->fetch_array($result))
    {
	show_relationship_leaf($row, $depth + 1);
	$ignore_relationships[] = $row['relatioship_id'];
    }
    
    echo ("</UL>\n");


}

?>