<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Volunteer statistics.
 *
 * $Id: stat.php,v 1.1 2003/10/05 16:14:35 andrewziem Exp $
 *
 */

if (preg_match('/textwriter.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function stats_update_volunteers($db)
{
    set_time_limit(60 * 10); // 10 minutes

    $result = $db->query("SELECT volunteer_id FROM volunteers"); 
    if (!$result)
	return FALSE;
    while (FALSE != ($row = $db->fetch_array($result)))
    {
	stats_update_volunteer($db, $row['volunteer_id']);
    }
    

}

function stats_update_volunteer($db, $vid)
{
    // to do: faster using select into table
    
    if (!is_numeric($vid))
    {
	process_system_error("stats_update_volunteer():"._("Expected integer."));
	return FALSE;
    }

    $result = $db->query("SELECT sum(hours) as hours FROM work WHERE volunteer_id = $vid");
    if (!$result)
	return FALSE;
    $row = $db->fetch_array($result);
    if (!$row)
	return FALSE;
    $hours_life = $row['hours'];
    
    $db->query("UPDATE volunteers SET hours_life = $hours_life WHERE volunteer_id = $vid LIMIT 1");

    $result = $db->query("SELECT sum(hours) as hours FROM work WHERE volunteer_id = $vid and year(date) = ".(date('Y')-1)."");
    if (!$result)
	return FALSE;
    $row = $db->fetch_array($result);
    if (!$row)
	return FALSE;
    $hours_ly = $row['hours'];
    
    $db->query("UPDATE volunteers SET hours_ly = $hours_ly WHERE volunteer_id = $vid  LIMIT 1");

    $result = $db->query("SELECT sum(hours) as hours FROM work WHERE volunteer_id = $vid and year(date) = ".date('Y')."");
    if (!$result)
	return FALSE;
    $row = $db->fetch_array($result);
    if (!$row)
	return FALSE;
    $hours_ytd = $row['hours'];
    
    $db->query("UPDATE volunteers SET hours_ytd = $hours_ytd WHERE volunteer_id = $vid LIMIT 1");
	
}



?>