<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: config.php,v 1.2 2003/10/05 19:51:29 andrewziem Exp $
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

$smtp_hostname = 'localhost';

$base_url = 'http://localhost/sos/';

// html_security_level
// 1: HTML stripped from most form input for maximum security
// 2: admin-level users can include some HTML
// 3: all users can include some HTM
$html_security_level = 1;

?>