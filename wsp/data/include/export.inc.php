<?
/**
 * class to export wsp-data in xml
 * @author stefan@covi.de
  * @since 3.1
 * @version 6.11
 * @lastchange 2022-11-17
 */

// SH 2018-09-18
// interesting concept .. should stay in view

/*
function export($menuid) {
	header('Content-Description: File Transfer');
	header("Content-Disposition: attachment; filename=".time().".xml");
	header('Content-Type: text/xml; charset=iso-8859-1', true);

	$export = "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?><!--This is a export of WSP-->\n";
	$export.= "<wsp>\n";
	$export.= "	<menu>\n";
	foreach($menuid as  $mvalue):
		$mid = mysql_fetch_array(mysql_query("SELECT * FROM `menu` WHERE mid = '".$mvalue."' LIMIT 1"),MYSQL_ASSOC);
		$export.= "		<item>\n";
		foreach($mid as $midkey => $midvalue):
			if($midvalue != ''):
				$export.= "			<$midkey><![CDATA[$midvalue]]></$midkey>\n";
			endif;
		endforeach;
		$export.= "		</item>\n";
	endforeach;
	$export.= "	</menu>\n";
	
	$export.= "	<content>\n";
	foreach($menuid as  $mvalue):
		$num = mysql_query("SELECT * FROM `content` WHERE `mid` = ".$mvalue." ORDER BY `position`");
		while($numdata=mysql_fetch_array($num,MYSQL_ASSOC)):
			$export.= "		<item>\n";
			foreach($numdata as $numdatakey => $numdatavalue):
				if($numdatavalue != ''):
					$export.= "			<$numdatakey><![CDATA[$numdatavalue]]></$numdatakey>\n";
				endif;
			endforeach;
			$export.= "		</item>\n";
		endwhile;
	endforeach;
	$export.= "	</content>\n";
	$export.= "</wsp>\n";
	
	echo $export;
}
*/

// EOF