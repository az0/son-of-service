<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: config.php,v 1.1 2003/10/05 16:14:13 andrewziem Exp $
 *
 */

if (preg_match('/config.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

$cfg['mysqlhost'] = 'localhost';
$cfg['mysqlpassword'] = 'secret2';
$cfg['mysqluser'] = 'root';
$cfg['mysqlfile'] = 'sos';

$cfg['email_smtp'] = 'pop.clsp.qwest.net';

$base_url = 'http://localhost/sos/';

?>