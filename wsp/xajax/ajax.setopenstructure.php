<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2017-09-04
 */

session_start();
if (isset($_POST['mid']) && intval($_POST['mid'])>0):
	if (isset($_SESSION['wspvars']['openpath'])):
        $_SESSION['wspvars']['openpath'] = array_unique($_SESSION['wspvars']['openpath']);
    else:
        $_SESSION['wspvars']['openpath'] = array();
    endif;
    if (isset($_POST['op']) && trim($_POST['op'])=='add'):
		$_SESSION['wspvars']['openpath'][] = intval($_POST['mid']);
        $_SESSION['wspvars']['openpath'] = array_unique($_SESSION['wspvars']['openpath']);
	elseif (isset($_POST['op']) && trim($_POST['op'])=='remove'):
        $mk = array_search(intval($_POST['mid']), $_SESSION['wspvars']['openpath']);
        unset($_SESSION['wspvars']['openpath'][$mk]);
    endif;
endif;

// EOF
