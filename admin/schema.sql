CREATE TABLE IF NOT EXISTS `nowspots_Advertisers` (
	`id` int(11) NOT NULL AUTO_INCREMENT, 
	`Name` varchar(255) NOT NULL, 
	`Status` ENUM('Active','Inactive') NOT NULL, 
	`CreatedDate` datetime, 
	`ModifiedDate` datetime, 
	PRIMARY KEY (`id`),
) TYPE=InnoDB;
CREATE TABLE IF NOT EXISTS `nowspots_SocialMediaAccounts` (
	`id` int(11) NOT NULL AUTO_INCREMENT, 
	`AdvertiserID` int(11) NOT NULL, 
	`Type` ENUM('Facebook','Twitter') NOT NULL, 
	`Name` varchar(255), 
	`URL` varchar(255), 
	`Data` text, 
	`Status` ENUM('Active','Inactive') NOT NULL, 
	`CreatedDate` datetime,
	`ModifiedDate` datetime, 
	PRIMARY KEY (`id`)
) TYPE=InnoDB;
CREATE TABLE IF NOT EXISTS `nowspots_SocialMediaAccountUpdates` ( 
	`id` int(11) NOT NULL AUTO_INCREMENT, 
	`SocialMediaAccountID` int(11) NOT NULL, 
	`Title` TEXT, 
	`Text` TEXT, 
	`URL` varchar(255), 
	`Status` ENUM('Active','Inactive') NOT NULL, 
	`CreatedDate` datetime, 
	`ModifiedDate` datetime, 
	PRIMARY KEY (`id`)
) TYPE=InnoDB;


CREATE TABLE IF NOT EXISTS `nowspots_Ads` (
	`id` int(11) NOT NULL AUTO_INCREMENT, 
	`AdvertiserID` int(11) NOT NULL,
	`Name` varchar(255), 
	`StartDate` datetime, 
	`EndDate` datetime, 
	`SocialMediaAccountID` int(11) NOT NULL,
	`ImageID` int(11), 
	`AdTemplateID` bigint(11), 
	`AdStatus` ENUM('Active','Pending','Inactive') NOT NULL, 
	`CreatedDate` datetime, 
	`ModifiedDate` datetime, 
	PRIMARY KEY (`id`)
) TYPE=InnoDB;
CREATE TABLE IF NOT EXISTS `nowspots_Images` ( 
	`id` int(11) NOT NULL AUTO_INCREMENT, 
	`AdvertiserID` int(11), 
	`SocialMediaAccountID` int(11), 
	`Path` varchar(255), 
	`ImageStatus` ENUM('Active','Inactive') NOT NULL, 
	`CreatedDate` datetime, 
	`ModifiedDate` datetime, 
	PRIMARY KEY (`id`)
) TYPE=InnoDB;
CREATE TABLE IF NOT EXISTS `nowspots_AdTemplates` ( 
	`id` int(11) NOT NULL AUTO_INCREMENT, 
	`AdTemplateName` varchar(255),
	`Height` int, 
	`Width` int, 
	`ImageHeight` int, 
	`ImageWidth` int, 
	`NumUpdates` int, 
	`AdTemplateContent` TEXT, 
	`LayoutCSS` TEXT, 
	`AdTemplateStatus` ENUM('Active','Inactive') NOT NULL, 
	`CreatedDate` datetime,
	`ModifiedDate` datetime, 
	PRIMARY KEY (`id`)
) TYPE=InnoDB;
CREATE TABLE IF NOT EXISTS `nowspots_Transactions` ( 
	`id` int(11) NOT NULL AUTO_INCREMENT, 
	`AdvertiserID` int(11) NOT NULL, 
	`AdID` int(11) NOT NULL, 
	`SocialMediaAccountID` int(11), 
	`ImpressionCount` int(11) default '0', 
	`ProfileClicks` int(11) default '0', 
	`FollowClicks` int(11) default '0', 
	`ContentClicks` int(11) default '0', 
	`ShareClicks` int(11) default '0', 
	`TransactionStatus` ENUM('Active','Inactive') NOT NULL, 
	`CreatedDate` datetime,
	`ModifiedDate` datetime, 
	PRIMARY KEY (`id`)
) TYPE=InnoDB;