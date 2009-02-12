<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Volunteer statistics.
 *
 * $Id: stat.php,v 1.7 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

if (preg_match('/textwriter.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function stats_update_volunteers($db)
{
    set_time_limit(60 * 10); // 10 minutes

    $result = $db->Execute("SELECT volunteer_id FROM volunteers"); 
    if (!$result)
	return FALSE;
    while (!$result->EOF)
    {
	$row = $result->fields;
	stats_update_volunteer($db, $row['volunteer_id']);
	$result->MoveNext();
    }
    

}

function stats_update_volunteer($db, $vid)
{
    // todo: faster using select into table
    
    if (!is_numeric($vid))
    {
	process_system_error("stats_update_volunteer():"._("Expected integer."));
	return FALSE;
    }

    $result = $db->Execute("SELECT sum(hours) as hours FROM work WHERE volunteer_id = $vid");
    
    if (!$result)
    {
	return FALSE;
    }
    $row = $result->fields;
    if (!$row)
    {
	return FALSE;
    }
    $hours_life = $row['hours'];

    // todo: portable LIMIT 1 for UPDATE
    $db->Execute("UPDATE volunteers SET hours_life = $hours_life WHERE volunteer_id = $vid");

    $sql = "SELECT sum(hours) as hours FROM work WHERE volunteer_id = $vid and year(date) = ".(date('Y')-1)."";
    
    $result = $db->Execute($sql);
    
    if (!$result)
    {
	return FALSE;
    }
    
    $row = $result->fields;
    
    if (!$row)
    {
	return FALSE;
    }
    
    $hours_ly = $row['hours'];

    // todo: portable LIMIT 1 for UPDATE    
    $sql = "UPDATE volunteers SET hours_ly = $hours_ly WHERE volunteer_id = $vid";
    
    $db->Execute($sql);

    $sql = "SELECT sum(hours) as hours FROM work WHERE volunteer_id = $vid and year(date) = ".date('Y')."";
    
    $result = $db->Execute($sql);
    
    if (!$result)
    {
	return FALSE;
    }
    
    $row = $result->fields;
    
    if (!$row)
    {
	return FALSE;
    }
    
    $hours_ytd = $row['hours'];
    
    // todo: portable LIMIT 1 for UPDATE    
    $sql = "UPDATE volunteers SET hours_ytd = $hours_ytd WHERE volunteer_id = $vid";
    
    $db->Execute($sql);
	
}



?>
