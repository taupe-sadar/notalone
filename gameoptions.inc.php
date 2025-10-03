<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * NotAlone implementation : © Romain Fromi <romain.fromi@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gameoptions.inc.php
 *
 * NotAlone game options description
 *
 * In this file, you can define your game options (= game variants).
 *
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in notalone.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(
    // note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.
    100 => array(
        'name' => totranslate('Game designer expert variant'),
        'values' => array(
            // A simple value for this option:
            0 => array('name' => totranslate('Disabled')),
            1 => array('name' => totranslate('Enabled'))

            // A simple value for this option.
            // If this value is chosen, the value of "tmdisplay" is displayed in the game lobby
            //2 => array( 'name' => totranslate('option 2'), 'tmdisplay' => totranslate('option 2') ),

            // Another value, with other options:
            //  beta=true => this option is in beta version right now.
            //  nobeginner=true  =>  this option is not recommended for beginners
            //3 => array( 'name' => totranslate('option 3'),  'beta' => true, 'nobeginner' => true ),) )
        )
    ),

    101 => array(
        'name' => totranslate('Who will play the Creature?'),
        'values' => array(
            0 => array('name' => totranslate('A random player')),
            1 => array('name' => totranslate('The table administrator')),
            2 => array('name' => totranslate('NOT the table administrator'))
        )
    )
);


