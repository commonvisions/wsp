<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version 6.8
 */

session_start();

if (!empty($_POST['msgtype'] ?? null)) {
	echo ($_SESSION['wspvars'][$_POST['msgtype']] ?? '');
	$_SESSION['wspvars'][$_POST['msgtype']] = '';
}
