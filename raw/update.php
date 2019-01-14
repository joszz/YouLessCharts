<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1"/>		
		<title>YouLess - Energy Monitor</title>
		<link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico" />
		<link type="text/css" href="css/style.min.css" rel="stylesheet" />
	</head>
	<body>
		<div id="topHeader"></div>
		<div id="header">		
			<div id="logo"></div>
		</div>
		<div id="container">
			<div id="installDiv">
<?php

        $errorMsg = '';
        $ok = true;
        
        if(!file_exists('inc/settings.inc.php'))
        {
                $errorMsg .= '<p class="error"><b>settings.inc.php</b> ontbreekt, pas <b>settings.inc.php.example</b> aan en hernoem deze naar <b>settings.inc.php</b></p>';
                $ok = false;
        }
        include "inc/settings.inc.php";
        include "classes/database.class.php";
        include "classes/generic.class.php";    
//      include "inc/session.inc.php";

        $db = new Database();
        $gen = new Generic();
        $settings = $db->getSettings(); 
        
        echo $errorMsg;
        if($ok)
	{
		
		try {
		    $db = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASS);
		    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
				
		    $succes = $db->exec("
				CREATE TABLE IF NOT EXISTS `".DB_NAME."`. `meter` (
					`time` datetime NOT NULL,
					`count` decimal(10,3) NOT NULL,
					`islow` tinyint(1) NOT NULL,
					UNIQUE KEY `islow` (`islow`)
				) ENGINE = MYISAM DEFAULT CHARSET = utf8 COLLATE=utf8_bin;
				
				alter table `".DB_NAME."`.`meter` add  UNIQUE KEY `islow` (`islow`);

				INSERT INTO  `".DB_NAME."`.`meter` (`time`, `count`, `islow`) VALUES
				( '2013-01-01','0','0' ),
				( '2013-01-01','0','1' );
				
				UPDATE `".DB_NAME."`. `settings` SET `value` = '1.0.22' WHERE 'settings'.'key' = 'version';
				
				CREATE TABLE IF NOT EXISTS `".DB_NAME."`. `data_h` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `time` datetime NOT NULL,
				  `unit` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
				  `delta` int(11) NOT NULL,
				  `value` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
				  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  PRIMARY KEY (`id`),
				  KEY `time` (`time`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;

				CREATE TABLE IF NOT EXISTS `".DB_NAME."`. `data_m` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `time` datetime NOT NULL,
				  `unit` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
				  `delta` int(11) NOT NULL,
				  `value` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
				  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `time` (`time`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;
				
				ALTER TABLE `".DB_NAME."`. `data_m` ADD COLUMN cpKwh   	decimal(10,6);
				ALTER TABLE `".DB_NAME."`. `data_m` ADD COLUMN IsLow   	tinyint(1);
				
				CREATE TABLE IF NOT EXISTS `".DB_NAME."`. `kwh_h` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `kwh` varchar(20) NOT NULL,
				  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  PRIMARY KEY (`id`),
				  KEY `inserted` (`inserted`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

				INSERT INTO `".DB_NAME."`. `settings` (`key`, `value`) VALUES
				('version','2.1.0'),
				('LastUpdate_UnixTime', '0');

		    ");

			$gen->updateDatabase();

			echo "<p style='color:green;'>Update succesvol. Verwijder <b>install.php</b> en <b>update.php</b></p>";

		} catch (PDOException $e) {
		    die(print("<p class='error'>Database error: ". $e->getMessage() ."</p>"));
		}	
	}
?>
			</div>
		</div>
	</body>
</html>
