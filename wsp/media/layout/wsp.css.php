<?php
/**
 * @description WSPStyle
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 3.2.3
 * @type design
 * @version 6.7
 * @lastchange 2018-09-17
 */

header("Content-Type: text/css");
?>a {
	color: #354E65;
/*	color: #9E4C0B; */
	text-decoration: none;
	}

#menuholder li.level0.active, #menuholder li.level0.active ul.level1, #menuholder li.level0.active ul.level1 li {
	background-color: #354E65;
	}
	
#menuholder li.level0.active li {
	background-color: #354E65;
	}
	
#menuholder li.level0.active a, #menuholder li.level0.active ul.level1 li a {
	color: #fff;
	}

#menuholder li ul li a, #menuholder li.active li a, #menuholder li:hover li a {
	color: #354E65;
	}
	
#menuholder li ul li a:hover, #menuholder li ul li a.selected, #menuholder li.active li a:hover, #menuholder li:hover li a:hover {
	color: #fff;
	}

#menuholder.ddsmoothmenu-v li li a {
	padding: 10px;	
	color: #fff;
	}

/* EOF */