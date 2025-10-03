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
 * notalone.action.php
 *
 * NotAlone main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/notalone/notalone/myAction.html", ...)
 *
 */


class action_notalone extends APP_GameAction
{
    // Constructor: please do not modify
    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
        } else {
            $this->view = "notalone_notalone";
            self::trace("Complete reinitialization of board game");
        }
    }

    /**
     * The player playing the Creature puts the board in the center of the table [1], whichever way up he likes.
     */
    public function chooseBoardSide()
    {
        self::setAjaxMode();
        $side = self::getArg("side", AT_posint, true);
        $this->game->chooseBoardSide($side);
        self::ajaxResponse();
    }

    /**
     * The Hunted simultaneously play one Place card (2 for those who used The River or The Artefact on previous turn).
     */
    public function exploration()
    {
        self::setAjaxMode();
        $places = array_unique($this->extractIntegers(self::getArg("places", AT_numberlist, true)));
        $this->game->exploration($places);
        self::ajaxResponse();
    }

    /**
     * Resist: the player forfeits 1 or 2 Will counters to take back 2 or 4 Place cards of his choice, respectively, from his discard pile.
     */
    public function resist()
    {
        self::setAjaxMode();
        $places = array_unique($this->extractIntegers(self::getArg("places", AT_numberlist, true)));
        $this->game->resist($places);
        self::ajaxResponse();
    }

    /**
     * Give Up: the player regains all of his Will counters and takes back all of his discarded Place cards.
     */
    public function giveUp()
    {
        self::setAjaxMode();
        $this->game->giveUp();
        self::ajaxResponse();
    }

    /**
     * The Creature may place tokens on the Place cards that make up Artemia
     */
    public function placeToken()
    {
        self::setAjaxMode();
        $tokenType = self::getArg("tokenType", AT_enum, true, null, array('creature', 'artemia', 'target'));
        $position = self::getArg("position", AT_posint, true);
        $this->game->placeToken($tokenType, $position);
        self::ajaxResponse();
    }

    /**
     * Vortex: Swap your played Place card for one Place card from your discard pile.
     */
    public function swapPlaceCard()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, true);
        $swappedPlace = self::getArg("swappedPlace", AT_posint, true);
        $this->game->swapPlaceCard($place, $swappedPlace);
        self::ajaxResponse();
    }

    /**
     * The Lair: take back to your hand the Place cards from your discard pile
     */
    public function takeBackDiscardedPlaceCards()
    {
        self::setAjaxMode();
        $this->game->takeBackDiscardedPlaceCards();
        self::ajaxResponse();
    }

    /**
     * The Lair: copy the power of the place with the Creature token.
     */
    public function copyCreaturePlace()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, true);
        $this->game->copyCreaturePlace($place);
        self::ajaxResponse();
    }

    /**
     * The Jungle: take back to your hand this Place card and 1 Place card from your discard pile.
     */
    public function theJungle()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, true);
        $this->game->theJungle($place);
        self::ajaxResponse();
    }

    /**
     * The River: next turn, play 2 Place cards. Before revealing, choose one and return the second to your hand.
     */
    public function theRiver()
    {
        self::setAjaxMode();
        $this->game->theRiver();
        self::ajaxResponse();
    }

    /**
     * The River: next turn, play 2 Place cards. Before revealing, choose one and return the second to your hand.
     */
    public function chooseRiverPlaceCard()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, true);
        $this->game->chooseRiverPlaceCard($place);
        self::ajaxResponse();
    }

    /**
     * The Beach: place the Marker counter on the Beach OR remove it to move the Rescue counter forward 1 space.
     */
    public function theBeach()
    {
        self::setAjaxMode();
        $this->game->theBeach();
        self::ajaxResponse();
    }

    /**
     * The Rover: take from the reserve 1 Place card you do not own and add it to your hand.
     */
    public function theRover()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, true);
        $this->game->theRover($place);
        self::ajaxResponse();
    }

    /**
     * The Swamp: take back to your hand this Place card and 2 Place cards from your discard pile.
     */
    public function theSwamp()
    {
        self::setAjaxMode();
        $places = array_unique($this->extractIntegers(self::getArg("places", AT_numberlist, true)));
        $this->game->theSwamp($places);
        self::ajaxResponse();
    }

    /**
     * The Shelter: Draw 2 Survival cards, choose one and discard the second.
     */
    public function theShelter()
    {
        self::setAjaxMode();
        $this->game->theShelter();
        self::ajaxResponse();
    }

    /**
     * The Shelter: Draw 2 Survival cards, choose one and discard the second.
     */
    public function chooseSurvivalCard()
    {
        self::setAjaxMode();
        $card = self::getArg("card", AT_enum, true, null, array_keys($this->game->survivalCards));
        $this->game->chooseSurvivalCard($card);
        self::ajaxResponse();
    }

    /**
     * The Wreck: move the Rescue counter forward 1 space.
     */
    public function theWreck()
    {
        self::setAjaxMode();
        $this->game->theWreck();
        self::ajaxResponse();
    }

    /**
     * The Source: the Hunted of your choice (you or another player) regains 1 Will.
     */
    public function regainWill()
    {
        self::setAjaxMode();
        $playerId = self::getArg("playerId", AT_posint, true);
        $this->game->regainWill($playerId);
        self::ajaxResponse();
    }

    /**
     * The Source: you draw 1 Survival card.
     */
    public function drawSurvivalCard()
    {
        self::setAjaxMode();
        $this->game->drawSurvivalCard();
        self::ajaxResponse();
    }

    /**
     * The Artefact: next turn, play 2 Place cards. Resolve both places.
     */
    public function theArtefact()
    {
        self::setAjaxMode();
        $this->game->theArtefact();
        self::ajaxResponse();
    }

    /**
     * The Artefact: choose which place to resolve first.
     */
    public function chooseArtefactPlaceCard()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, true);
        $this->game->chooseArtefactPlaceCard($place);
        self::ajaxResponse();
    }

    /**
     * Each Hunted who explores a place without a Hunt token may take back 1 Place card of his choice from his discard pile.
     */
    public function takeBackDiscardedPlace()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, true);
        $this->game->takeBackDiscardedPlace($place);
        self::ajaxResponse();
    }

    /**
     * When using the Jungle or the Swamp we can take back the played card.
     * Also, when playing Double Back with The Artefact we have to choose which place to take back.
     */
    public function takeBackPlayedCard()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, false);
        $this->game->takeBackPlayedCard($place);
        self::ajaxResponse();
    }

    /**
     * Hunt Cards may be played at any time during the phase shown on the card and are discarded after use.
     */
    public function playHuntCard()
    {
        self::setAjaxMode();
        $card = self::getArg("card", AT_enum, true, null, array_keys($this->game->huntCards));
        $this->game->playHuntCard($card);
        self::ajaxResponse();
    }

    /**
     * Survival cards may be played at any time during the phase shown on the card and are discarded after use.
     */
    public function playSurvivalCard()
    {
        self::setAjaxMode();
        $card = self::getArg("card", AT_enum, true, null, array_keys($this->game->survivalCards));
        $this->game->playSurvivalCard($card);
        self::ajaxResponse();
    }

    /**
     * Each Hunted who explores the place where the Artemia token is located discards 1 Place card from his hand.
     * Sacrifice: discard 1 Place card. No Hunt card may be played this turn.
     */
    public function discardPlaceCard()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, true);
        $this->game->discardPlaceCard($place);
        self::ajaxResponse();
    }

    /**
     * Sixth Sense: take back to your hand 2 Place cards from your discard pile.
     */
    public function takeBack2PlaceCards()
    {
        self::setAjaxMode();
        $places = array_unique($this->extractIntegers(self::getArg("places", AT_numberlist, true)));
        $this->game->takeBack2PlaceCards($places);
        self::ajaxResponse();
    }

    /**
     * Anticipation: Choose one Hunted. If you catch him with the Creature token, move the Assimilation counter forward 1 extra space.
     * Ascendancy: Force one Hunted to discard all but 2 Place cards from his hand.
     */
    public function chooseHuntedPlayer()
    {
        self::setAjaxMode();
        $playerId = self::getArg("playerId", AT_posint, true);
        $this->game->chooseHuntedPlayer($playerId);
        self::ajaxResponse();
    }

    /**
     * Ascendancy: Force one Hunted to discard all but 2 Place cards from his hand.
     * Scream: Each Hunted on the targeted place must discard 2 Place cards or lose 1 Will.
     */
    public function discardPlaceCards()
    {
        self::setAjaxMode();
        $places = array_unique($this->extractIntegers(self::getArg("places", AT_numberlist, true)));
        $this->game->discardPlaceCards($places);
        self::ajaxResponse();
    }

    /**
     * Scream: Each Hunted on the targeted place must discard 2 Place cards or lose 1 Will.
     */
    public function loseWill()
    {
        self::setAjaxMode();
        $this->game->loseWill();
        self::ajaxResponse();
    }

    /**
     * Phobia: Force one Hunted to show you all but 2 Place cards from his hand.
     */
    public function showPlaces()
    {
        self::setAjaxMode();
        $places = array_unique($this->extractIntegers(self::getArg("places", AT_numberlist, true)));
        $this->game->showPlaces($places);
        self::ajaxResponse();
    }

    /**
     * Toxin: Each Hunted on the targeted place discards 1 Survival card.
     */
    public function discardSurvivalCard()
    {
        self::setAjaxMode();
        $card = self::getArg("card", AT_enum, true, null, array_keys($this->game->survivalCards));
        $this->game->discardSurvivalCard($card);
        self::ajaxResponse();
    }

    /**
     * Gate: instead of using the power of your Place card, copy the power of an adjacent place.
     */
    public function copyAdjacentPlace()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, true);
        $this->game->copyAdjacentPlace($place);
        self::ajaxResponse();
    }

    /**
     * Hologram: move the Artemia token to an adjacent place.
     * Wrong Track: move the Creature token to an adjacent place.
     */
    public function moveHuntToken()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, true);
        $tokenType = self::getArg("tokenType", AT_enum, true, null, array('creature', 'artemia', 'target'));
        $this->game->moveHuntToken($tokenType, $place);
        self::ajaxResponse();
    }

    /**
     * Cataclysm: the place’s power of your choice is ineffective.
     */
    public function chooseIneffectivePlace()
    {
        self::setAjaxMode();
        $place = self::getArg("place", AT_posint, true);
        $this->game->chooseIneffectivePlace($place);
        self::ajaxResponse();
    }

    /**
     * Detour: after the Hunted reveal their Place cards, move one Hunted to an adjacent place.
     */
    public function moveHunted()
    {
        self::setAjaxMode();
        $huntedId = self::getArg("huntedId", AT_posint, true);
        $origin = self::getArg("origin", AT_posint, true);
        $destination = self::getArg("destination", AT_posint, true);
        $this->game->moveHunted($huntedId, $origin, $destination);
        self::ajaxResponse();
    }

    /**
     * When player is active but does not want to play something.
     */
    public function pass()
    {
        self::setAjaxMode();
        $this->game->pass();
        self::ajaxResponse();
    }

    // Utility functions

    /**
     * When Javascript sends a list of integers separated by "," (example: "1,2,3,4") as an argument
     */
    private function extractIntegers($rawValue)
    {
        if (substr($rawValue, -1) == ',')
            $rawValue = substr($rawValue, 0, -1);
        if ($rawValue == '')
            return array();
        else
            return explode(',', $rawValue);
    }

}
  

