<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Delete a volunteer.
 *
 * $Id: delete_volunteer.php,v 1.3 2003/11/27 16:34:18 andrewziem Exp $
 *
 */

if (preg_match('/delete_volunteer.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function delete_volunteer($vid)
{
    global $db;
    
    
    $vid = intval($vid);

    echo ("<P>"._("Deleting volunteer...")."</P>\n");
	
    // delete related records
    $result = $db->query("DELETE FROM notes WHERE vid=$vid");
    $result = $db->query("DELETE FROM work WHERE vid=$vid");
    $result = $db->query("DELETE FROM availability WHERE vid=$vid");      
    $result = $db->query("DELETE FROM volunteer_skills WHERE vid=$vid");            
    $result = $db->query("DELETE FROM relationships WHERE volunteer1_id = $vid OR volunteer2_id = $vid");            	
      
    // delete primary record
    $result = $db->query("DELETE FROM volunteers WHERE volunteer_id=$vid LIMIT 1");

    if ($result)
    {
	// todo: log
        echo ("<P>Volunteer permanently deleted.</P>\n");
        return TRUE;
    }
    else
    {
        process_system_error(_("Error deleting data from database."), array('debug' => $db->get_error()));
        return FALSE;
    }
}

?>