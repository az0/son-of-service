<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 * 
 *
 * $Id: auth.php,v 1.1 2003/10/05 16:14:35 andrewziem Exp $
 *
 */


function is_logged_in($or_die = TRUE)
{
    if (isset($_SESSION['u']))
	return TRUE;
	
    if ($or_die)
    {
	exit(_("You must be logged in to access this page."));
    }

} /* is_logged_in() */


?>