<?php
/**
 * @author stefan@covi.de
 * @since 6.1
 * @version GIT
 */

session_start();
if (isset($_REQUEST['site'])):
	$_SESSION['wspvars']['site'] = intval($_REQUEST['site']);
endif;
