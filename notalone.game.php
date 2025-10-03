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
 * notalone.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');


class NotAlone extends Table
{
    public $placeCards;
    public $huntCards;
    public $survivalCards;
    public $survivalDeck;
    public $huntDeck;

    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels(array(
            'creature_player' => 10,
            'board_side' => 11,
            'rescue_counter' => 12,
            'assimilation_counter' => 13,
            'marker_counter' => 14,

            // token location, value will be between 1 and 10 (0 if not on artemia)
            'creature_token' => 15,
            // 11 to 23 when targeting adjacent places
            'artemia_token' => 16,
            'target_token' => 17,

            // Some locations can only be used 1x per turn
            'beach_used' => 18,
            'wreck_used' => 19,

            // Keep track when the Target token makes the place's power ineffective
            'targeted_places_ineffective' => 20,

            // Cards with global effects
            'despair' => 21,
            'fierceness' => 22,
            'interference' => 23,
            'persecution' => 24,
            'mutation' => 25,
            'clone' => 26,
            'scream' => 27,
            'toxin' => 28,
            'stasis' => 29,
            'cataclysm' => 30,
            'detour_target_hunted' => 31,
            'detour_origin' => 32,
            'detour_destination' => 33,
            'tracking' => 34,
            'sacrifice' => 35,
            'smokescreen' => 36,
            'anticipation' => 37,
            'detector' => 38,
            'dodge' => 39,

            'game_designer_expert_variant' => 100
        ));

        $this->survivalDeck = self::getNew("module.common.deck");
        $this->survivalDeck->init("survival_card");
        $this->survivalDeck->autoreshuffle = true;
        $this->survivalDeck->autoreshuffle_trigger = array('obj' => $this, 'method' => 'survivalDeckShuffled');

        $this->huntDeck = self::getNew("module.common.deck");
        $this->huntDeck->init("hunt_card");
        $this->huntDeck->autoreshuffle = true;
        $this->huntDeck->autoreshuffle_trigger = array('obj' => $this, 'method' => 'huntDeckShuffled');
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "notalone";
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = [])
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $playersNumber = sizeof($players);
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = [];
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        $rescueCounter = 11 + $playersNumber;
        $assimilationCounter = 5 + $playersNumber;
        self::setGameStateInitialValue('rescue_counter', $rescueCounter);
        self::setGameStateInitialValue('assimilation_counter', $assimilationCounter);
        self::setGameStateInitialValue('creature_token', -1);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // Choose a Creature player and activate him
        if ($options[101] == 1) { // Admin should be the Creature
            $creaturePlayerId = $this->getAdminPlayerID();
        } else {
            $player_ids = array_keys($players);
            if ($options[101] == 2) { // Admin should NOT be the Creature
                $player_ids = array_diff($player_ids, [$this->getAdminPlayerID()]);
            }
            $creaturePlayerId = $player_ids[array_rand($player_ids)];
        }

        $this->gamestate->changeActivePlayer($creaturePlayerId);
        self::setGameStateInitialValue("creature_player", $creaturePlayerId);
        self::DbQuery("UPDATE player SET player_score = -$rescueCounter WHERE player_id <> $creaturePlayerId");
        self::DbQuery("UPDATE player SET player_score = -$assimilationCounter WHERE player_id = $creaturePlayerId");


        // Form the reserve with the Place cards numbered 6 to 10.
        $placeReserveQuantity = $playersNumber < 3 ? 1 : ($playersNumber < 5 ? 2 : 3);
        self::DbQuery("INSERT INTO place_card_reserve (place_number, quantity)
    			VALUES (6, $placeReserveQuantity), (7, $placeReserveQuantity), (8, $placeReserveQuantity), (9, $placeReserveQuantity), (10, $placeReserveQuantity)");

        // Shuffle the Survival cards
        $survivalCards = [];
        foreach ($this->survivalCards as $key => $survivalCard) {
            $survivalCards[] = array('type' => $key, 'type_arg' => 0, 'nbr' => 1);
        }
        $this->survivalDeck->createCards($survivalCards, 'deck');
        $this->survivalDeck->shuffle('deck');

        // Shuffle the Hunt cards
        $huntCards = [];
        foreach ($this->huntCards as $key => $huntCard) {
            $huntCards[] = array('type' => $key, 'type_arg' => 0, 'nbr' => 1);
        }
        $this->huntDeck->createCards($huntCards, 'deck');
        $this->huntDeck->shuffle('deck');

        // Each player playing one of the Hunted takes a set of 5 Place cards numbered from 1 to 5.
        foreach ($players as $playerId => $player) {
            if ($playerId != $creaturePlayerId) {
                self::DbQuery("INSERT INTO hunted_place_card (hunted_player_id, place_number, location)
          			VALUES ($playerId, 1, 'HAND'), ($playerId, 2, 'HAND'), ($playerId, 3, 'HAND'), ($playerId, 4, 'HAND'), ($playerId, 5, 'HAND')");
            }
        }
    }

    function getAdminPlayerID() {
        return self::getUniqueValueFromDB( "SELECT global_value value FROM global WHERE global_id = 5" );
    }

    /************ End of the game initialization *****/

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = [];

        $currentPlayerId = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, will_counters willCounters, place_pending_effect placePendingEffect FROM player ";
        $result['players'] = self::getCollectionFromDb($sql);

        $creaturePlayerId = self::getGameStateValue('creature_player');
        unset($result['players'][$creaturePlayerId]['willCounters']);

        $boardSide = self::getGameStateValue('board_side');
        if ($boardSide != 0) {
            $result['boardSide'] = $boardSide;
        }
        $result['creaturePlayer'] = $creaturePlayerId;
        $result['rescueCounter'] = self::getGameStateValue('rescue_counter');
        $result['assimilationCounter'] = self::getGameStateValue('assimilation_counter');
        $result['markerCounter'] = self::getGameStateValue('marker_counter');
        $result['placesReserve'] = self::getCollectionFromDB("SELECT place_number placeNumber, quantity quantity FROM place_card_reserve");
        $result['placeCards'] = $this->placeCards;
        $result['huntCards'] = $this->huntCards;
        $result['survivalCards'] = $this->survivalCards;

        if ($currentPlayerId == $creaturePlayerId) {
            $result['playerHuntCards'] = $this->huntDeck->getCardsInLocation('hand', $currentPlayerId);
        } else {
            $result['playerSurvivalCards'] = $this->survivalDeck->getCardsInLocation('hand', $currentPlayerId);
            $result['playerPlaceCards'] = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $currentPlayerId AND location = 'HAND'", true);
        }

        $result['discardedHuntCards'] = array_merge($this->huntDeck->getCardsInLocation('discard', null, 'card_location_arg'), $this->huntDeck->getCardsInLocation('played', null, 'card_location_arg'));
        $result['discardedSurvivalCards'] = $this->survivalDeck->getCardsInLocation('discard', null, 'card_location_arg');
        $result['huntDeckSize'] = $this->huntDeck->countCardInLocation('deck');
        $result['survivalDeckSize'] = $this->survivalDeck->countCardInLocation('deck');

        $smokescreen = self::getGameStateValue('smokescreen');
        $playerSurvivalCardsQuantity = $this->survivalDeck->countCardsByLocationArgs('hand');
        $playerPlaceCardsQuantity = self::getCollectionFromDB("SELECT hunted_player_id id, count(*) quantity FROM hunted_place_card WHERE location = 'HAND' GROUP BY hunted_player_id", true);
        $playersPlayedPlaces = self::getDoubleKeyCollectionFromDB("SELECT hunted_player_id, place_number, location FROM hunted_place_card WHERE location NOT IN ('HAND', 'DISCARD')", true);
        foreach ($result['players'] as $playerId => $player) {
            if ($playerId == $creaturePlayerId) {
                $result['players'][$playerId]['huntCardsSize'] = $this->huntDeck->countCardInLocation('hand', $creaturePlayerId);
            } else {
                $result['players'][$playerId]['survivalCardsSize'] = $playerSurvivalCardsQuantity[$playerId] ?? 0;
                $result['players'][$playerId]['placeCardsSize'] = $playerPlaceCardsQuantity[$playerId] ?? 0;
                if ($currentPlayerId == $playerId) {
                    $result['players'][$playerId]['playedPlaces'] = array_map("strval", array_keys($playersPlayedPlaces[$playerId] ?? []));
                } else {
                    $playedPlaces = [];
                    $hiddenPlaces = 0;
                    foreach ($playersPlayedPlaces[$playerId] ?? [] as $place => $location) {
                        if ($location == 'PLAYED') {
                            array_push($playedPlaces, '?' . $hiddenPlaces);
                            $hiddenPlaces++;
                        } else {
                            array_push($playedPlaces, strval($place));
                        }
                    }
                    $result['players'][$playerId]['playedPlaces'] = $playedPlaces;
                }
                if (!$smokescreen || $currentPlayerId != $creaturePlayerId) {
                    $result['players'][$playerId]['discardedPlaces'] = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'DISCARD' ORDER BY discard_order", true);
                }
            }
        }

        $detourTarget = self::getGameStateValue('detour_target_hunted');
        if ($detourTarget != 0) {
            $result['players'][$detourTarget]['detour'] = array('origin' => self::getGameStateValue('detour_origin'), 'destination' => self::getGameStateValue('detour_destination'));
        }

        $result['creatureToken'] = self::getGameStateValue('creature_token');
        $result['artemiaToken'] = self::getGameStateValue('artemia_token');
        $result['targetToken'] = self::getGameStateValue('target_token');

        $potentialOngoingEffects = ['tracking', 'despair', 'force-field', 'sacrifice', 'smokescreen', 'anticipation', 'fierceness', 'interference', 'persecution', 'mutation', 'clone', 'mirage', 'scream', 'toxin', 'cataclysm', 'detour', 'detector', 'dodge'];
        $result['ongoingEffects'] = [];
        foreach ($potentialOngoingEffects as $effect) {
            switch ($effect) {
                case 'force-field':
                    if ($this->gamestate->state()['phase'] == 1 && self::getGameStateValue('target_token') != 0) {
                        array_push($result['ongoingEffects'], $effect);
                    }
                    break;
                case 'mirage':
                    if (self::getGameStateValue('targeted_places_ineffective') == 1 && (self::getGameStateValue('target_token') > 10 || self::getGameStateValue('target_token') == -2)) {
                        array_push($result['ongoingEffects'], $effect);
                    }
                    break;
                case 'detour':
                    if ($this->gamestate->state()['name'] == 'detour' || self::getGameStateValue('detour_target_hunted') != 0) {
                        array_push($result['ongoingEffects'], $effect);
                    }
                    break;
                default:
                    if (self::getGameStateValue($effect) != 0) {
                        array_push($result['ongoingEffects'], $effect);
                    }
                    break;
            }
        }

        $result['smokescreen'] = $smokescreen;

        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $playersNumber = self::getPlayersNumber();
        $rescueCounterProgress = 1 - (self::getGameStateValue('rescue_counter') / (11 + $playersNumber));
        $assimilationCounterProgress = 1 - (self::getGameStateValue('assimilation_counter') / (5 + $playersNumber));
        return min($rescueCounterProgress, $assimilationCounterProgress) * 100;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    private function setPlayerNonActiveDuringExploration($playerId)
    {
        if ($this->gamestate->setPlayerNonMultiactive($playerId, 'hunting')) {
            $this->startHunting();
        }
    }

    private function startHunting()
    {
        $creature = self::getGameStateValue('creature_player');
        $this->gamestate->changeActivePlayer($creature);
        $this->giveExtraTime($creature);
        $this->gamestate->nextState('hunting');
        $placeCardsPlayed = self::getDoubleKeyCollectionFromDB("SELECT hunted_player_id, place_number, location FROM hunted_place_card WHERE location = 'PLAYED'", true);
        $players = self::loadPlayersBasicInfos();
        $playerId = self::getPlayerAfter($creature);
        while ($playerId != $creature) {
            $places = array_keys($placeCardsPlayed[$playerId]);
            if (sizeof($places) == 1) {
                self::notifyAllPlayers("explorationDone", clienttranslate('${player_name} plays one Place card.'),
                    array('playerId' => $playerId, 'player_name' => $players[$playerId]['player_name'], 'played' => sizeof($places)));
            } else {
                $placePendingEffect = self::getUniqueValueFromDB("SELECT place_pending_effect FROM player WHERE player_id = $playerId");
                self::notifyAllPlayers("explorationDone", clienttranslate('${player_name} uses ${place_icon} ${place_name} and plays 2 Place cards.'),
                    array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => $players[$playerId]['player_name'], 'played' => sizeof($places),
                        'place_name' => $this->placeCards[$placePendingEffect]['name'], 'place_icon' => $this->getPlaceIcon($placePendingEffect)));
            }
            self::notifyPlayer($playerId, "myExplorationDone", '', array('places' => $places));
            $playerId = $this->getPlayerAfter($playerId);
        }
    }

    private function getHuntedPlayers()
    {
        return array_diff(array_keys(self::loadPlayersBasicInfos()), [self::getGameStateValue('creature_player')]);
    }

    private function activateHuntedPlayers()
    {
        $players = $this->getHuntedPlayers();
        $this->gamestate->setPlayersMultiactive($players, '', true);
        foreach ($players as $playerId) {
            $this->giveExtraTime($playerId);
        }
    }

    private function isArtemiaSymbolUnderRescueCounter()
    {
        $boardSide = self::getGameStateValue('board_side');
        $rescueCounter = self::getGameStateValue('rescue_counter');
        return $boardSide == 1 && $rescueCounter <= 6 || $boardSide == 2 && $rescueCounter % 2 == 1 && $rescueCounter <= 11;
    }

    private function isPlacePowerIneffective($place)
    {
        return self::getGameStateValue('targeted_places_ineffective') == 1 && in_array($place, $this->getTargetedPlaces())
            || ($place == 4 || $place == 8) && self::getGameStateValue('interference') == 1
            || self::getGameStateValue('cataclysm') == $place;
    }

    private function getCreaturePlaces()
    {
        $creaturePlaces = array(self::getGameStateValue('creature_token'));
        if (self::getGameStateValue('clone') == 1) {
            $creaturePlaces = array_unique(array_merge($creaturePlaces, $this->getTargetedPlaces()));
        }
        return $creaturePlaces;
    }

    private function getArtemiaPlaces()
    {
        return $this->getTokenPlaces(self::getGameStateValue('artemia_token'));
    }

    private function getTargetedPlaces()
    {
        return $this->getTokenPlaces(self::getGameStateValue('target_token'));
    }

    private function getTokenPlaces($token)
    {
        switch ($token) {
            case 0:
                return [];
            case 11:
                return array(1, 2);
            case 12:
                return array(2, 3);
            case 13:
                return array(3, 4);
            case 14:
                return array(4, 5);
            case 15:
                return array(1, 6);
            case 16:
                return array(2, 7);
            case 17:
                return array(3, 8);
            case 18:
                return array(4, 9);
            case 19:
                return array(5, 10);
            case 20:
                return array(6, 7);
            case 21:
                return array(7, 8);
            case 22:
                return array(8, 9);
            case 23:
                return array(9, 10);
            default:
                return array($token);
        }
    }

    private function getPlacesAdjacentToToken($token)
    {
        switch ($token) {
            case 1:
                return array(2, 6);
            case 2:
                return array(1, 3, 7);
            case 3:
                return array(2, 4, 8);
            case 4:
                return array(3, 5, 9);
            case 5:
                return array(4, 10);
            case 6:
                return array(1, 7);
            case 7:
                return array(2, 6, 8);
            case 8:
                return array(3, 7, 9);
            case 9:
                return array(4, 8, 10);
            case 10:
                return array(5, 9);
            case 11:
                return array(3, 6, 7);
            case 12:
                return array(1, 4, 7, 8);
            case 13:
                return array(2, 5, 8, 9);
            case 14:
                return array(3, 9, 10);
            case 15:
                return array(2, 7);
            case 16:
                return array(1, 3, 6, 8);
            case 17:
                return array(2, 4, 7, 9);
            case 18:
                return array(3, 5, 8, 10);
            case 19:
                return array(4, 9);
            case 20:
                return array(1, 2, 8);
            case 21:
                return array(2, 3, 6, 9);
            case 22:
                return array(3, 4, 7, 10);
            case 23:
                return array(4, 5, 8);
            default:
                return [];
        }
    }

    private function putMarkerCounterOnTheBeach($notificationText, $notificationData = [])
    {
        self::setGameStateValue('marker_counter', 1);
        $notificationData['markerCounter'] = 1;
        self::notifyAllPlayers("markerCounterMoved", $notificationText, $notificationData);
    }

    private function removeMarkerCounterFromTheBeach()
    {
        self::setGameStateValue('marker_counter', 0);
        self::notifyAllPlayers("markerCounterMoved", "", array('markerCounter' => 0));
    }

    private function moveRescueCounter($notificationText, $notificationData = [])
    {
        $rescueCounter = self::incGameStateValue('rescue_counter', -1);
        $notificationData['rescueCounter'] = $rescueCounter;
        $notificationData['begin_rescue'] = '<span class="rescueCounterName">';
        $notificationData['end_rescue'] = '</span>';
        self::notifyAllPlayers("rescueCounterMoved", $notificationText, $notificationData);
        $creaturePlayerId = self::getGameStateValue('creature_player');
        self::DbQuery("UPDATE player SET player_score = player_score + 1 WHERE player_id <> $creaturePlayerId");
        if ($rescueCounter == 0) {
            self::notifyAllPlayers("rescueArrived", clienttranslate("A rescue mission arrives to extract the survivors from this hostile planet. Hunted players win!"), []);
            $this->gamestate->nextState("endOfGame");
        }
        return $rescueCounter;
    }

    private function moveAssimilationCounter($notificationText, $notificationData = [])
    {
        $assimilationCounter = self::incGameStateValue('assimilation_counter', -1);
        $notificationData['assimilationCounter'] = $assimilationCounter;
        $notificationData['begin_assimilation'] = '<span class="assimilationCounterName">';
        $notificationData['end_assimilation'] = '</span>';
        self::notifyAllPlayers("assimilationCounterMoved", $notificationText, $notificationData);
        $creaturePlayerId = self::getGameStateValue('creature_player');
        self::DbQuery("UPDATE player SET player_score = player_score + 1 WHERE player_id = $creaturePlayerId");
        if ($assimilationCounter == 0) {
            self::notifyAllPlayers("huntedAssimilated", clienttranslate('With their will sapped, all of the Hunted have been assimilated. They are now part of the planet.'), []);
            $this->gamestate->nextState("endOfGame");
        }
        return $assimilationCounter;
    }

    private function removeWillCounter($playerId)
    {
        $willCounters = self::getUniqueValueFromDB("SELECT will_counters FROM player WHERE player_id = $playerId");
        if ($willCounters > 0) {
            $willCounters = $willCounters - 1;
            self::DbQuery("UPDATE player SET will_counters = $willCounters WHERE player_id = $playerId");
        } else {
            self::notifyAllPlayers("noWillCounterToLose", clienttranslate('Nothing happens because ${player_name} already lost its third ${will_icon}Will counter.'),
                array('playerId' => $playerId, 'player_name' => self::loadPlayersBasicInfos()[$playerId]['player_name'], 'will_icon' => $this->getWillIcons()));
        }
    }

    private function hasPlaceCard($playerId, $placeNumber)
    {
        return self::getObjectFromDB("SELECT 1 FROM hunted_place_card
			WHERE hunted_player_id = $playerId
			AND place_number = $placeNumber") != null;
    }

    private function activePlayerTakesBackDiscardedPlace($place)
    {
        $playerId = self::getActivePlayerId();
        $this->takeBackFromDiscard($playerId, $place);
        self::notifyAllPlayers("discardedPlaceTakenBack", clienttranslate('${player_name} takes back ${place_icon} ${place_name} from its discard.'),
            array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => self::getActivePlayerName(), 'place' => $place,
                'place_name' => $this->placeCards[$place]['name'], 'place_icon' => $this->getPlaceIcon($place)));
    }

    private function takeBackFromDiscard($playerId, $place)
    {
        $discardOrder = self::getUniqueValueFromDB("SELECT discard_order FROM hunted_place_card WHERE hunted_player_id = $playerId AND place_number = $place AND location = 'DISCARD'");
        if ($discardOrder == null) {
            throw new BgaVisibleSystemException("This place is not in your discard.");
        }
        self::DbQuery("UPDATE hunted_place_card SET location = 'HAND' WHERE hunted_player_id = $playerId AND place_number = $place");
        self::DbQuery("UPDATE hunted_place_card SET discard_order = discard_order - 1 WHERE hunted_player_id = $playerId AND discard_order > $discardOrder");
    }

    // If you copy The Jungle or The Swamp, The Lair becomes "this card" (see https://boardgamegeek.com/thread/1807360/lair-ability-clarification)
    private function takeBackResolvedPlace()
    {
        $playerId = self::getActivePlayerId();
        $playedPlace = self::getUniqueValueFromDB("SELECT place_number FROM hunted_place_card WHERE location = 'RESOLVING'");
        $powerUsed = $this->gamestate->state()['name'] == 'theJungle' ? 2 : 6;
        self::DbQuery("UPDATE hunted_place_card SET location = 'HAND' WHERE location = 'RESOLVING'");
        if ($playedPlace == $powerUsed) {
            self::notifyAllPlayers("playedPlaceTakenBack", clienttranslate('${player_name} takes back ${place_icon} ${place_name} using its power.'),
                array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => self::getActivePlayerName(), 'place' => $playedPlace,
                    'place_name' => $this->placeCards[$playedPlace]['name'], 'place_icon' => $this->getPlaceIcon($playedPlace)));
        } else {
            self::notifyAllPlayers("playedPlaceTakenBack", clienttranslate('${player_name} takes back ${place_icon_1} ${place_name_1} using the power of ${place_icon_2} ${place_name_2}.'),
                array('i18n' => array('place_name_1', 'place_name_2'), 'playerId' => $playerId, 'player_name' => self::getActivePlayerName(), 'place' => $playedPlace,
                    'place_name_1' => $this->placeCards[$playedPlace]['name'],
                    'place_icon_1' => $this->getPlaceIcon($playedPlace),
                    'place_name_2' => $this->placeCards[$powerUsed]['name'],
                    'place_icon_2' => $this->getPlaceIcon($powerUsed)));
        }
    }

    private function isAdjacentPlaces($place1, $place2)
    {
        switch ($place1) {
            case 1:
                return $place2 == 2 || $place2 == 6;
                break;
            case 2:
                return $place2 == 1 || $place2 == 3 || $place2 == 7;
                break;
            case 3:
                return $place2 == 2 || $place2 == 4 || $place2 == 8;
                break;
            case 4:
                return $place2 == 3 || $place2 == 5 || $place2 == 9;
                break;
            case 5:
                return $place2 == 4 || $place2 == 10;
                break;
            case 6:
                return $place2 == 1 || $place2 == 7;
                break;
            case 7:
                return $place2 == 2 || $place2 == 6 || $place2 == 8;
                break;
            case 8:
                return $place2 == 3 || $place2 == 7 || $place2 == 9;
                break;
            case 9:
                return $place2 == 4 || $place2 == 8 || $place2 == 10;
                break;
            case 10:
                return $place2 == 5 || $place2 == 9;
                break;
            default:
                throw new BgaVisibleSystemException("This is not a place number: " . $place1);
        }
    }

    private function getHuntedPlayersLocations($filterStatus = null, $filterPlaces = null)
    {
        $huntedLocations = [];
        if ($filterStatus == null) {
            $huntedPlayedPlaces = self::getDoubleKeyCollectionFromDB("SELECT hunted_player_id, place_number, location FROM hunted_place_card WHERE location IN ('REVEALED', 'RESOLVING', 'RESOLVED')");
        } else {
            $huntedPlayedPlaces = self::getDoubleKeyCollectionFromDB("SELECT hunted_player_id, place_number, location FROM hunted_place_card WHERE location = '$filterStatus'");
        }
        foreach ($huntedPlayedPlaces as $huntedId => $playedPlaces) {
            $locations = $this->getLocations($huntedId, array_keys($playedPlaces));
            if ($filterPlaces != null) {
                $locations = array_intersect($locations, $filterPlaces);
            }
            if (!empty($locations)) {
                $huntedLocations[$huntedId] = $locations;
            }
        }
        return $huntedLocations;
    }

    private function getLocations($huntedId, $playedPlaces = null)
    {
        if ($playedPlaces == null) {
            $playedPlaces = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $huntedId AND location IN ('REVEALED', 'RESOLVED')", true);
        }
        $locations = $playedPlaces;
        if ($huntedId == self::getGameStateValue('detour_target_hunted')) {
            $detourIndex = array_search(self::getGameStateValue('detour_origin'), $locations);
            if (is_int($detourIndex)) {
                $locations[$detourIndex] = self::getGameStateValue('detour_destination');
            }
        }
        return $locations;
    }

    private function hasTakenDetourFrom($huntedId, $place)
    {
        return $huntedId == self::getGameStateValue('detour_target_hunted') && $place == self::getGameStateValue('detour_origin');
    }

    private function hasTakenDetourTo($huntedId, $place)
    {
        return $huntedId == self::getGameStateValue('detour_target_hunted') && $place == self::getGameStateValue('detour_destination');
    }

    private function getHuntedResolvingLocation($huntedId)
    {
        $resolvingPlace = self::getUniqueValueFromDB("SELECT place_number FROM hunted_place_card WHERE location = 'RESOLVING'");
        if ($this->hasTakenDetourFrom($huntedId, $resolvingPlace)) {
            return self::getGameStateValue('detour_destination');
        } else {
            return $resolvingPlace;
        }
    }

    private function startHuntedLocationResolution($huntedId, $location)
    {
        if ('REVEALED' === self::getUniqueValueFromDB("SELECT location FROM hunted_place_card WHERE hunted_player_id = $huntedId AND place_number = $location")) {
            self::DbQuery("UPDATE hunted_place_card SET location = 'RESOLVING' WHERE hunted_player_id = $huntedId AND place_number = $location");
        } else {
            $revealedPlace = self::getGameStateValue('detour_origin');
            self::DbQuery("UPDATE hunted_place_card SET location = 'RESOLVING' WHERE hunted_player_id = $huntedId AND place_number = $revealedPlace");
        }
        $this->gamestate->nextState($location);
    }

    private function hasAtLeastOnePlaceCard($huntedId)
    {
        return self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $huntedId AND location = 'HAND'") > 0;
    }

    private function getPlaceIcon($place)
    {
        return '<span class="place-icon place' . $place . '"></span>';
    }

    private function getPlayerNameForNotification($player)
    {
        return '<span class="playername"><span class="playername" style="color:#' . $player['player_color'] . ';">' . $player['player_name'] . '</span></span>';
    }

    private function cardLeftThisTurnFor($playerId)
    {
        return self::getUniqueValueFromDB("SELECT cards_left_this_turn FROM player WHERE player_id = $playerId");
    }

    private function prepareNotificationDataWithCard($deck, $card, &$notificationData = [], $cardRoot = 'card')
    {
        $id = uniqid();
        $cards = $deck == 'hunt' ? $this->huntCards : $this->survivalCards;
        if (!array_key_exists('i18n', $notificationData)) {
            $notificationData['i18n'] = [];
        }
        $notificationData['i18n'][] = $cardRoot . '_name';
        $notificationData[$cardRoot . 'NameId'] = $id;
        $notificationData['begin_' . $cardRoot] = '<span id="' . $id . '" class="cardName" data-deck="' . $deck . '" data-value="' . $card . '">';
        $notificationData[$cardRoot . '_name'] = $cards[$card]['name'];
        $notificationData['end_' . $cardRoot] = '</span>';
        return $notificationData;
    }

    private function getWillIcons($quantity = 1)
    {
        return str_repeat('<span class="will-icon"></span>', $quantity);
    }

    /**
     * If a player got caught twice by the Artemia token because of a combination of Virus and The Artefact, he discards 2 cards.
     * With Mutation, he loses 2 Will counters.
     */
    private function numberOfTimeCaughtByArtemiaToken($playerId)
    {
        $artemiaPlaces = join(',', $this->getArtemiaPlaces());
        return self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND location IN ('REVEALED', 'RESOLVING', 'RESOLVED') AND place_number IN ($artemiaPlaces)");
    }

    /**
     * If a player got caught twice by the Artemia token because of a combination of Virus and The Artefact, he discards 2 cards.
     */
    private function numberOfPlacesToDiscardBecauseOfArtemiaToken($playerId)
    {
        return min($this->numberOfTimeCaughtByArtemiaToken($playerId), self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'HAND'"));
    }

    private function cancelExploration()
    {
        // Each Hunted player who already played a Place takes it back into their hand.
        self::DbQuery("UPDATE hunted_place_card SET location = 'HAND' WHERE location = 'PLAYED'");
        $playerPlaceCardsQuantity = self::getCollectionFromDB("SELECT hunted_player_id id, count(*) quantity FROM hunted_place_card WHERE location = 'HAND' GROUP BY hunted_player_id", true);
        self::notifyAllPlayers("explorationCancelled", "", $playerPlaceCardsQuantity);
    }

    private function mightPlayACard($playerId)
    {
        if ($playerId == self::getGameStateValue('creature_player')) {
            return self::getGameStateValue('sacrifice') == 0 && $this->cardLeftThisTurnFor($playerId) > 0 && !empty($this->huntDeck->getCardsInLocation('hand', $playerId));
        } else {
            return self::getGameStateValue('despair') == 0 && $this->cardLeftThisTurnFor($playerId) > 0 && !empty($this->survivalDeck->getCardsInLocation('hand', $playerId));
        }
    }

    private function addToDiscard($playerId, $place)
    {
        $discardSize = self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'DISCARD'");
        self::DbQuery("UPDATE hunted_place_card SET location = 'DISCARD', discard_order = $discardSize + 1 WHERE hunted_player_id = $playerId AND place_number = $place");
    }

    function survivalDeckShuffled()
    {
        self::notifyAllPlayers("survivalDeckShuffled", '', []);
    }

    function huntDeckShuffled()
    {
        self::notifyAllPlayers("huntDeckShuffled", '', []);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in notalone.action.php)
    */

    function chooseBoardSide($side)
    {
        self::checkAction('chooseBoardSide');
        if ($side != 1 && $side != 2) {
            throw new BgaVisibleSystemException("Illegal board side: " . $side);
        }
        self::setGameStateValue("board_side", $side);
        // The player playing the Creature draws 3 Hunt cards
        $creaturePlayerId = self::getGameStateValue('creature_player');
        $huntCards = $this->huntDeck->pickCards(3, 'deck', $creaturePlayerId);
        $this->gamestate->nextState();

        foreach (self::loadPlayersBasicInfos() as $playerId => $player) {
            if ($playerId != $creaturePlayerId) {
                $survivalCard = $this->survivalDeck->pickCard('deck', $playerId);
                self::notifyPlayer($playerId, "setupComplete", "", array('boardSide' => $side, 'survivalCard' => $survivalCard));
            } else {
                self::notifyPlayer($playerId, "huntCardsSeen", "", array('huntCards' => $huntCards));
                self::notifyPlayer($playerId, "setupComplete", "", array('boardSide' => $side));
            }
        }
    }

    function exploration($places)
    {
        $this->gamestate->checkPossibleAction('exploration');
        $playerId = self::getCurrentPlayerId();
        $willCounters = self::getUniqueValueFromDB("SELECT will_counters FROM player WHERE player_id = $playerId");
        if ($willCounters == 0) {
            throw new BgaUserException(self::_("You lost your 3 Will counters. You must Give Up."));
        }
        if (empty($places)) {
            // Cancelling exploration
            self::DbQuery("UPDATE hunted_place_card SET location = 'HAND' WHERE hunted_player_id = $playerId AND location = 'PLAYED'");
            $this->gamestate->setPlayersMultiactive(array($playerId), '');
            return;
        }
        $placePendingEffect = self::getUniqueValueFromDB("SELECT place_pending_effect FROM player WHERE player_id = $playerId");
        if ($placePendingEffect != null && sizeof($places) != 2) {
            throw new BgaVisibleSystemException("You must select 2 Place cards.");
        }
        if ($placePendingEffect == null && sizeof($places) != 1) {
            throw new BgaVisibleSystemException("You must select one Place card.");
        }
        $joinedPlaces = join(',', $places);
        if (sizeof($places) != self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND place_number IN ($joinedPlaces) AND location IN ('HAND', 'PLAYED')")) {
            throw new BgaVisibleSystemException("You can only play cards from your hand.");
        }
        if (!empty(array_intersect($places, $this->getTargetedPlaces()))) {
            throw new BgaUserException(self::_('You cannot play a Place targeted by "Force Field"'));
        }
        self::DbQuery("UPDATE hunted_place_card SET location = 'HAND' WHERE hunted_player_id = $playerId AND location = 'PLAYED'");
        self::DbQuery("UPDATE hunted_place_card SET location = 'PLAYED' WHERE hunted_player_id = $playerId AND place_number IN ($joinedPlaces)");
        if (in_array($playerId, $this->gamestate->getActivePlayerList())) {
            $this->setPlayerNonActiveDuringExploration($playerId);
        }
    }

    function resist($places)
    {
        $this->checkAction('resist');
        if (sizeof($places) == 0) {
            throw new BgaVisibleSystemException("You must take back at least one place card.");
        } else if (sizeof($places) > 2) {
            throw new BgaVisibleSystemException("You cannot only take 2 places cards at a time.");
        }
        $playerId = self::getCurrentPlayerId();
        $joinedPlaces = join(',', $places);
        if (sizeof($places) != self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND place_number IN ($joinedPlaces) AND location = 'DISCARD'")) {
            throw new BgaVisibleSystemException("You can only take back Place cards from your discard when you Resist.");
        }
        $willCounters = self::getUniqueValueFromDB("SELECT will_counters FROM player WHERE player_id = $playerId");
        if ($willCounters == 0) {
            throw new BgaUserException(self::_("You lost your 3 Will counters. You must Give Up."));
        }
        self::DbQuery("UPDATE player SET will_counters = $willCounters - 1 WHERE player_id = $playerId");
        $this->takeBackFromDiscard($playerId, $places[0]);
        if (sizeof($places) > 1) {
            $this->takeBackFromDiscard($playerId, $places[1]);
        }
        $placeCardsQuantity = self::getUniqueValueFromDb("SELECT count(*) FROM hunted_place_card WHERE location = 'HAND' AND hunted_player_id = '$playerId'");
        $notificationData = array('playerId' => $playerId, 'player_name' => self::getCurrentPlayerName(), 'places' => $places,
            'quantity' => 1, 'will_icon' => $this->getWillIcons());
        if (sizeof($places) == 2) {
            $notificationText = clienttranslate('${player_name} resists and loses ${will_icon}1 Will to take back ${place_icon_1} ${place_name_1} and ${place_icon_2} ${place_name_2} from its discard.');
            $notificationData['i18n'] = array('place_name_1', 'place_name_2');
            $notificationData['place_name_1'] = $this->placeCards[$places[0]]['name'];
            $notificationData['place_icon_1'] = $this->getPlaceIcon($places[0]);
            $notificationData['place_name_2'] = $this->placeCards[$places[1]]['name'];
            $notificationData['place_icon_2'] = $this->getPlaceIcon($places[1]);
            $notificationData['total_place_cards'] = $placeCardsQuantity;
        } else {
            $notificationText = clienttranslate('${player_name} resists and loses ${will_icon}1 Will to take back ${place_icon} ${place_name} from its discard.');
            $notificationData['i18n'] = array('place_name');
            $notificationData['place_name'] = $this->placeCards[$places[0]]['name'];
            $notificationData['place_icon'] = $this->getPlaceIcon($places[0]);
        }
        self::notifyAllPlayers("placeCardsTakenBack", $notificationText, $notificationData);
        self::notifyAllPlayers("willCounterLost", '', array('playerId' => $playerId));
    }

    function giveUp()
    {
        $this->checkAction('giveUp');
        $playerId = self::getCurrentPlayerId();
        $willCounters = self::getUniqueValueFromDB("SELECT will_counters FROM player WHERE player_id = $playerId");
        $discardPlaces = self::getCollectionFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'DISCARD'", true);
        if ($willCounters == 3 && empty($discardPlaces)) {
            throw new BgaUserException(self::_("You cannot Give Up: you have all your Will counters and all your places in hand."));
        }
        self::DbQuery("UPDATE player SET will_counters = 3 WHERE player_id = $playerId");
        self::DbQuery("UPDATE hunted_place_card SET location = 'HAND', discard_order = null WHERE hunted_player_id = $playerId AND location = 'DISCARD'");
        $placeCardsQuantity = self::getUniqueValueFromDb("SELECT count(*) FROM hunted_place_card WHERE location = 'HAND' AND hunted_player_id = '$playerId'");
        self::notifyAllPlayers("giveUp", clienttranslate('${player_name} gives up and take back its ${will_icons}3 Will counters and all its Place cards.'),
            array('playerId' => $playerId, 'player_name' => self::getCurrentPlayerName(), 'quantity' => 3, 'will_icons' => $this->getWillIcons(3),
            'total_place_cards' => $placeCardsQuantity));
        $this->moveAssimilationCounter(clienttranslate('The ${begin_assimilation}Assimilation counter${end_assimilation} moves forward!'));
    }

    function placeToken($tokenType, $position)
    {
        self::checkAction('placeToken');
        if ($this->gamestate->state()['name'] == 'forceField' && $tokenType != 'target') {
            throw new BgaVisibleSystemException("You can only place the target token with Force Field.");
        }
        $tokenState = self::getGameStateValue($tokenType . '_token');
        if ($tokenState == 0) {
            throw new BgaVisibleSystemException("This token is not available.");
        } else if ($tokenState > 0) {
            throw new BgaVisibleSystemException("This token is already placed.");
        } else if ($tokenState == -1 && ($position < 1 || $position > 10) || $tokenState == -2 && ($position < 11 || $position > 23)) {
            throw new BgaVisibleSystemException("Invalid location for this token.");
        }
        self::setGameStateValue($tokenType . '_token', $position);
        $notificationData = array('player_name' => self::getActivePlayerName(), 'tokenType' => $tokenType, 'position' => $position);
        if ($position <= 10) {
            switch ($tokenType) {
                case 'creature':
                    $notificationText = clienttranslate('${player_name} puts the Creature token on ${place_icon} ${place_name}.');
                    break;
                case 'artemia':
                    $notificationText = clienttranslate('${player_name} puts the Artemia token on ${place_icon} ${place_name}.');
                    break;
                default:
                    $notificationText = clienttranslate('${player_name} puts the Target token on ${place_icon} ${place_name}.');
                    break;
            }
            $notificationData['i18n'] = array('place_name');
            $notificationData['place_name'] = $this->placeCards[$position]['name'];
            $notificationData['place_icon'] = $this->getPlaceIcon($position);
        } else {
            $places = $this->getTokenPlaces($position);
            switch ($tokenType) {
                case 'creature':
                    $notificationText = clienttranslate('${player_name} puts the Creature token between ${place_icon_1} ${place_name_1} and ${place_icon_2} ${place_name_2}.');
                    break;
                case 'artemia':
                    $notificationText = clienttranslate('${player_name} puts the Artemia token between ${place_icon_1} ${place_name_1} and ${place_icon_2} ${place_name_2}.');
                    break;
                default:
                    $notificationText = clienttranslate('${player_name} puts the Target token between ${place_icon_1} ${place_name_1} and ${place_icon_2} ${place_name_2}.');
                    break;
            }
            $notificationData['i18n'] = array('place_name_1', 'place_name_2');
            $notificationData['place_name_1'] = $this->placeCards[$places[0]]['name'];
            $notificationData['place_icon_1'] = $this->getPlaceIcon($places[0]);
            $notificationData['place_name_2'] = $this->placeCards[$places[1]]['name'];
            $notificationData['place_icon_2'] = $this->getPlaceIcon($places[1]);
        }
        self::notifyAllPlayers("tokenPlaced", $notificationText, $notificationData);
        if ($this->gamestate->state()['name'] == 'forceField') {
            $this->gamestate->nextState("exploration");
        } else {
            $this->stHunting();
        }
    }

    function swapPlaceCard($place, $swappedPlace)
    {
        self::checkAction('swapPlaceCard');
        $playerId = self::getActivePlayerId();
        $discardOrder = self::getUniqueValueFromDB("SELECT discard_order FROM hunted_place_card WHERE hunted_player_id = $playerId AND place_number = $place AND location = 'DISCARD'");
        if ($discardOrder == null) {
            throw new BgaVisibleSystemException("This place is not in your discard.");
        }
        if (self::getUniqueValueFromDB("SELECT location FROM hunted_place_card WHERE hunted_player_id = $playerId AND place_number = $swappedPlace") != 'PLAYED') {
            throw new BgaVisibleSystemException("You did no play this place.");
        }
        self::DbQuery("UPDATE hunted_place_card SET location = 'DISCARD', discard_order = $discardOrder WHERE hunted_player_id = $playerId AND place_number = $swappedPlace");
        self::DbQuery("UPDATE hunted_place_card SET location = 'PLAYED', discard_order = null WHERE hunted_player_id = $playerId AND place_number = $place");
        self::notifyAllPlayers("placeCardSwapped", clienttranslate('${player_name} swaps ${swapped_place_icon} ${swapped_place_name} with ${place_icon} ${place_name} from its discard.'),
            array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => self::getActivePlayerName(),
                'place' => $place, 'place_name' => $this->placeCards[$place]['name'], 'place_icon' => $this->getPlaceIcon($place),
                'swappedPlace' => $swappedPlace, 'swapped_place_name' => $this->placeCards[$swappedPlace]['name'], 'swapped_place_icon' => $this->getPlaceIcon($swappedPlace)));
        $this->gamestate->nextState('');
        $creature = self::getGameStateValue('creature_player');
        $this->gamestate->changeActivePlayer($creature);
        $this->giveExtraTime($creature);
        $this->gamestate->nextState('hunting');
    }

    function takeBackDiscardedPlaceCards()
    {
        self::checkAction('takeBackDiscardedPlaceCards');
        $playerId = self::getActivePlayerId();
        $number = self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'DISCARD'");
        self::DbQuery("UPDATE hunted_place_card SET location = 'HAND', discard_order = null WHERE hunted_player_id = $playerId AND location = 'DISCARD'");
        $placeCardsQuantity = self::getUniqueValueFromDb("SELECT count(*) FROM hunted_place_card WHERE location = 'HAND' AND hunted_player_id = '$playerId'");
        self::notifyAllPlayers("discardPlacesTakenBack", clienttranslate('${player_name} uses ${lair_icon} ${the_lair} to take back all its places from its discard.'),
            array('i18n' => array('the_lair'), 'playerId' => $playerId, 'player_name' => self::getActivePlayerName(),
                'the_lair' => $this->placeCards[1]['name'], 'lair_icon' => $this->getPlaceIcon(1), 'number' => $number, 'total_place_cards' => $placeCardsQuantity));
        $this->gamestate->nextState("continue");
    }

    function copyCreaturePlace($place)
    {
        self::checkAction('copyCreaturePlace');
        if (!in_array($place, $this->getCreaturePlaces())) {
            throw new BgaVisibleSystemException("The Creature is not on this place.");
        }
        if ($place == 10) {
            throw new BgaUserException(self::_("You may not copy the Artefact."));
        } else if ($place == 1) {
            throw new BgaUserException(self::_("Congratulation, you invented the perpetual motion!"));
        }
        $this->gamestate->nextState($place);
        self::notifyAllPlayers("creatureLocationCopied", clienttranslate('${player_name} uses ${lair_icon} ${the_lair} to copy ${place_icon} ${place_name}.'),
            array('i18n' => array('the_lair', 'place_name'),
                'player_name' => self::getActivePlayerName(),
                'the_lair' => $this->placeCards[1]['name'],
                'lair_icon' => $this->getPlaceIcon(1),
                'place_name' => $this->placeCards[$place]['name'],
                'place_icon' => $this->getPlaceIcon($place)));
    }

    function theJungle($place)
    {
        self::checkAction('theJungle');
        $this->takeBackResolvedPlace();
        $this->activePlayerTakesBackDiscardedPlace($place);
        $this->gamestate->nextState("continue");
    }

    function theRiver()
    {
        self::checkAction('theRiver');
        $playerId = self::getActivePlayerId();
        $placePendingEffect = self::getUniqueValueFromDB("SELECT place_pending_effect FROM player WHERE player_id = $playerId");
        if ($placePendingEffect != null) {
            throw new BgaUserException(self::_("You cannot use The River and The Artefact at the same turn."));
        }
        self::DbQuery("UPDATE player SET place_pending_effect = 3 WHERE player_id = $playerId");
        self::notifyAllPlayers("riverUsed", clienttranslate('${player_name} uses ${place_icon} ${place_name}.'),
            array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => self::getActivePlayerName(),
                'place_name' => $this->placeCards[3]['name'], 'place_icon' => $this->getPlaceIcon(3)));
        $this->gamestate->nextState("continue");
    }

    function chooseRiverPlaceCard($place)
    {
        self::checkAction('chooseRiverPlaceCard');
        $playerId = self::getCurrentPlayerId();
        if (self::getObjectFromDB("SELECT 1 FROM hunted_place_card WHERE hunted_player_id = $playerId AND place_number = $place AND location = 'PLAYED'") == null) {
            throw new BgaUserException(self::_("You must select one of the Places you played this turn."));
        }
        self::DbQuery("UPDATE hunted_place_card SET location = 'HAND' WHERE hunted_player_id = $playerId AND location = 'PLAYED' AND place_number <> $place");
        self::notifyAllPlayers("chooseRiverPlaceCard", "", array('playerId' => $playerId, 'place' => $place));
        $this->gamestate->setPlayerNonMultiactive($playerId, "");
    }

    function theBeach()
    {
        self::checkAction('theBeach');
        if (self::getGameStateValue('beach_used') == 1) {
            throw new BgaUserException(self::_("The Beach has already been used this turn."));
        }
        $notificationData = array('i18n' => array('the_beach'), 'playerId' => self::getActivePlayerId(), 'player_name' => self::getActivePlayerName(),
            'the_beach' => $this->placeCards[4]['name'], 'beach_icon' => $this->getPlaceIcon(4));
        if (self::getGameStateValue('marker_counter') == 0) {
            $this->putMarkerCounterOnTheBeach(clienttranslate('${player_name} places the Marker counter on ${beach_icon} ${the_beach}.'), $notificationData);
        } else {
            $this->removeMarkerCounterFromTheBeach();
            $rescueCounter = $this->moveRescueCounter(clienttranslate('${player_name} removes the Marker counter from ${beach_icon} ${the_beach} and moves the ${begin_rescue}Rescue counter${end_rescue} forward 1 space.'), $notificationData);
            if ($rescueCounter == 0) {
                return; // End of game
            }
        }
        self::setGameStateValue('beach_used', 1);
        $this->gamestate->nextState("continue");
    }

    function theRover($place)
    {
        self::checkAction('theRover');
        if ($place < 1 || $place > 10) {
            throw new BgaVisibleSystemException("Illegal place number: " . $place);
        }
        $playerId = self::getActivePlayerId();
        if ($this->hasPlaceCard($playerId, $place)) {
            throw new BgaUserException(self::_("You already own this Place card"));
        }
        $reserve = self::getUniqueValueFromDB("SELECT quantity FROM place_card_reserve WHERE place_number = $place");
        if ($reserve < 1) {
            throw new BgaUserException(self::_("This place is no longer available in the reserve"));
        }
        self::DbQuery("UPDATE place_card_reserve SET quantity = quantity-1 WHERE place_number = $place");
        self::DbQuery("INSERT INTO hunted_place_card (hunted_player_id, place_number, location) VALUES ($playerId, $place, 'HAND')");
        self::notifyAllPlayers("roverUsed", clienttranslate('${player_name} uses ${rover_icon} ${the_rover} to take ${place_icon} ${place_name} from the reserve.'),
            array('i18n' => array('the_rover', 'place_name'), 'player' => $playerId, 'player_name' => self::getActivePlayerName(), 'place' => $place, 'quantityLeft' => $reserve - 1,
                'the_rover' => $this->placeCards[5]['name'],
                'rover_icon' => $this->getPlaceIcon(5),
                'place_name' => $this->placeCards[$place]['name'],
                'place_icon' => $this->getPlaceIcon($place)));
        $this->gamestate->nextState("continue");
    }

    function theSwamp($places)
    {
        $this->checkAction('theSwamp');
        if (count($places) > 2) {
            throw new BgaUserException(self::_("You cannot take back more than 2 Place cards"));
        }
        $this->takeBackResolvedPlace();
        foreach ($places as $place) {
            $this->activePlayerTakesBackDiscardedPlace($place);
        }
        $this->gamestate->nextState("continue");
    }

    function theShelter()
    {
        self::checkAction('theShelter');
        if (self::getGameStateValue('despair') == 1) {
            throw new BgaUserException(self::_('No survival cards can be drawn this turn because the Creature played "Despair".'));
        }
        $survivalCards = $this->survivalDeck->pickCardsForLocation(2, 'deck', 'choice');
        if ($survivalCards == null) {
            throw new BgaUserException(self::_("All the Survival cards are already in the Hunted players hands"));
        }
        self::notifyAllPlayers("shelterUsed", clienttranslate('${player_name} uses ${place_icon} ${place_name} to draw 2 Survival cards.'),
            array('i18n' => array('place_name'), 'player_name' => self::getActivePlayerName(),
                'place_name' => $this->placeCards[7]['name'], 'place_icon' => $this->getPlaceIcon(7)));
        $this->gamestate->nextState("chooseSurvivalCard");
    }

    function chooseSurvivalCard($cardName)
    {
        self::checkAction('chooseSurvivalCard');
        $cards = $this->survivalDeck->getCardsOfType($cardName);
        if (empty($cards)) {
            throw new BgaVisibleSystemException("Unexpected survival card: " . $cardName);
        }
        $card = array_pop($cards);
        if ($card['location'] != 'choice') {
            throw new BgaVisibleSystemException("This survival card cannot be chosen: " . $cardName);
        }
        $playerId = self::getActivePlayerId();
        $this->survivalDeck->moveCard($card['id'], 'hand', $playerId);
        $remainingChoice = $this->survivalDeck->getCardsInLocation('choice');
        $discardedCard = array_pop($remainingChoice);
        $this->survivalDeck->insertCardOnExtremePosition($discardedCard['id'], 'discard', true);
        $notificationData = $this->prepareNotificationDataWithCard('survival', $discardedCard['type']);
        $notificationData['playerId'] = $playerId;
        $notificationData['player_name'] = self::getActivePlayerName();
        $notificationData['discardedCard'] = $discardedCard['type'];
        self::notifyAllPlayers("survivalCardChosen", clienttranslate('${player_name} choose a Survival card and discards ${begin_card}${card_name}${end_card}.'), $notificationData);
        $this->gamestate->nextState("playSurvivalCardDrawn");
    }

    function theWreck()
    {
        self::checkAction('theWreck');
        if (self::getGameStateValue('wreck_used') == 1) {
            throw new BgaUserException(self::_("The Wreck has already been used this turn."));
        }
        $rescueCounter = $this->moveRescueCounter(clienttranslate('${player_name} uses ${place_icon} ${place_name} to move the ${begin_rescue}Rescue counter${end_rescue} forward 1 space.'),
            array('i18n' => array('place_name'), 'playerId' => self::getActivePlayerId(), 'player_name' => self::getActivePlayerName(),
                'place_name' => $this->placeCards[8]['name'], 'place_icon' => $this->getPlaceIcon(8)));
        self::setGameStateValue('wreck_used', 1);
        if ($rescueCounter != 0) {
            $this->gamestate->nextState("continue");
        }
    }

    function regainWill($playerId)
    {
        self::checkAction('regainWill');
        $willCounters = self::getUniqueValueFromDB("SELECT will_counters FROM player WHERE player_id = $playerId");
        if ($willCounters != 1 && $willCounters != 2) {
            throw new BgaVisibleSystemException("Illegal player: " . $playerId);
        }
        self::DbQuery("UPDATE player SET will_counters = $willCounters+1 WHERE player_id = $playerId");
        $activePlayerId = self::getActivePlayerId();
        if ($playerId == $activePlayerId) {
            self::notifyAllPlayers("willCounterRegained", clienttranslate('${player_name} chooses to regain ${will_icon}1 Will.'),
                array('playerId' => $playerId, 'player_name' => self::getActivePlayerName(), 'quantity' => 1, 'will_icon' => $this->getWillIcons()));
        } else {
            self::notifyAllPlayers("willCounterRegained", clienttranslate('${player_name} chooses ${other_player} to regain ${will_icon}1 Will.'),
                array('playerId' => $playerId, 'player_name' => self::getActivePlayerName(), 'other_player' => $this->getPlayerNameForNotification(self::loadPlayersBasicInfos()[$playerId]),
                    'quantity' => 1, 'will_icon' => $this->getWillIcons()));
        }
        $this->gamestate->nextState("continue");
    }

    function drawSurvivalCard()
    {
        self::checkAction('drawSurvivalCard');
        if (self::getGameStateValue('despair') == 1) {
            throw new BgaUserException(self::_('No survival cards can be drawn this turn because the Creature played "Despair".'));
        }
        $playerId = self::getActivePlayerId();
        $survivalCard = $this->survivalDeck->pickCard('deck', $playerId);
        self::notifyPlayer($playerId, "survivalCardSeen", "", array('survivalCard' => $survivalCard));
        self::notifyAllPlayers("survivalCardDrawn", clienttranslate('${player_name} draws a Survival card.'),
            array('playerId' => $playerId, 'player_name' => self::getActivePlayerName()));
        $this->gamestate->nextState("playSurvivalCardDrawn");
    }

    function theArtefact()
    {
        self::checkAction('theArtefact');
        $playerId = self::getActivePlayerId();
        $placePendingEffect = self::getUniqueValueFromDB("SELECT place_pending_effect FROM player WHERE player_id = $playerId");
        if ($placePendingEffect != null) {
            throw new BgaUserException(self::_("You cannot use The River and The Artefact at the same turn."));
        }
        self::DbQuery("UPDATE player SET place_pending_effect = 10 WHERE player_id = $playerId");
        self::notifyAllPlayers("artefactUsed", clienttranslate('${player_name} uses ${place_icon} ${place_name}.'),
            array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => self::getActivePlayerName(),
                'place_name' => $this->placeCards[10]['name'], 'place_icon' => $this->getPlaceIcon(10)));
        $this->gamestate->nextState("continue");
    }

    function chooseArtefactPlaceCard($place)
    {
        self::checkAction('chooseArtefactPlaceCard');
        $playerId = self::getActivePlayerId();
        if (!in_array($place, $this->getLocations($playerId))) {
            throw new BgaUserException(self::_("You must select one of the Places you played this turn."));
        }
        $this->startHuntedLocationResolution($playerId, $place);
    }

    function takeBackDiscardedPlace($place)
    {
        self::checkAction('takeBackDiscardedPlace');
        $this->activePlayerTakesBackDiscardedPlace($place);
        $this->gamestate->nextState("continue");
    }

    function takeBackPlayedCard($place = null)
    {
        self::checkAction('takeBackPlayedCard');
        if ($this->gamestate->state()['name'] == 'doubleBack') {
            $playerId = self::getActivePlayerId();
            if (self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND place_number = $place AND location IN ('REVEALED', 'RESOLVED')") != 1) {
                throw new BgaVisibleSystemException("You cannot take back this card");
            }
            self::DbQuery("UPDATE hunted_place_card SET location = 'HAND' WHERE hunted_player_id = $playerId AND place_number = $place");
            self::notifyAllPlayers("playedPlaceTakenBack", clienttranslate('${player_name} takes back ${place_icon} ${place_name}.'),
                array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => self::getCurrentPlayerName(), 'place' => $place,
                    'place_name' => $this->placeCards[$place]['name'], 'place_icon' => $this->getPlaceIcon($place)));
            $this->gamestate->nextState("");
        } else {
            $this->takeBackResolvedPlace();
            $this->gamestate->nextState("continue");
        }
    }

    function playHuntCard($cardName)
    {
        if (self::getGameStateValue('sacrifice') == 1) {
            throw new BgaUserException(self::_('No Hunt cards can be played this turn because the Hunted played "Sacrifice".'));
        }
        $playerId = self::getCurrentPlayerId();
        if ($this->cardLeftThisTurnFor($playerId) == 0) {
            throw new BgaUserException(self::_('You already played all the Hunt cards you were allowed to play this turn.'));
        }
        $cards = $this->huntDeck->getCardsOfType($cardName);
        if (empty($cards)) {
            throw new BgaVisibleSystemException("Unexpected hunt card: " . $cardName);
        }
        $card = array_pop($cards);
        if ($card['location'] != 'hand' && $card['card_location_arg'] != $playerId) {
            throw new BgaVisibleSystemException("This card is not in your hand: " . $cardName);
        }
        $phase = $this->huntCards[$cardName]['phase'];
        $notificationData = array('playerId' => $playerId, 'player_name' => self::getCurrentPlayerName());
        if ($cardName == 'flashback') {
            $lastDiscardedCard = $this->huntDeck->getCardOnTop('discard');
            if ($lastDiscardedCard == null) {
                throw new BgaUserException(self::_("You did not discard any Hunt card yet."));
            }
            $cardName = $lastDiscardedCard['type'];
            $phase = $this->huntCards[$cardName]['phase'];
            $notificationData['flashback'] = true;
            $this->prepareNotificationDataWithCard('hunt', 'flashback', $notificationData, 'flashback');
            $notificationText = clienttranslate('${player_name} copies ${begin_card}${card_name}${end_card} using ${begin_flashback}${flashback_name}${end_flashback}.');
        } else {
            $notificationData['flashback'] = false;
            $notificationText = clienttranslate('${player_name} plays ${begin_card}${card_name}${end_card}.');
        }
        $notificationData['card'] = $cardName;
        $this->prepareNotificationDataWithCard('hunt', $cardName, $notificationData);
        $notificationData['ongoingEffect'] = $this->huntCards[$cardName]['ongoingEffect'];
        $state = $this->gamestate->state();
        if ($phase != $state['phase']) {
            throw new BgaUserException(sprintf(self::_("This card cannot be played during phase %s"), $state['phase']));
        }
        // Cards preconditions
        switch ($cardName) {
            case 'force-field':
            case 'forbidden-zone':
                if (!in_array($cardName, array_keys($state['transitions']))) {
                    throw new BgaUserException(self::_("Please wait for ongoing card action to be resolved."));
                }
                break;
            case 'anticipation':
            case 'cataclysm':
            case 'detour':
                if (!in_array("changeActivePlayer", array_keys($state['transitions']))) {
                    throw new BgaUserException(self::_("Please wait for ongoing card action to be resolved."));
                }
                break;
            case 'ascendancy':
                if (!in_array("changeActivePlayer", array_keys($state['transitions']))) {
                    throw new BgaUserException(self::_("Please wait for ongoing card action to be resolved."));
                }
                if (empty(self::getObjectListFromDB("SELECT hunted_player_id FROM hunted_place_card WHERE location = 'HAND' GROUP BY hunted_player_id HAVING count(*) > 2", true))) {
                    throw new BgaUserException(self::_("No hunted has more than 2 place cards in hand."));
                }
            case 'phobia':
                if (!in_array("changeActivePlayer", array_keys($state['transitions']))) {
                    throw new BgaUserException(self::_("Please wait for ongoing card action to be resolved."));
                }
                break;
        }
        $this->huntDeck->insertCardOnExtremePosition($card['id'], 'played', true);
        self::DbQuery("UPDATE player SET cards_left_this_turn = cards_left_this_turn - 1 WHERE player_id = $playerId");
        self::notifyAllPlayers("huntCardPlayed", $notificationText, $notificationData);
        // Cards effects
        switch ($cardName) {
            case 'despair':
                $this->cancelExploration();
                if ($this->gamestate->state()['name'] == 'exploration') {
                    $this->activateHuntedPlayers();
                }
                self::setGameStateValue($cardName, 1);
                break;
            case 'fierceness':
            case 'interference':
            case 'persecution':
            case 'mutation':
            case 'clone':
            case 'scream':
            case 'stasis':
            case 'tracking':
                self::setGameStateValue($cardName, 1);
                break;
            case 'force-field':
                $this->cancelExploration();
                self::setGameStateValue('target_token', -2);
                $this->gamestate->changeActivePlayer($playerId);
                $this->giveExtraTime($playerId);
                $this->gamestate->nextState($cardName);
                break;
            case 'anticipation':
            case 'ascendancy':
                $this->gamestate->nextState("changeActivePlayer");
                $this->gamestate->changeActivePlayer($playerId);
                $this->giveExtraTime($playerId);
                $this->gamestate->nextState($cardName);
                break;
            case 'phobia':
                if (!empty(self::getObjectListFromDB("SELECT hunted_player_id FROM hunted_place_card WHERE location = 'HAND' GROUP BY hunted_player_id HAVING count(*) > 2", true))) {
                    $this->gamestate->nextState("changeActivePlayer");
                    $this->gamestate->changeActivePlayer($playerId);
                    $this->giveExtraTime($playerId);
                    $this->gamestate->nextState($cardName);
                }
                break;
            case 'forbidden-zone':
                $this->gamestate->nextState($cardName);
                $players = array_filter($this->getHuntedPlayers(), array($this, 'hasAtLeastOnePlaceCard'));
                $this->gamestate->setPlayersMultiactive($players, '', true);
                foreach ($players as $playerId) {
                    $this->giveExtraTime($playerId);
                }
                break;
            case 'virus':
                if (self::getGameStateValue('artemia_token') > 0) {
                    $notificationData = $this->prepareNotificationDataWithCard('hunt', 'virus');
                    $notificationData['player_name'] = self::getCurrentPlayerName();
                    $notificationData['tokenType'] = 'artemia';
                    $notificationData['card'] = 'virus';
                    self::notifyAllPlayers("tokenTakenBack", clienttranslate('Using ${begin_card}${card_name}${end_card}, ${player_name} can take back the Artemia token and replace it on adjacent places.'), $notificationData);
                }
                self::setGameStateValue('artemia_token', -2);
                break;
            case 'mirage':
                if (self::getGameStateValue('target_token') > 0) {
                    $notificationData = $this->prepareNotificationDataWithCard('hunt', 'mirage');
                    $notificationData['player_name'] = self::getCurrentPlayerName();
                    $notificationData['tokenType'] = 'target';
                    $notificationData['card'] = 'mirage';
                    self::notifyAllPlayers("tokenTakenBack", clienttranslate('Using ${begin_card}${card_name}${end_card}, ${player_name} can take back the Target token and replace it on adjacent places.'), $notificationData);
                }
                self::setGameStateValue('targeted_places_ineffective', 1);
                self::setGameStateValue('target_token', -2);
                break;
            case 'toxin':
                self::setGameStateValue('targeted_places_ineffective', 1);
                self::setGameStateValue('toxin', 1);
                break;
            case 'cataclysm':
            case 'detour':
                // Rollback current place resolution in case of a interruption
                self::DbQuery("UPDATE hunted_place_card SET location = 'REVEALED' WHERE location = 'RESOLVING'");
                $this->gamestate->nextState("changeActivePlayer");
                $this->gamestate->changeActivePlayer($playerId);
                $this->giveExtraTime($playerId);
                $this->gamestate->nextState($cardName);
                break;
        }
        if ($this->huntCards[$cardName]['symbol'] != null) {
            switch ($this->huntCards[$cardName]['symbol']) {
                case 'Artemia':
                    if (self::getGameStateValue('artemia_token') == 0) {
                        self::setGameStateValue('artemia_token', -1);
                    }
                    break;
                case 'Target':
                    if (self::getGameStateValue('target_token') == 0) {
                        self::setGameStateValue('target_token', -1);
                    }
                    break;
            }
            switch ($this->gamestate->state()['name']) {
                case 'hunting':
                    // Loop on state to force refresh
                    $this->gamestate->nextState('refreshState');
                    break;
                case 'phase2Cards':
                    $this->gamestate->nextState('changeActivePlayer');
                    $this->gamestate->changeActivePlayer($playerId);
                    $this->giveExtraTime($playerId);
                    $this->gamestate->nextState('hunting');
                    break;
            }
        }
        $state = $this->gamestate->state();
        if ($state['type'] == 'multipleactiveplayer'
            && in_array('pass', $state['possibleactions'])
            && in_array($playerId, $this->gamestate->getActivePlayerList())
            && !$this->mightPlayACard($playerId)) {
            $this->pass();
        }
    }

    function playSurvivalCard($cardName)
    {
        if (self::getGameStateValue('despair') == 1) {
            throw new BgaUserException(self::_('No Survival cards can be played this turn because the Creature played "Despair".'));
        }
        $playerId = self::getCurrentPlayerId();
        if ($this->cardLeftThisTurnFor($playerId) == 0) {
            throw new BgaUserException(self::_('You may only play one Survival card per turn.'));
        }
        $cards = $this->survivalDeck->getCardsOfType($cardName);
        if (empty($cards)) {
            throw new BgaVisibleSystemException("Unexpected survival card: " . $cardName);
        }
        $card = array_pop($cards);
        if ($card['location'] != 'hand' && $card['card_location_arg'] != $playerId) {
            throw new BgaVisibleSystemException("This card is not in your hand: " . $cardName);
        }
        $state = $this->gamestate->state();
        if ($this->survivalCards[$cardName]['phase'] != $state['phase']) {
            throw new BgaUserException(sprintf(self::_("This card cannot be played during phase %s"), $state['phase']));
        }
        // Cards preconditions
        switch ($cardName) {
            case 'adrenaline':
                if (self::getUniqueValueFromDB("SELECT will_counters FROM player WHERE player_id = $playerId") == 3) {
                    throw new BgaUserException(self::_("You already have your 3 Will counters."));
                }
                break;
            case 'ingenuity':
                if (self::getGameStateValue('marker_counter') == 1) {
                    throw new BgaUserException(self::_("The marker counter is already on The Beach."));
                }
                break;
            case 'sacrifice':
                if ($this->cardLeftThisTurnFor(self::getGameStateValue('creature_player')) == 0) {
                    throw new BgaUserException(self::_("The Creature already played a Hunt card."));
                }
                if (!in_array($cardName, array_keys($state['transitions']))) {
                    throw new BgaUserException(self::_("Please wait for ongoing card action to be resolved."));
                }
                break;
            case 'sixth-sense':
                $discardSize = self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'DISCARD'");
                if ($discardSize == 0) {
                    throw new BgaUserException(self::_("Your discard is empty."));
                } else if ($discardSize > 2 && !in_array($cardName, array_keys($state['transitions']))) {
                    throw new BgaUserException(self::_("Please wait for ongoing card action to be resolved."));
                }
                break;
            case 'vortex':
                if (self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'DISCARD'") == 0) {
                    throw new BgaUserException(self::_("Your discard is empty."));
                }
                if (!in_array($cardName, array_keys($state['transitions']))) {
                    throw new BgaUserException(self::_("Please wait for ongoing card action to be resolved."));
                }
                break;
            case 'detector':
                if ($state['name'] == 'artemiaTokenEffects') {
                    throw new BgaUserException(self::_("This card must be played before the Artemia token effects resolution."));
                }
                if (self::getGameStateValue('artemia_token') <= 0) {
                    throw new BgaUserException(self::_("The Artemia token is not on a place card."));
                }
                $huntedLocations = $this->getLocations($playerId);
                if (empty(array_intersect($this->getArtemiaPlaces(), $huntedLocations))) {
                    throw new BgaUserException(self::_("You are not in the same place as the Artemia token."));
                }
                break;
            case 'dodge':
                if ($state['name'] == 'artemiaTokenEffects') {
                    throw new BgaUserException(self::_("This card must be played before the Artemia token effects resolution."));
                }
                $huntedLocations = $this->getLocations($playerId);
                if (empty(array_intersect($this->getCreaturePlaces(), $huntedLocations))) {
                    throw new BgaUserException(self::_("You are not in the same place as the Creature token."));
                }
                break;
            case 'drone':
            case 'gate':
                if (!array_key_exists('place', $state) || !in_array($playerId, $this->gamestate->getActivePlayerList())) {
                    self::notifyPlayer($playerId, "information", self::_("This card can only be played instead of using the power of your place card. Please use it when it is your turn to use your place’s power."), array());
                    return;
                }
                break;
            case 'hologram':
            case 'wrong-track':
                if ($cardName == 'hologram' && self::getGameStateValue('artemia_token') <= 0) {
                    throw new BgaUserException(self::_("The Artemia token is not on a place card."));
                }
                if ($state['name'] == 'artemiaTokenEffects') {
                    throw new BgaUserException(self::_("This card must be played before the Artemia token effects resolution."));
                }
                if (!in_array("changeActivePlayer", array_keys($state['transitions']))) {
                    throw new BgaUserException(self::_("Please wait for ongoing card action to be resolved."));
                }
                break;
            case 'amplifier':
                if (self::getGameStateValue('marker_counter') == 0) {
                    throw new BgaUserException(self::_("The marker counter is not on The Beach."));
                }
                break;
            case 'double-back':
                if (self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND location IN ('REVEALED', 'RESOLVED')") == 0) {
                    throw new BgaUserException(self::_("You already took back the Place card you just played."));
                }
                break;
        }
        $this->survivalDeck->insertCardOnExtremePosition($card['id'], 'discard', true);
        self::DbQuery("UPDATE player SET cards_left_this_turn = cards_left_this_turn - 1 WHERE player_id = $playerId");
        $notificationData = $this->prepareNotificationDataWithCard('survival', $cardName);
        $notificationData['playerId'] = $playerId;
        $notificationData['player_name'] = self::getCurrentPlayerName();
        $notificationData['card'] = $cardName;
        $notificationData['ongoingEffect'] = $this->survivalCards[$cardName]['ongoingEffect'];
        self::notifyAllPlayers("survivalCardPlayed", clienttranslate('${player_name} plays ${begin_card}${card_name}${end_card}.'), $notificationData);
        // Cards effects
        switch ($cardName) {
            case 'adrenaline':
                self::DbQuery("UPDATE player SET will_counters = will_counters + 1 WHERE player_id = $playerId");
                self::notifyAllPlayers("willCounterRegained", "", array('playerId' => $playerId));
                break;
            case 'ingenuity':
                $this->putMarkerCounterOnTheBeach('${player_name} places the Marker counter on the Beach.',
                    array('playerId' => $playerId, 'player_name' => self::getCurrentPlayerName()));
                break;
            case 'sacrifice':
                self::setGameStateValue('sacrifice', 1);
                $this->cancelExploration();
                $this->gamestate->changeActivePlayer($playerId);
                $this->giveExtraTime($playerId);
                $this->gamestate->nextState("sacrifice");
                break;
            case 'sixth-sense':
                $discardPlaces = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'DISCARD'", true);
                if (sizeof($discardPlaces) <= 2) {
                  self::DbQuery("UPDATE hunted_place_card SET location = 'HAND', discard_order = null WHERE hunted_player_id = $playerId AND location = 'DISCARD'");
                  $placeCardsQuantity = self::getUniqueValueFromDb("SELECT count(*) FROM hunted_place_card WHERE location = 'HAND' AND hunted_player_id = '$playerId'");
                  if (sizeof($discardPlaces) == 1) {
                    self::notifyAllPlayers("placeCardsTakenBack", clienttranslate('${player_name} takes back ${place_icon} ${place_name} from its discard.'),
                        array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => self::getCurrentPlayerName(), 'places' => $discardPlaces,
                            'place_name' => $this->placeCards[$discardPlaces[0]]['name'], 'place_icon' => $this->getPlaceIcon($discardPlaces[0]),
                            'total_place_cards' => $placeCardsQuantity));
                  } else {
                    self::notifyAllPlayers("placeCardsTakenBack", clienttranslate('${player_name} takes back ${place_icon_1} ${place_name_1} and ${place_icon_2} ${place_name_2} from its discard.'),
                        array('i18n' => array('place_name_1', 'place_name_2'), 'playerId' => $playerId, 'player_name' => self::getCurrentPlayerName(), 'places' => $discardPlaces,
                            'place_name_1' => $this->placeCards[$discardPlaces[0]]['name'],
                            'place_icon_1' => $this->getPlaceIcon($discardPlaces[0]),
                            'place_name_2' => $this->placeCards[$discardPlaces[1]]['name'],
                            'place_icon_2' => $this->getPlaceIcon($discardPlaces[1]),
                            'total_place_cards' => $placeCardsQuantity));
                  }
                } else {
                  $this->gamestate->changeActivePlayer($playerId);
                  $this->giveExtraTime($playerId);
                  $this->gamestate->nextState($cardName);
                }
                break;
            case 'smokescreen':
                self::setGameStateValue($cardName, 1);
                break;
            case 'detector':
            case 'dodge':
                self::setGameStateValue($cardName, $playerId);
                break;
            case 'strike-back':
                $creaturePlayer = self::getGameStateValue('creature_player');
                $creatureCards = $this->huntDeck->getCardsInLocation('hand', $creaturePlayer);
                if (sizeof($creatureCards) > 2) {
                    $selectedIndexes = array_rand($creatureCards, 2);
                } else {
                    $selectedIndexes = array_keys($creatureCards);
                }
                foreach ($selectedIndexes as $index) {
                    $this->huntDeck->insertCardOnExtremePosition($creatureCards[$index]['id'], 'deck', false);
                    $notificationData = $this->prepareNotificationDataWithCard('hunt', $creatureCards[$index]['type']);
                    $notificationData['playerId'] = $creaturePlayer;
                    $notificationData['player_name'] = self::loadPlayersBasicInfos()[$creaturePlayer]['player_name'];
                    $notificationData['card'] = $creatureCards[$index]['type'];
                    self::notifyAllPlayers("huntCardLost", clienttranslate('${player_name} put ${begin_card}${card_name}${end_card} at the bottom of the Hunt deck.'), $notificationData);
                }
                break;
            case 'vortex':
                $this->gamestate->changeActivePlayer($playerId);
                $this->giveExtraTime($playerId);
                $this->gamestate->nextState($cardName);
                break;
            case 'drone':
            case 'gate':
                $this->gamestate->nextState($cardName);
                break;
            case 'hologram':
            case 'wrong-track':
                // Rollback current place resolution in case of a interruption
                self::DbQuery("UPDATE hunted_place_card SET location = 'REVEALED' WHERE location = 'RESOLVING'");
                $this->gamestate->nextState("changeActivePlayer");
                $this->gamestate->changeActivePlayer($playerId);
                $this->giveExtraTime($playerId);
                $this->gamestate->nextState($cardName);
                break;
            case 'amplifier':
                $this->removeMarkerCounterFromTheBeach();
                $this->moveRescueCounter(clienttranslate('${player_name} moves the ${begin_rescue}Rescue counter${end_rescue} forward 1 space.'),
                    array('playerId' => $playerId, 'player_name' => self::getCurrentPlayerName()));
                break;
            case 'double-back':
                $places = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location IN ('REVEALED', 'RESOLVED')", true);
                if (sizeof($places) > 1) {
                    $this->gamestate->changeActivePlayer($playerId);
                    $this->giveExtraTime($playerId);
                    $this->gamestate->nextState($cardName);
                } else {
                    self::DbQuery("UPDATE hunted_place_card SET location = 'HAND' WHERE hunted_player_id = $playerId AND place_number = $places[0]");
                    self::notifyAllPlayers("playedPlaceTakenBack", clienttranslate('${player_name} takes back ${place_icon} ${place_name}.'),
                        array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => self::getCurrentPlayerName(), 'place' => $places[0],
                            'place_name' => $this->placeCards[$places[0]]['name'], 'place_icon' => $this->getPlaceIcon($places[0])));
                }
                break;
        }
        $state = $this->gamestate->state();
        if ($state['type'] == 'multipleactiveplayer'
            && $state['name'] != "exploration"
            && in_array('pass', $state['possibleactions'])
            && in_array($playerId, $this->gamestate->getActivePlayerList())) {
            $this->pass();
        }
    }

    function discardPlaceCard($place)
    {
        self::checkAction('discardPlaceCard');
        $playerId = self::getCurrentPlayerId();
        if (self::getUniqueValueFromDB("SELECT location FROM hunted_place_card WHERE hunted_player_id = $playerId AND place_number = $place") != 'HAND') {
            throw new BgaVisibleSystemException("This card is not in your hand");
        }
        if ($this->gamestate->state()['name'] == 'artemiaTokenEffects' && $this->numberOfPlacesToDiscardBecauseOfArtemiaToken($playerId) > 1) {
            throw new BgaVisibleSystemException("You where caught on 2 places by the Artemia token, you must discard 2 place cards.");
        }
        $this->addToDiscard($playerId, $place);
        self::notifyAllPlayers("placeDiscarded", clienttranslate('${player_name} discards ${place_icon} ${place_name}.'),
            array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => self::getCurrentPlayerName(), 'place' => $place,
                'place_name' => $this->placeCards[$place]['name'], 'place_icon' => $this->getPlaceIcon($place)));
        if ($this->gamestate->state()['type'] == 'multipleactiveplayer') {
            $this->gamestate->setPlayerNonMultiactive($playerId, "");
        } else {
            $this->gamestate->nextState("");
        }
    }

    function takeBack2PlaceCards($places)
    {
        $this->checkAction('takeBack2PlaceCards');
        if (sizeof($places) != 2) {
            throw new BgaVisibleSystemException("You must take back exactly 2 places cards.");
        }
        $playerId = self::getCurrentPlayerId();
        $this->takeBackFromDiscard($playerId, $places[0]);
        $this->takeBackFromDiscard($playerId, $places[1]);
        $placeCardsQuantity = self::getUniqueValueFromDb("SELECT count(*) FROM hunted_place_card WHERE location = 'HAND' AND hunted_player_id = '$playerId'");
        self::notifyAllPlayers("placeCardsTakenBack", clienttranslate('${player_name} takes back ${place_icon_1} ${place_name_1} and ${place_icon_2} ${place_name_2} from its discard.'),
            array('i18n' => array('place_name_1', 'place_name_2'), 'playerId' => $playerId, 'player_name' => self::getActivePlayerName(), 'places' => $places,
                'place_name_1' => $this->placeCards[$places[0]]['name'],
                'place_icon_1' => $this->getPlaceIcon($places[0]),
                'place_name_2' => $this->placeCards[$places[1]]['name'],
                'place_icon_2' => $this->getPlaceIcon($places[1]),
                'total_place_cards' => $placeCardsQuantity));
        $this->gamestate->nextState("");
    }

    function chooseHuntedPlayer($huntedPlayerId)
    {
        self::checkAction('chooseHuntedPlayer');
        $playersInfos = self::loadPlayersBasicInfos();
        if (!array_key_exists($huntedPlayerId, $playersInfos) || $huntedPlayerId == self::getGameStateValue('creature_player')) {
            throw new BgaVisibleSystemException("You must choose a Hunted player.");
        }
        switch ($this->gamestate->state()['name']) {
            case 'anticipation':
                self::setGameStateValue('anticipation', $huntedPlayerId);
                $notificationData = $this->prepareNotificationDataWithCard('hunt', 'anticipation');
                $notificationData['player_name'] = self::getActivePlayerName();
                $notificationData['other_player'] = $playersInfos[$huntedPlayerId]['player_name'];
                self::notifyAllPlayers("anticipationTargetChosen", clienttranslate('${player_name} targets ${other_player} with ${begin_card}${card_name}${end_card}.'), $notificationData);
                $this->gamestate->nextState("hunting");
                break;
            case 'ascendancyChooseHunted':
                if (self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $huntedPlayerId AND location = 'HAND'") <= 2) {
                    throw new BgaVisibleSystemException("You must choose a Hunted player with at least 3 Place cards in hand.");
                }
                $this->gamestate->nextState("changeActivePlayer");
                $this->gamestate->changeActivePlayer($huntedPlayerId);
                $this->giveExtraTime($huntedPlayerId);
                $this->gamestate->nextState("ascendancyDiscard");
                break;
            case 'phobiaChooseHunted':
                if (self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $huntedPlayerId AND location = 'HAND'") <= 2) {
                    throw new BgaVisibleSystemException("You must choose a Hunted player with at least 3 Place cards in hand.");
                }
                $this->gamestate->nextState("changeActivePlayer");
                $this->gamestate->changeActivePlayer($huntedPlayerId);
                $this->giveExtraTime($huntedPlayerId);
                $this->gamestate->nextState("phobiaSelectPlacesToShow");
                break;
        }
    }

    function discardPlaceCards($places)
    {
        self::checkAction('discardPlaceCards');
        $playerId = self::getCurrentPlayerId();
        $state = $this->gamestate->state();
        switch ($state['name']) {
            case 'ascendancyDiscard':
                $cardsInHand = self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'HAND'");
                if ($cardsInHand - 2 != count($places)) {
                    throw new BgaUserException(self::_("You must discard all but 2 Place cards."));
                }
                break;
            case 'scream':
                if (count($places) != 2) {
                    throw new BgaUserException(self::_("You must discard 2 Place cards."));
                }
                break;
            case 'artemiaTokenEffects':
                if ($this->numberOfPlacesToDiscardBecauseOfArtemiaToken($playerId) != 2) {
                    throw new BgaVisibleSystemException("You should only discard one place card.");
                } else if (count($places) != 2) {
                    throw new BgaUserException(self::_("You must discard 2 Place cards."));
                }
                break;
        }
        $joinedPlaces = join(',', $places);
        if (sizeof($places) != self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND place_number IN ($joinedPlaces) AND location IN ('HAND', 'PLAYED')")) {
            throw new BgaVisibleSystemException("You can only discard cards from your hand.");
        }
        foreach ($places as $place) {
            $this->addToDiscard($playerId, $place);
            self::notifyAllPlayers("placeDiscarded", clienttranslate('${player_name} discards ${place_icon} ${place_name}.'),
                array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => self::getCurrentPlayerName(),
                    'place_name' => $this->placeCards[$place]['name'], 'place' => $place, 'place_icon' => $this->getPlaceIcon($place)));
        }
        if ($state['type'] == 'multipleactiveplayer') {
            $this->gamestate->setPlayerNonMultiactive($playerId, "");
        } else {
            $this->gamestate->nextState("");
            if ($state['name'] == 'ascendancyDiscard') {
                $creature = self::getGameStateValue('creature_player');
                $this->gamestate->changeActivePlayer($creature);
                $this->giveExtraTime($creature);
                $this->gamestate->nextState('hunting');
            }
        }
    }

    function loseWill()
    {
        self::checkAction('loseWill');
        $playerId = self::getCurrentPlayerId();
        $notificationData = $this->prepareNotificationDataWithCard('hunt', 'scream');
        $notificationData['playerId'] = $playerId;
        $notificationData['player_name'] = self::getCurrentPlayerName();
        $notificationData['card'] = 'scream';
        $notificationData['quantity'] = 1;
        $notificationData['will_icon'] = $this->getWillIcons();
        self::notifyAllPlayers("willCounterLost", clienttranslate('${player_name} chooses to lose ${will_icon}1 Will because of ${begin_card}${card_name}${end_card}.'), $notificationData);
        $this->removeWillCounter($playerId);
        $this->gamestate->setPlayerNonMultiactive($playerId, "");
    }

    function showPlaces($places)
    {
        self::checkAction('showPlaces');
        $playerId = self::getActivePlayerId();
        $cardsInHand = self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'HAND'");
        if ($cardsInHand - 2 != count($places)) {
            throw new BgaUserException(self::_("You must show all but 2 Place cards."));
        }
        $joinedPlaces = join(',', $places);
        if (sizeof($places) != self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $playerId AND place_number IN ($joinedPlaces) AND location IN ('HAND', 'PLAYED')")) {
            throw new BgaVisibleSystemException("You can only show cards from your hand.");
        }
        $creature = self::getGameStateValue('creature_player');
        foreach ($places as $place) {
            self::notifyPlayer($creature, "placeRevealed", clienttranslate('${player_name} reveals ${place_icon} ${place_name}.'),
                array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => self::getActivePlayerName(), 'place' => $place,
                    'place_name' => $this->placeCards[$place]['name'], 'place_icon' => $this->getPlaceIcon($place)));
        }
        self::notifyPlayer($creature, 'placesRevealed', '', array('player_name' => self::getActivePlayerName(), 'quantity' => count($places), 'places' => $places));
        $this->gamestate->nextState("");
        $this->gamestate->changeActivePlayer($creature);
        $this->giveExtraTime($creature);
        $this->gamestate->nextState('hunting');
    }

    function discardSurvivalCard($cardName)
    {
        self::checkAction('discardSurvivalCard');
        $cards = $this->survivalDeck->getCardsOfType($cardName);
        if (empty($cards)) {
            throw new BgaVisibleSystemException("Unexpected survival card: " . $cardName);
        }
        $card = array_pop($cards);
        $playerId = self::getCurrentPlayerId();
        if ($card['location'] != 'hand' && $card['card_location_arg'] != $playerId) {
            throw new BgaVisibleSystemException("This card is not in your hand: " . $cardName);
        }
        $this->survivalDeck->insertCardOnExtremePosition($card['id'], 'discard', true);
        $notificationData = $this->prepareNotificationDataWithCard('survival', $cardName);
        $notificationData['playerId'] = $playerId;
        $notificationData['player_name'] = self::getCurrentPlayerName();
        $notificationData['card'] = $cardName;
        self::notifyAllPlayers("survivalCardDiscarded", clienttranslate('${player_name} discards ${begin_card}${card_name}${end_card}.'), $notificationData);
        $this->gamestate->setPlayerNonMultiactive($playerId, "");
    }

    function copyAdjacentPlace($place)
    {
        self::checkAction('copyAdjacentPlace');
        $resolvingPlace = self::getUniqueValueFromDB("SELECT place_number FROM hunted_place_card WHERE location = 'RESOLVING'");
        if (!$this->isAdjacentPlaces($resolvingPlace, $place)) {
            throw new BgaUserException(sprintf(self::_("This place is not adjacent to %s."), self::_($this->placeCards[$place]['name'])));
        }
        if ($place == 10) {
            throw new BgaUserException(self::_("You may not copy the Artefact."));
        }
        $this->gamestate->nextState($place);
        $notificationData = $this->prepareNotificationDataWithCard('survival', 'gate');
        $notificationData['player_name'] = self::getActivePlayerName();
        $notificationData['place_name'] = $this->placeCards[$place]['name'];
        $notificationData['i18n'][] = 'place_name';
        $notificationData['place_icon'] = $this->getPlaceIcon($place);
        self::notifyAllPlayers("placeCopied", clienttranslate('${player_name} uses ${begin_card}${card_name}${end_card} to copy ${place_icon} ${place_name}.'), $notificationData);
    }

    function moveHuntToken($tokenType, $place)
    {
        self::checkAction('moveHuntToken');
        $tokenLocation = self::getGameStateValue($tokenType . '_token');
        if (!in_array($place, $this->getPlacesAdjacentToToken($tokenLocation))) {
            throw new BgaUserException(self::_("This Place is not adjacent to the Hunt token."));
        }
        self::setGameStateValue($tokenType . '_token', $place);
        switch ($tokenType) {
            case 'creature':
                if ($this->gamestate->state()['name'] == 'hologram') {
                    throw new BgaVisibleSystemException("You are not allowed to move this token.");
                }
                $text = clienttranslate('${player_name} moves the Creature token to ${place_icon} ${place_name}.');
                break;
            case 'artemia':
                if ($this->gamestate->state()['name'] != 'hologram') {
                    throw new BgaVisibleSystemException("You are not allowed to move this token.");
                }
                $text = clienttranslate('${player_name} moves the Artemia token to ${place_icon} ${place_name}.');
                break;
            case 'target':
                if ($this->gamestate->state()['name'] != 'wrongTrackClone') {
                    throw new BgaVisibleSystemException("You are not allowed to move this token.");
                }
                $text = clienttranslate('${player_name} moves the Target token to ${place_icon} ${place_name}.');
                break;
            default:
                throw new BgaVisibleSystemException("Unexpected token: " . $tokenType);
        }
        self::notifyAllPlayers("huntTokenMoved", $text,
            array('i18n' => array('place_name'), 'player_name' => self::getActivePlayerName(), 'token' => $tokenType . '_token',
                'place' => $place, 'place_name' => $this->placeCards[$place]['name'], 'place_icon' => $this->getPlaceIcon($place)));
        if ($this->gamestate->state()['name'] == 'wrongTrack' && self::getGameStateValue('clone') == 1) {
            $this->gamestate->nextState("clone");
        } else {
            $this->gamestate->nextState("continue");
        }
    }

    function chooseIneffectivePlace($place)
    {
        self::checkAction('chooseIneffectivePlace');
        self::setGameStateValue('cataclysm', $place);
        $notificationData = $this->prepareNotificationDataWithCard('hunt', 'cataclysm');
        $notificationData['player_name'] = self::getActivePlayerName();
        $notificationData['place_name'] = $this->placeCards[$place]['name'];
        $notificationData['i18n'][] = 'place_name';
        $notificationData['place_icon'] = $this->getPlaceIcon($place);
        self::notifyAllPlayers("cataclysmPlaceChosen", clienttranslate('${player_name} targets ${place_icon} ${place_name} with ${begin_card}${card_name}${end_card}.'), $notificationData);
        $this->gamestate->nextState("continue");
    }

    function moveHunted($huntedId, $origin, $destination)
    {
        self::checkAction('moveHunted');
        if (self::getUniqueValueFromDB("SELECT count(*) FROM hunted_place_card WHERE hunted_player_id = $huntedId AND place_number = $origin AND location IN ('REVEALED', 'RESOLVED')") != 1) {
            throw new BgaVisibleSystemException("This player did not reveal this place card.");
        } else if (!$this->isAdjacentPlaces($origin, $destination)) {
            throw new BgaVisibleSystemException("The destination is not adjacent.");
        }
        self::setGameStateValue('detour_target_hunted', $huntedId);
        self::setGameStateValue('detour_origin', $origin);
        self::setGameStateValue('detour_destination', $destination);
        self::notifyAllPlayers("huntedMoved", clienttranslate('${player_name} moves ${other_player} from ${place_icon_1} ${place_name_1} to ${place_icon_2} ${place_name_2}.'),
            array('i18n' => array('place_name_1', 'place_name_2'),
                'player_name' => self::getCurrentPlayerName(),
                'other_player' => $this->getPlayerNameForNotification(self::loadPlayersBasicInfos()[$huntedId]),
                'playerId' => $huntedId,
                'origin' => $origin,
                'destination' => $destination,
                'place_name_1' => $this->placeCards[$origin]['name'],
                'place_icon_1' => $this->getPlaceIcon($origin),
                'place_name_2' => $this->placeCards[$destination]['name'],
                'place_icon_2' => $this->getPlaceIcon($destination)));
        $this->gamestate->nextState("continue");
    }

    function pass()
    {
        self::checkAction('pass');
        $playerId = self::getCurrentPlayerId();
        switch ($this->gamestate->state()['phase']) {
            case 1:
                if ($playerId != self::getGameStateValue('creature_player')) {
                    throw new BgaVisibleSystemException("You must play a Place card during exploration phase.");
                }
                $this->setPlayerNonActiveDuringExploration($playerId);
                return;
            case 3:
                if ($this->gamestate->state()['name'] == 'wrongTrack' && self::getGameStateValue('clone') == 1) {
                    $this->gamestate->nextState('clone');
                    return;
                } else if ($this->gamestate->state()['name'] == 'theBeach' && self::getGameStateValue('beach_used') == 0) {
                    throw new BgaVisibleSystemException("You can use the Beach");
                } else if ($this->gamestate->state()['name'] == 'theWreck' && self::getGameStateValue('wreck_used') == 0) {
                    throw new BgaVisibleSystemException("You can use the Wreck");
                }
                break;
        }
        if ($this->gamestate->state()['type'] == 'multipleactiveplayer') {
            $this->gamestate->setPlayerNonMultiactive($playerId, 'continue');
        } else {
            $this->gamestate->nextState('continue');
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argsHunting()
    {
        return array(
            'creatureToken' => self::getGameStateValue('creature_token') < 0,
            'artemiaToken' => self::getGameStateValue('artemia_token') < 0,
            'artemia2AdjacentPlaces' => self::getGameStateValue('artemia_token') == -2,
            'targetToken' => self::getGameStateValue('target_token') < 0,
            'target2AdjacentPlaces' => self::getGameStateValue('target_token') == -2
        );
    }

    function argsTheLairState()
    {
        return array(
            'place' => self::getGameStateValue('creature_token'),
            'clonePlaces' => self::getGameStateValue('clone') == 1 ? $this->getTargetedPlaces() : []
        );
    }

    function argsResolvingPlace()
    {
        $resolvingPlace = self::getUniqueValueFromDB("SELECT place_number FROM hunted_place_card WHERE location = 'RESOLVING'");
        return array(
            'i18n' => array('resolvingPlaceName'),
            'resolvingPlace' => $resolvingPlace,
            'resolvingPlaceName' => $this->placeCards[$resolvingPlace]['name']
        );
    }

    function argsChooseSurvivalCard()
    {
        return array(
            '_private' => array(
                'active' => array(
                    'survivalCards' => $this->survivalDeck->getCardsInLocation('choice')
                )
            )
        );
    }

    function argsChooseArtefactPlaceCard()
    {
        return $this->getLocations(self::getActivePlayerId());
    }

    function argsHuntTokenAdjacentPlaces()
    {
        return $this->getPlacesAdjacentToToken(self::getGameStateValue($this->gamestate->state()['token'] . '_token'));
    }

    function argsWrongTrackClone()
    {
        $creatureToken = self::getGameStateValue('creature_token');
        $targetToken = self::getGameStateValue('target_token');
        return array(
            'creature' => $this->getPlacesAdjacentToToken($creatureToken),
            'target' => $this->getPlacesAdjacentToToken($targetToken)
        );
    }

    function argsArtemiaTokenEffects()
    {

        $artemiaPlaces = $this->getArtemiaPlaces();
        $caughtPlayers = $this->getHuntedPlayersLocations(null, $artemiaPlaces);
        if (!empty($artemiaPlaces)) {
            array_walk($caughtPlayers, function (&$value, $playerId) {
                $value = $this->numberOfPlacesToDiscardBecauseOfArtemiaToken($playerId);
            });
        }
        return $caughtPlayers;
    }

    function argsPreviousSurvivalCardPlayed()
    {
        $card = $this->survivalDeck->getCardOnTop('discard');
        return array('card_name' => self::_($this->survivalCards[$card['type']]['name']));
    }

    function argsPreviousHuntCardPlayed()
    {
        $card = $this->huntDeck->getCardOnTop('played');
        return array('card_name' => self::_($this->huntCards[$card['type']]['name']));
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stExploration()
    {
        if ($this->mightPlayACard(self::getGameStateValue('creature_player'))) {
            $this->gamestate->setAllPlayersMultiactive();
            foreach (self::loadPlayersBasicInfos() as $playerId => $player) {
                $this->giveExtraTime($playerId);
            }
        } else {
            $this->activateHuntedPlayers();
        }
    }

    function stHunting()
    {
        if (self::getGameStateValue('creature_token') >= 0 && self::getGameStateValue('artemia_token') >= 0 && self::getGameStateValue('target_token') >= 0) {
            $this->gamestate->nextState("phase2Cards");
        }
    }

    function stChooseRiverPlaceCard()
    {
        $players = self::getObjectListFromDB("SELECT player_id FROM player WHERE place_pending_effect = 3", true);
        $this->gamestate->setPlayersMultiactive($players, '', true);
        foreach ($players as $playerId) {
            $this->giveExtraTime($playerId);
        }
    }

    function stStartReckoning()
    {
        self::DbQuery("UPDATE player SET place_pending_effect = null");
        $placeCardsPlayed = self::getDoubleKeyCollectionFromDB("SELECT hunted_player_id, place_number, location FROM hunted_place_card WHERE location = 'PLAYED'", true);
        if (!empty($placeCardsPlayed)) {
            self::DbQuery("UPDATE hunted_place_card SET location = 'REVEALED' WHERE location = 'PLAYED'");
            $players = self::loadPlayersBasicInfos();
            $creature = self::getGameStateValue('creature_player');
            $playerId = self::getPlayerAfter($creature);
            while ($playerId != $creature) {
                $places = array_map("strval", array_keys($placeCardsPlayed[$playerId]));
                if (sizeof($places) == 1) {
                    self::notifyAllPlayers("placeCardsRevealed", clienttranslate('${player_name} reveals ${place_icon} ${place_name}.'),
                        array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => $players[$playerId]['player_name'], 'places' => $places,
                            'place_name' => $this->placeCards[$places[0]]['name'], 'place_icon' => $this->getPlaceIcon($places[0])));
                } else {
                    self::notifyAllPlayers("placeCardsRevealed", clienttranslate('${player_name} reveals ${place_icon_1} ${place_name_1} and ${place_icon_2} ${place_name_2}.'),
                        array('i18n' => array('place_name_1', 'place_name_2'), 'playerId' => $playerId, 'player_name' => $players[$playerId]['player_name'], 'places' => $places,
                            'place_name_1' => $this->placeCards[$places[0]]['name'],
                            'place_icon_1' => $this->getPlaceIcon($places[0]),
                            'place_name_2' => $this->placeCards[$places[1]]['name'],
                            'place_icon_2' => $this->getPlaceIcon($places[1])));
                }
                $playerId = $this->getPlayerAfter($playerId);
            }
        }
        $this->stActivatePlayersWhoMightPlayACard();
    }

    /**
     * Starting with the player to the left of the Creature, each Hunted who explores a place without a Hunt token may immediately use the place’s power
     * OR take back 1 Place card of his choice from his discard pile.
     */
    function stNextUncaughtPlayer()
    {
        self::DbQuery("UPDATE hunted_place_card SET location = 'RESOLVED' WHERE location = 'RESOLVING'");
        $creature = self::getGameStateValue('creature_player');
        $huntedUnresolvedLocations = $this->getHuntedPlayersLocations('REVEALED');
        $currentPlayer = self::getPlayerAfter($creature);
        while ($currentPlayer != $creature) {
            if (isset($huntedUnresolvedLocations[$currentPlayer])) {
                $unresolvedLocations = $huntedUnresolvedLocations[$currentPlayer];
                $placesWithEffectiveToken = self::getGameStateValue('clone') != 1 ? $this->getTargetedPlaces() : [];
                if (self::getGameStateValue('dodge') != $currentPlayer) {
                    $placesWithEffectiveToken = array_unique(array_merge($placesWithEffectiveToken, $this->getCreaturePlaces()));
                }
                if (self::getGameStateValue('detector') != $currentPlayer) {
                    $placesWithEffectiveToken = array_unique(array_merge($placesWithEffectiveToken, $this->getArtemiaPlaces()));
                }
                $effectiveLocations = array_unique(array_diff($unresolvedLocations, $placesWithEffectiveToken));
                if (!empty($effectiveLocations)) {
                    $this->gamestate->changeActivePlayer($currentPlayer);
                    $this->giveExtraTime($currentPlayer);
                    if (sizeof($effectiveLocations) == 1) {
                        $this->startHuntedLocationResolution($currentPlayer, current($effectiveLocations));
                    } else {
                        $this->gamestate->nextState("chooseArtefactPlaceCard");
                    }
                    return;
                }
            }
            $currentPlayer = $this->getPlayerAfter($currentPlayer);
        }
        if (empty($eligiblePlayers)) {
            $this->gamestate->nextState("targetTokenEffects");
        }
    }

    function stPlaceReckoning()
    {
        $huntedResolvingLocation = $this->getHuntedResolvingLocation(self::getActivePlayerId());
        $isCopyingCurrentPlacePower = $huntedResolvingLocation != $this->gamestate->state()['place'];
        if (!$isCopyingCurrentPlacePower && $this->isPlacePowerIneffective($huntedResolvingLocation)) {
            self::notifyAllPlayers("ineffectivePlace", clienttranslate('${player_name} cannot use ${place_icon} ${place_name} because it is ineffective.'),
                array('i18n' => array('place_name'), 'player_name' => self::getActivePlayerName(),
                    'place_name' => $this->placeCards[$huntedResolvingLocation]['name'], 'place_icon' => $this->getPlaceIcon($huntedResolvingLocation)));
            $this->gamestate->nextState("continue");
        } else if (array_key_exists('persecution', $this->gamestate->state()['transitions']) && self::getGameStateValue('persecution') == 1) {
            $this->gamestate->nextState("persecution");
        }
    }

    function stPlaySurvivalCardDrawn()
    {
        self::DbQuery("UPDATE hunted_place_card SET location = 'RESOLVED' WHERE location = 'RESOLVING'");
        if (!$this->mightPlayACard(self::getActivePlayerId())) {
            $this->gamestate->nextState("continue");
        }
    }

    function stTargetTokenEffects()
    {
        $targetedPlaces = $this->getTargetedPlaces();
        if (empty($targetedPlaces) || self::getGameStateValue('clone') == 1) {
            $this->gamestate->nextState("artemiaToken");
            return;
        }
        $huntedTargetedLocations = $this->getHuntedPlayersLocations('REVEALED', $targetedPlaces);
        if (empty($huntedTargetedLocations)) {
            $this->gamestate->nextState("artemiaToken");
            return;
        }
        $targetedPlayers = array_keys($huntedTargetedLocations);
        if (self::getGameStateValue('scream') == 1) {
            self::setGameStateValue('scream', 0);
            $this->gamestate->setPlayersMultiactive($targetedPlayers, '', true);
            foreach ($targetedPlayers as $playerId) {
                $this->giveExtraTime($playerId);
            }
            $this->gamestate->nextState("scream");
            return;
        }
        if (self::getGameStateValue('toxin') == 1) {
            self::setGameStateValue('toxin', 0);
            $this->gamestate->nextState("toxin");
            $playersWithSurvivalCards = self::getObjectListFromDB("SELECT DISTINCT card_location_arg FROM survival_card WHERE card_location = 'HAND'", true);
            $targetedPlayersWithNoSurvivalCards = array_diff($targetedPlayers, $playersWithSurvivalCards);
            foreach ($targetedPlayersWithNoSurvivalCards as $playerId) {
                $notificationData = $this->prepareNotificationDataWithCard('hunt', 'toxin');
                $notificationData['playerId'] = $playerId;
                $notificationData['player_name'] = self::loadPlayersBasicInfos()[$playerId]['player_name'];
                self::notifyAllPlayers("toxinDiscardAvoided", clienttranslate('${player_name} has 0 Survival cards and discards nothing due to ${begin_card}${card_name}${end_card}.'), $notificationData);
            }
            $this->gamestate->setPlayersMultiactive(array_intersect($targetedPlayers, $playersWithSurvivalCards), '', true);
            foreach ($targetedPlayers as $playerId) {
                $this->giveExtraTime($playerId);
            }
            return;
        }
        $creature = self::getGameStateValue('creature_player');
        $currentPlayer = self::getPlayerAfter($creature);
        while ($currentPlayer != $creature) {
            if (isset($huntedTargetedLocations[$currentPlayer])) {
                $targetedLocations = $huntedTargetedLocations[$currentPlayer];
                $this->gamestate->changeActivePlayer($currentPlayer);
                $this->giveExtraTime($currentPlayer);
                $location = current($targetedLocations);
                // We do not resolve places with an effective creature or artemia token
                if (($location != self::getGameStateValue('creature_token') || self::getGameStateValue('dodge') == $currentPlayer)
                    && (!in_array($location, $this->getArtemiaPlaces()) || self::getGameStateValue('detector') == $currentPlayer)) {
                    $this->startHuntedLocationResolution($currentPlayer, $location);
                    return;
                }
            }
            $currentPlayer = $this->getPlayerAfter($currentPlayer);
        }
        $this->gamestate->nextState("artemiaToken");
    }

    function stArtemiaTokenEffects()
    {
        $artemiaPlaces = $this->getArtemiaPlaces();
        if (empty($artemiaPlaces)) {
            $this->gamestate->nextState("");
            return;
        }
        $huntedArtemiaLocations = $this->getHuntedPlayersLocations(null, $artemiaPlaces);
        unset($huntedArtemiaLocations[self::getGameStateValue('detector')]);
        $caughtPlayers = array_keys($huntedArtemiaLocations);
        if (self::getGameStateValue('mutation') == 1) {
            foreach ($caughtPlayers as $playerId) {
                $quantityLost = $this->numberOfTimeCaughtByArtemiaToken($playerId);
                $notificationData = $this->prepareNotificationDataWithCard('hunt', 'mutation');
                $notificationData['playerId'] = $playerId;
                $notificationData['player_name'] = self::loadPlayersBasicInfos()[$playerId]['player_name'];
                $notificationData['card'] = 'mutation';
                $notificationData['quantity'] = $quantityLost;
                $notificationData['will_icons'] = $this->getWillIcons($quantityLost);
                self::notifyAllPlayers("willCounterLost", clienttranslate('Due to ${begin_card}${card_name}${end_card}, ${player_name} loses ${will_icons}${quantity} Will.'), $notificationData);
                $this->removeWillCounter($playerId);
                if ($quantityLost == 2) {
                    $this->removeWillCounter($playerId);
                    self::notifyAllPlayers("willCounterLost", '', array('playerId' => $playerId));
                }
            }
        }
        $discardingPlayers = array_filter($caughtPlayers, array($this, 'hasAtLeastOnePlaceCard'));
        $this->gamestate->setPlayersMultiactive($discardingPlayers, '', true);
        foreach ($discardingPlayers as $playerId) {
            $this->giveExtraTime($playerId);
        }
    }

    function stCreatureTokenEffects()
    {
        if (self::getGameStateValue('tracking') == 1) {
            // We set it to 0 as late as possible to keep Tracking visible in the ongoing effects zone.
            self::setGameStateValue('tracking', 0);
        }

        $creaturePlaces = $this->getCreaturePlaces();
        $huntedCreatureLocations = $this->getHuntedPlayersLocations(null, $creaturePlaces);
        unset($huntedCreatureLocations[self::getGameStateValue('dodge')]);
        $players = self::loadPlayersBasicInfos();
        if (!empty($huntedCreatureLocations)) {
            foreach ($huntedCreatureLocations as $playerId => $places) {
                $playerName = $players[$playerId]['player_name'];
                foreach ($places as $place) {
                    $this->removeWillCounter($playerId);
                    if ($place == 1) {
                        // The Lair: Lose 1 extra Will if caught by the Creature token.
                        $this->removeWillCounter($playerId);
                        self::notifyAllPlayers("willCounterLost", clienttranslate('${player_name} got caught by the Creature in ${lair_icon} ${the_lair} and loses ${will_icons}2 Will.'),
                            array('i18n' => array('the_lair'), 'playerId' => $playerId, 'player_name' => $playerName,
                                'the_lair' => $this->placeCards[1]['name'],
                                'lair_icon' => $this->getPlaceIcon(1),
                                'quantity' => 2, 'will_icons' => $this->getWillIcons(2)));
                        self::notifyAllPlayers("willCounterLost", '', array('playerId' => $playerId));
                    } else {
                        self::notifyAllPlayers("willCounterLost", clienttranslate('${player_name} got caught by the Creature on ${place_icon} ${place_name} and loses ${will_icon}1 Will.'),
                            array('i18n' => array('place_name'), 'playerId' => $playerId, 'player_name' => $playerName,
                                'place_name' => $this->placeCards[$place]['name'], 'place_icon' => $this->getPlaceIcon($place),
                                'quantity' => 1, 'will_icon' => $this->getWillIcons()));
                    }
                }
                if (self::getGameStateValue('fierceness') == 1) {
                    $notificationData = $this->prepareNotificationDataWithCard('hunt', 'fierceness');
                    $notificationData['playerId'] = $playerId;
                    $notificationData['player_name'] = $playerName;
                    $notificationData['card'] = 'fierceness';
                    $notificationData['will_icon'] = $this->getWillIcons();
                    self::notifyAllPlayers("willCounterLost", clienttranslate('Due to ${begin_card}${card_name}${end_card}, ${player_name} loses one extra ${will_icon}Will.'), $notificationData);
                    $this->removeWillCounter($playerId);
                }
            }
            if ($this->moveAssimilationCounter(clienttranslate('The ${begin_assimilation}Assimilation counter${end_assimilation} moves forward because at least one Hunted player got caught by the Creature!')) == 0) {
                return;
            }
            $anticipationTargetPlayer = self::getGameStateValue('anticipation');
            if (in_array($anticipationTargetPlayer, array_keys($huntedCreatureLocations))) {
                $notificationData = $this->prepareNotificationDataWithCard('hunt', 'anticipation');
                $notificationData['player_name'] = $players[$anticipationTargetPlayer]['player_name'];
                if ($this->moveAssimilationCounter(clienttranslate('The Creature successfully played ${begin_card}${card_name}${end_card} on ${player_name} and moves the ${begin_assimilation}Assimilation counter${end_assimilation} forward 1 extra space!'), $notificationData) == 0) {
                    return; // End of game
                }
            }
        }
        $willLessHunted = self::getObjectListFromDB("SELECT player_id FROM player WHERE will_counters = 0", true);
        if (!empty($willLessHunted)) {
            $willCountersTakenBack = $this->gamestate->table_globals[100] == 1 ? 2 : 3;
            self::DbQuery("UPDATE player SET will_counters = $willCountersTakenBack WHERE will_counters = 0");
            foreach ($willLessHunted as $playerId) {
                self::DbQuery("UPDATE hunted_place_card SET location = 'HAND', discard_order = null WHERE hunted_player_id = $playerId");
                $placeCardsQuantity = self::getUniqueValueFromDb("SELECT count(*) FROM hunted_place_card WHERE location = 'HAND' AND hunted_player_id = '$playerId'");
                self::notifyAllPlayers("giveUp", clienttranslate('${player_name} takes back ${will_icons}${quantity} Will counters and all its places after losing its last ${will_icon}Will.'),
                    array('playerId' => $playerId, 'player_name' => $players[$playerId]['player_name'], 'quantity' => $willCountersTakenBack,
                        'will_icons' => $this->getWillIcons($willCountersTakenBack), 'will_icon' => $this->getWillIcons(), 'total_place_cards' => $placeCardsQuantity));
            }
            $assimilationCounter = $this->moveAssimilationCounter(clienttranslate('The ${begin_assimilation}Assimilation counter${end_assimilation} moves forward because at least one Hunted player lost its third ${will_icon}Will counter during Reckoning phase!'),
                array('will_icon' => $this->getWillIcons()));
            if ($assimilationCounter == 0) {
                return; // End of game
            }
        }
        $this->gamestate->nextState("endOfTurn");
    }

    function stWrongTrack()
    {
        if (self::getGameStateValue('clone') == 1) {
            $this->gamestate->nextState("clone");
        }
    }

    function stIfCreatureCanRetaliate()
    {
        $creature = self::getGameStateValue('creature_player');
        if ($this->mightPlayACard($creature)) {
            $this->gamestate->changeActivePlayer($creature);
            $this->gamestate->nextState("retaliate");
        } else {
            $this->gamestate->nextState("continue");
        }
    }

    /**
     * Activates all players that did not play all the cards they are authorized to play, and having at least one card in hand.
     */
    function stActivatePlayersWhoMightPlayACard()
    {
        $players = array_filter(array_keys(self::loadPlayersBasicInfos()), array($this, 'mightPlayACard'));
        $this->gamestate->setPlayersMultiactive($players, 'continue', true);
        foreach ($players as $playerId) {
            $this->giveExtraTime($playerId);
        }
    }

    function stEndOfTurn()
    {
        $revealedPlaces = self::getObjectListFromDB("SELECT hunted_player_id, place_number FROM hunted_place_card WHERE location IN ('REVEALED', 'RESOLVED')");
        foreach ($revealedPlaces as $revealedPlace) {
            $this->addToDiscard($revealedPlace['hunted_player_id'], $revealedPlace['place_number']);
        }

        $huntCardsPlayed = $this->huntDeck->getCardsInLocation('played');
        foreach ($huntCardsPlayed as $huntCard) {
          $this->huntDeck->insertCardOnExtremePosition($huntCard['id'], 'discard', true);
        }

        self::setGameStateValue('beach_used', 0);
        self::setGameStateValue('wreck_used', 0);
        self::setGameStateValue("targeted_places_ineffective", 0);
        self::setGameStateValue('despair', 0);
        self::setGameStateValue('fierceness', 0);
        self::setGameStateValue('interference', 0);
        self::setGameStateValue('persecution', 0);
        self::setGameStateValue('mutation', 0);
        self::setGameStateValue('clone', 0);
        self::setGameStateValue('scream', 0);
        self::setGameStateValue('toxin', 0);
        self::setGameStateValue('cataclysm', 0);
        self::setGameStateValue('detour_target_hunted', 0);
        self::setGameStateValue('detour_origin', 0);
        self::setGameStateValue('detour_destination', 0);
        self::setGameStateValue('sacrifice', 0);
        self::setGameStateValue('anticipation', 0);
        self::setGameStateValue('detector', 0);
        self::setGameStateValue('dodge', 0);

        $creaturePlayerId = self::getGameStateValue('creature_player');

        if (self::getGameStateValue('smokescreen') == 1) {
            self::setGameStateValue('smokescreen', 0);
            $notificationData = [];
            foreach (self::loadPlayersBasicInfos() as $playerId => $player) {
                if ($playerId != $creaturePlayerId) {
                    $notificationData[$playerId] = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'DISCARD' ORDER BY discard_order", true);
                }
            }
            self::notifyPlayer($creaturePlayerId, "smokescreenDissipates", "", $notificationData);
        }

        $huntCardsCount = $this->huntDeck->countCardInLocation('hand', $creaturePlayerId);
        if ($huntCardsCount < 3) {
            $huntCards = $this->huntDeck->pickCards(3 - $huntCardsCount, 'deck', $creaturePlayerId);
            self::notifyPlayer($creaturePlayerId, "huntCardsSeen", "", array('huntCards' => $huntCards));
            self::notifyAllPlayers("huntCardsDrawn", clienttranslate('${player_name} draws Hunt cards up to a hand of 3.'),
                array('playerId' => $creaturePlayerId, 'player_name' => self::loadPlayersBasicInfos()[$creaturePlayerId]['player_name']));
        }

        self::DbQuery("UPDATE player SET cards_left_this_turn = 1");
        if (self::getGameStateValue('tracking') == 1) {
            self::DbQuery("UPDATE player SET cards_left_this_turn = 2 WHERE player_id = $creaturePlayerId");
        }

        if (self::getGameStateValue('stasis') == 0) {
            $rescueCounter = $this->moveRescueCounter(clienttranslate('End of turn: the ${begin_rescue}Rescue counter${end_rescue} moves forward 1 space.'));
            if ($rescueCounter != 0) {
                $this->gamestate->nextState("exploration");
            }
        } else {
            self::setGameStateValue('stasis', 0);
            $this->gamestate->nextState("exploration");
        }

        self::setGameStateValue("creature_token", -1);
        if ($this->isArtemiaSymbolUnderRescueCounter()) {
            self::setGameStateValue("artemia_token", -1);
        } else {
            self::setGameStateValue("artemia_token", 0);
        }
        self::setGameStateValue("target_token", 0);
    }

    function stDoNothing()
    {
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn($state, $playerId)
    {
        switch ($state['name']) {
            case 'boardSetup':
                $this->chooseBoardSide(0);
                break;
            case 'exploration':
                if ($playerId == self::getGameStateValue('creature_player')) {
                    $this->setPlayerNonActiveDuringExploration($playerId);
                } else {
                    $placePendingEffect = self::getUniqueValueFromDB("SELECT place_pending_effect FROM player WHERE player_id = $playerId");
                    $places = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'HAND'", true);
                    if (sizeof($places) == 0 || $placePendingEffect != null && sizeof($places) == 1) {
                        $this->giveUp();
                        $places = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'HAND'", true);
                    }
                    if ($placePendingEffect == null) {
                        $this->exploration(array($places[array_rand($places)]));
                    } else {
                        $this->exploration(array_rand(array_flip($places), 2));
                    }
                }
                break;
            case 'artemiaTokenEffects':
            case 'forbiddenZone':
                $places = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'HAND'", true);
                $this->discardPlaceCard($places[array_rand($places)]);
                break;
            case 'sacrifice':
                $places = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'HAND'", true);
                if (sizeof($places) == 0) {
                    $this->giveUp();
                    $places = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'HAND'", true);
                }
                $this->discardPlaceCard($places[array_rand($places)]);
                break;
            case 'sixthSense':
                $places = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'DISCARD'", true);
                $this->takeBack2PlaceCards(array_rand(array_flip($places), 2));
                break;
            case 'ascendancyDiscard':
                $places = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'HAND'", true);
                $this->discardPlaceCards(array_rand(array_flip($places), sizeof($places) - 2));
                break;
            case 'phobiaSelectPlacesToShow':
                $places = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'HAND'", true);
                $this->showPlaces(array_rand(array_flip($places), sizeof($places) - 2));
                break;
            case 'scream':
                $this->loseWill();
                break;
            case 'toxin':
                $cards = $this->survivalDeck->getCardsInLocation('hand', $playerId);
                $this->discardSurvivalCard($cards[0]['type']);
                break;
            case 'vortex':
                $this->gamestate->nextState('');
                $creature = self::getGameStateValue('creature_player');
                $this->gamestate->changeActivePlayer($creature);
                $this->giveExtraTime($creature);
                $this->gamestate->nextState('hunting');
                break;
            case 'gate':
                $resolvingPlace = self::getUniqueValueFromDB("SELECT place_number FROM hunted_place_card WHERE location = 'RESOLVING'");
                $this->copyAdjacentPlace($resolvingPlace < 6 ? $resolvingPlace + 5 : $resolvingPlace - 5);
                break;
            case 'theLair':
                $this->takeBackDiscardedPlaceCards();
                break;
            case 'theJungle':
            case 'Jungle_Swamp_Persecution':
                $this->takeBackPlayedCard();
                break;
            case 'theSwamp':
                $this->theSwamp([]);
                break;
            case 'chooseRiverPlaceCard':
                $places = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'PLAYED'", true);
                $this->chooseRiverPlaceCard($places[array_rand($places)]);
                break;
            case 'chooseSurvivalCard':
                $cards = $this->survivalDeck->getCardsInLocation('choice');
                $this->chooseSurvivalCard($cards[0]['type']);
                break;
            case 'chooseArtefactPlaceCard':
                $places = self::getObjectListFromDB("SELECT place_number FROM hunted_place_card WHERE hunted_player_id = $playerId AND location = 'REVEALED'", true);
                $this->chooseArtefactPlaceCard($places[array_rand($places)]);
                break;
            case 'phase2Cards':
            case 'reckoning':
            case 'endOfTurnActions':
            case 'hologram':
            case 'wrongTrack':
            case 'wrongTrackClone':
            case 'theRiver':
            case 'theBeach':
            case 'theRover':
            case 'theShelter':
            case 'theWreck':
            case 'theSource':
            case 'theArtefact':
            case 'theLair_Persecution':
            case 'playSurvivalCardDrawn':
            case 'creaturePlayPhase3CardsInResponse':
            case 'huntedPlayPhase3CardsInResponse':
                $this->pass();
                break;
            default:
                $this->gamestate->nextState("zombiePass");
                break;
        }
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }
}
