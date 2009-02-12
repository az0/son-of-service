<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Delete a volunteer.
 *
 * $Id: delete_volunteer.php,v 1.8 2009/02/12 04:11:20 andrewziem Exp $
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
    $result = $db->Execute("DELETE FROM availability WHERE volunteer_id=$vid");          
    $result = $db->Execute("DELETE FROM extended WHERE volunteer_id=$vid");              
    $result = $db->Execute("DELETE FROM notes WHERE volunteer_id=$vid");
    $result = $db->Execute("DELETE FROM phone_numbers WHERE volunteer_id=$vid");        
    $result = $db->Execute("DELETE FROM relationships WHERE volunteer1_id = $vid OR volunteer2_id = $vid");            	    
    $result = $db->Execute("DELETE FROM volunteer_skills WHERE volunteer_id=$vid");            
    $result = $db->Execute("DELETE FROM work WHERE volunteer_id=$vid");    
      
    // delete primary record
    // todo: portable LIMIT 1
    $sql = "DELETE FROM volunteers WHERE volunteer_id=$vid LIMIT 1";
    $result = $db->Execute($sql);

    if ($result)
    {
	// todo: log
        echo ("<P>Volunteer permanently deleted.</P>\n");
        return TRUE;
    }
    else
    {
	die_message(MSG_SYSTEM_ERROR, _("Error deleting data from database."), __FILE__, __LINE__, $sql);
    }
}

?>
