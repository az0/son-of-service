<?php

/*
 * Son of Service
 * Copyright (C) 2003-2009 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Functions for making form fields.
 *
 * $Id: formmaker.php,v 1.16 2009/02/12 04:11:20 andrewziem Exp $
 *
 */

if (preg_match('/formmaker.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

/**
 * render_form_field($type, $name, $attributes, $value)
 *
 * Renders a form field via echo() given its parameters.
 *
 * @param string type string, text, integer, password, date, boolean, 
 *                    textarea, select, checkbox
 * @param string name HTML formname
 * @param array attributes depends on type
 * @param mixed value initial value
 * @return void
 */

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

	    if (0 == strlen(trim($value)))
	    {
		$v = '';
	    }
	    else 
	    {
		$v =" VALUE=\"" . htmlentities($value) . "\" ";
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
	    if (0 == strlen(trim($value)))
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
	    if (0 < strlen(trim($value)))
		echo htmlentities($value);
	    echo ("</TEXTAREA>\n");
	    break;
	    
	case 'select':
	    echo ("<SELECT name=\"$name\">\n");
	    foreach ($attributes as $k => $v)
	    {
		if (is_array($v) and array_key_exists('value', $v) and array_key_exists('label', $v))
		{
		    $selected = "";
		    if ($v['value'] == $value)
		    {
			$selected = " selected";
		    }
		    echo ("<OPTION value=\"".$v['value']."\"$selected>".$v['label']."</OPTION>\n");
		}
	    }
	    echo ("</SELECT>\n");	    
	    break;

	case 'checkbox':
	    echo ("<INPUT type=\"checkbox\" name=\"$name\" value=\"1\" ");
	    if (1 == $value)
	    {
		echo ("checked");	
	    }
	    echo (">\n");
	    break;

	default:
	    process_system_error("render_form_field(): "._("Bad parameter"));
	    break;	    
    }
}

define('FS_PLAIN', 1);
define('FS_TABLE', 2);

class formMaker
// todo: complex forms: multiple tables with one form
{
    var $style;
    var $buttons;
    var $hidden_fields;
    
    
    function formMaker()
    // constructor
    {
	$this->hidden_fields = array();
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
    
    function addField($label, $type, $name, $attributes, $value)
    {
	// todo: blind user support for fields
	
	if (FS_TABLE == $this->style)
	{
	    echo ("<TR>\n");
	    echo ("<TH class=\"vert\">" . htmlentities($label) . "</TH>\n");
	    echo ("<TD>\n");
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
