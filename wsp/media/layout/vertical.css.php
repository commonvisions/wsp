<?php
/**
 * WSP Main Stylesheet
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 3.2.3
 * @version 6.7
 * @lastchange 2018-09-17
 */

header("Content-Type: text/css");

?>body {
	margin-left: 220px;
	}

#topholderback {
	position: fixed;
	top: 0px;
	left: 0px;
	width: 200px;
	height: 100%;
	border-right: 1px solid #999;
	-moz-box-shadow: none;
	-webkit-box-shadow: none;
	box-shadow: none;
	z-index: 4;
	}

#topholder {
	position: fixed;
	top: 0px;
	left: 0px;
	width: 100%;
	height: 0px;
	background: #111; 
	line-height: 40px;
	z-index: 5;
	}

#topspacer {
	height: 0.1px;
	}

#menuholder {
	position: absolute;
	top: 0px;
	left: 0px;
	width: 200px;
	height: auto;
	}
	
#menuholder li {
	width: 200px;
	border-right: none;
	border-top: 1px solid #fff;
	}

#menuholder li:first-child {
	width: 200px;
	border-right: none;
	border-top: none;
	}

#menuholder li ul li {
	width: 200px;
	border: none;
	background: #354E65;
	}
	
#menuholder li.select {}

#menuholder li.select select {
	width: 80%;
	}

#menuholder img {
	display: none;
	}

#contentholder, #contentarea {
	z-index: 1;
	position: relative;
	margin: 10px 0px 20px;
	width: 99%;
	}
	
#infoholder {
	z-index: 1;
	position: relative;
	margin: 10px 0px;
	width: 99%;
	}
	
#wspinfos {
	z-index: 1;
	position: relative;
	margin: 10px 0px;
	width: 98%;	
	}

/* EOF */