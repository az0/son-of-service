<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: summary.php,v 1.2 2003/11/10 17:22:30 andrewziem Exp $
 *
 */

require_once(SOS_PATH .'functions/table.php');

if (preg_match('/summary.php/i', $_SERVER['PHP_SELF']))
{
die('Do not access this page directly.');
}


function volunteer_summary()
{
global $volunteer, $db, $vid;

// show contact card

$contact_card = '';
if ($volunteer['first'] or $volunteer['last'])
{
	$contact_card .= $volunteer['prefix'].' '.$volunteer['first'].' '.$volunteer['middle'].' '.$volunteer['last'].' '.$volunteer['suffix'].' (#'.$volunteer['volunteer_id'].")\n";
}
if (!empty($volunteer['organization']))
	$contact_card .= $volunteer['organization']."\n";
$address = $volunteer['street']."\n".$volunteer['city'].', '.$volunteer['state'].' '.$volunteer['zip']."\n";
if (strlen(trim($address))>2)
	$contact_card .= $address;
if (!empty($volunteer['phone_home']))
	$contact_card .= "Home: ".$volunteer['phone_home']."\n";
if (!empty($volunteer['phone_work']))
	$contact_card .= "Work: ".$volunteer['phone_work']."\n";
if (!empty($volunteer['phone_cell']))
	$contact_card .= "Cell: ".$volunteer['phone_cell']."\n";
$tab = new DataTableDisplay();
$tab->begin();
$tab->addRow(array(nl2br($contact_card)));
$tab->end();
unset($tab);

// show notes

// code to consolidate notes and work history:
// select concat(year(date),'-',month(date),'-',dayofmonth(date)) as date, work_id, 'workhistory', concat(hours,' ',if(isnull(memo),'',memo)) from work union select year(dt), month(dt), dayofmonth(dt), note_id, 'notes', message from notes;

include_once('notes.php');

volunteer_view_notes(TRUE);

// show work history

include_once('workhistory.php');

volunteer_view_work_history(TRUE);

// show skills

// to do

// show availability

// to do

// show custom fields

// to do

// show relationships

include_once('relationships.php');

relationships_view(TRUE);


}

?>
