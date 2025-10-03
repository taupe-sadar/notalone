-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- NotAlone implementation : © Romain Fromi <romain.fromi@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Form the reserve with the Place cards numbered 6 to 10.
CREATE TABLE IF NOT EXISTS `place_card_reserve`
(
  `place_number` tinyint unsigned NOT NULL,
  `quantity`     tinyint          NOT NULL,
  PRIMARY KEY (`place_number`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

-- Each Hunted begins the game with the 5 basic Place cards and may explore new places during the game.
CREATE TABLE IF NOT EXISTS `hunted_place_card`
(
  `hunted_player_id` int(10) unsigned                                                        NOT NULL,
  `place_number`     tinyint unsigned                                                        NOT NULL,
  `location`         ENUM ('HAND', 'DISCARD', 'PLAYED', 'REVEALED', 'RESOLVING', 'RESOLVED') NOT NULL,
  `discard_order`    tinyint unsigned,
  PRIMARY KEY (`hunted_player_id`, `place_number`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

-- Survival cards increase the chances of the Hunted of resisting the Creature’s attacks until the arrival of the Rescue mission.
CREATE TABLE IF NOT EXISTS `survival_card`
(
  `card_id`             SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `card_type`           VARCHAR(16)       NOT NULL,
  `card_type_arg`       int(11),
  `card_location`       VARCHAR(16)       NOT NULL,
  `card_location_arg`   INT(11)           NOT NULL,
  `card_location_order` TINYINT,
  PRIMARY KEY (`card_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

-- Hunt cards increase the chances of the Creature of assimilating the Hunted.
CREATE TABLE IF NOT EXISTS `hunt_card`
(
  `card_id`             SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `card_type`           VARCHAR(16)       NOT NULL,
  `card_type_arg`       int(11)           NOT NULL,
  `card_location`       VARCHAR(16)       NOT NULL,
  `card_location_arg`   INT(11)           NOT NULL,
  `card_location_order` TINYINT,
  PRIMARY KEY (`card_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

-- Each player playing one of the Hunted takes 3 Will counters
ALTER TABLE `player`
  ADD `will_counters` TINYINT UNSIGNED NOT NULL DEFAULT '3';
ALTER TABLE `player`
  ADD `place_pending_effect` tinyint unsigned;
ALTER TABLE `player`
  ADD `cards_left_this_turn` TINYINT UNSIGNED NOT NULL DEFAULT '1';
