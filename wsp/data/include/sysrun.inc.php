<?php
/**
* sysrun describes changes from version to version
* @author stefan@covi.de
* @copyright (c) 2018, Common Visions Media.Agentur (COVI)
* @since 5.0
* @version 6.7
* @lastchange 2018-09-18
*/

if (isset($sysrun) && $sysrun<4.0):
	?>
	<fieldset><p>Running Updates prior version 4.0</p></fieldset>
	<?php
	// props update from version 4.0 to upper versions
	$props_sql = "SELECT * FROM `wspproperties`";
	$props_res = mysql_query($props_sql);
	if ($props_res):
		$props_num = mysql_num_rows($props_res);
		$oldprops_sql = "SELECT * FROM `siteproperties`";
		$oldprops_res = mysql_query($oldprops_sql);
		if ($oldprops_res):
			$oldprops_num = mysql_num_rows($oldprops_res);
		endif;
		if ($oldprops_num>0):
			$dataarray = mysql_fetch_array($oldprops_res, MYSQL_ASSOC);
			if (count($dataarray)>$props_num):
				foreach ($dataarray AS $datakey => $datavalue):
					$deletedata_sql = "DELETE FROM `wspproperties` WHERE `varname` = '".$datakey."'";
					mysql_query($deletedata_sql);
					$insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = '".$datakey."', `varvalue` = '".mysql_real_escape_string($datavalue)."'";
					mysql_query($insertdata_sql);
				endforeach;
			endif;
		endif;
	else:
		$sql = "CREATE TABLE `wspproperties` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY , `varname` TEXT NOT NULL , `varvalue` TEXT NOT NULL) ENGINE = MYISAM ;";
		mysql_query($sql);
		$oldprops_sql = "SELECT * FROM `siteproperties`";
		$oldprops_res = mysql_query($oldprops_sql);
		if ($oldprops_res):
			$oldprops_num = mysql_num_rows($oldprops_res);
		endif;
		if ($oldprops_num>0):
			$dataarray = mysql_fetch_array($oldprops_res, MYSQL_ASSOC);
			if (count($dataarray)>$props_num):
				foreach ($dataarray AS $datakey => $datavalue):
					$deletedata_sql = "DELETE FROM `wspproperties` WHERE `varname` = '".$datakey."'";
					mysql_query($deletedata_sql);
					$insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = '".$datakey."', `varvalue` = '".mysql_real_escape_string($datavalue)."'";
					mysql_query($insertdata_sql);
				endforeach;
			endif;
		endif;
	endif;
	$insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = 'lastversion', `varvalue` = '4.0'";
	mysql_query($insertdata_sql);
	$sysrun = 4.0;
elseif (isset($sysrun) && $sysrun<5.1):
	?>
	<fieldset><p>Running Updates prior version 5.0</p></fieldset>
	<?php
	// renaming all template vars from <% VARNAME %> TO [%VARNAME%]
	
	$sql = "UPDATE `templates` SET `template` = replace(template, '<% ', '[%')";
	mysql_query($sql);
	$sql = "UPDATE `templates` SET `template` = replace(template, ' %>', '%]')";
	mysql_query($sql);
	
	$sql = "ALTER TABLE `menu` CHANGE `visibility` `visibility` VARCHAR(3) NOT NULL";
	mysql_query($sql);
	$sql = "UPDATE `menu` SET `visibility` = 1 WHERE `visibility` = 'yes'";
	mysql_query($sql);
	$sql = "UPDATE `menu` SET `visibility` = 0 WHERE `visibility` = 'no'";
	mysql_query($sql);
	
	$sql = "ALTER TABLE `end_menu` CHANGE `visibility` `visibility` VARCHAR(3) NOT NULL";
	mysql_query($sql);
	$sql = "UPDATE `end_menu` SET `visibility` = 1 WHERE `visibility` = 'yes'";
	mysql_query($sql);
	$sql = "UPDATE `end_menu` SET `visibility` = 0 WHERE `visibility` = 'no'";
	mysql_query($sql);
	
	$sql = "UPDATE `content` SET `visibility` = 1 WHERE `visibility` = 'yes'";
	mysql_query($sql);
	$sql = "UPDATE `content` SET `visibility` = 0 WHERE `visibility` = 'no'";
	mysql_query($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS `wspmsg` (`id` int(10) unsigned NOT NULL auto_increment, `targetuid` int(10) unsigned NOT NULL, `message` text NOT NULL, `read` int(10) unsigned NOT NULL, PRIMARY KEY  (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
	mysql_query($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS `wspqueue` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(11) NOT NULL,
  `set` int(11) NOT NULL,
  `action` text NOT NULL,
  `param` text NOT NULL,
  `timeout` int(11) NOT NULL,
  `done` int(11) NOT NULL,
  `priority` int(11) NOT NULL,
  `outputuid` varchar(80) NOT NULL,
  `output` text NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
	mysql_query($sql);
	
	$sql = "DELETE FROM `wspproperties` WHERE `varname` = 'lastversion'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varvalue` = '5.1', `varname` = 'lastversion'";
	mysql_query($sql);
	$sysrun = 5.1;
elseif (isset($sysrun) && $sysrun<6.1):
	$sql = "ALTER TABLE wspmedia DROP INDEX filename";
	mysql_query($sql);
	$sql = "DELETE FROM `wspproperties` WHERE `varname` = 'lastversion'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varvalue` = '6.1', `varname` = 'lastversion'";
	mysql_query($sql);
	$sysrun = 6.1;
elseif (isset($sysrun) && $sysrun<6.2):
	$sql = "UPDATE `stylesheets` SET `cfolder` = `lastchange` WHERE `cfolder` = ''";
	mysql_query($sql);
	$sql = "ALTER TABLE `stylesheets` ADD UNIQUE(`cfolder`)";
	mysql_query($sql);
	$sql = "UPDATE `javascript` SET `cfolder` = `lastchange` WHERE `cfolder` = ''";
	mysql_query($sql);
	$sql = "ALTER TABLE `javascript` ADD UNIQUE(`cfolder`)";
	mysql_query($sql);
	$sql = "DELETE FROM `wspproperties` WHERE `varname` = 'lastversion'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varvalue` = '6.2', `varname` = 'lastversion'";
	mysql_query($sql);
	$sysrun = 6.2;
elseif (isset($sysrun) && $sysrun<6.7):
	$sql = "DELETE FROM `wspproperties` WHERE `varname` = 'lastversion'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varvalue` = '6.7', `varname` = 'lastversion'";
	mysql_query($sql);
	$sysrun = 6.7;
else:
	$sysrun = "finished";
endif;

// EOF ?>