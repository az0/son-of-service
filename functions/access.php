<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 * 
 * Handles user permissions and access control restrictions.
 *
 * $Id: access.php,v 1.1 2003/11/27 16:34:18 andrewziem Exp $
 *
 */


function is_logged_in($or_die = TRUE)
{
    if (isset($_SESSION['u']))
	return TRUE;
	
    if ($or_die)
    {
	echo(_("You must be logged in to access this page."));
	process_user_notice("<P>You may get this error if your system is blocking cookie</A>s.  Try enabling cookies.</P>\n");
	echo("<P><A href=\"".SOS_PATH."src/cookie_probe.php\">"._("Is my system blocking cookies?")."</A></P>\n");
	
	exit();
    }

} /* is_logged_in() */


define('PC_ADMIN', 1);
define('PC_VOLUNTEER', 2);

define('PT_READ', 1);
define('PT_WRITE', 1);


function has_permission($category, $type, $volunteer_id, $user_id)
// category = PC_ADMIN, PC_VOLUNTEER
// type = READ, MODIFY
// volunteer_id = volunteer on which operation requested
// user_id = user on which operation requested
// not all combinations used now
{
    if (!is_logged_in(FALSE))
    {
	return FALSE;
    }
    
    switch ($category)
    {
	case PC_ADMIN:
	    return ('1' == $_SESSION['user']['access_admin']);
	    break;
	
	case PC_VOLUNTEER:
	    if (PT_READ == $type or (PT_WRITE == $type and $_SESSION['user']['access_change_vol']))
	    {
		return TRUE;
	    }
	    return FALSE;
	    break;
	default:
	    process_system_error(_("Unexpected parameter"));
	    break;
    }
    return FALSE;
}

?>