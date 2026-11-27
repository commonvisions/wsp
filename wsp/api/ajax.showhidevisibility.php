<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version GIT
 */

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
	session_start();
	$stat = (($_SESSION['wspvars']['showhideInvStruc'] ?? '')=="hide") ? 'show' : 'hide';
	$_SESSION['wspvars']['showhideInvStruc'] = $stat;
	echo $stat;
}
