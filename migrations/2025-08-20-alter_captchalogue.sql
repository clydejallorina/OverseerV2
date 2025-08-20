ALTER TABLE `Captchalogue`
    MODIFY COLUMN `art` varchar(50) NOT NULL DEFAULT ('') COMMENT 'filename of the item art',
    MODIFY COLUMN `credit` varchar(50) NOT NULL DEFAULT ('') COMMENT 'name of artist',
    MODIFY COLUMN `effects` longtext NOT NULL DEFAULT ('') COMMENT 'tags that define this item''s effects',
    MODIFY COLUMN `status` longtext NOT NULL DEFAULT ('') COMMENT 'Any status effects that are applied when this item is equipped',
    MODIFY COLUMN `gristcosts` longtext NOT NULL DEFAULT ('') COMMENT 'all of the grist costs. format: Grist_Name:Amount|Grist_Name:Amount|etc',
    MODIFY COLUMN `notes` longtext NOT NULL DEFAULT ('') COMMENT 'holds comments/discussion on the item by players and devs';