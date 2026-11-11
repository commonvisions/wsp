<?php

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
/* require classes --------------------------- */
require_once './data/classes/base.inc.php';
require_once './data/classes/dosql.inc.php';
/* require functions ------------------------- */
require ("./data/include/funcs.inc.php");
require ("./data/include/filesystemfuncs.inc.php");
