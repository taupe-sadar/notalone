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
 * states.inc.php
 *
 * NotAlone game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!


$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array("" => 2)
    ),

    // Note: ID=2 => your first state

    2 => array(
        "name" => "boardSetup",
        "description" => clienttranslate('You crashed on Artemia. You are Not Alone. ${actplayer} is hunting you! (${actplayer} must choose the board side)'),
        "descriptionmyturn" => clienttranslate('${you} are the creature. Select a side for the board.'),
        "type" => "activeplayer",
        "possibleactions" => array("actChooseBoardSide"),
        "transitions" => array("" => 3),
        "phase" => 0
    ),

    3 => array(
        "name" => "exploration",
        "description" => clienttranslate('Exploration: each hunted player play one Place card. Players may play Phase 1 cards.'),
        "descriptionmyturn" => clienttranslate('Exploration: ${you} must play one Place card, and you may play Phase 1 cards.'),
        "type" => "multipleactiveplayer",
        "action" => "stExploration",
        "possibleactions" => array("actExploration", "actResist", "actGiveUp", "actPass"),
        "transitions" => array("hunting" => 98, "endOfGame" => 99, "force-field" => 20, "sacrifice" => 21, "sixth-sense" => 22),
        "updateGameProgression" => true,
        "phase" => 1
    ),

    4 => array(
        "name" => "hunting",
        "description" => clienttranslate('Hunting: the creature may place tokens on Artemia.'),
        "descriptionmyturn" => clienttranslate('Hunting: ${you} may place tokens on Artemia.'),
        "type" => "activeplayer",
        "action" => "stHunting",
        "args" => "argsHunting",
        "possibleactions" => array("actPlaceToken"),
        "transitions" => array("refreshState" => 4, "phase2Cards" => 5, "forbidden-zone" => 26, "changeActivePlayer" => 98, "zombiePass" => 99),
        "phase" => 2
    ),

    5 => array(
        "name" => "phase2Cards",
        "description" => clienttranslate('Hunting: each player may play Phase 2 cards.'),
        "descriptionmyturn" => clienttranslate('Hunting: ${you} may play Phase 2 cards.'),
        "type" => "multipleactiveplayer",
        "action" => "stActivatePlayersWhoMightPlayACard",
        "possibleactions" => array("actPass"),
        "transitions" => array("continue" => 111, "forbidden-zone" => 26, "vortex" => 31, "changeActivePlayer" => 98),
        "phase" => 2
    ),

    6 => array(
        "name" => "reckoning",
        "description" => clienttranslate('Reckoning: each player may play Phase 3 cards.'),
        "descriptionmyturn" => clienttranslate('Reckoning: ${you} may play Phase 3 cards.'),
        "type" => "multipleactiveplayer",
        "action" => "stStartReckoning",
        "possibleactions" => array("actPass"),
        "transitions" => array("continue" => 7, "changeActivePlayer" => 98),
        "phase" => 3
    ),

    7 => array(
        "name" => "nextUncaughtPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextUncaughtPlayer",
        "transitions" => array(1 => 101, 2 => 102, 3 => 103, 4 => 104, 5 => 105, 6 => 106, 7 => 107, 8 => 108, 9 => 109, 10 => 110,
            "targetTokenEffects" => 8, "chooseArtefactPlaceCard" => 113),
        "phase" => 3
    ),

    8 => array(
        "name" => "targetTokenEffects",
        "description" => "",
        "type" => "game",
        "action" => "stTargetTokenEffects",
        "transitions" => array(1 => 101, 2 => 102, 3 => 103, 4 => 104, 5 => 105, 6 => 106, 7 => 107, 8 => 108, 9 => 109, 10 => 110,
            "artemiaToken" => 9, "scream" => 29, "toxin" => 30),
        "phase" => 3
    ),

    9 => array(
        "name" => "artemiaTokenEffects",
        "description" => clienttranslate('Each Hunted who explores a place where the Artemia token is located discards 1 Place card.'),
        "descriptionmyturn" => clienttranslate('${you} must discard 1 Place card because of the Artemia token.'),
        "type" => "multipleactiveplayer",
        "action" => "stArtemiaTokenEffects",
        "args" => "argsArtemiaTokenEffects",
        "possibleactions" => array("actDiscardPlaceCard", "actDiscardPlaceCards"),
        "transitions" => array("" => 10),
        "phase" => 3
    ),

    10 => array(
        "name" => "creatureTokenEffects",
        "description" => "",
        "type" => "game",
        "action" => "stCreatureTokenEffects",
        "transitions" => array("endOfTurn" => 11, "endOfGame" => 99),
        "phase" => 3
    ),

    11 => array(
        "name" => "endOfTurnActions",
        "description" => clienttranslate('End of turn: each player may play Phase 4 cards.'),
        "descriptionmyturn" => clienttranslate('End of turn: ${you} may play Phase 4 cards.'),
        "type" => "multipleactiveplayer",
        "action" => "stActivatePlayersWhoMightPlayACard",
        "possibleactions" => array("actPass"),
        "transitions" => array("continue" => 12, "double-back" => 38, "endOfGame" => 99),
        "phase" => 4
    ),

    12 => array(
        "name" => "endOfTurn",
        "description" => "",
        "type" => "game",
        "action" => "stEndOfTurn",
        "transitions" => array("exploration" => 3, "endOfGame" => 99),
        "phase" => 4
    ),

    20 => array(
        "name" => "forceField",
        "description" => clienttranslate('Force Field: ${actplayer} must target 2 adjacent places.'),
        "descriptionmyturn" => clienttranslate('Force Field: ${you} must target 2 adjacent places.'),
        "type" => "activeplayer",
        "possibleactions" => array("actPlaceToken"),
        "transitions" => array("exploration" => 3, "zombiePass" => 99),
        "phase" => 1
    ),

    21 => array(
        "name" => "sacrifice",
        "description" => clienttranslate('Sacrifice: ${actplayer} must discard 1 Place card.'),
        "descriptionmyturn" => clienttranslate('Sacrifice: ${you} must discard 1 Place card.'),
        "type" => "activeplayer",
        "possibleactions" => array("actDiscardPlaceCard", "actResist", "actGiveUp"),
        "transitions" => array("" => 3),
        "phase" => 1
    ),

    22 => array(
        "name" => "sixthSense",
        "description" => clienttranslate('Sixth Sense: ${actplayer} takes back 2 Place cards from its discard pile.'),
        "descriptionmyturn" => clienttranslate('Sixth Sense: ${you} take back 2 Place cards from your discard pile.'),
        "type" => "activeplayer",
        "possibleactions" => array("actTakeBack2PlaceCards"),
        "transitions" => array("" => 3),
        "phase" => 1
    ),

    23 => array(
        "name" => "anticipation",
        "description" => clienttranslate('Anticipation: ${actplayer} must choose one Hunted.'),
        "descriptionmyturn" => clienttranslate('Anticipation: ${you} must choose one Hunted.'),
        "type" => "activeplayer",
        "possibleactions" => array("actChooseHuntedPlayer"),
        "transitions" => array("hunting" => 4, "zombiePass" => 99),
        "phase" => 2
    ),

    24 => array(
        "name" => "ascendancyChooseHunted",
        "description" => clienttranslate('Ascendancy: ${actplayer} must choose one Hunted to discard all but 2 Place cards.'),
        "descriptionmyturn" => clienttranslate('Ascendancy: ${you} must choose one Hunted to discard all but 2 Place cards.'),
        "type" => "activeplayer",
        "possibleactions" => array("actChooseHuntedPlayer"),
        "transitions" => array("changeActivePlayer" => 98, "zombiePass" => 99),
        "phase" => 2
    ),

    25 => array(
        "name" => "ascendancyDiscard",
        "description" => clienttranslate('Ascendancy: ${actplayer} must discard all but 2 Place cards.'),
        "descriptionmyturn" => clienttranslate('Ascendancy: ${you} must discard all but 2 Place cards.'),
        "type" => "activeplayer",
        "possibleactions" => array("actDiscardPlaceCards"),
        "transitions" => array("" => 98),
        "phase" => 2
    ),

    26 => array(
        "name" => "forbiddenZone",
        "description" => clienttranslate('Forbidden Zone: All Hunted discard 1 Place card simultaneously.'),
        "descriptionmyturn" => clienttranslate('Forbidden Zone: ${you} must discard 1 Place card.'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array("actDiscardPlaceCard"),
        "transitions" => array("" => 4),
        "phase" => 2
    ),

    27 => array(
        "name" => "phobiaChooseHunted",
        "description" => clienttranslate('Phobia: ${actplayer} must choose which Hunted will reveal all but 2 Place cards.'),
        "descriptionmyturn" => clienttranslate('Phobia: ${you} must choose which Hunted will show you all but 2 Place cards.'),
        "type" => "activeplayer",
        "possibleactions" => array("actChooseHuntedPlayer"),
        "transitions" => array("changeActivePlayer" => 98, "zombiePass" => 99),
        "phase" => 2
    ),

    28 => array(
        "name" => "phobiaSelectPlacesToShow",
        "description" => clienttranslate('Phobia: ${actplayer} must reveal all but 2 Place cards to the Creature.'),
        "descriptionmyturn" => clienttranslate('Phobia: ${you} must reveal all but 2 Place cards to the Creature.'),
        "type" => "activeplayer",
        "possibleactions" => array("actShowPlaces"),
        "transitions" => array("" => 98),
        "phase" => 2
    ),

    29 => array(
        "name" => "scream",
        "description" => clienttranslate('Scream: Each Hunted on the targeted place must discard 2 Place cards or lose 1 Will.'),
        "descriptionmyturn" => clienttranslate('Scream: ${you} must discard 2 Place cards or lose 1 Will.'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array("actDiscardPlaceCards", "actLoseWill"),
        "transitions" => array("" => 8),
        "phase" => 3
    ),

    30 => array(
        "name" => "toxin",
        "description" => clienttranslate('Toxin: Each Hunted on the targeted place discards 1 Survival card.'),
        "descriptionmyturn" => clienttranslate('Toxin: ${you} must discard 1 Survival card.'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array("actDiscardSurvivalCard"),
        "transitions" => array("" => 8),
        "phase" => 3
    ),

    31 => array(
        "name" => "vortex",
        "description" => clienttranslate('Vortex: ${actplayer} swap its played Place card for one Place card from its discard pile.'),
        "descriptionmyturn" => clienttranslate('Vortex: ${you} must choose a Place card from you discard pile.'),
        "type" => "activeplayer",
        "possibleactions" => array("actSwapPlaceCard"),
        "transitions" => array("" => 98),
        "phase" => 2
    ),

    32 => array(
        "name" => "gate",
        "description" => clienttranslate('Gate: ${actplayer} must choose an place adjacent to ${resolvingPlaceName} to copy its power.'),
        "descriptionmyturn" => clienttranslate('Gate: ${you} must choose an place adjacent to ${resolvingPlaceName} to copy its power.'),
        "type" => "activeplayer",
        "args" => "argsResolvingPlace",
        "possibleactions" => array("actCopyAdjacentPlace"),
        "transitions" => array(1 => 101, 2 => 102, 3 => 103, 4 => 104, 5 => 105, 6 => 106, 7 => 107, 8 => 108, 9 => 109),
        "phase" => 3
    ),

    33 => array(
        "name" => "hologram",
        "description" => clienttranslate('Hologram: ${actplayer} can move the Artemia token to an adjacent place.'),
        "descriptionmyturn" => clienttranslate('Hologram: ${you} must move the Artemia token to an adjacent place.'),
        "type" => "activeplayer",
        "args" => "argsHuntTokenAdjacentPlaces",
        "possibleactions" => array("actMoveHuntToken"),
        "transitions" => array("continue" => 117),
        "phase" => 3,
        "token" => "artemia"
    ),

    34 => array(
        "name" => "wrongTrack",
        "description" => clienttranslate('Wrong Track: ${actplayer} can move the Creature token to an adjacent place.'),
        "descriptionmyturn" => clienttranslate('Wrong Track: ${you} must move the Creature token to an adjacent place.'),
        "type" => "activeplayer",
        "action" => "stWrongTrack",
        "args" => "argsHuntTokenAdjacentPlaces",
        "possibleactions" => array("actMoveHuntToken"),
        "transitions" => array("continue" => 117, "clone" => 35),
        "phase" => 3,
        "token" => "creature"
    ),

    35 => array(
        "name" => "wrongTrackClone",
        "description" => clienttranslate('Wrong Track: ${actplayer} can move the Creature or its Clone to an adjacent place.'),
        "descriptionmyturn" => clienttranslate('Wrong Track: ${you} must move the Creature or its Clone to an adjacent place.'),
        "type" => "activeplayer",
        "args" => "argsWrongTrackClone",
        "possibleactions" => array("actMoveHuntToken"),
        "transitions" => array("continue" => 117),
        "phase" => 3
    ),

    36 => array(
        "name" => "cataclysm",
        "description" => clienttranslate('Cataclysm: ${actplayer} chooses which Place will be ineffective.'),
        "descriptionmyturn" => clienttranslate('Cataclysm: ${you} must choose which Place will be ineffective.'),
        "type" => "activeplayer",
        "possibleactions" => array("actChooseIneffectivePlace"),
        "transitions" => array("continue" => 7, "zombiePass" => 99),
        "phase" => 3
    ),

    37 => array(
        "name" => "detour",
        "description" => clienttranslate('Detour: ${actplayer} moves one Hunted to an adjacent place.'),
        "descriptionmyturn" => clienttranslate('Detour: ${you} must move one Hunted to an adjacent place.'),
        "type" => "activeplayer",
        "possibleactions" => array("actMoveHunted"),
        "transitions" => array("continue" => 119, "zombiePass" => 99),
        "phase" => 3
    ),

    38 => array(
        "name" => "doubleBack",
        "description" => clienttranslate('Double Back: ${actplayer} must choose which Place he takes back.'),
        "descriptionmyturn" => clienttranslate('Double Back: ${you} must choose which Place you take back.'),
        "type" => "activeplayer",
        "possibleactions" => array("actTakeBackPlayedCard"),
        "transitions" => array("" => 11),
        "phase" => 4
    ),

    101 => array(
        "name" => "theLair",
        "description" => clienttranslate('${actplayer} can take back its discarded Place cards OR copy the power of the place with the Creature token.'),
        "descriptionmyturn" => clienttranslate('${you} can take back your discarded Place cards OR copy the power of the place with the Creature token.'),
        "type" => "activeplayer",
        "action" => "stPlaceReckoning",
        "args" => "argsTheLairState",
        "possibleactions" => array("actTakeBackDiscardedPlaceCards", "actCopyCreaturePlace"),
        "transitions" => array(2 => 102, 3 => 103, 4 => 104, 5 => 105, 6 => 106, 7 => 107, 8 => 108, 9 => 109, "continue" => 7, "persecution" => 114, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3,
        "place" => 1
    ),

    102 => array(
        "name" => "theJungle",
        "description" => clienttranslate('${actplayer} can take back 1 discarded Place card and ${resolvingPlaceName}.'),
        "descriptionmyturn" => clienttranslate('${you} can take back 1 discarded Place card and ${resolvingPlaceName}.'),
        "type" => "activeplayer",
        "action" => "stPlaceReckoning",
        "args" => "argsResolvingPlace",
        "possibleactions" => array("actTakeBackPlayedCard", "actTheJungle"),
        "transitions" => array("continue" => 7, "persecution" => 115, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3,
        "place" => 2
    ),

    103 => array(
        "name" => "theRiver",
        "description" => clienttranslate('${actplayer} can use the River OR take back 1 discarded Place card.'),
        "descriptionmyturn" => clienttranslate('${you} can use the River OR take back 1 discarded Place card.'),
        "type" => "activeplayer",
        "action" => "stPlaceReckoning",
        "possibleactions" => array("actTheRiver", "actTakeBackDiscardedPlace", "actPass"),
        "transitions" => array("continue" => 7, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3,
        "place" => 3
    ),

    104 => array(
        "name" => "theBeach",
        "description" => clienttranslate('${actplayer} can use the Beach OR take back 1 discarded Place card.'),
        "descriptionmyturn" => clienttranslate('${you} can use the Beach OR take back 1 discarded Place card.'),
        "type" => "activeplayer",
        "action" => "stPlaceReckoning",
        "possibleactions" => array("actTheBeach", "actTakeBackDiscardedPlace", "actPass"),
        "transitions" => array("continue" => 7, "endOfGame" => 99, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3,
        "place" => 4
    ),

    105 => array(
        "name" => "theRover",
        "description" => clienttranslate('${actplayer} can take a new Place from the reserve OR take back 1 discarded Place card.'),
        "descriptionmyturn" => clienttranslate('${you} can take a new Place from the reserve OR take back 1 discarded Place card.'),
        "type" => "activeplayer",
        "action" => "stPlaceReckoning",
        "possibleactions" => array("actTheRover", "actTakeBackDiscardedPlace", "actPass"),
        "transitions" => array("continue" => 7, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3,
        "place" => 5
    ),

    106 => array(
        "name" => "theSwamp",
        "description" => clienttranslate('${actplayer} can take back 2 discarded Place cards and ${resolvingPlaceName}.'),
        "descriptionmyturn" => clienttranslate('${you} can take back 2 discarded Place cards and ${resolvingPlaceName}.'),
        "type" => "activeplayer",
        "action" => "stPlaceReckoning",
        "args" => "argsResolvingPlace",
        "possibleactions" => array("actTheSwamp"),
        "transitions" => array("continue" => 7, "persecution" => 115, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3,
        "place" => 6
    ),

    107 => array(
        "name" => "theShelter",
        "description" => clienttranslate('${actplayer} can draw 2 Survival cards and keep 1 OR take back 1 discarded Place card.'),
        "descriptionmyturn" => clienttranslate('${you} can draw 2 Survival cards and keep 1 OR take back 1 discarded Place card.'),
        "type" => "activeplayer",
        "action" => "stPlaceReckoning",
        "possibleactions" => array("actTheShelter", "actTakeBackDiscardedPlace", "actPass"),
        "transitions" => array("chooseSurvivalCard" => 112, "continue" => 7, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3,
        "place" => 7
    ),

    108 => array(
        "name" => "theWreck",
        "description" => clienttranslate('${actplayer} can move the Rescue counter forward 1 space OR take back 1 discarded Place card.'),
        "descriptionmyturn" => clienttranslate('${you} can move the Rescue counter forward 1 space OR take back 1 discarded Place card.'),
        "type" => "activeplayer",
        "action" => "stPlaceReckoning",
        "possibleactions" => array("actTheWreck", "actTakeBackDiscardedPlace", "actPass"),
        "transitions" => array("continue" => 7, "endOfGame" => 99, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3,
        "place" => 8
    ),

    109 => array(
        "name" => "theSource",
        "description" => clienttranslate('${actplayer} can choose a Hunted player to regain 1 Will OR draw 1 Survival card OR take back 1 discarded Place card.'),
        "descriptionmyturn" => clienttranslate('${you} can choose a Hunted player to regain 1 Will OR draw 1 Survival card OR take back 1 discarded Place card.'),
        "type" => "activeplayer",
        "action" => "stPlaceReckoning",
        "possibleactions" => array("actRegainWill", "actDrawSurvivalCard", "actTakeBackDiscardedPlace", "actPass"),
        "transitions" => array("continue" => 7, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98, "playSurvivalCardDrawn" => 116),
        "phase" => 3,
        "place" => 9
    ),

    110 => array(
        "name" => "theArtefact",
        "description" => clienttranslate('${actplayer} can use the Artefact OR take back 1 discarded Place card.'),
        "descriptionmyturn" => clienttranslate('${you} can use the Artefact OR take back 1 discarded Place card.'),
        "type" => "activeplayer",
        "action" => "stPlaceReckoning",
        "possibleactions" => array("actTheArtefact", "actTakeBackDiscardedPlace", "actPass"),
        "transitions" => array("continue" => 7, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3,
        "place" => 10
    ),

    111 => array(
        "name" => "chooseRiverPlaceCard",
        "description" => clienttranslate('The River: some players must choose which Place card they reveal.'),
        "descriptionmyturn" => clienttranslate('${you} must choose which Place card you reveal.'),
        "type" => "multipleactiveplayer",
        "action" => "stChooseRiverPlaceCard",
        "possibleactions" => array("actChooseRiverPlaceCard"),
        "transitions" => array("" => 6),
        "phase" => 3
    ),

    112 => array(
        "name" => "chooseSurvivalCard",
        "description" => clienttranslate('${actplayer} must choose one Survival card and discard the second.'),
        "descriptionmyturn" => clienttranslate('${you} must choose one Survival card and discard the second.'),
        "type" => "activeplayer",
        "args" => "argsChooseSurvivalCard",
        "possibleactions" => array("actChooseSurvivalCard"),
        "transitions" => array("playSurvivalCardDrawn" => 116),
        "phase" => 3
    ),

    113 => array(
        "name" => "chooseArtefactPlaceCard",
        "description" => clienttranslate('${actplayer} must choose which place he resolves first.'),
        "descriptionmyturn" => clienttranslate('${you} must choose which place you resolve first.'),
        "type" => "activeplayer",
        "args" => "argsChooseArtefactPlaceCard",
        "possibleactions" => array("actChooseArtefactPlaceCard"),
        "transitions" => array(1 => 101, 2 => 102, 3 => 103, 4 => 104, 5 => 105, 6 => 106, 7 => 107, 8 => 108, 9 => 109, 10 => 110, "changeActivePlayer" => 98),
        "phase" => 3
    ),

    114 => array(
        "name" => "theLair_Persecution",
        "description" => clienttranslate('${actplayer} can copy the power of the place with the Creature token OR take back 1 Place card (because of Persecution).'),
        "descriptionmyturn" => clienttranslate('${you} can copy the power of the place with the Creature token OR take back 1 Place card (because of Persecution).'),
        "type" => "activeplayer",
        "args" => "argsTheLairState",
        "possibleactions" => array("actCopyCreaturePlace", "actTakeBackDiscardedPlace", "actPass"),
        "transitions" => array(2 => 102, 3 => 103, 4 => 104, 5 => 105, 6 => 106, 7 => 107, 8 => 108, 9 => 109, "continue" => 7, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3,
        "place" => 1
    ),

    115 => array(
        "name" => "Jungle_Swamp_Persecution",
        "description" => clienttranslate('${actplayer} can take back ${resolvingPlaceName} OR 1 discarded Place card (because of Persecution).'),
        "descriptionmyturn" => clienttranslate('${you} can take back ${resolvingPlaceName} OR 1 discarded Place card (because of Persecution).'),
        "type" => "activeplayer",
        "args" => "argsResolvingPlace",
        "possibleactions" => array("actTakeBackPlayedCard", "actTakeBackDiscardedPlace"),
        "transitions" => array("continue" => 7, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3,
        "place" => 2 // or 6 but not really important here, as long as the value exists to play "Drone" and "Gate"
    ),

    116 => array(
        "name" => "playSurvivalCardDrawn",
        "description" => clienttranslate('Reckoning: ${actplayer} may play Phase 3 cards.'),
        "descriptionmyturn" => clienttranslate('Reckoning: ${you} may play Phase 3 cards.'),
        "type" => "activeplayer",
        "action" => "stPlaySurvivalCardDrawn",
        "possibleactions" => array("actPass"),
        "transitions" => array("continue" => 7, "drone" => 105, "gate" => 32, "changeActivePlayer" => 98),
        "phase" => 3
    ),

    117 => array(
        "name" => "ifCreatureCanRetaliate",
        "description" => '',
        "type" => "game",
        "action" => "stIfCreatureCanRetaliate",
        "transitions" => array("continue" => 7, "retaliate" => 118),
        "phase" => 3
    ),

    118 => array(
        "name" => "creaturePlayPhase3CardsInResponse",
        "description" => clienttranslate('${actplayer} may play Phase 3 cards in response to ${card_name}.'),
        "descriptionmyturn" => clienttranslate('${you} may play Phase 3 cards in response to ${card_name}.'),
        "type" => "activeplayer",
        "args" => "argsPreviousSurvivalCardPlayed",
        "possibleactions" => array("actPass"),
        "transitions" => array("continue" => 7, "changeActivePlayer" => 98),
        "phase" => 3
    ),

    119 => array(
        "name" => "huntedPlayPhase3CardsInResponse",
        "description" => clienttranslate('Hunted players may play Phase 3 cards in response to ${card_name}.'),
        "descriptionmyturn" => clienttranslate('${you} may play Phase 3 cards in response to ${card_name}.'),
        "type" => "multipleactiveplayer",
        "args" => "argsPreviousHuntCardPlayed",
        "action" => "stActivatePlayersWhoMightPlayACard",
        "possibleactions" => array("actPass"),
        "transitions" => array("continue" => 7, "changeActivePlayer" => 98),
        "phase" => 3
    ),

    // Hack to workaround the limitation of not being able to change active player between 2 states of type "activeplayer"
    98 => array(
        "name" => "doNothingButChangeActivePlayer",
        "description" => "",
        "type" => "game",
        "action" => "stDoNothing",
        "transitions" => array("hunting" => 4, "anticipation" => 23, "ascendancy" => 24, "ascendancyDiscard" => 25, "phobia" => 27, "phobiaSelectPlacesToShow" => 28, "hologram" => 33, "wrong-track" => 34, "cataclysm" => 36, "detour" => 37),
        "phase" => 0
    ),

    // Final state.
    // Please do not modify.
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd",
        "phase" => 0
    )

);
