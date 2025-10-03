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
 * notalone.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in notalone_notalone.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once(APP_BASE_PATH . "view/common/game.view.php");

class view_notalone_notalone extends game_view
{
    /** @var NotAlone */
    public $game;

    function getGameName()
    {
        return "notalone";
    }

    function build_page($viewArgs)
    {
        $this->tpl['CHOOSE_A_CARD'] = self::_("Choose a card");
        $this->tpl['ARTEMIA'] = self::_("Artemia");
        $this->tpl['ONGOING_EFFECTS'] = self::_("Ongoing effects");
        $this->tpl['DISCARDED'] = self::_("Discarded Hunt and Survival cards");
    }
}
  

