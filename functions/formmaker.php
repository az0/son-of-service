<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Functions for making form fields.
 *
 * $Id: formmaker.php,v 1.3 2003/11/22 05:16:14 andrewziem Exp $
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
	case 'text':	
	case 'integer':
	case 'password':			
	
	    $htype = 'text';
	
	    if ('password' == $type)
	    {
		$htype = 'password';
	    }	    

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
	    echo ("<INPUT type=\"$htype\" name=\"$name\" size=\"$length\"$v>\n");
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

define('FS_PLAIN', 1);
define('FS_TABLE', 2);

class formMaker
{
    var $style;
    var $buttons;
    var $hidden_fields;
    var $values_array;
    
    
    function formMaker()
    // constructor
    {
	$this->values_array = array();
    }

    function open($title = FALSE, $method, $action, $style)
    {
	assert($style == FS_PLAIN or $style == FS_TABLE);
	$this->style = $style;
	echo ("<FORM method=\"$method\" action=\"$action\">\n");
	if (FS_TABLE == $this->style)
	{
	    echo ("<TABLE border=\"1\" class=\"form\">\n");
	}
    }
    
    function setValuesArray($va)
    {
	$this->values_array = $va;
    }

    function addField($label, $type, $name, $attributes, $value)
    {
	// to do: blind support for fields
	
	if (FS_TABLE == $this->style)
	{
	    echo ("<TR>\n");
	    echo ("<TH class=\"vert\">$label</TH>\n");
	    echo ("<TD>\n");
	}
	
	if (array_key_exists($name, $this->values_array))
	{
	    $value = $this->values_array['name'];
	}
	else
	{
	    $value = NULL;
	}

	render_form_field($type, $name, $attributes, $value);
	
	if (FS_TABLE == $this->style)
	{
	    echo ("</TD>\n");
	}

    }
    
    function addButton($name, $value)
    {
	$this->buttons[] = array('name' => $name, 'value' => $value);	
    }
    
    function addHiddenField($name, $value)
    {
	$this->hidden_fields[] = array('name' => $name, 'value' => $value);	    
    }
    
    function close()
    {

	if (FS_TABLE == $this->style)
	{
	    echo ("</TABLE>\n");
	}
	
	if (is_array($this->hidden_fields))
	{
	    foreach ($this->hidden_fields as $hf)
	    {
		echo ("<INPUT type=\"hidden\" name=\"".$hf['name']."\" value=\"".$hf['value']."\">\n");
	    }
	}

	if (is_array($this->buttons))
	{
	    foreach ($this->buttons as $b)
	    {
		echo ("<INPUT type=\"submit\" name=\"".$b['name']."\" value=\"".$b['value']."\">\n");
	    }
	}
	
	echo ("</FORM>\n");
	
    }



}


?>