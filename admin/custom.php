<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Administration of custom data fields.
 *
 * $Id: custom.php,v 1.8 2003/11/10 17:22:30 andrewziem Exp $
 *
 */

if (preg_match('/custom.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

// to do
// - field position, ability to change position
// - validation rules
// - required

require_once(SOS_PATH . 'functions/formmaker.php');

function custom_add_field_form1()
{
?>
<FIELDSET>
<CAPTION><?php echo _("Add custom field"); ?></CAPTION>
<FORM action="." method="post">
<P class="instructionstext">First choose a type of field.  Then specify options for that type.  Then give a label.</P>

<FIELDSET>
<CAPTION>1. Type of field</CAPTION>
<FIELDSET>
<CAPTION><?php echo _("String");?></CAPTION>
<BR><INPUT type="radio" name="fieldtype" value="string">String: a single line of text
<BR><INPUT class="indented" type="text" name="string_length" value="20" size="3">Maximum length
</FIELDSET>

<FIELDSET>
<CAPTION><?php echo _("Text area");?></CAPTION>
<BR><INPUT type="radio" name="fieldtype" value="textarea">Text area: multiple lines of text
<BR><INPUT class="indented" type="text" name="textarea_rows" value="10" size="3">Width (characters)
<BR><INPUT class="indented" type="text" name="textarea_cols" value="80"  size="3">Height (characters)
<BR><INPUT class="indented" type="text" name="textarea_length" value="250"  size="5">Maximum length (characters)
</FIELDSET>

<FIELDSET>
<CAPTION><?php echo _("Date");?></CAPTION>
<BR><INPUT type="radio" name="fieldtype" value="date">Date
</FIELDSET>

<FIELDSET>
<CAPTION><?php echo _("Integer");?></CAPTION>
<BR><INPUT type="radio" name="fieldtype" value="integer">Integer: -1, 0, 1, 2, 3, 4...
</FIELDSET>

<FIELDSET>
<CAPTION><?php echo _("Decimal");?></CAPTION>
<BR><INPUT type="radio" name="fieldtype" value="decimal" disabled>Decimal [not available]
</FIELDSET>

<FIELDSET>
<CAPTION><?php echo _("Boolean");?></CAPTION>
<BR><INPUT type="radio" name="fieldtype" value="boolean" disabled>Boolean: yes or no
</FIELDSET>

<FIELDSET>
<CAPTION>Check box</CAPTION>
[not available]
<BR><INPUT type="radio" name="fieldtype" value="checkbox" disabled>Check box: none, one, or more choices from list
</FIELDSET>


<FIELDSET>
<CAPTION>Not yet available</CAPTION>
<BR><INPUT type="radio" name="fieldtype" value="radio" disabled>Multiple choice: exactly one choice from list
<BR><INPUT type="radio" name="fieldtype" value="file" disabled>File: a word processing document, image, or any other file
</FIELDSET>

</FIELDSET>

<FIELDSET>
<CAPTION>2. Field attributes</CAPTION>
<BR>Label <INPUT type="text" name="label">

<!--<BR><INPUT type="text" name="description">Description-->
</FIELDSET>

<INPUT type="hidden" name="stage" value="2">
<P><INPUT type="submit" name="add_custom_field" value="<?php echo _("Next"); ?>"></P>
</FORM>

</FIELDSET>

<?php

} /* custom_add_field_form1() */


$fieldtypes = array('integer','string','textarea','date','boolean');
//$fieldtypes = array('integer','string','textarea','boolean','checkbox','radio','file');

function custom_add_field_form2()
{
    global $fieldtypes;

    // validate and sanitize form input    

    $errors_found = 0;
    
    print_r($_POST);
/*
    if (!array_search($_POST['fieldtype'], $fieldtypes))
    {
	process_system_error(_("Bad form input:".' fieldtype[1]'));
	$errors_found++;
    }
*/    
    $fieldtype = $_POST['fieldtype'];
    
    $label = strip_tags($_POST['label']);
    
    if (strlen($label) < 2)
    {
	$errors_found++;            
	process_user_error(_("Too short:").' '._("Label"));
    }
    
    $attributes = array();
    
    switch ($fieldtype)
    {
	case 'string':
	    $attributes['length'] = intval($_POST['string_length']);
	    if (2 > $attributes['length'] or $attributes['length'] > 254)
		process_user_warning(_("Length is extreme."));
	    break;
	case 'date':
	    $attributes['length'] = 12;
	    break;	    
	case 'boolean':
	case 'integer':	
	    break;	    
	case 'textarea';
	    $attributes['rows'] = intval($_POST['textarea_rows']);
	    $attributes['cols'] = intval($_POST['textarea_cols']);	    
            $attributes['length'] = intval($_POST['textarea_length']);	    	    
	    break;
	default:
	    process_system_error(_("Bad form input:" .' fieldtype'));	    
	    $errors_found++;
	    break;
    }
    
    if ($errors_found)
	return FALSE;
    
?>
<FIELDSET>
<CAPTION><?php echo _("Sample"); ?></CAPTION>
<TABLE border="1">
<TR>
<TH class="vert"><?php echo $label;?></TH>
<TH><?php render_form_field($fieldtype, 'sample', $attributes, ''); ?></TH>
</TR>
</TABLE>
</FIELDSET>

<P><?php echo _("Add this field?"); ?></P>
<FORM action="." method="post">
<INPUT type="hidden" name="stage" value="3">
<?php
    foreach($_POST as $pk => $pv)
    {
	if ($pk != 'stage' and $pk != 'add_custom_field')
	{
	    $pk = strip_tags($pk); // security feature
	    $pv = strip_tags($pv); // security feature
	    echo ("<INPUT type=\"hidden\" name=\"$pk\" value=\"$pv\">\n");
	}
    }
?>
<INPUT type="submit" name="add_custom_field" value="<?php echo(_("Add"));?>">
</FORM>

<?php
    
} /* custom_add_field_form2() */


function custom_add_field_form3()
{
    global $db;
    
    
    // validate some input

    $errors_found = 0;
    
    if (empty($_POST['label']) or strlen(trim($_POST['label'])) < 3)
    {
	$errors_found++;
	process_user_error(_("Too short:").' '._("Label"));
    }
    
    if ($errors_found)
	return;
    
    // find a unique code
    
    $unique = FALSE;    
    
    $label = $db->escape_string(strip_tags($_POST['label']));
    $codebase = str_replace(' ','_', $label);
    $code = $codebase;
    
    for ($i = 0; !$unique; $i++)
    {    
	// if code is not unique, try finding a unique variation
	echo "code = $code";
	$result = $db->query("SELECT code FROM extended_meta WHERE code = '$code'");
	if (!$result)
	{
	    process_system_error(_("Error querying database.").' code', array('debug' => $db->get_error()));
	    return;
	}	
	if (0 == $db->num_rows($result))
	{
	    $unique = TRUE;
	}
	else 
	{	
	    $code = $code.$i;
	}
	if ($i > 20)
	{
	    process_system_error("Unable to find unique code based on $label.");
	    die();
	}
    };

    echo ("<p>debug: code = $code</p>\n");
    
    print_r($_POST);

    // add to extended_meta 

    switch ($_POST['fieldtype'])
    {
	case 'string':
	    $length = intval($_POST['string_length']);	
	    $sql_meta = 'INSERT INTO extended_meta '.
		'(code, label, size1, fieldtype) '.
		"VALUES ('$code', '$label', $length, 'string')";
	    $sql_ext = "ALTER TABLE extended ADD COLUMN $code varchar($length)";
    	    break;
	case 'textarea':
	    $ta_length = intval($_POST['textarea_length']);	
	    $ta_cols = intval($_POST['textarea_cols']);		    
	    $ta_rows = intval($_POST['textarea_rows']);		    
	    $sql_meta = 'INSERT INTO extended_meta '.
		'(code, label, size1, size2, size3, fieldtype) '.
		"VALUES ('$code', '$label', $ta_length, $ta_cols, $ta_rows, 'textarea')";
	    $sql_ext = "ALTER TABLE extended ADD COLUMN $code varchar($ta_length)";		
	    break;
	case 'date':	    		
	    $sql_meta = 'INSERT INTO extended_meta '.
		'(code, label, fieldtype) '.
		"VALUES ('$code', '$label', 'date')";
	    $sql_ext = "ALTER TABLE extended ADD COLUMN $code DATE";		
	    break;	
	case 'integer':	    		
	    $sql_meta = 'INSERT INTO extended_meta '.
		'(code, label, fieldtype) '.
		"VALUES ('$code', '$label', 'integer')";
	    $sql_ext = "ALTER TABLE extended ADD COLUMN $code INT";		
	    break;	
	    
	    
	default:
	    process_system_error(_("Bad form input:" .' fieldtype'));	    
	    assert(FALSE);    
	    break;
	    
    }
    
    $result = $db->query($sql_meta);
    
    echo ($db->get_error());

    if (!$result)
    {
	process_system_error(_("Error adding data to database."), array('debug' => $db->get_error()) );
	return;	
    }    
    
    $result = $db->query($sql_ext);
    
    echo ("$sql_ext:".$db->get_error());
    
    if (!$result)
    {
	// to do: roll back changes to _meta
	process_system_error(_("Error adding data to database."), array('debug' => $db->get_error()));
	$result = $db->query("DELETE FROM extended_meta WHERE code = '$code' LIMIT 1");
	return;	
    }    
    
    echo ("<P>Your column $label has been added succesfully.</P>\n");

}


function custom_add_field_form()
{

    if (array_key_exists('stage',$_POST) and $_POST['stage'] == 3)
    {
	custom_add_field_form3();
    }
    else
    if (array_key_exists('stage',$_POST) and $_POST['stage'] == 2)
    {
	custom_add_field_form2();
    }
    else
    {
	custom_add_field_form1();
    }

} /* custom_add_field_form() */

?>