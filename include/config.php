<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: config.php,v 1.7 2003/12/06 19:39:49 andrewziem Exp $
 *
 */

if (preg_match('/config.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

$cfg['ado_path'] = SOS_PATH . 'adodb';

$cfg['dbtype'] = 'mysql';	//default: mysql
$cfg['dbhost'] = 'localhost';
$cfg['dbpass'] = 'secret2';
$cfg['dbuser'] = 'root';
$cfg['dbname'] = 'sos';
$cfg['dbpersist'] = TRUE;	//default: persistant connections on (true)

$smtp_hostname = 'localhost';

// html_security_level
// 1: HTML stripped from most form input for maximum security
// 2: admin-level users can include some HTML
// 3: all users can include some HTM
$html_security_level = 1;

// db_cache_timeout: measured in seconds
$db_cache_timeout = 60;

$ADODB_CACHE_DIR = SOS_PATH . 'data/';

$debug = TRUE;

?>