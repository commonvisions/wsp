<?php
/**
 *
 * @author stefan@covi.de
 * @since 3.1.2
 * @version 7.0
 * @lastchange 2021-09-15
*/

ksort($_SESSION);

?><!doctype html>
<html lang="de" style="height: 100%;">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <meta name="author" content="https://www.covi.de" />
    <meta name="robots" content="nofollow" />
    <title>WSP 7.0</title>
    <!-- get icon fonts -->
    <link rel="stylesheet" href="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/fontface.css" />
    <link rel="stylesheet" href="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/font-awesome.css" />
    <!-- load jquery -->
    <script src="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/jquery/js/jquery-3.3.1.js"></script>
</head>
<body class="tiny" style="width: 100%; height: 100%;">