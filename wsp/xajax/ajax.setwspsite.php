<?php
/**
 * setting wsp site session variable
 * @author stefan@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.1
 * @version 7.0
 * @lastchange 2019-01-19
 */
session_start();
if (isset($_REQUEST['site'])):
	$_SESSION['wspvars']['site'] = intval($_REQUEST['site']);
endif;
// EOF ?>
