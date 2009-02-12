<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 * 
 * Handles user permissions and access control restrictions.
 *
 * $Id: access.php,v 1.12 2009/02/12 04:11:20 andrewziem Exp $
 *
 */


/**
 * is_logged_in($or_die = TRUE)
 *
 * Deterine whether the user has logged in correctly.  Optionally die
 * if he hasn't.
 *
 * @param bool or_die If set to true, the function dies on failure.
 * @return void
 */

function is_logged_in($or_die = TRUE)
{
	if (isset($_SESSION['u']))
		return TRUE;
	
	if ($or_die)
	{
		echo(_("You must be logged in to access this page."));
		echo("</P><A href=\"" . SOS_PATH . "\">" . _("Log in") . "</A>.</P>\n");
		process_user_notice("<P>You may get this error if your system is blocking cookie</A>s.  Try enabling cookies.</P>\n");
		echo("<P><A href=\"".SOS_PATH."src/cookie_probe.php\">"._("Is my system blocking cookies?")."</A></P>\n");
		exit();
	}
} /* is_logged_in() */


define('PC_ADMIN', 1);
define('PC_VOLUNTEER', 2);

define('PT_READ', 1);
define('PT_WRITE', 2);


/** 
 * has_permission($category, $type, $volunteer_id = NULL, $user_id = NULL)
 *
 * Determine whether the user has permission to access a feature.
 * Note: Not all combinations are in use now.
 * 
 * @param category int PC_ADMIN or PC_VOLUNTEER
 * @param type int PT_READ or PT_WRITE
 * @param volunteer_id int integer or NULL
 * @param user_id int user_id or NULL
 * @return bool
 */

function has_permission($category, $type, $volunteer_id = NULL, $user_id = NULL)
{
    if (!is_logged_in(FALSE))
    {
	return FALSE;
    }
    switch ($category)
    {
	case PC_ADMIN:
		return ('1' == $_SESSION['sos_user']['access_admin']);
		break;
	
	case PC_VOLUNTEER:
	    if (PT_READ == $type or (PT_WRITE == $type and 1 == $_SESSION['sos_user']['access_change_vol']))
	    {
		return TRUE;
	    }
	    break;
	default:
	    save_message(MSG_SYSTEM_ERROR, _("Unexpected parameter."), __FILE__, __LINE__);
	    break;
    }
    return FALSE;
}

?>
