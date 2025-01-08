<?php
/**
 * WSP Main Stylesheet
 * @author s.haendler@covi.de
 * @copyright (c) 2024, Common Visions Media.Agentur (COVI)
 * @since 3.2.3
 * @version 6.8.6
 * @lastchange 2024-06-09
 */

/*
 * 2024-06-09: fixed Safari bug
 *  
 */

header("Content-Type: text/css");

?>
* { margin: 0px; padding: 0px; }

body { font-family: 'Source Sans Pro', 'Open Sans', sans-serif; font-weight: 400; font-size: 13px; overflow: scroll; overflow-x: hidden; }
input { font-family: 'Source Sans Pro', 'Open Sans', sans-serif; font-weight: 400; }
select { font-family: 'Source Sans Pro', 'Open Sans', sans-serif; font-weight: 400; }
textarea { font-family: 'Source Sans Pro', 'Open Sans', sans-serif; font-weight: 400; }
textarea.source { font-family: 'Source Code Pro', monospace; font-weight: 400; }

a { text-decoration: none; }

hr.clearbreak { width: 100%; height: 0.1px; clear: both; float: none; background: none; border: none; outline: none; margin: 0px; }

/* font-size for other screen sizes */

@media screen and (max-device-width: 1290px) { body { font-size: 12px; } }

/* fieldsets */
	
fieldset { padding: 0.5%; margin: 5px 0px; border: 1px solid #D3DDE3; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; }
fieldset.halffield.left { position: relative; width: 48%; float: left; margin: 0px; margin-right: 2%; margin-bottom: 5px; }
fieldset.halffield.right { position: relative; margin: 0px; margin-bottom: 5px; }
fieldset.options { background: #D3DDE3; text-align: center; }
fieldset.comment { background: #D3DDE3; text-align: left; }

fieldset.two { position: relative; width: 23%; width: calc( 23.5% - 2px ); }
fieldset.two.left { float: left; }
fieldset.two.right { float: right; }

fieldset.two.first { position: relative; width: 23%; width: calc( 23.5% - 2px ); float: left; margin-right: 0.65%; }
fieldset.two.second { position: relative; width: 23%; width: calc( 23.5% - 2px ); float: left; margin-right: 0.65%; }
fieldset.two.third { position: relative; width: 23%; width: calc( 23.5% - 2px ); float: left; margin-right: 0.65%; }
fieldset.two.fourth { position: relative; width: 23%; width: calc( 23.5% - 2px ); float: left; }

fieldset.four { position: relative; width: 48%; width: calc( 48.5% - 2px ); }
fieldset.four.left { float: left; }
fieldset.four.right { float: right; }
fieldset.six { position: relative; width: 73%; width: calc( 73.5% - 2px ); }
fieldset.six.left { float: left; }
fieldset.six.right { float: right; }

fieldset.full { clear: both; float: none; }

fieldset#resultmsg { background: #3E8F00; color: #fff; }
fieldset#noticemsg { background: #EC842D; color: #000; }
fieldset#errormsg { background: #770000; color: #fff; }
fieldset#dhtmltooltip { position: absolute; width: 250px; border: none; padding: 5px; visibility: hidden; z-index: 100; opacity: 1; background: rgba(255,255,255,0.9); -moz-box-shadow: 0px 0px 10px #000; -webkit-box-shadow: 0px 0px 10px #000; box-shadow: 0px 0px 10px #000; }

/* fieldset "base" contents */
	
fieldset legend {
	padding: 0px 4px;
	font-weight: 400;
	font-size: 0.9em;
	}

fieldset h1 {
	font-size: 1.2em;
	font-weight: 700;
	}
	
fieldset p {
	margin: 5px 0px 3px 0px;
	}
	
/* fieldset buttons */

a.greenfield {
	color: #354E65;
	text-decoration: none;
	border: 1px solid #354E65;
	background: #fff;
	padding: 3px;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
	cursor: pointer;
	}

a.greenfield:hover {
	color: #fff;
	border: 1px solid #354E65;
	background: #354E65;
	}

a.orangefield {
	color: #5E7B95;
	text-decoration: none;
	border: 1px solid #fff;
	background: #fff;
	padding: 3px;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
	cursor: pointer;
	}

a.orangefield:hover {
	color: #fff;
	border: 1px solid #fff;
	background: #D3DDE3;
	}	

a.redfield {
	color: #952320;
	text-decoration: none;
	border: 1px solid #952320;
	background: #fff;
	padding: 3px;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
	cursor: pointer;
	}

a.redfield:hover {
	color: #fff;
	border: 1px solid #952320;
	background: #952320;
	}

/* bubble boxes */

span.bubblemessageholder {
	background: none;
	}

span.bubblemessage {
	display: inline-block;
	background: #356397;
	padding: 0px 2px; 
	min-width: 15px;
	height: 14px;
	font-size: 0.8em;
	font-weight: 700;
	color: #fff;
	position: relative;
	line-height: 13px;
	top: -1px;
	-webkit-border-radius: 2px;
	-moz-border-radius: 2px;
	border-radius: 2px;
	cursor: pointer;
	text-align: center;
	}

span.bubblemessage.orange {
	background: #EC842D;
	}
	
span.bubblemessage.red {
	background: #770000;
	}
	
span.bubblemessage.green {
	background: #3E8F00;
	}

span.bubblemessage.disabled {
	opacity: 0.3;
	cursor: text;
	}

span.bubblemessage.hidden {
	opacity: 0.0;
	visibility: hidden;
	}

span.icon {
	display: inline-block;
	background: none;
	padding: 2px 2px; 
	min-width: 15px;
	height: 13px;
	font-size: 1.15em;
	font-family: 'FontAwesome', sans-serif;
	color: #000;
	position: relative;
	line-height: 13px;
	top: 0px;
	-webkit-border-radius: 2px;
	-moz-border-radius: 2px;
	border-radius: 2px;
	border: 1px solid #bbb;
	cursor: pointer;
	text-align: center;
	}

/* fieldset legend special */

legend span.bubblemessage {
	font-size: 1em;
	background: none;
	color: #000;
	width: 0.5em;
	min-width: 0.5em;
	border-radius: 0px;
	}

/* content divs and fieldsets by id */

#topholderback	{ position: fixed; height: 17px; width: 100%; background: #B0BAC2; z-index: 15; }
#topholder		{ position: fixed; height: 34px; width: 100%; background: #C5D1D9; z-index: 10; border-bottom: 1px solid #fff; -moz-box-shadow: 0px 0px 10px #000; -webkit-box-shadow: 0px 0px 10px #000; box-shadow: 0px 0px 10px #000; }
#menuholder		{ position: fixed; height: 34px; width: 100%; z-index: 30; }

#logincontent {
	position: relative;
	margin: 0px auto;
	width: 95%;
	max-width: 600px;
	}

#topspacer {
	z-index: 3;
	position: relative;
	margin: 0px auto 10px auto;
	width: 100%;
	height: 3em;
	}

#contentholder, #infoholder {
	position: relative;
	margin: 0px auto;
	width: 95%;
	max-width: 1340px;
	}
	
#showdevmsg {
	position: relative;
	margin: 0px auto;
	width: 95%;
	max-width: 1330px;
	display: none;
	}

#msgbar {
	position: fixed;
	top: 10px;
	right: 10px;
	width: 250px;
	z-index: 50;
	}

#footer {
	clear: both;
	width: 98%;
	margin: 20px 0% 20px 1%;
	border-top: 1px solid black;
	}

#footer p {
	padding: 15px 0px;
	font-size: 0.85em;
	text-align: center;
	}
	
#footer p.rightpos {
	float: right;
	text-align: right;
	}

#footer p.leftpos {
	float: left;
	text-align: left;
	}

/* icon description area */

ul.icondesc { list-style-type: none; }
li.icondescitem { float: left; margin-right: 5px; border-radius: 4px; background: rgb(227,235,242); padding: 2px 4px; margin-bottom: 5px; }
li.icondescitem.red { background: #F2B3B2; }

/* ul design */

ul.contenttable {
	display: table;
	width: 100%;
	list-style-type: none;
	}

/* table design */
/* generic definition */
table.tablelist { width: 100%; border: none; border-collapse: collapse; display: table; }
table.tablelist td.tablecell { display: table-cell; padding: 5px 0.39%; min-height: 1.5em; border-bottom: 1px solid rgba(223,229,233,1); }

.tablecell.alignright { text-align: right; }

/* light cells */
table.tablelist td.tablecell.publishrequired { background-color: rgba(247,221,200,1); border-bottom: 1px solid rgba(236,132,45,1); }
table.tablelist td.tablecell.publish { background-color: rgba(215,228,218,1); border-bottom: 1px solid rgba(167,204,170,1); }
table.tablelist td.tablecell.inqueue { background-color: rgba(254,247,166,1); border-bottom: 1px solid rgba(254,222,108,1); }

/* dark cells */
table.tablelist tr:nth-child(odd) td.tablecell { background-color: rgba(223,229,233,1); border-bottom: 1px solid rgba(255,255,255,1); }
table.tablelist tr:nth-child(odd) td.tablecell.publishrequired { background-color: rgba(236,132,45,1); border-bottom: 1px solid rgba(247,221,200,1); }
table.tablelist tr:nth-child(odd) td.tablecell.publish { background-color: rgba(167,204,170,1); border-bottom: 1px solid rgba(167,204,170,1); }
table.tablelist tr:nth-child(odd) td.tablecell.inqueue { background-color: rgba(254,222,108,1); border-bottom: 1px solid rgba(254,247,166,1); }

/* other cells */
table.tablelist tr td.tablecell.head { background-color: rgba(53,78,101,1); color: rgba(255,255,255,1); }

table.tablelist td.tablecell.one { width: 11.7%; }
table.tablelist td.tablecell.two { width: 24.2%; }
table.tablelist td.tablecell.three { width: 36.7%; }
table.tablelist td.tablecell.four { width: 49.2%; }
table.tablelist td.tablecell.five { width: 61.7%; }
table.tablelist td.tablecell.six { width: 74.2%; }
table.tablelist td.tablecell.seven { width: 86.7%; }
table.tablelist td.tablecell.eight { width: 99.2%; }

table.tablelist td select { 
	border: 1px solid #ccc;
	padding: 3px; 
	border-radius: 5px;
	font-size: 0.9em;
	}

table.tablelist td select.full { width: 97%; }
table.tablelist td input.full { width: 97%; }
table.tablelist td textarea.full { width: 97%; }

/* autocombo table.tablelist design */

table.tablelist td span.autocombo { width: 98%; display: block; }
table.tablelist td span.autocombo input.custom-combobox-input { width: 90%; border-top-right-radius: 0px; border-bottom-right-radius: 0px; border-right: none; float: left; }
table.tablelist td span.autocombo a.custom-combobox-toggle { width: 7%;
  background: #ccc;
  border: 1px solid #ccc;
  border-left: none;
  padding: 4px 0px;
  height: 1em;
  }

table.tablelist td img.autocombo-previewimg { position: relative; margin: 0px auto; max-width: 99%; max-height: 50px; width: auto; height: auto; }

div.ui-tooltip { width: 50%; left: 50%; margin-left: -25%; }

/* NEW STYLE MARCH 2015 */
ul.tablelist li.tablecell { display: table-cell; float: none; background: none; margin-bottom: 0px; padding: 5px 0.39%; min-height: 1px; }
ul.tablelist li.tablecell.info {}
ul.tablelist li.tablecell.desc {}
ul.tablelist li.tablecell.data {}



/* textcomplete ????????? */

ul.dropdown-menu { list-style-type: none; background-color: rgba(223,229,233,1); border-radius: 4px; }
ul.dropdown-menu li.textcomplete-item { padding: 2px 4px; cursor: pointer; }
ul.dropdown-menu li.textcomplete-item.active { color: #fff; background: #354E65; }
ul.dropdown-menu li.textcomplete-item.active a { color: inherit; }



/* delete next attr after building NEW design everywhere */

ul.tablelist { display: table; width: 100%; list-style-type: none; }

ul.tablelist li.tablecell { 
	display: table-cell;
	float: left;
	background: #DFE5E9;
	border-bottom: 1px solid rgba(255,255,255,1);
	padding: 5px 0.39%;
	min-height: 1.5em;
	}

ul.tablelist li.tablecell.publishrequired { background: #EC842D; }
ul.tablelist li.tablecell.publish { background: #A7CCAA; }
ul.tablelist li.tablecell.inqueue { background: rgb(254,222,108); }
ul.tablelist li.tablecell.hiddencontent { background: rgb(210,136,124); }
ul.tablelist li.tablecell.switchclass { background: none; }
ul.tablelist li.tablecell.switchclass.publishrequired { background: rgba(247,221,200,1); }
ul.tablelist li.tablecell.switchclass.publish { background: #D7E4DA; }
ul.tablelist li.tablecell.switchclass.inqueue { background: rgb(245,236,200); }
ul.tablelist li.tablecell.switchclass.hiddencontent { background: rgb(210,136,124); }
ul.tablelist li.tablecell.head, ul.tablelist li.tablecell.switchclass.head { background: #354E65; color: #fff; }
ul.tablelist.structure li.tablecell.switchclass { background: #DFE5E9; }
ul.tablelist.structure ul.tablelist.structure li.tablecell, ul.tablelist.structure ul.tablelist.structure li.tablecell.switchclass { background: #fff; }

ul.tablelist.structure.level-1 li.tablecell { background: #DFE5E9; border-bottom: 1px solid rgba(255,255,255,1); }
ul.tablelist.structure.level-2 li.tablecell { background: #ffffff; border-bottom: 1px solid #DFE5E9; }
ul.tablelist.structure.level-3 li.tablecell { background: #DFE5E9; border-bottom: 1px solid rgba(255,255,255,1); }
ul.tablelist.structure.level-4 li.tablecell { background: #ffffff; border-bottom: 1px solid #DFE5E9; }
ul.tablelist.structure.level-5 li.tablecell { background: #DFE5E9; border-bottom: 1px solid rgba(255,255,255,1); }
ul.tablelist.structure.level-6 li.tablecell { background: #ffffff; border-bottom: 1px solid #DFE5E9; }

ul.tablelist.hiddenstructure.level-1 li.tablecell { background: #F2B3B2; }
ul.tablelist.hiddenstructure.level-2 li.tablecell { background: #F9E0DF; }
ul.tablelist.hiddenstructure.level-3 li.tablecell { background: #F2B3B2; }
ul.tablelist.hiddenstructure.level-4 li.tablecell { background: #F9E0DF; }
ul.tablelist.hiddenstructure.level-5 li.tablecell { background: #F2B3B2; }
ul.tablelist.hiddenstructure.level-6 li.tablecell { background: #F9E0DF; }

ul.publishinglist li { cursor: pointer;	}

ul.tablelist li.tablecell.one { width: 11.7%; }
ul.tablelist li.tablecell.two { width: 24.2%; }
ul.tablelist li.tablecell.three { width: 36.7%; }
ul.tablelist li.tablecell.four { width: 49.2%; }
ul.tablelist li.tablecell.five { width: 61.7%; }
ul.tablelist li.tablecell.six { width: 74.2%; }
ul.tablelist li.tablecell.seven { width: 86.7%; }
ul.tablelist li.tablecell.eight { width: 99.2%; }

li.tablecell select, table.contenttable td select { 
	border: 1px solid #ccc;
	padding: 3px; 
	border-radius: 5px;
	font-size: 0.9em;
	}

li.tablecell input, table.contenttable td input, table.tablelist td input {
	border: 1px solid #ccc;
	padding: 3px;
	border-radius: 5px;
	font-size: 0.9em;
	}

li.tablecell textarea, table.contenttable td textarea, table.tablelist td textarea { 
	border: 1px solid #ccc;
	padding: 3px; 
	border-radius: 5px;
	font-size: 0.9em;
	}
	
li.tablecell select.full, table.contenttable td select.full, table.tablelist td select.full { width: 97%; }
li.tablecell input.full, table.contenttable td input.full { width: 98%; }
li.tablecell textarea.full, table.contenttable td textarea.full { width: 97%; }

li.tablecell span.custom-combobox.full { width: 97%; display: inline-block; }
li.tablecell .custom-combobox-toggle { background: #ccc; border: 1px solid #ccc; border-radius: 5px; border-top-left-radius: 0px; border-bottom-left-radius: 0px; border-left: none; height: calc(1em + 6px); }
li.tablecell .custom-combobox-input { border-top-right-radius: 0px; border-bottom-right-radius: 0px; border-right: none; padding: 3px; font-size: 0.9em; }



li.tablecell.one select, li.tablecell.one input, li.tablecell.one textarea { width: 97%; max-width: 160px; }
li.tablecell.two select, li.tablecell.two input, li.tablecell.two textarea { width: 97%; max-width: 320px; }
li.tablecell.three select, li.tablecell.three input, li.tablecell.three textarea { width: 97%; max-width: 480px; }
li.tablecell.four select, li.tablecell.four input, li.tablecell.four textarea { width: 97%; max-width: 640px; }
li.tablecell.five select, li.tablecell.five input, li.tablecell.five textarea { width: 97%; max-width: 800px; }
li.tablecell.six select, li.tablecell.six input, li.tablecell.six textarea { max-width: 960px; }
li.tablecell.seven select, li.tablecell.seven input, li.tablecell.seven textarea { max-width: 1120px; }
li.tablecell.eight select, li.tablecell.eight input, li.tablecell.eight textarea { max-width: 1280px; }

li.tablecell.four input.custom-combobox-input { width: calc(97% - 2.4em); float: left; }



li.tablecell.four select.four { width: 97%; }
	


ul.tablelist li.tablecell ul.innercell { list-style-type: none; float: left; }

ul.tablelist li.tablecell ul.innercell.block {}

ul.tablelist li.tablecell ul.innercell.block li { float: left; margin-right: 5px; }

ul.tablelist li.tablecell textarea.small, table.tablelist textarea.small, table.contenttable textarea.small { height: 3em; }
ul.tablelist li.tablecell textarea.medium, table.tablelist textarea.medium, table.contenttable textarea.medium { height: 7em; }
ul.tablelist li.tablecell textarea.large, table.tablelist textarea.large, table.contenttable textarea.large  { height: 13em; }
ul.tablelist li.tablecell textarea.noresize, table.tablelist textarea.noresize, table.contenttable textarea.noresize { resize: none; }

ul.tablelist li.tablecell input[type="checkbox"] { width: 2em; }

ul.tablelist li.tablecell.two select.two { width: 99%; max-width: 300px; }
ul.tablelist li.tablecell.four select.two { width: 49%; max-width: 300px; }
ul.tablelist li.tablecell.six select.two { width: 33%; max-width: 300px; }

ul.droplist { list-style-type: none; width: 100%; }

ul.emptytablecell { height: 27px; width: 100%; display: block; background: rgba(247,221,200,1); border: 1px dashed #ccc;  }

ul.publishinglist span.levelclass, ul.sortable span.levelclass {
	height: 1em;
	display: block;
	float: left;
	}

ul.sortable ul.sortable span.levelclass { width: 2em; }
ul.sortable ul.sortable ul.sortable span.levelclass { width: 4em; }
ul.sortable ul.sortable ul.sortable ul.sortable span.levelclass { width: 6em; }
ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable span.levelclass { width: 8em; }
ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable span.levelclass { width: 10em; }
ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable span.levelclass { width: 12em; }
ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable span.levelclass { width: 14em; }
ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable span.levelclass { width: 16em; }
ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable ul.sortable span.levelclass { width: 18em; }

ul.publishinglist ul.publishinglist span.levelclass { width: 2em; }
ul.publishinglist ul.publishinglist ul.publishinglist span.levelclass { width: 4em; }
ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist span.levelclass { width: 6em; }
ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist span.levelclass { width: 8em; }
ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist span.levelclass { width: 10em; }
ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist span.levelclass { width: 12em; }
ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist span.levelclass { width: 14em; }
ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist span.levelclass { width: 16em; }
ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist ul.publishinglist span.levelclass { width: 18em; }

li.structurelistspacer {
	width: 100%;
	height: 3px;
	margin-bottom: 1px;
	background: #E46F22;
	}

table.contenttable tr.tablehead {
	background: #DFE5E9;
	}

ul.contenttable li.contentrow {
	clear: both;
	display: table-row;
	}

ul.contenttable li.contentrow:nth-child(odd) {
	background: #DFE5E9;
	}

ul.contenttable li.tablehead {
	background: #DFE5E9;
	}

ul.contentrow {
	clear: both;
	width: 100%;
	list-style-type: none;
	display: table;
	}

ul.contentrow li.contentcell {
	float: left;
	padding: 0.25%;
	display: table-cell;
	vertical-align: middle;
	}

li.contentcell.two { width: 24.5%; }
li.contentcell.six { width: 74.5%; }

li.contentcell textarea { resize: none; }
li.contentcell textarea.six.full { width: 100%; }

table.contenttable tr:nth-child(odd) {
	background: #DFE5E9;
	}
	
table.contenttable td { padding: 3px; }

table.contenttable td.one { width: 12.5%; }
table.contenttable td.two { width: 25%; }
table.contenttable td.three { width: 25%; }
table.contenttable td.four { width: 50%; }
table.contenttable td.five { width: 50%; }
table.contenttable td.six { width: 75%; }

table.contenttable input {
	border: none;
	outline: none;
	border: 1px solid lightgrey;
	border-radius: 3px;
	padding: 2px;
	font-size: 1em;
	}

table.contenttable select {
	border: none;
	outline: none;
	border: 1px solid #ccc;
	border-radius: 0px;
	padding: 2px;
	font-size: 1em;
	}

table.contenttable input.one.full {
	width: 97.5%;
	}
	
table.contenttable select.one.full {
	width: 99%;
	}

table.contenttable input[type="checkbox"] {
	width: 1.5em;
	}

/* menu */

#imenu { display: none; }

#menuholder.horizontal {}

#menuholder ul { list-style-type: none; }

#menuholder li.select {
	width: 180px;
	line-height: 34px;
	}
	
#menuholder li.select select {
	margin: 7px 10px;
	height: 1.8em;
	line-height: 1.6em;
	font-size: 1.0em;
	width: 160px;
	}

#menuholder.horizontal li.level0 {
	float: left;
	position: relative;
	line-height: 34px;
	display: inline-flex;
}

#menuholder.vertical li.level0 {
	clear: both;
	position: relative;
	line-height: 34px;
	}

#menuholder.horizontal li.level0 a { padding: 0px 7px; }

#menuholder.vertical li.level0 a { padding: 0px 10px; }

#menuholder li.level0:hover { cursor: pointer; }

#menuholder li.level0 { }

#menuholder.horizontal li.level0 ul.level1 {
	position: absolute;
	background: #C5D1D9;
	display: none;
	top: 100%;
	left: 0;
}
	
#menuholder.vertical li.level0 ul.level1 {
	position: absolute;
	top: -1px;
	left: 200px;
	background: #C5D1D9;
	display: none;
	}
	
#menuholder ul.level1 li {
	white-space: nowrap;	
	}
	
#menuholder li.level0 ul li {
	border-top: 1px solid #fff;
	background: #C5D1D9;
	position: relative;
	}
	
#menuholder li.level0 ul li ul {
	margin-top: -1px;
	border-left: 1px solid #fff;
	position: absolute;
	top: 0px;
	left: 100%;
	}

#menuholder li.level0 ul li ul li {
	white-space: nowrap;
	}



/* lists, drag & Drop */

ul.checklist {
	list-style-type: none;
	width: 99%;
	}

li.ui-state-highlight {
	background: rgba(236,125,35,0.1);
	border: 1px dotted grey;
	border-radius: 4px;
	min-height: 1.5em;
	}

/* something else */

p.feedreader_date { font-size: 0.8em; }
p.link { color: orange; }


/* filemanagement */

ul.folderstructure { list-style-type: none; }
ul.folderstructure.level2 { padding-top: 8px; margin-bottom: -5px; }
ul.folderstructure.level3 { padding-top: 8px; margin-bottom: -5px; }
ul.folderstructure.level4 { padding-top: 8px; margin-bottom: -5px; }

li.filesystemfolder { position: relative; padding: 5px 0px; min-height: 1.5em; }
li.filesystemfolder .filesystemfoldername { cursor: pointer; float: left; min-width: 90%; }
li.filesystemfolder .filesystemfolderinfo { position: absolute; right: 0.5em; }

li.filesystemfolder.level1 { background: #B0BAC2; border-bottom: 1px solid #ffffff; color: #000; }
li.filesystemfolder.level1 .filesystemfoldername { margin-left: 0.5em; }
li.filesystemfolder.level2 { background: #ffffff; border-bottom: 1px solid #B0BAC2; color: #000; }
li.filesystemfolder.level2 .filesystemfoldername { margin-left: 1em; }
li.filesystemfolder.level3 { background: #B0BAC2; border-bottom: 1px solid #ffffff; color: #000; }
li.filesystemfolder.level3 .filesystemfoldername { margin-left: 1.5em; }
li.filesystemfolder.level4 { background: #ffffff; border-bottom: 1px solid #B0BAC2; color: #000; }
li.filesystemfolder.level4 .filesystemfoldername { margin-left: 2em; }
li.filesystemfolder.level5 { background: #B0BAC2; border-bottom: 1px solid #ffffff; color: #000; }
li.filesystemfolder.level5 .filesystemfoldername { margin-left: 2.5em; }

li.filesystemfolder.active { background: #356397; color: #ffffff; border-bottom: 1px solid #ffffff; }

li.filesystemfolder:last-child { border-bottom: none; }

ul.filestructure { list-style-type: none; clear: both; }

li.filestructureheader { padding: 5px 0.5em; min-height: 1.5em; background: #356397; color: #ffffff; border-bottom: 1px solid #ffffff; }
li.fileactionholder { padding: 5px 0.5em; min-height: 1.5em; background: #B0BAC2; border-bottom: 1px solid #ffffff; }
li.filelistholder { padding: 0px; }

li.filestructureheader .filesystemfoldername { display: inline-block; width: 33.3%; }
li.filestructureheader .countfiles { display: inline-block; width: 33.3%; }
li.filestructureheader .actionheader { display: inline-block; width: 33.3%; text-align: right; }

ul.outputfilelist { list-style-type: none; clear: both; }

li.outputfiledata.list { clear: both; padding: 5px 0.5em; min-height: 1.5em; border-bottom: 1px solid #356397; }
li.outputfiledata.list .filepreview.list { display: none; }
li.outputfiledata.list .filename.list { display: block; width: 49.66%; float: left; overflow: hidden; }
li.outputfiledata.list .timeinfo.list { display: block; margin-right: 1em; float: left; }
li.outputfiledata.list .sizeinfo.list { display: block; margin-right: 1em; float: left; }
li.outputfiledata.list .datainfo.list { display: block; margin-right: 1em; float: left; }
li.outputfiledata.list .fileaction.list { display: inline-block; float: right; }
li.outputfiledata.list .endoutputfiledata.list { clear: both; height: 0.1px; width: 100%; background: none;}

li.outputfiledata.box { float: left; padding: 5px 0.5em; width: calc(16.66% - 1em); }
li.outputfiledata.box .filepreview.box { display: inline-block; border: 1px solid #356397; margin: 2px 2px 2px 0px; width: 98%; height: 150px; overflow: hidden; vertical-align: middle; }
li.outputfiledata.box .filename.box { display: none; }
li.outputfiledata.box .timeinfo.box { display: none; }
li.outputfiledata.box .sizeinfo.box { display: none; }
li.outputfiledata.box .datainfo.box { display: none; }



li.folder, li.file, li.upload { position: relative; width: 11.9%; border-radius: 5px; list-style-type: none; margin: 0.2%; float: left; height: 15.4em; border: 1px solid #B0BDC8; }
ul.folderdata { list-style-type: none; } 
li.folder { border: 1px solid #DCE1E6; }
li.folder.empty {}
li.folder.empty li.foldergrabber {}
li.folder.empty li.foldername {}
li.upload { width: 0%; display: none; background: #356397; }
li.upload.shown { display: block; width: 11.9%; }
li.file.hidden { display: none; }
li.folder li, li.file li { list-style-type: none; height: 1.3em; overflow: hidden; padding: 3px; }
li.folder:hover, li.folder.list:hover { background: #B0BDC8; }
li.file:hover { background: #DCE1E6; }
li.foldergrabber { list-style-type: none; background: #DCE1E6; }
li.filegrabber { list-style-type: none; background: #B0BDC8; }
li.folder li.foldericon, li.file li.fileicon { height: 6em; list-style-type: none; text-align: center; }
li.file li.fileicon img { margin-top: 0.5em; max-height: 5em; max-width: 100%; height: auto; width: auto; cursor: pointer; }
li.foldername { position: relative; list-style-type: none; height: 1.3em; overflow: hidden; padding: 3px; border-top: 1px solid #DCE1E6; }
li.filename { position: relative; list-style-type: none; height: 1.3em; overflow: hidden; padding: 3px; border-top: 1px solid #B0BDC8; }
li.foldersize, li.filesize { list-style-type: none; height: 1.3em; overflow: hidden; padding: 3px; }
li.filedate { list-style-type: none; }
li.folderaction, li.fileaction { list-style-type: none; overflow: hidden; padding: 3px; }
ul.uploaddata { position: relative; width: 100%; height: 100%; overflow: hidden; list-style-type: none; color: #ffffff; }
ul.uploaddata li {}
ul.createsubdir.list { list-style-type: none; }
ul.createsubdir.list li { position: relative; border: 1px solid #B0BDC8; border-radius: 5px; list-style-type: none; margin: 0.2%; width: 99%; float: none; clear: both; height: auto; }
ul.createsubdir.list li input { margin: 3px; border: 1px solid #ccc; padding: 3px; border-radius: 5px; font-size: 0.9em; width: 50%; max-width: 320px; }
div.qq-uploader { height: 100%; }
div.qq-upload-failed-text { display: none; }
div.qq-upload-button { padding: 3px; }
div.qq-upload-drop-area { position: absolute; background: #EC842D; z-index: 10; padding: 3px; height: 100%; width: 100%; }
li.qq-upload-fail { background: #770000; color: #fff; }
li.qq-upload-success { background: green; color: #fff; }
ul.uploaditems li div.filegrabber { width: 25.7%; display: none; }
ul.uploaditems li div.qq-upload-file { width: 25.5%; float: left; }
ul.uploaditems li div.qq-upload-size { width: 25.5%; float: left; clear: none; }
ul.uploaditems li div.qq-upload-failed-text { width: 25.5%; float: left; clear: right; }

/* list display options */

ul.uploaditems.list { list-style-type: none; }
ul.uploaditems.list li {  border: 1px solid #D3DDE3;  border-radius: 5px; padding: 3px; margin: 0 0 1px 2px; width: 98.5%; height: 1.3em; }
li.folder.list, li.file.list, li.upload.list { width: 99%; float: none; clear: both; height: auto; }
li.folder.list { background: #D3DDE3; }
ul.folder.false li.folder.list { background: #D8B89E; }
li.upload.list { height: 5em; }
li.foldergrabber.list, li.foldericon.list { display: none; }
li.foldername.list { float: left; border: none; width: 50%; }
li.foldersize.list { float: left; border: none; width: 0.1%; }
li.folderaction.list { float: right; }
li.filegrabber.list, li.fileicon.list { display: none; }
li.filename.list, li.filesize.list, li.filedate.list { float: left; border: none; width: 25%; }
li.fileaction.list { float: right; }

/* box display options */

li.folder.box, li.file.box, li.upload.box { width: 11.9%; margin: 0.2%; float: left; height: 15.4em; }

/* tinybox display options */

li.folder.tinybox, li.file.tinybox, li.upload.tinybox { height: 9em; }
li.foldericon.tinybox, li.fileicon.tinybox { display: none; }
ul.uploaditems.tinybox li { position: relative; border: 1px solid #B0BDC8; width: 11.9%; border-radius: 5px; list-style-type: none; margin: 0.2%; float: left; height: 9em; }
div.qq-upload-file { position: relative; display: block; width: 100%; line-height: 1.3em; clear: both; } 
div.qq-upload-size { position: relative; display: block; width: 100%; line-height: 1.3em; clear: both; } 
div.qq-upload-failed-text { position: relative; display: block; width: 100%; line-height: 1.3em; clear: both; }

/* end filemanagement */

@media screen and (max-width: 900px) { 

#menuholder li.select { width: 110px; }
#menuholder li.select select { width: 90px; }
#menuholder.horizontal li.level0 a { letter-spacing: -0.05em; }
	
	}


@media screen and (max-width: 786px) {
	
body {
	font-size: 0.7em;
	}

fieldset.options {
	font-size: 0.9em;
	}

fieldset.halffield.left {
	position: relative;
	width: 98%;
	float: none;
	clear: both;
	margin: 0px;
	}

#topspacer {
	height: 4em;
	}

#topholderback, #topholder {
	display: none;
	}

#footer {
	margin: 20px auto;
	width: 96%;
	}

#footer p {
	padding: 5px 0px;
	width: 100%;
	text-align: center;
	}
	
#footer p.rightpos {
	padding: 15px 0px 5px 0px;
	text-align: center;
	}

#footer p.leftpos {
	padding: 5px 0px;
	text-align: center;
	}
	
/* menu */

#imenu {
	display: block;
	padding: 10px 2.5%;
	position: fixed;
	top: 0px;
	left: 0px;
	width: 95%;
	background: #fff;
	-moz-box-shadow: 3px 0px 10px #000;
	-webkit-box-shadow: 3px 0px 10px #000;
	box-shadow: 3px 0px 10px #000;
	z-index: 2000;
	}

#imenu select {
	width: 100%;
	font-size: 1.3em;
	}
	
#menuholder ul {
	display: none;
	}
	
	}
	
@media screen and (max-width: 480px) {
	body {
		font-size: 0.7em;
		}
	
	fieldset.halffield.left {
		width: 95.5%;
		}
	
	#imenu {
		padding: 2.5% 2.5%;
		}
	
	#imenu select {
		font-size: 1.1em;
		}
	
	#topspacer {
		height: 3.5em;
		}
	
	#footer .cleardate {
		display: block;
		width: 200px;
		}
	
	#showdevmsg {
		width: 92%;
		opacity: 0.7;
		}

	ul.contenttable li.contentrow:nth-child(odd) { background: none; }
	ul.contenttable li.contentrow:nth-child(even) ul { background: #DFE5E9; }
	ul.contenttable li.tablehead { display: none; }
	ul.contenttable li.contentrow {  line-height: 1.8em; }
/*	ul.contentrow li:nth-child(odd) { background: #DFE5E9; } */
	ul.contentrow li.contentcell.two { width: 99%; }
	ul.contentrow li.contentcell.six { width: 99%; }
	ul.contentrow li.contentcell { padding: 3px 0.5%; }
	
	li.contentcell select, li.contentcell select.one.full, li.contentcell select.two.full, li.contentcell select.three.full, li.contentcell select.four.full, li.contentcell select.five.full, li.contentcell select.six.full, li.contentcell select.seven.full, li.contentcell select.eight.full { width: 100%; }
	li.contentcell textarea, li.contentcell textarea.one.full, li.contentcell textarea.two.full, li.contentcell textarea.three.full, li.contentcell textarea.four.full, li.contentcell textarea.five.full, li.contentcell textarea.six.full, li.contentcell textarea.seven.full, li.contentcell textarea.eight.full { width: 100%; }
	
	li.contentcell input[type="checkbox"] { width: 1.5em; height: 1.2em; margin-top: 0.5em; line-height: 2em; }
	
	ul.tablelist { /* new tablestyle */
		display: table;
		width: 100%;
		list-style-type: none;
		}

	ul.tablelist li.tablecell { /* new tablestyle */
		display: block;
		padding: 4px 5px;
		clear: both;
		float: none;
		}

	ul.tablelist li.tablecell.one, ul.tablelist li.tablecell.two, ul.tablelist li.tablecell.three, ul.tablelist li.tablecell.four, ul.tablelist li.tablecell.five, ul.tablelist li.tablecell.six, ul.tablelist li.tablecell.seven, ul.tablelist li.tablecell.eight { /* new tablestyle */
		width: auto;
		background: none;
		}
	
	ul.tablelist li.tablecell:nth-child(odd) { 
		background: #DFE5E9;
		padding: 4px 5px;
		}
	
	ul.tablelist li.tablecell.head {
		background: #354E65;
		color: #fff;
		padding: 4px 5px;
		}
	
	ul.tablelist li.tablecell ul.innercell.block {
		clear: both;
		padding-bottom: 5px;
		}
	
	ul.tablelist li.tablecell textarea.small, table.contenttable textarea.small, ul.tablelist li.tablecell textarea.medium, table.contenttable textarea.medium { height: 13em; }
	ul.tablelist li.tablecell textarea.large, table.contenttable textarea.large  { height: 13em; }
	
	}

/* Bootstrap Preview */

.form-control {
display: block;
width: 100%;
height: 34px;
padding: 6px 12px;
font-size: 14px;
line-height: 1.42857143;
color: #555;
background-color: #fff;
background-image: none;
border: 1px solid #ccc;
-webkit-transition: border-color ease-in-out .15s, -webkit-box-shadow ease-in-out .15s;
-o-transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
-webkit-border-radius: 2px;
-moz-border-radius: 2px;
border-radius: 2px;
border-color: #e1e3ea;
background-color: #fcfcfc;
color: #5e6773;
-webkit-box-sizing: border-box;
-moz-box-sizing: border-box;
box-sizing: border-box;
}

textarea.form-control {
    resize: vertical;
    height: 10em;
}

div.row::after {
    display: table;
    content: " ";
    clear: both;
}

div.row::before {
    display: table;
    content: " ";
    clear: both;
}

div.row {
    clear: both;
    float: none;
    margin-right: -15px;
    margin-left: -15px;

}

div.col-md-1, div.col-md-2, div.col-md-3, div.col-md-4, div.col-md-5, div.col-md-6, 
div.col-md-7, div.col-md-8, div.col-md-9, div.col-md-10, div.col-md-11, div.col-md-12 {
    float: left; 
    margin-bottom: 5px;
    position: relative;
    min-height: 1px;
    padding-right: 15px;
    padding-left: 15px;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
}

div.col-md-1 { width: 8.33333%; }
div.col-md-2 { width: 16.6667%; }
div.col-md-3 { width: 25%; }
div.col-md-4 { width: 33.3333%; }
div.col-md-5 { width: 41.6667%; }
div.col-md-6 { width: 50%; }
div.col-md-7 { width: 58.3333%; }
div.col-md-8 { width: 66.6667%; }
div.col-md-9 { width: 75%; }
div.col-md-10 { width: 83.3333%; }
div.col-md-11 { width: 91.6667%; }
div.col-md-12 { width: 100%; }

div.col-md-1 > input, div.col-md-2 > input, div.col-md-3 > input, div.col-md-4 > input, div.col-md-5 > input, div.col-md-6 > input,
div.col-md-7 > input, div.col-md-8 > input, div.col-md-9 > input, div.col-md-10 > input, div.col-md-11 > input, div.col-md-12 > input { width: 90%; padding: 2px 1%; }

div.row div.singleline {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* EOF */