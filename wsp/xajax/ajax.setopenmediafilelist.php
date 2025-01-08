<?php
/**
 * ...
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2018-09-17
 */
session_start();

if (isset($_REQUEST['fkid']) && trim($_REQUEST['fkid'])!=''):
	if (is_array($_SESSION['openmedia'])):
		if (in_array(trim($_REQUEST['fkid']), $_SESSION['openmedia'])):
			$remove = array_keys($_SESSION['openmedia'], trim($_REQUEST['fkid']));
			$_SESSION['openmedia'][intval($remove[0])] = '';
		else:
			$_SESSION['openmedia'][] = trim($_REQUEST['fkid']);
		endif;
	else:
		$_SESSION['openmedia'] = array(trim($_REQUEST['fkid']));
	endif;
	
	if (isset($_REQUEST['stat']) && trim($_REQUEST['stat'])=='open'):
		$_SESSION['openmedia'][] = trim($_REQUEST['fkid']);
	endif;
	$_SESSION['openmedia'] = array_unique($_SESSION['openmedia']);
endif;
// EOF ?>