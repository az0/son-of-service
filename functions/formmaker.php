<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Functions for making form fields.
 *
 * $Id: formmaker.php,v 1.2 2003/11/09 20:21:22 andrewziem Exp $
 *
 */

if (preg_match('/formmaker.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

function render_form_field($type, $name, $attributes, $value)
{
    assert(is_array($attributes));
    assert(is_string($name));

    switch ($type)
    {
	case 'string':
	case 'integer':

	    if (empty($value))
	    {
		$v = '';
	    }
	    else 
	    {
		$v =" VALUE=\"$value\" ";
	    }
	    if (empty($attributes['length']))
	    {
		$length = 4;
	    }
	    else
	    {
		$length = intval($attributes['length']);
	    }
	    echo ("<INPUT type=\"text\" name=\"$name\" size=\"$length\"$v>\n");
	    break;
	    
	case 'date':		    
	    if (empty($value))
	    {
		$v = '';
	    }
	    else 
	    {
		$v =" VALUE=\"$value\" ";
	    }
	    echo ("<INPUT type=\"text\" name=\"$name\" size=\"12\"$v>\n");
	    break;
	    

	case 'boolean':
	    echo ("<INPUT type=\"radio\" name=\"$name\" value=\"1\">Yes\n");
	    echo ("<INPUT type=\"radio\" name=\"$name\" value=\"0\">No\n");	    
	    break;

	case 'textarea':
	    $rows = intval($attributes['rows']);
	    $cols = intval($attributes['cols']);
	    echo ("<TEXTAREA name=\"$name\" rows=\"$rows\" cols=\"$cols\">");
	    if (!empty($value))
		echo $value;
	    echo ("</TEXTAREA>\n");
	    break;
	    
	default:
	    process_system_error("render_form_field(): "._("Bad parameter"));
	    break;	    
    }

}


?>