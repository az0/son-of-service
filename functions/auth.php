<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 * 
 *
 * $Id: auth.php,v 1.2 2003/11/22 05:16:14 andrewziem Exp $
 *
 */


function is_logged_in($or_die = TRUE)
{
    if (isset($_SESSION['u']))
	return TRUE;
	
    if ($or_die)
    {
	echo(_("You must be logged in to access this page."));
	// to do: cookie probe
	process_user_notice("<P>You may get this error if your system is blocking cookie</A>s.  Try enabling cookies.</P>\n");
	echo("<P><A href=\"".SOS_PATH."src/cookie_probe.php\">"._("Is my system blocking cookies?")."</A></P>\n");
	
	exit();
    }

} /* is_logged_in() */


?>