<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: general.php,v 1.1 2003/11/22 15:52:41 andrewziem Exp $
 *
 */

if (preg_match('/notes.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function volunteer_view_general()
{
    global $db;
    global $volunteer;

    $vid = intval($_REQUEST['vid']);

    //    echo ("<H3>General information</H3>\n");


    $form = new FormMaker();
    $form->open(FALSE, 'post', '.', FS_TABLE);
    $form->setValuesArray($volunteer);
    $form->addField(_("Prefix"), 'text', 'prefix', array('length' => 20), 'prefix');
    $form->addField(_("First name"), 'text', 'first', array('length' => 20), 'first');
    $form->addField(_("Middle name"), 'text', 'middle', array('length' => 20), 'middle');
    $form->addField(_("Last name"), 'text', 'last', array('length' => 40), 'last');
    $form->addField(_("Suffix"), 'text', 'suffix', array('length' => 10), 'suffix');
    $form->addField(_("Organization"), 'text', 'organization', array('length' => 40), 'organization');    
    $form->addField(_("Street"), 'text', 'street', array('length' => 40), 'street');
    $form->addField(_("City"), 'text', 'city', array('length' => 30), 'city');    
    $form->addField(_("State/Province"), 'text', 'state', array('length' => 40), 'state');    
    $form->addField(_("Zip/Postal code"), 'text', 'postal_code', array('length' => 10), 'postal_code');        
    $form->addField(_("Country"), 'text', 'country', array('length' => 30), 'country');            
    
    // fixme: put phone numbers into separate table
    
?>
<tr>
 <th class="vert"><?php echo _("Home phone");?></th>
 <td><input type="Text" name="phone_home" value="<?php echo ($volunteer["phone_home"]); ?>" size="20"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Work phone");?></th>
 <td><input type="Text" name="phone_work" value="<?php echo ($volunteer["phone_work"]); ?>" size="20"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Cell phone");?></th>
 <td><input type="Text" name="phone_cell" value="<?php echo ($volunteer["phone_cell"]); ?>" size="20"></td>
 </tr>
<?php

    $form->addField(_("E-mail"), 'text', 'email_address', array('length' => 40), 'email_address');            


// show custom fields
// to do: SQL_CACHE

    $result_ext = $db->query("SELECT * FROM extended WHERE volunteer_id = $vid");
    
    if ($result_ext)
    {
	$row_ext = $db->fetch_array($result_ext);
    }
    else 
    {
	$row_ext = array();
    }

    $result_meta = $db->query("SELECT * FROM extended_meta");

    if ($result_meta)
    {
	while (FALSE != ($row_meta = $db->fetch_array($result_meta)))
	{

	    $form->addField($row_meta['label'], $row_meta['fieldtype'], 'custom_'.$row_meta['code'], $attributes, $value);
	}
    }
    
    $db->free_result($result_meta);

    $form->addHiddenField('vid', $vid);
    $form->addButton('volunteer_save', _("Save"));
    $form->addButton('volunteer_delete', _("Delete"));

    $form->close();

} /* volunteer_view_general() */


?>
