<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * General information about Son of Service.
 *
 * $Id: about.php,v 1.2 2003/11/23 03:26:58 andrewziem Exp $
 *
 */


define('SOS_PATH', '../');

require_once (SOS_PATH . 'functions/html.php');

make_html_begin(_("About Son of Service"), array());

make_nav_begin();

?>

<H2>About Son of Service</H2>

<P>Son of Service (SOS) is a multiuser volunteer management database. 
It is free, easy to use, standards-based, and has few system
requirements.</P>

<P>SOS is provided to you under the GNU <A href="http://www.gnu.org/licenses/gpl.html">General Public License version 2</A>.</P>

<P><A href="http://sos.sourceforge.net">SOS web site</A></P>


<?php

make_html_end();

?>
