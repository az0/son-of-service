<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Administration of custom data fields.
 *
 * $Id: custom.php,v 1.2 2003/10/06 00:33:32 andrewziem Exp $
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


function render_form_field($type, $name, $attributes, $value)
{
    assert(is_array($attributes));
    assert(is_string($name));

    switch ($type)
    {
	case 'string':
	case 'integer':
	    if (empty($value))
		$v = "";
		else $v=" VALUE=\"$value\" ";
	    if (empty($attributes['length']))
		$length = 4;
		else
		$length = intval($attributes['length']);
	    echo ("<INPUT type=\"text\" name=\"$name\" size=\"$length\"$v>\n");
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

function custom_add_field_form1()
{
?>
<FIELDSET>
<CAPTION>Add custom field</CAPTION>
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
<CAPTION><?php echo _("Integer");?></CAPTION>
<BR><INPUT type="radio" name="fieldtype" value="integer">Integer
</FIELDSET>

<FIELDSET>
<CAPTION><?php echo _("Decimal");?></CAPTION>
<BR><INPUT type="radio" name="fieldtype" value="decimal" disabled>Decimal [not available]
</FIELDSET>

<FIELDSET>
<CAPTION><?php echo _("Boolean");?></CAPTION>
<BR><INPUT type="radio" name="fieldtype" value="boolean">Boolean: yes or no
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
<BR><INPUT type="text" name="label"> Label

<!--<BR><INPUT type="text" name="description">Description-->
</FIELDSET>

<INPUT type="hidden" name="stage" value="2">
<P><INPUT type="submit" name="add_custom_field" value="Next"></P>
</FORM>

</FIELDSET>

<?php

} /* custom_add_field_form1() */


$fieldtypes = array('integer','string','textarea','boolean');
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
	process_user_error(_("Give a longer value for label."));
    }
    
    $attributes = array();
    
    switch ($fieldtype)
    {
	case 'string':
	    $attributes['length'] = intval($_POST['string_length']);
	    if (2 > $attributes['length'] or $attributes['length'] > 254)
		process_user_warning(_("Length is extreme."));
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
<CAPTION>Sample</CAPTION>
<TABLE border="1">
<TR>
<TH class="vert"><?php echo $label;?></TH>
<TH><?php render_form_field($fieldtype, 'sample', $attributes, ''); ?></TH>
</TR>
</TABLE>
</FIELDSET>

<P>Really add this field?</P>
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
	process_user_error(_("Label too short"));
    }
    
    if ($errors_found)
	return;
    
    // find a unique code
    
    $unique = FALSE;
    
    
    $label = $db->escape_string(strip_tags($_POST['label']));
    $codebase = str_replace(' ','', $label);
    $code = $codebase;
    
    for ($i = 0; !$unique; $i++)
    {    
	echo "code = $code";
	$result = $db->query("SELECT code from extended_meta where code = '$code'");
	if (!$result)
	{
	    process_system_error(_("Error while querying database").' code', array('debug',mysql_error()));
	    return;
	}	
	if (0 == $db->num_rows($result))
	    $unique = TRUE;
	    else $code = $code.$i;
	if ($i > 20)
	{
	    process_system_error("Unable to find unique code based on $label.");
	}
    };

    echo ("<p>debug: code = $code</p>\n");

    // add to extended_meta 

    switch ($_POST['fieldtype'])
    {
	case 'string':
	    $length = intval($_POST['string_length']);	
	    $sql_meta = 'INSERT INTO extended_meta '.
		'(code, label, databasecolumntype, databasecolumnsize, fieldtype) '.
		"VALUES ('$code', '$label', 'varchar', $length, 'string')";
	    $sql_ext = "ALTER TABLE extended ADD COLUMN $code varchar($length)";
    	    break;
	default:
	    process_system_error(_("Bad form input:" .' fieldtype'));	    
	    assert(FALSE);    
	    break;
	    
    }
    
    $result = $db->query($sql_meta);
    
    echo (mysql_error());

    if (!$result)
    {
	process_system_error(_("Error while adding custom meta data."), array('debug', mysql_error()) );
	return;	
    }    
    
    $result = $db->query($sql_ext);
    
    echo ("$sql_ext:".mysql_error());
    
    if (!$result)
    {
	// to do: roll back changes to _meta
	process_system_error(_("Error while adding column to table extended."), array('debug', mysql_error()));
	$result = $db->query("DELETE FROM extended_meta where code = '$code'");
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