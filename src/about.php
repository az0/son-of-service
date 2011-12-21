<?php

/*
 * Son of Service
 * Copyright (C) 2003-2011 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * General information about Son of Service.
 *
 * $Id: about.php,v 1.10 2011/12/21 05:03:47 andrewziem Exp $
 *
 */

session_start();

define('SOS_PATH', '../');

require_once (SOS_PATH . 'functions/html.php');

make_html_begin(_("About Son of Service"), array());

make_nav_begin();

?>

<H2>About Son of Service</H2>

<P>Son of Service, Copyright &copy; 2003-2011 Andrew Ziem.</P>

<P>SOS comes with ABSOLUTELY NO WARRANTY.  This is free software, and
you may redistribute it under certain conditions.  For details please
see the GNU <A href="http://www.gnu.org/licenses/gpl.html">General
Public License version 2</A>.</P>

<P>For more information see the <A href="http://sos.sourceforge.net">SOS web site</A>.</P>

<p>Your donations say "thank you" and encourage future development.
<br><a rel="external nofollow" href="http://sourceforge.net/donate/index.php?group_id=91083"><img src="http://images.sourceforge.net/images/project-support.jpg" width="88" height="32" border="0" alt="Support Son of Service" /> </a>
</p>

<?php

make_html_end();

?>
