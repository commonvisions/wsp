<?php
/**
 * ...
 * @author stefan@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-01-19
 */
session_start();
if (isset($_REQUEST['tabname'])):
	if (isset($_REQUEST['tabstatus']) && $_REQUEST['tabstatus']=='none'):
		$_SESSION['opentabs'][$_REQUEST['tabname']] = 'display: block;';
	else:
		$_SESSION['opentabs'][$_REQUEST['tabname']] = 'display: none;';
	endif;
endif;
// EOF ?>