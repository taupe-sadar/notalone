/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * NotAlone implementation : © Romain Fromi <romain.fromi@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * notalone.js
 *
 * NotAlone user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

// Polyfill for Internet Explorer
if (!String.prototype.startsWith) {
    String.prototype.startsWith = function (searchString, position) {
        position = position || 0;
        return this.indexOf(searchString, position) === position;
    };
}
if (!Array.prototype.includes) {
    Object.defineProperty(Array.prototype, "includes", {
        enumerable: false,
        value: function (obj) {
            var newArr = this.filter(function (el) {
                return el === obj;
            });
            return newArr.length > 0;
        }
    });
}

define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
], function (dojo, declare) {
    return declare("bgagame.notalone", ebg.core.gamegui, {
        constructor: function () {
        },

        /*
            setup:

            This method must set up the game user interface according to current game situation specified
            in parameters.

            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)

            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */

        setup: function (gamedatas) {

            this.player = gamedatas.players[this.player_id];

            // Artemia
            this.artemia = new ebg.stock();
            this.artemia.setSelectionMode(0);
            this.artemia.create(this, $('artemia'), 136, 196);
            this.artemia.image_items_per_row = 5;
            this.artemia.item_margin = 7;
            this.artemia.onItemCreate = dojo.hitch(this, 'setupCard');
            this.artemia.addItemType(1, 1, g_gamethemeurl + 'img/place_cards.jpg', 0);
            this.artemia.addItemType(2, 2, g_gamethemeurl + 'img/place_cards.jpg', 1);
            this.artemia.addItemType(3, 3, g_gamethemeurl + 'img/place_cards.jpg', 2);
            this.artemia.addItemType(4, 4, g_gamethemeurl + 'img/place_cards.jpg', 3);
            this.artemia.addItemType(5, 5, g_gamethemeurl + 'img/place_cards.jpg', 4);
            this.artemia.addItemType(6, 6, g_gamethemeurl + 'img/place_cards.jpg', 5);
            this.artemia.addItemType(7, 7, g_gamethemeurl + 'img/place_cards.jpg', 6);
            this.artemia.addItemType(8, 8, g_gamethemeurl + 'img/place_cards.jpg', 7);
            this.artemia.addItemType(9, 9, g_gamethemeurl + 'img/place_cards.jpg', 8);
            this.artemia.addItemType(10, 10, g_gamethemeurl + 'img/place_cards.jpg', 9);

            if( gamedatas.gamestate != 'boardSetup')
                this.buildArtemiaZone();

            var tmpobj = {id: "marker_counter", "data-value": gamedatas.markerCounter};
            tmpobj['class'] = "counter";
            dojo.create("div", tmpobj, "artemia_wrap");
            this.addTooltip('marker_counter', _('Marker counter for The Beach'), '');

            // Setting up player boards
            this.willCounters = {};
            for (var playerId in gamedatas.players) {
                if (gamedatas.players.hasOwnProperty(playerId)) {
                    var player = gamedatas.players[playerId];

                    // Hunted will counters
                    if (player.hasOwnProperty('willCounters')) {
                        dojo.place(this.format_block('jstpl_hunted_board', player), $('player_board_' + playerId));
                        this.willCounters[playerId] = new ebg.stock();
                        this.willCounters[playerId].setSelectionMode(0);
                        this.willCounters[playerId].create(this, $('will_counters_player_' + playerId), 30, 30);
                        this.willCounters[playerId].addItemType(1, 0, g_gamethemeurl + 'img/counters.png', 0);
                        for (var willCounter = 1; willCounter <= player['willCounters']; willCounter++) {
                            this.willCounters[playerId].addToStock(1);
                        }
                        this.addTooltip('will_counters_player_' + playerId, _('Will counter'), '');

                        player.placeCardsCounter = new ebg.counter();
                        player.placeCardsCounter.create('place_cards_count_player_' + playerId);
                        this.setPlayerPlaceCardsSize(playerId, player.placeCardsSize);

                        player.survivalCardsCounter = new ebg.counter();
                        player.survivalCardsCounter.create('survival_cards_count_player_' + playerId);
                        this.setPlayerSurvivalCardsSize(playerId, player.survivalCardsSize);

                        gamedatas.players[playerId].discard = new ebg.stock();
                        var discard = gamedatas.players[playerId].discard;
                        discard.order_items = false;
                        discard.setSelectionMode(0);
                        discard.setSelectionAppearance('class');
                        discard.create(this, $('discard_player_' + playerId), 32, 32);
                        discard.addItemType(1, 0, g_gamethemeurl + 'img/place_cards_numbers.jpg', 0);
                        discard.addItemType(2, 0, g_gamethemeurl + 'img/place_cards_numbers.jpg', 1);
                        discard.addItemType(3, 0, g_gamethemeurl + 'img/place_cards_numbers.jpg', 2);
                        discard.addItemType(4, 0, g_gamethemeurl + 'img/place_cards_numbers.jpg', 3);
                        discard.addItemType(5, 0, g_gamethemeurl + 'img/place_cards_numbers.jpg', 4);
                        discard.addItemType(6, 0, g_gamethemeurl + 'img/place_cards_numbers.jpg', 5);
                        discard.addItemType(7, 0, g_gamethemeurl + 'img/place_cards_numbers.jpg', 6);
                        discard.addItemType(8, 0, g_gamethemeurl + 'img/place_cards_numbers.jpg', 7);
                        discard.addItemType(9, 0, g_gamethemeurl + 'img/place_cards_numbers.jpg', 8);
                        discard.addItemType(10, 0, g_gamethemeurl + 'img/place_cards_numbers.jpg', 9);
                        discard.addItemType(0, 0, g_gamethemeurl + 'img/place_cards_numbers.jpg', 10);
                        if (player.discardedPlaces) {
                            for (var j = 0; j < player.discardedPlaces.length; j++) {
                                this.addPlaceToDiscard(playerId, player.discardedPlaces[j], j);
                            }
                        }
                        if (player.playedPlaces && (!gamedatas.gamestate.phase || gamedatas.gamestate.phase > 1)) {
                            this.displayPlayerOnArtemia(player, false);
                            for (var p = 0; p < player.playedPlaces.length; p++) {
                                this.addPlayedPlace(playerId, player.playedPlaces[p]);
                            }
                        }
                        if (playerId === this.player_id.toString()) {
                            dojo.connect(discard, 'onChangeSelection', this, 'onDiscardSelectionChange');
                        }
                    } else {
                        // Creature player board
                        dojo.place(this.format_block('jstpl_creature_board', player), $('player_board_' + playerId));
                        player.huntCardsCounter = new ebg.counter();
                        player.huntCardsCounter.create('hunt_cards_count');
                        this.setPlayerHuntCardsSize(playerId, player.huntCardsSize);
                    }
                }
            }

            if (gamedatas.boardSide) {
                this.displayBoard();
            }

            for (var placeNumber in gamedatas.placesReserve) {
                if (gamedatas.placesReserve.hasOwnProperty(placeNumber)) {
                    this.addTooltip("reserve_" + placeNumber, _("Copies available in the reserve."), '')
                    dojo.byId("reserve_" + placeNumber).textContent = 'x' + gamedatas.placesReserve[placeNumber].quantity;
                }
            }

            if (this.player) {
                // Player hand
                this.playerHand = new ebg.stock();
                this.playerHand.setSelectionMode(1);
                this.playerHand.setSelectionAppearance('class');
                this.playerHand.autowidth = true;
                this.playerHand.item_margin = 10;
                this.playerHand.create(this, $('hand'), 136, 196);
                this.playerHand.image_items_per_row = 5;
                this.playerHand.onItemCreate = dojo.hitch(this, 'setupCard');
                if (gamedatas.hasOwnProperty('playerPlaceCards')) {
                    this.playerHand.addItemType(1, 1, g_gamethemeurl + 'img/place_cards.jpg', 0);
                    this.playerHand.addItemType(2, 2, g_gamethemeurl + 'img/place_cards.jpg', 1);
                    this.playerHand.addItemType(3, 3, g_gamethemeurl + 'img/place_cards.jpg', 2);
                    this.playerHand.addItemType(4, 4, g_gamethemeurl + 'img/place_cards.jpg', 3);
                    this.playerHand.addItemType(5, 5, g_gamethemeurl + 'img/place_cards.jpg', 4);
                    this.playerHand.addItemType(6, 6, g_gamethemeurl + 'img/place_cards.jpg', 5);
                    this.playerHand.addItemType(7, 7, g_gamethemeurl + 'img/place_cards.jpg', 6);
                    this.playerHand.addItemType(8, 8, g_gamethemeurl + 'img/place_cards.jpg', 7);
                    this.playerHand.addItemType(9, 9, g_gamethemeurl + 'img/place_cards.jpg', 8);
                    this.playerHand.addItemType(10, 10, g_gamethemeurl + 'img/place_cards.jpg', 9);
                    for (var i = 0; i < gamedatas.playerPlaceCards.length; i++) {
                        this.playerHand.addToStockWithId(gamedatas.playerPlaceCards[i], gamedatas.playerPlaceCards[i]);
                    }
                    if (gamedatas.gamestate.phase && gamedatas.gamestate.phase === 1) {
                        this.playerHand.setSelectionMode(2);
                        for (var k = 0; k < this.player.playedPlaces.length; k++) {
                            this.playerHand.addToStockWithId(this.player.playedPlaces[k], this.player.playedPlaces[k]);
                            this.playerHand.selectItem(this.player.playedPlaces[k]);
                        }
                    }
                    this.playerHand.addItemType(11, 11, g_gamethemeurl + 'img/hunt_survival_cards.jpg', 1);
                    for (var survivalCardId in gamedatas.playerSurvivalCards) {
                        if (gamedatas.playerSurvivalCards.hasOwnProperty(survivalCardId)) {
                            var survivalCard = gamedatas.playerSurvivalCards[survivalCardId];
                            this.playerHand.addToStockWithId(11, survivalCard.type);
                        }
                    }
                } else {
                    this.playerHand.addItemType(12, 0, g_gamethemeurl + 'img/hunt_survival_cards.jpg', 0);
                    for (var huntCardId in gamedatas.playerHuntCards) {
                        if (gamedatas.playerHuntCards.hasOwnProperty(huntCardId)) {
                            var huntCard = gamedatas.playerHuntCards[huntCardId];
                            this.playerHand.addToStockWithId(12, huntCard.type);
                        }
                    }
                }
            } else {
                dojo.destroy('hand_zone');
            }

            if (parseInt(gamedatas.creatureToken) > 0) {
                var tmpobj = {id: 'creature_token'};
                tmpobj['class'] = "creature huntToken";

                dojo.create("div", tmpobj, "artemia_tokens_" + gamedatas.creatureToken);
                this.addTooltip('creature_token', _('Creature token'), '');
            }
            if (parseInt(gamedatas.artemiaToken) > 0) {
                var tmpobj = {id: 'artemia_token'};
                tmpobj['class'] = "artemia huntToken";

                dojo.create("div", tmpobj, "artemia_tokens_" + gamedatas.artemiaToken);
                this.addTooltip('artemia_token', _('Artemia token'), '');
            }
            if (parseInt(gamedatas.targetToken) > 0) {
                var tmpobj = {id: 'target_token'};
                tmpobj['class'] = "target huntToken";

                dojo.create("div", tmpobj, "artemia_tokens_" + gamedatas.targetToken);
                this.addTooltip('target_token', _('Target token'), '');
            }

            dojo.connect(this.playerHand, 'onChangeSelection', this, 'onHandCardSelectionChange');

            this.huntDiscard = new ebg.stock();
            this.huntDiscard.setSelectionMode(0);
            this.huntDiscard.setOverlap(70, 0);
            this.huntDiscard.item_margin = 10;
            this.huntDiscard.autowidth = true;
            this.huntDiscard.create(this, $('huntDiscard'), 136, 196);
            this.huntDiscard.onItemCreate = dojo.hitch(this, 'setupCard');
            this.huntDiscard.order_items = false;
            this.huntDiscard.addItemType(12, 0, g_gamethemeurl + 'img/hunt_survival_cards.jpg', 0);
            for (var h = 0; h < gamedatas.discardedHuntCards.length; h++) {
                this.huntDiscard.addToStockWithId(12, gamedatas.discardedHuntCards[h].type);
            }

            this.survivalDiscard = new ebg.stock();
            this.survivalDiscard.setSelectionMode(0);
            this.survivalDiscard.setOverlap(70, 0);
            this.survivalDiscard.item_margin = 10;
            this.survivalDiscard.autowidth = true;
            this.survivalDiscard.create(this, $('survivalDiscard'), 136, 196);
            this.survivalDiscard.onItemCreate = dojo.hitch(this, 'setupCard');
            this.survivalDiscard.order_items = false;
            this.survivalDiscard.addItemType(11, 0, g_gamethemeurl + 'img/hunt_survival_cards.jpg', 1);
            for (var s = 0; s < gamedatas.discardedSurvivalCards.length; s++) {
                this.survivalDiscard.addToStockWithId(11, gamedatas.discardedSurvivalCards[s].type);
            }

            gamedatas.huntDeckSize = parseInt(gamedatas.huntDeckSize);
            this.huntDeckCounter = new ebg.counter();
            this.huntDeckCounter.create('hunt_deck_count');
            this.huntDeckCounter.setValue(gamedatas.huntDeckSize);
            this.addTooltip('hunt_deck_count', _('Number of Hunt cards left in the deck.'), '');

            gamedatas.survivalDeckSize = parseInt(gamedatas.survivalDeckSize);
            this.survivalDeckCounter = new ebg.counter();
            this.survivalDeckCounter.create('survival_deck_count');
            this.survivalDeckCounter.setValue(gamedatas.survivalDeckSize);
            this.addTooltip('survival_deck_count', _('Number of Survival cards left in the deck.'), '');

            this.ongoingEffects = new ebg.stock();
            this.ongoingEffects.setSelectionMode(0);
            this.ongoingEffects.item_margin = 10;
            this.ongoingEffects.autowidth = true;
            this.ongoingEffects.create(this, $('ongoingEffects'), 136, 196);
            this.ongoingEffects.onItemCreate = dojo.hitch(this, 'setupCard');
            this.ongoingEffects.addItemType(11, 0, g_gamethemeurl + 'img/hunt_survival_cards.jpg', 1);
            this.ongoingEffects.addItemType(12, 0, g_gamethemeurl + 'img/hunt_survival_cards.jpg', 0);
            if (gamedatas.ongoingEffects.length > 0) {
                dojo.removeClass('ongoing_effects_zone', 'hidden');
                for (var e = 0; e < gamedatas.ongoingEffects.length; e++) {
                    var card = gamedatas.ongoingEffects[e];
                    var type = gamedatas.huntCards.hasOwnProperty(card) ? 12 : 11;
                    this.ongoingEffects.addToStockWithId(type, card);
                }
            }

            // Workaround: display tooltip on card names for past logs. Need a callback when a log get displayed for better result.
            var self = this;
            setTimeout(function () {
                self.addAllCardNameLogsTooltips();
            }, 1000);

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();
        },


        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function (stateName, state) {
            this.stateHandles = [];
            var self = this;
            switch (stateName) {
                case 'boardSetup':
                    if (this.isCurrentPlayerActive()) {
                        dojo.setStyle("hand_zone", "display", "none");
                        dojo.addClass("artemia_zone", "hidden");
                        this.gamedatas.boardSide = 1;
                        this.displayBoard();
                    }
                    else
                    {
                        this.buildArtemiaZone();
                    }
                    break;
                case 'exploration':
                    if (this.player && this.player_id.toString() !== this.gamedatas.creaturePlayer.toString()) {
                        this.player.discard.setSelectionMode(2);
                        this.playerHand.setSelectionMode(2);
                    }
                    break;
                case 'forceField':
                    if (this.isCurrentPlayerActive()) {
                        dojo.query(".huntTokensWrapper.adjacentPlaces").forEach(function (tokenWrapper) {
                            var tmpobj = {};
                            tmpobj['class'] = 'target huntToken placeholder';
                            var targetToken = dojo.create("div", tmpobj, tokenWrapper);
                            self.stateHandles.push(dojo.connect(targetToken, 'onclick', self, 'onPlaceTargetToken'));
                        });
                    }
                    break;
                case 'hunting':
                    if (this.playerHand) {
                        this.playerHand.setSelectionMode(1);
                    }
                    if (this.isCurrentPlayerActive()) {
                        dojo.query("#artemia .huntTokensWrapper").forEach(function (tokenWrapper) {
                            if (state.args.creatureToken) {
                                var tmpobj = {};
                                tmpobj['class'] = 'creature huntToken placeholder';
                                var creatureToken = dojo.create("div", tmpobj, tokenWrapper);
                                self.stateHandles.push(dojo.connect(creatureToken, 'onclick', self, 'onPlaceCreatureToken'));
                            }
                            if (state.args.artemiaToken && !state.args.artemia2AdjacentPlaces) {
                                var tmpobj = {};
                                tmpobj['class'] = 'artemia huntToken placeholder';
                                var artemiaToken = dojo.create("div", tmpobj, tokenWrapper);
                                self.stateHandles.push(dojo.connect(artemiaToken, 'onclick', self, 'onPlaceArtemiaToken'));
                            }
                            if (state.args.targetToken && !state.args.target2AdjacentPlaces) {
                                var tmpobj = {};
                                tmpobj['class'] = 'target huntToken placeholder';
                                var targetToken = dojo.create("div", tmpobj, tokenWrapper);
                                self.stateHandles.push(dojo.connect(targetToken, 'onclick', self, 'onPlaceTargetToken'));
                            }
                        });
                        if (state.args.artemia2AdjacentPlaces || state.args.target2AdjacentPlaces) {
                            dojo.query(".huntTokensWrapper.adjacentPlaces").forEach(function (tokenWrapper) {
                                if (state.args.artemia2AdjacentPlaces) {
                                    var tmpobj = {};
                                    tmpobj['class'] = 'artemia huntToken placeholder';
                                    var artemiaToken = dojo.create("div", tmpobj, tokenWrapper);
                                    self.stateHandles.push(dojo.connect(artemiaToken, 'onclick', self, 'onPlaceArtemiaToken'));
                                }
                                if (state.args.target2AdjacentPlaces) {
                                    var tmpobj = {};
                                    tmpobj['class'] = 'target huntToken placeholder';
                                    var targetToken = dojo.create("div", tmpobj, tokenWrapper);
                                    self.stateHandles.push(dojo.connect(targetToken, 'onclick', self, 'onPlaceTargetToken'));
                                }
                            });
                        }
                    }
                    break;
                case 'reckoning':
                    if (this.player) {
                        this.player.placePendingEffect = null;
                    }
                    break;
                case 'anticipation':
                case 'detour':
                    if (this.isCurrentPlayerActive()) {
                        for (var huntedPlayerId in this.gamedatas.players) {
                            if (this.gamedatas.players.hasOwnProperty(huntedPlayerId) && huntedPlayerId !== this.gamedatas.creaturePlayer) {
                                this.makeHuntedSelectable(huntedPlayerId);
                            }
                        }
                    }
                    break;
                case 'ascendancyChooseHunted':
                case 'phobiaChooseHunted':
                    if (this.isCurrentPlayerActive()) {
                        for (var huntedId in this.gamedatas.players) {
                            if (this.gamedatas.players.hasOwnProperty(huntedId) && huntedId !== this.gamedatas.creaturePlayer && this.gamedatas.players[huntedId].placeCardsSize > 2) {
                                this.makeHuntedSelectable(huntedId);
                            }
                        }
                    }
                    break;
                case 'ascendancyDiscard':
                case 'phobiaSelectPlacesToShow':
                case 'scream':
                    if (this.isCurrentPlayerActive()) {
                        this.playerHand.setSelectionMode(2);
                    }
                    break;
                case 'theJungle':
                case 'vortex':
                    if (this.isCurrentPlayerActive()) {
                        this.player.discard.setSelectionMode(1);
                        this.player.discard.unselectAll();
                    }
                    break;
                case 'theRover':
                    if (this.isCurrentPlayerActive()) {
                        for (var placeNum in this.gamedatas.placesReserve) {
                            if (this.gamedatas.placesReserve.hasOwnProperty(placeNum) && parseInt(this.gamedatas.placesReserve[placeNum].quantity) > 0) {
                                dojo.addClass('artemia_item_' + placeNum, 'selectable');
                            }
                        }
                    }
                    break;
                case 'theSwamp':
                case 'sixthSense':
                    if (this.isCurrentPlayerActive()) {
                        this.player.discard.setSelectionMode(2);
                        this.player.discard.unselectAll();
                    }
                    break;
                case 'theSource':
                    if (this.isCurrentPlayerActive()) {
                        for (var id in this.willCounters) {
                            if (this.willCounters.hasOwnProperty(id) && this.willCounters[id].count() < 3) {
                                this.makeHuntedSelectable(id);
                            }
                        }
                    }
                    break;
                case 'chooseSurvivalCard':
                    if (this.isCurrentPlayerActive()) {
                        dojo.create("div", {id: "choice"}, "choice_zone");
                        this.choiceStock = new ebg.stock();
                        this.choiceStock.setSelectionMode(1);
                        this.choiceStock.setSelectionAppearance('disappear');
                        this.choiceStock.autowidth = true;
                        this.choiceStock.item_margin = 10;
                        this.choiceStock.create(this, $('choice'), 136, 196);
                        this.choiceStock.onItemCreate = dojo.hitch(this, 'setupCard');
                        this.choiceStock.addItemType(11, 11, g_gamethemeurl + 'img/hunt_survival_cards.jpg', 1);
                        for (var cardNumber in state.args._private.survivalCards) {
                            if (state.args._private.survivalCards.hasOwnProperty(cardNumber)) {
                                var survivalCard = state.args._private.survivalCards[cardNumber];
                                this.choiceStock.addToStockWithId(11, survivalCard.type);
                            }
                        }
                        this.stateHandles.push(dojo.connect(this.choiceStock, 'onChangeSelection', this, 'onSurvivalCardChosen'));
                        dojo.removeClass("choice_zone", "hidden");
                    }
                    break;
                case 'chooseRiverPlaceCard':
                    if (this.player && this.player.playedPlaces && this.player.playedPlaces.length > 1) {
                        dojo.addClass('artemia_item_' + this.player.playedPlaces[0], 'selectable');
                        dojo.addClass('artemia_item_' + this.player.playedPlaces[1], 'selectable');
                        this.player.discard.setSelectionMode(1);
                        this.player.discard.unselectAll();
                    }
                    break;
                case 'chooseArtefactPlaceCard':
                    if (this.isCurrentPlayerActive()) {
                        dojo.addClass('artemia_item_' + state.args[0], 'selectable');
                        dojo.addClass('artemia_item_' + state.args[1], 'selectable');
                        this.player.discard.setSelectionMode(1);
                        this.player.discard.unselectAll();
                    }
                    break;
                case 'gate':
                    if (this.isCurrentPlayerActive()) {
                        var adjacentPlaces = this.getAdjacentPlaces(parseInt(state.args.resolvingPlace));
                        for (var k = 0; k < adjacentPlaces.length; k++) {
                            if (adjacentPlaces[k] !== 10) {
                                dojo.addClass('artemia_item_' + adjacentPlaces[k], 'selectable');
                            }
                        }
                    }
                    break;
                case 'hologram':
                case 'wrongTrack':
                    if (this.isCurrentPlayerActive()) {
                        for (var t = 0; t < state.args.length; t++) {
                            dojo.addClass('artemia_item_' + state.args[t], 'selectable');
                        }
                    }
                    break;
                case 'wrongTrackClone':
                    if (this.isCurrentPlayerActive()) {
                        dojo.addClass('creature_token', 'selectable');
                        dojo.addClass('target_token', 'selectable');
                        self.stateHandles.push(dojo.connect(dojo.byId('creature_token'), 'onclick', self, 'onClickHuntToken'));
                        self.stateHandles.push(dojo.connect(dojo.byId('target_token'), 'onclick', self, 'onClickHuntToken'));
                    }
                    break;
                case 'cataclysm':
                    if (this.isCurrentPlayerActive()) {
                        dojo.query('#artemia .stockitem').forEach(function (element) {
                            dojo.addClass(element, 'selectable');
                        });
                    }
                    break;
                case 'doubleBack':
                    if (this.isCurrentPlayerActive()) {
                        for (var j = 0; j < this.player.playedPlaces.length; j++) {
                            dojo.addClass('artemia_item_' + this.player.playedPlaces[j], 'selectable');
                        }
                    }
                    break;
                case 'creatureTokenEffects':
                    this.ongoingEffects.removeFromStockById('tracking');
                    if (this.ongoingEffects.count() === 0) {
                        dojo.addClass('ongoing_effects_zone', 'hidden');
                    }
                    break;
                case 'endOfTurn':
                    dojo.query("#artemia_zone .huntToken").forEach(dojo.destroy);
                    dojo.query("#artemia .huntedName").forEach(dojo.destroy);
                    dojo.query(".playerDiscardStock .stockitem.played").forEach(function (element) {
                        dojo.removeClass(element, 'played');
                    });
                    for (var playerId in this.gamedatas.players) {
                        if (this.gamedatas.players.hasOwnProperty(playerId)) {
                            if (this.gamedatas.players.hasOwnProperty(playerId) && this.gamedatas.players[playerId].playedPlaces && this.gamedatas.players[playerId].discardedPlaces) {
                                var player = this.gamedatas.players[playerId];
                                player.discardedPlaces = player.discardedPlaces.concat(player.playedPlaces);
                                player.playedPlaces = [];
                            }
                        }
                    }
                    var ongoingEffects = this.ongoingEffects.getAllItems();
                    for (var i = 0; i < ongoingEffects.length; i++) {
                        if (ongoingEffects[i].id !== 'tracking') {
                            this.ongoingEffects.removeFromStockById(ongoingEffects[i].id);
                        }
                    }
                    if (this.ongoingEffects.count() === 0) {
                        dojo.addClass('ongoing_effects_zone', 'hidden');
                    }
                    break;
            }
            if (this.isCurrentPlayerActive() && this.gamedatas.gamestate.possibleactions.includes('actTakeBackDiscardedPlace')) {
                this.player.discard.setSelectionMode(1);
                this.player.discard.unselectAll();
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function (stateName) {
            switch (stateName) {
                case 'boardSetup':
                    if (this.isCurrentPlayerActive()) {
                        dojo.removeClass("artemia_zone", "hidden");
                        this.buildArtemiaZone();
                    }
                    break;
                case 'hunting':
                    dojo.query("#artemia_zone .huntToken.placeholder").forEach(dojo.destroy);
                    break;
                case 'theRover':
                case 'chooseRiverPlaceCard':
                case 'chooseArtefactPlaceCard':
                case 'gate':
                case 'hologram':
                case 'wrongTrack':
                case 'wrongTrackClone':
                case 'cataclysm':
                case 'detour':
                case 'doubleBack':
                    dojo.query("#artemia .stockitem.selectable").forEach(function (element) {
                        dojo.removeClass(element, 'selectable');
                    });
                    break;
                case 'theSource':
                case 'anticipation':
                case 'ascendancyChooseHunted':
                case 'phobiaChooseHunted':
                    dojo.query(".player-board.selectable").forEach(function (element) {
                        dojo.removeClass(element, 'selectable');
                    });
                    break;
                case 'scream':
                case 'artemiaTokenEffects':
                    if (this.playerHand) {
                         this.playerHand.setSelectionMode(1);
                    }
                    break;
                case 'endOfTurn':
                    this.gamedatas.huntDeckSize -= 3 - this.gamedatas.players[this.gamedatas.creaturePlayer].huntCardsSize;
                    this.huntDeckCounter.setValue(this.gamedatas.huntDeckSize);
                    this.setPlayerHuntCardsSize(this.gamedatas.creaturePlayer, 3);
                    break;
            }
            if (this.player && this.player.discard) {
                this.player.discard.setSelectionMode(0);
            }
            for (var i = 0; i < this.stateHandles.length; i++) {
                dojo.disconnect(this.stateHandles[i]);
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function (stateName, args) {
            if (this.isCurrentPlayerActive()) {
                switch (stateName) {
                    case 'boardSetup':
                        this.addActionButton('flipBoardButton', _('Flip board'), 'onFlipBoard');
                        this.addActionButton('startHuntingButton', _('Start hunting!'), 'onStartHunting');
                        break;
                    case 'exploration':
                        if (parseInt(this.player_id) === parseInt(this.gamedatas.creaturePlayer)) {
                            dojo.byId("pagemaintitletext").innerHTML = dojo.string.substitute(_("Exploration: ${you} may play Phase 1 cards."),
                              {you: '<span id="pagemaintitletext"><span style="font-weight:bold;color:#' + this.gamedatas.players[this.player_id].color + ';">' + _('You') + '</span>'});
                            this.createPlayCardPassButton();
                        } else {
                            if (this.player.placePendingEffect) { // The River or The Artefact
                                dojo.byId("pagemaintitletext").innerHTML = dojo.string.substitute(_("${place}: ${you} must play 2 Place cards"),
                                  {
                                      place: this.getPlaceName(this.player.placePendingEffect),
                                      you: '<span id="pagemaintitletext"><span style="font-weight:bold;color:#' + this.gamedatas.players[this.player_id].color + ';">' + _('You') + '</span>'
                                  });
                            }
                            this.addActionButton('resistButton', _('Resist'), 'onResist');
                            this.addActionButton('giveUpButton', _('Give up'), 'onGiveUp');
                            this.addTooltip('resistButton', _('Forfeit 1 Will counter to take back 2 Place cards from your discard. Can be done twice.'), '');
                            this.addTooltip('giveUpButton', _('Move the Assimilation counter forward 1 space to regain all your Will counters and take back all your discarded Place cards.'), '');
                        }
                        break;
                    case 'artemiaTokenEffects':
                        if (args.hasOwnProperty(this.player_id) && args[this.player_id] === "2") {
                            dojo.byId("pagemaintitletext").innerHTML = dojo.string.substitute(_("${you} must discard 2 Place cards because of the Artemia token."),
                              {you: '<span id="pagemaintitletext"><span style="font-weight:bold;color:#' + this.gamedatas.players[this.player_id].color + ';">' + _('You') + '</span>'});
                            this.playerHand.setSelectionMode(2);
                            this.addActionButton('discardSelection', _('Discard selection'), 'onDiscardSelection');
                        }
                        break;
                    case 'sacrifice':
                        this.addActionButton('resistButton', _('Resist'), 'onResist');
                        this.addActionButton('giveUpButton', _('Give up'), 'onGiveUp');
                        this.addTooltip('resistButton', _('Forfeit 1 Will counter to take back 2 Place cards from your discard. Can be done twice.'), '');
                        this.addTooltip('giveUpButton', _('Move the Assimilation counter forward 1 space to regain all your Will counters and take back all your discarded Place cards.'), '');
                        break;
                    case 'chooseRiverPlaceCard':
                        dojo.byId("pagemaintitletext").innerHTML = dojo.string.substitute(_("${you} must choose which Place card you reveal."),
                          {you: '<span id="pagemaintitletext"><span style="font-weight:bold;color:#' + this.gamedatas.players[this.player_id].color + ';">' + _('You') + '</span>'});
                        break;
                    case 'theLair':
                        var takeBackButtonText = dojo.string.substitute(_('Take back ${number} cards'), {number: this.player.discardedPlaces.length});
                        this.addActionButton('takeBackDiscardedCardsButton', takeBackButtonText, 'onTakeBackDiscardedCards');
                        this.addActionButton('copyCreaturePlaceButton_' + args.place, dojo.string.substitute(_('Copy ${place}'), {place: this.getPlaceName(args.place)}), 'onCopyCreaturePlace');
                        for (var i = 0; i < args.clonePlaces.length; i++) {
                            if (args.place !== args.clonePlaces[i]) {
                                this.addActionButton('copyCreaturePlaceButton_' + args.clonePlaces[i], dojo.string.substitute(_('Copy ${place}'), {place: this.getPlaceName(args.clonePlaces[i])}), 'onCopyCreaturePlace');
                            }
                        }
                        break;
                    case 'theLair_Persecution':
                        this.addActionButton('copyCreaturePlaceButton_' + args.place, dojo.string.substitute(_('Copy ${place}'), {place: this.getPlaceName(args.place)}), 'onCopyCreaturePlace');
                        for (var j = 0; j < args.clonePlaces.length; j++) {
                            if (args.place !== args.clonePlaces[j]) {
                                this.addActionButton('copyCreaturePlaceButton_' + args.clonePlaces[j], dojo.string.substitute(_('Copy ${place}'), {place: this.getPlaceName(args.clonePlaces[j])}), 'onCopyCreaturePlace');
                            }
                        }
                        if (this.player.discardedPlaces.length) {
                            this.addActionButton('takeBackDiscardedPlace', _('Take back 1 place card'), 'onTakeBackDiscardedPlace');
                        } else {
                            this.addActionButton('passButton', _('Pass'), 'onConfirmReckoningPass');
                        }
                        break;
                    case 'theJungle':
                        if (this.player.discardedPlaces.length === 0) {
                            this.addActionButton('takeBackPlayedPlaceOnly_' + args.resolvingPlace, dojo.string.substitute(_('Take back ${place} only'), {place: this.getPlaceName(args.resolvingPlace)}), 'onTakeBackPlayedPlaceOnly');
                        }
                        break;
                    case 'theRiver':
                        this.addActionButton('useTheRiver', _('Use the River'), 'onUseTheRiver');
                        if (this.player.discardedPlaces.length) {
                            this.addActionButton('takeBackDiscardedPlace', _('Take back 1 place card'), 'onTakeBackDiscardedPlace');
                        } else {
                            this.addActionButton('passButton', _('Pass'), 'onConfirmReckoningPass');
                        }
                        break;
                    case 'theBeach':
                        this.addActionButton('useTheBeach', _('Use the Beach'), 'onUseTheBeach');
                        if (this.player.discardedPlaces.length) {
                            this.addActionButton('takeBackDiscardedPlace', _('Take back 1 place card'), 'onTakeBackDiscardedPlace');
                        } else {
                            this.addActionButton('passButton', _('Pass'), 'onConfirmReckoningPass');
                        }
                        break;
                    case 'theRover':
                        if (this.player.discardedPlaces.length) {
                            this.addActionButton('takeBackDiscardedPlace', _('Take back 1 place card'), 'onTakeBackDiscardedPlace');
                        } else {
                            this.addActionButton('passButton', _('Pass'), 'onConfirmReckoningPass');
                        }
                        break;
                    case 'theSwamp':
                        this.addActionButton('useTheSwamp', _('Take back selection'), 'onUseTheSwamp');
                        break;
                    case 'theShelter':
                        this.addActionButton('useTheShelter', _('Draw Survival cards'), 'onUseTheShelter');
                        if (this.player.discardedPlaces.length) {
                            this.addActionButton('takeBackDiscardedPlace', _('Take back 1 place card'), 'onTakeBackDiscardedPlace');
                        } else {
                            this.addActionButton('passButton', _('Pass'), 'onConfirmReckoningPass');
                        }
                        break;
                    case 'theWreck':
                        this.addActionButton('useTheWreck', _('Move rescue counter'), 'onUseTheWreck');
                        if (this.player.discardedPlaces.length) {
                            this.addActionButton('takeBackDiscardedPlace', _('Take back 1 place card'), 'onTakeBackDiscardedPlace');
                        } else {
                            this.addActionButton('passButton', _('Pass'), 'onConfirmReckoningPass');
                        }
                        break;
                    case 'theSource':
                        this.addActionButton('drawSurvivalCard', _('Draw Survival card'), 'onDrawSurvivalCard');
                        if (this.player.discardedPlaces.length) {
                            this.addActionButton('takeBackDiscardedPlace', _('Take back 1 place card'), 'onTakeBackDiscardedPlace');
                        } else {
                            this.addActionButton('passButton', _('Pass'), 'onConfirmReckoningPass');
                        }
                        break;
                    case 'theArtefact':
                        this.addActionButton('useTheArtefact', _('Use the Artefact'), 'onUseTheArtefact');
                        if (this.player.discardedPlaces.length) {
                            this.addActionButton('takeBackDiscardedPlace', _('Take back 1 place card'), 'onTakeBackDiscardedPlace');
                        } else {
                            this.addActionButton('passButton', _('Pass'), 'onConfirmReckoningPass');
                        }
                        break;
                    case 'Jungle_Persecution':
                    case 'Swamp_Persecution':
                        this.addActionButton('takeBackPlayedPlaceOnly_' + args.resolvingPlace, dojo.string.substitute(_('Take back ${place} only'), {place: this.getPlaceName(args.resolvingPlace)}), 'onTakeBackPlayedPlaceOnly');
                        if (this.player.discardedPlaces.length) {
                            this.addActionButton('takeBackDiscardedPlace', _('Take back 1 place card'), 'onTakeBackDiscardedPlace');
                        }
                        break;
                    case 'sixthSense':
                        this.addActionButton('takeBack2PlaceCards', _('Take back selection'), 'onTakeBack2PlaceCards');
                        break;
                    case 'ascendancyDiscard':
                        this.addActionButton('discardSelection', _('Discard selection'), 'onDiscardSelection');
                        break;
                    case 'phobiaSelectPlacesToShow':
                        this.addActionButton('showSelection', _('Show selection'), 'onShowSelection');
                        break;
                    case 'scream':
                        this.addActionButton('loseWill', _('Lose 1 Will'), 'onLoseWill');
                        this.addActionButton('discardSelection', _('Discard selection'), 'onDiscardSelection');
                        break;
                    case 'phase2Cards':
                    case 'reckoning':
                    case 'creaturePlayPhase3CardsInResponse':
                    case 'huntedPlayPhase3CardsInResponse':
                    case 'playSurvivalCardDrawn':
                    case 'endOfTurnActions':
                        this.createPlayCardPassButton();
                        break;
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        displayBoard: function () {
            dojo.place(this.format_block('jstpl_board',
              {
                  temp: '',
                  boardSide: this.gamedatas.boardSide,
                  rescueCounter: this.gamedatas.rescueCounter,
                  assimilationCounter: this.gamedatas.assimilationCounter
              }),
              'ongoing_effects_zone', 'after');
            this.addTooltip('rescueCounter', _('Rescue counter'), '');
            this.addTooltip('assimilationCounter', _('Assimilation counter'), '');
        },

        buildArtemiaZone: function() {
            for (var placeNum = 1; placeNum <= 10; placeNum++) {
                this.artemia.addToStockWithId(placeNum, placeNum);
            }
            dojo.query('#artemia .stockitem').connect('onclick', this, 'onClickArtemiaPlace');
        },

        setupCard: function (cardDiv, cardTypeId, cardId) {
            if (cardTypeId === 12) {
                this.setupHuntCard(cardDiv, cardId.split('_')[2]);
            } else if (cardTypeId === 11) {
                this.setupSurvivalCard(cardDiv, cardId.split('_')[2]);
            } else {
                this.setupPlaceCard(cardDiv, cardId);
            }
        },

        setupHuntCard: function (cardDiv, card) {
            dojo.addClass(cardDiv, "huntCard");
            this.createHuntCardContent(cardDiv, card);
            this.addHundCardTooltip(cardDiv.id, card);
        },

        createHuntCardContent: function (cardDiv, card) {
            var huntCard = this.gamedatas.huntCards[card];
            dojo.create("h3", {innerHTML: _(huntCard.name)}, cardDiv);
            if (huntCard.symbol) {
                var tmpobj = {};
                tmpobj['class'] = (huntCard.symbol === 'Target' ? 'target' : 'artemia') + ' huntToken';
                dojo.create("div", tmpobj, cardDiv);
            }

            var tmpobj = {innerHTML: '<span>' + _(huntCard.description) + '</span>'};
            tmpobj['class'] = 'description';
            dojo.create("p", tmpobj, cardDiv);
            var phase = isNaN(huntCard.phase) ? _(huntCard.phase) : dojo.string.substitute(_("Phase ${number}"), {number: huntCard.phase});

            var tmpobj = {innerHTML: phase};
            tmpobj['class'] = 'phase';

            dojo.create("p", tmpobj, cardDiv);
        },

        setupSurvivalCard: function (cardDiv, card) {
            dojo.addClass(cardDiv, "survivalCard");
            this.createSurvivalCardContent(cardDiv, card);
            this.addSurvivalCardTooltip(cardDiv.id, card);
        },

        createSurvivalCardContent: function (cardDiv, card) {
            var survivalCard = this.gamedatas.survivalCards[card];
            dojo.create("h3", {innerHTML: _(survivalCard.name)}, cardDiv);

            var tmpobj = {innerHTML: '<span>' + _(survivalCard.description) + '</span>'};
            tmpobj['class'] = 'description';
            dojo.create("p", tmpobj, cardDiv);

            var phase = dojo.string.substitute(_("Phase ${number}"), {number: survivalCard.phase});

            var tmpobj = {innerHTML: phase};
            tmpobj['class'] = 'phase';
            dojo.create("p", tmpobj, cardDiv);
        },

        setupPlaceCard: function (cardDiv, cardId) {
            var place = cardId.split("_")[2];
            dojo.addClass(cardDiv, "placeCard");
            this.createPlaceCardContent(cardDiv, place);
            if (cardId.startsWith("artemia_item_")) {
                var tmpobj = {id: "artemia_tokens_" + place};
                tmpobj['class'] = 'huntTokensWrapper';

                dojo.create("div", tmpobj, cardDiv);
            }
            this.addPlaceCardTooltip(cardDiv.id, place);
        },

        createPlaceCardContent: function (cardDiv, place) {
            var placeCard = this.gamedatas.placeCards[place];
            dojo.create("h3", {innerHTML: _(placeCard.name)}, cardDiv);

            var tmpobj = {};
            tmpobj['class'] = 'description';

            var description = dojo.create("div", tmpobj, cardDiv);
            for (var i = 0; i < placeCard.description.length; i++) {
                dojo.create("p", {innerHTML: _(placeCard.description[i])}, description);
            }
        },

        displayPlayerOnArtemia: function (player, animate) {
            var places = player.playedPlaces;
            for (var i = 0; i < places.length; i++) {
                var place = places[i];
                if (!places.toString().startsWith('?') && dojo.query('#artemia_item_' + place + ' .huntedName[data-value="' + player.id + '"]').length === 0) {
                    if (player.detour && player.detour.origin === place) {
                        place = player.detour.destination;
                    }
                    this.displayPlayerOnPlace(player, place, animate);
                }
            }
        },

        displayPlayerOnPlace: function (player, place, animate) {

            var tmpobj = {
                id: 'place_' + place + '_hunted_' + player.id,
                'data-value': player.id,
                textContent: player.name
            };
            tmpobj['class'] = 'huntedName' + (animate ? ' showLater' : '');


            var huntedName = dojo.create("span", tmpobj, 'artemia_item_' + place);
            dojo.setStyle(huntedName, 'color', '#' + player.color);
            if (animate) {
                var tempHtml = '<span data-value="' + huntedName + '" class="huntedName zoom-in" style="color: #' + player.color + ';">' + player.name + '</span>'
                this.slideTemporaryObject(tempHtml, 'overall-content', 'player_name_' + player.id, 'place_' + place + '_hunted_' + player.id, 1000);
            }
        },

        setPlayersInitialHandsSize: function () {
            for (var player_id in this.gamedatas.players) {
                if (this.gamedatas.players.hasOwnProperty(player_id)) {
                    if (this.gamedatas.creaturePlayer === player_id) {
                        this.gamedatas.huntDeckSize -= 3;
                        this.setPlayerHuntCardsSize(player_id, 3);
                    } else {
                        this.gamedatas.survivalDeckSize -= 1;
                        this.setPlayerPlaceCardsSize(player_id, 5);
                        this.setPlayerSurvivalCardsSize(player_id, 1);
                    }
                }
            }
            this.huntDeckCounter.setValue(this.gamedatas.huntDeckSize);
            this.survivalDeckCounter.setValue(this.gamedatas.survivalDeckSize);
        },

        addToPlayerPlaceCardsSize: function (playerId, quantity) {
            var player = this.gamedatas.players[playerId];
            this.setPlayerPlaceCardsSize(playerId, parseInt(player.placeCardsSize) + quantity);
        },

        setPlayerPlaceCardsSize: function (playerId, quantity) {
            var player = this.gamedatas.players[playerId];
            quantity = Math.max(quantity, 0);
            player.placeCardsSize = quantity;
            player.placeCardsCounter.setValue(quantity);
            this.removeTooltip('place_cards_count_player_' + playerId);
            var tooltipText;
            if (this.player_id.toString() === playerId.toString()) {
                tooltipText = dojo.string.substitute(_('You have ${quantity} place cards in hand.'), {quantity: quantity});
            } else {
                tooltipText = dojo.string.substitute(_('${player} has ${quantity} place cards in hand.'), {player: player.name, quantity: quantity});
            }
            this.addTooltip('place_cards_count_player_' + playerId, tooltipText, '');
        },

        addToPlayerHuntCardsSize: function (playerId, quantity) {
            var player = this.gamedatas.players[playerId];
            this.setPlayerHuntCardsSize(playerId, parseInt(player.huntCardsSize) + quantity);
        },

        setPlayerHuntCardsSize: function (playerId, quantity) {
            var player = this.gamedatas.players[playerId];
            quantity = Math.max(quantity, 0);
            player.huntCardsSize = quantity;
            player.huntCardsCounter.setValue(quantity);
            this.removeTooltip('hunt_cards_count');
            var tooltipText;
            if (this.player_id.toString() === playerId.toString()) {
                tooltipText = dojo.string.substitute(_('You have ${quantity} hunt cards in hand.'), {quantity: quantity});
            } else {
                tooltipText = dojo.string.substitute(_('${player} has ${quantity} hunt cards in hand.'), {player: player.name, quantity: quantity});
            }
            this.addTooltip('hunt_cards_count', tooltipText, '');
        },

        addToPlayerSurvivalCardsSize: function (playerId, quantity) {
            var player = this.gamedatas.players[playerId];
            this.setPlayerSurvivalCardsSize(playerId, parseInt(player.survivalCardsSize) + quantity);
        },

        setPlayerSurvivalCardsSize: function (playerId, quantity) {
            var player = this.gamedatas.players[playerId];
            quantity = Math.max(quantity, 0);
            player.survivalCardsSize = quantity;
            player.survivalCardsCounter.setValue(quantity);
            this.removeTooltip('survival_cards_count_player_' + playerId);
            var tooltipText;
            if (this.player_id.toString() === playerId.toString()) {
                tooltipText = dojo.string.substitute(_('You have ${quantity} survival cards in hand.'), {quantity: quantity});
            } else {
                tooltipText = dojo.string.substitute(_('${player} has ${quantity} survival cards in hand.'), {player: player.name, quantity: quantity});
            }
            this.addTooltip('survival_cards_count_player_' + playerId, tooltipText, '');
        },

        addPlaceToDiscard: function (playerId, place, position) {
            var player = this.gamedatas.players[playerId];
            player.discard.items.splice(position, 0, {id: place, type: place});
            player.discard.updateDisplay();
            var tooltipText;
            if (this.player_id.toString() === playerId.toString()) {
                tooltipText = dojo.string.substitute(_('${place} is in your discard.'), {place: this.getPlaceName(place)});
            } else {
                tooltipText = dojo.string.substitute(_('${place} is in ${player}’s discard.'), {place: this.getPlaceName(place), player: player.name});
            }
            this.addTooltip('discard_player_' + playerId + '_item_' + place, tooltipText, '');
        },

        addPlayedPlace: function (playerId, place, from) {
            var player = this.gamedatas.players[playerId];
            var tooltipText;
            if (place.toString().startsWith('?')) {
                player.discard.addToStockWithId(0, place, from);
                tooltipText = dojo.string.substitute(_('This place played by ${player} is not revealed yet.'), {player: player.name});
            } else {
                player.discard.addToStockWithId(place, place, from);
                if (this.player_id.toString() === playerId.toString()) {
                    tooltipText = dojo.string.substitute(_('You played ${place} this turn.'), {place: this.getPlaceName(place)});
                } else {
                    tooltipText = dojo.string.substitute(_('${player} played ${place} this turn.'), {player: player.name, place: this.getPlaceName(place)});
                }
            }
            this.addTooltip('discard_player_' + playerId + '_item_' + place, tooltipText, '');
            dojo.addClass('discard_player_' + playerId + '_item_' + place, 'played');
        },

        getId: function (object) {
            return object.id;
        },

        isSurvivalCard: function (card) {
            return card.type === 11;
        },

        isHuntCard: function (card) {
            return card.type === 12;
        },

        getPlaceName: function (place) {
            return _(this.gamedatas.placeCards[place].name);
        },

        getAdjacentPlaces: function (place) {
            switch (place) {
                case 1:
                    return [2, 6];
                case 2:
                    return [1, 3, 7];
                case 3:
                    return [2, 4, 8];
                case 4:
                    return [3, 5, 9];
                case 5:
                    return [4, 10];
                case 6:
                    return [1, 7];
                case 7:
                    return [2, 6, 8];
                case 8:
                    return [3, 7, 9];
                case 9:
                    return [4, 8, 10];
                case 10:
                    return [5, 9];
            }
        },

        showDetourDestinations: function () {
            dojo.byId("pagemaintitletext").innerHTML = _("Detour: you must select the Hunted destination.");
            var adjacentPlaces = this.getAdjacentPlaces(parseInt(this.detour.origin));
            for (var i = 0; i < adjacentPlaces.length; i++) {
                dojo.addClass('artemia_item_' + adjacentPlaces[i], 'selectable');
            }
        },

        displayDialog: function (title, content) {
            if (!this.dialog) {
                this.dialog = new ebg.popindialog();
            }
            this.dialog.create('dialog', 'left-side');
            this.dialog.setContent('<div id="dialogContent"><a href="#" id="custom_dialog_close" class="bgabutton bgabutton_blue"><span>' + _("OK") + '</span></a></div>');
            this.dialog.setTitle(title);
            dojo.place(content, $('dialogContent'), 0);
            this.dialog.show();
            dojo.destroy('popin_dialog_close');
            dojo.connect(dojo.byId('popin_dialog_underlay'), 'onclick', this, this.closeDialog);
            dojo.connect(dojo.byId('custom_dialog_close'), 'onclick', this, this.closeDialog);
        },

        closeDialog: function (event) {
            event.stopPropagation();
            this.dialog.destroy(false);
            endnotif();
        },

        addAllCardNameLogsTooltips: function () {
            var self = this;
            dojo.query("#logs .cardName").forEach(function (element) {
                self.addCardNameLogTooltip(dojo.attr(element, 'id'), dojo.attr(element, 'data-value'));
            });
        },

        addCardNameLogTooltip: function (id, card) {
            if (this.gamedatas.huntCards.hasOwnProperty(card)) {
                this.addHundCardTooltip(id, card);
            } else {
                this.addSurvivalCardTooltip(id, card);
            }
        },

        addHundCardTooltip: function (id, card) {
            var wrapper = dojo.create("div");

            var tmpobj = {};
            tmpobj['class'] = 'huntCard';

            var huntCard = dojo.create("div", tmpobj, wrapper);
            this.createHuntCardContent(huntCard, card);
            this.addTooltipHtml(id, wrapper.innerHTML);
        },

        addSurvivalCardTooltip: function (id, card) {
            var wrapper = dojo.create("div");

            var tmpobj = {};
            tmpobj['class'] = 'survivalCard';

            var survivalCard = dojo.create("div", tmpobj, wrapper);
            this.createSurvivalCardContent(survivalCard, card);
            this.addTooltipHtml(id, wrapper.innerHTML);
        },

        addPlaceCardTooltip: function (id, card) {
            var wrapper = dojo.create("div");

            var tmpobj = {'data-value': card};
            tmpobj['class'] = 'placeCard';

            var placeCard = dojo.create("div", tmpobj, wrapper);
            dojo.setStyle(placeCard, 'background-position-x', (card - 1) % 5 * 25 + '%');
            if (card > 5) {
                dojo.setStyle(placeCard, 'background-position-y', 'bottom');
            }
            this.createPlaceCardContent(placeCard, card);
            this.addTooltipHtml(id, wrapper.innerHTML);
        },

        canPlayCard: function () {
            var data = this.gamedatas;
            return this.playerHand.getAllItems().some(function (card) {
                return data.huntCards.hasOwnProperty(card.id) && (card.id === 'flashback' || data.huntCards[card.id].phase === data.gamestate.phase)
                  || data.survivalCards.hasOwnProperty(card.id) && data.survivalCards[card.id].phase === data.gamestate.phase;
            });
        },

        createPlayCardPassButton: function () {
            if (this.bRealtime && !this.canPlayCard()) {
                var self = this;
                if (!this.autoPass) {
                    this.autoPass = {};
                }
                var id = this.gamedatas.gamestate.name + 'AutoPassButton';
                if (!this.autoPass[id]) {
                    var delay = Math.floor(Math.random() * 5) + 8; // Between 8 and 12 seconds
                    this.autoPass[id] = {
                        buttonID: id,
                        delay: delay - 1,
                        timeout: setTimeout(function () {
                            if (dojo.byId(id)) {
                                self.onPass();
                            }
                        }, delay * 1000),
                        interval: setInterval(function () {
                            var button = dojo.byId(id);
                            if (self.autoPass[id].delay && button) {
                                self.autoPass[id].delay--;
                                button.innerHTML = _('Pass') + ' (' + self.autoPass[id].delay + ')';
                            } else {
                                clearInterval(self.autoPass[id].interval);
                                clearTimeout(self.autoPass[id].timeout);
                                delete self.autoPass[id];
                            }
                        }, 1000)
                    };
                }
                this.addActionButton(id, _('Pass') + ' (' + (this.autoPass[id].delay) + ')', 'onPass');
            } else {
                this.addActionButton('passButton', _('Pass'), 'onPass');
            }
        },

        moveBoardIntoView: function () {
            var boardPosition = dojo.position('board');
            dojo.setStyle('board', 'visibility', 'hidden');
            dojo.place(this.format_block('jstpl_board',
              {
                  temp: '_temp',
                  boardSide: this.gamedatas.boardSide,
                  rescueCounter: this.gamedatas.rescueCounter,
                  assimilationCounter: this.gamedatas.assimilationCounter
              }), 'game_play_area');
            dojo.setStyle('board_temp', 'width', boardPosition.w + 'px');
            dojo.setStyle('board_temp', 'top', boardPosition.y + 'px');
            setTimeout(function () {
                dojo.setStyle('board_temp', 'top', (window.innerHeight - boardPosition.h) / 2 + 'px');
            }, 100);
        },

        putBoardBackIntoPosition: function () {
            var boardPosition = dojo.position('board');
            dojo.setStyle('board_temp', 'top', boardPosition.y + 'px');
            setTimeout(function () {
                dojo.setStyle('board', 'visibility', 'visible');
                dojo.destroy('board_temp');
            }, 1000);
        },

        makeHuntedSelectable: function (huntedPlayerId) {
            dojo.addClass('overall_player_board_' + huntedPlayerId, 'selectable');
            this.stateHandles.push(dojo.connect(dojo.byId('overall_player_board_' + huntedPlayerId), 'onclick', this, 'onChooseHuntedPlayer'));
        },

        ///////////////////////////////////////////////////
        //// Player's action

        /*

            Here, you are defining methods to handle player's action (ex: results of mouse click on
            game objects).

            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server

        */

        // The player playing the Creature puts the board in the center of the table [1], whichever way up he likes.
        onFlipBoard: function (event) {
            dojo.stopEvent(event);
            this.gamedatas.boardSide = this.gamedatas.boardSide === 1 ? 2 : 1;
            dojo.destroy("board");
            this.displayBoard();
        },

        onStartHunting: function (event) {
            dojo.stopEvent(event);
            dojo.destroy("board");
            this.bgaPerformAction('actChooseBoardSide', {side: this.gamedatas.boardSide});
        },

        onHandCardSelectionChange: function () {
            var selectedItems = this.playerHand.getSelectedItems();
            var survivalCard = selectedItems.find(this.isSurvivalCard);
            if (survivalCard) {
                return this.onSurvivalCardSelected(survivalCard.id);
            }
            var huntCard = selectedItems.find(this.isHuntCard);
            if (huntCard) {
                return this.onHuntCardSelected(huntCard.id);
            }
            if (this.gamedatas.gamestate.name === 'exploration') {
                if (this.player.placePendingEffect) {
                    if (selectedItems.length === 2) {
                        this.explore(selectedItems);
                    } else if (this.player.playedPlaces.length === 2) {
                        this.explore([]);
                    }
                } else {
                    if (selectedItems.length === 1) {
                        this.explore(selectedItems);
                    } else if (selectedItems.length === 2) {
                        var playedPlace = this.player.playedPlaces[0];
                        this.playerHand.unselectItem(playedPlace);
                        this.explore(this.playerHand.getSelectedItems());
                    } else if (this.player.playedPlaces.length === 1) {
                        this.explore([]);
                    }
                }
            } else if (selectedItems.length === 1 && this.isCurrentPlayerActive() && this.gamedatas.gamestate.possibleactions.includes('actDiscardPlaceCard') && (!this.gamedatas.gamestate.args || this.gamedatas.gamestate.args[this.player_id] !== "2")) {
                this.discardPlaceCard(selectedItems[0].id);
            } else {
                if (this.isCurrentPlayerActive() && (this.gamedatas.gamestate.name === 'chooseRiverPlaceCard' || this.gamedatas.gamestate.name === 'chooseArtefactPlaceCard')) {
                    this.displayDialog(_("Information"), dojo.create("p", {innerHTML: _("Please select on Artemia one of the two places you played this turn.")}));
                }
            }
        },

        explore: function (placeCards) {
            var places = placeCards.map(this.getId);
            this.bgaPerformAction('actExploration', {
              places: places.join(',')
            },{checkAction: false}).then(()=>{
              this.player.playedPlaces = places;
            }).catch(()=>{
              this.playerHand.unselectAll();
            });
        },

        onHuntCardSelected: function (card) {
            this.bgaPerformAction('actPlayHuntCard', {cardName: card}, {checkAction: false}).catch(()=>{
              this.playerHand.unselectItem(card);
            });
        },

        onSurvivalCardSelected: function (card) {
            if (card === 'sixth-sense' && this.player.discardedPlaces.length === 1) {
                this.confirmationDialog(dojo.string.substitute(_('You only have ${place} in you discard. Do you want to play Sixth Sense to take it back?'), {place: this.getPlaceName(this.player.discardedPlaces[0])}),
                  dojo.hitch(this, function () {
                      this.doPlaySurvivalCard(card);
                  }));
            } else if (card === 'vortex' && this.gamedatas.gamestate.name === 'hunting') {
                this.showMessage(_("You can play Vortex after the Creature has placed its hunt tokens."), 'info')
            } else {
                this.doPlaySurvivalCard(card);
            }
        },

        doPlaySurvivalCard: function (card) {
            if (this.gamedatas.gamestate.possibleactions.includes('actDiscardSurvivalCard')) {
                this.bgaPerformAction('actDiscardSurvivalCard', {cardName: card}).then(()=>{
                  this.playerHand.unselectItem(card);
                }).catch(()=>{
                  this.playerHand.unselectItem(card);
                });
            }
            else {
              this.bgaPerformAction('actPlaySurvivalCard', {cardName: card}, {checkAction: false}).then(()=>{
                this.playerHand.unselectItem(card);
              }).catch(()=>{
                this.playerHand.unselectItem(card);
              });
            }
        },

        onDiscardSelectionChange: function () {
            var selectedPlaces = this.player.discard.getSelectedItems();
            if (!selectedPlaces.length) {
                return;
            }
            if (this.gamedatas.gamestate.name === 'chooseRiverPlaceCard') {
                this.onChooseRiverPlaceCard(selectedPlaces[0].type);
                this.player.discard.unselectItem(selectedPlaces[0].type);
            } else if (this.gamedatas.gamestate.name === 'chooseArtefactPlaceCard') {
                this.onChooseArtefactPlaceCard(selectedPlaces[0].type);
                this.player.discard.unselectItem(selectedPlaces[0].type);
            } else {
                for (var i = 0; i < selectedPlaces.length; i++) {
                    if (this.player.playedPlaces.indexOf(selectedPlaces[i].type) !== -1) {
                        this.player.discard.unselectItem(selectedPlaces[i].type);
                        return;
                    }
                }
            }

            if (this.isCurrentPlayerActive() && selectedPlaces.length === 1) {
                switch (this.gamedatas.gamestate.name) {
                    case 'theJungle':
                        this.bgaPerformAction('actTheJungle', {place : selectedPlaces[0].type});
                        break;
                    case 'vortex':
                        if (this.player.playedPlaces.length === 1) {
                            var place = selectedPlaces[0].type;
                            var swappedPlace = this.player.playedPlaces[0];
                            this.doSwap(place, swappedPlace);
                            this.player.playedPlaces[0] = place;
                        } else {
                            this.addActionButton('swap_with_0', dojo.string.substitute(_('Swap with ${place}'), {place: this.getPlaceName(this.player.playedPlaces[0])}), 'onSwapWithPlace');
                            this.addActionButton('swap_with_1', dojo.string.substitute(_('Swap with ${place}'), {place: this.getPlaceName(this.player.playedPlaces[1])}), 'onSwapWithPlace');
                        }
                        break;
                }
            }
        },

        onSwapWithPlace: function (event) {
            dojo.stopEvent(event);
            var place = this.player.discard.getSelectedItems()[0].type;
            var playedPlaceSwapped = event.target.id.split('_')[2];
            var swappedPlace = this.player.playedPlaces[playedPlaceSwapped];
            this.doSwap(place, swappedPlace);
            this.player.playedPlaces[playedPlaceSwapped] = place;
        },

        doSwap: function (place, swappedPlace) {
            this.bgaPerformAction('actSwapPlaceCard', {place: place, swappedPlace: swappedPlace}).then(() => {
              dojo.query('#artemia_item_' + swappedPlace + ' .huntedName[data-value="' + this.player_id + '"]').forEach(dojo.destroy);
              this.displayPlayerOnPlace(this.player, place, false);
              var swappedIndex = this.player.playedPlaces.indexOf(swappedPlace);
              this.player.playedPlaces[swappedIndex] = place;
            });
        },

        onResist: function (event) {
            dojo.stopEvent(event);
            switch (this.player.discardedPlaces.length) {
                case 0:
                    this.showMessage(_("You discard is empty."), 'error');
                    break;
                case 1:
                    this.confirmationDialog(dojo.string.substitute(_('You only have ${place} in you discard. Do you want to spend 1 Will counter to take it back?'), {place: this.getPlaceName(this.player.discardedPlaces[0])}),
                      dojo.hitch(this, function () {
                          this.doResist(this.player.discardedPlaces);
                      }));
                    break;
                case 2:
                    this.doResist(this.player.discardedPlaces);
                    break;
                default:
                    var selectedPlaces = this.player.discard.getSelectedItems().map(this.getId);
                    if (selectedPlaces.length !== 2) {
                        this.showMessage(_("Please select first 2 Place cards from your discard to take back in hand."), 'info')
                    } else {
                        this.doResist(selectedPlaces);
                    }
                    break;
            }
        },

        doResist: function (places) {
            this.bgaPerformAction('actResist', {
              places: places.join(',')
            });
        },

        onGiveUp: function (event) {
            dojo.stopEvent(event);
            var willCounters = this.willCounters[this.player_id].count();
            if (willCounters > 1) {
                this.confirmationDialog(dojo.string.substitute(_('You have ${willCounters} Will counters left. Are you sure you want to Give Up?'), {willCounters: willCounters}), dojo.hitch(this, this.doGiveUp));
            } else {
                this.doGiveUp();
            }
        },

        doGiveUp: function () {
            this.bgaPerformAction('actGiveUp');
        },

        onPlaceCreatureToken: function (event) {
            dojo.stopEvent(event);
            this.onPlaceToken('creature', event.target.parentNode.id.split('_')[2])
        },

        onPlaceArtemiaToken: function (event) {
            dojo.stopEvent(event);
            this.onPlaceToken('artemia', event.target.parentNode.id.split('_')[2])
        },

        onPlaceTargetToken: function (event) {
            dojo.stopEvent(event);
            this.onPlaceToken('target', event.target.parentNode.id.split('_')[2])
        },

        onPlaceToken: function (tokenType, position) {
            this.bgaPerformAction('actPlaceToken', {tokenType: tokenType, position: position}, {checkAction:false, checkPossibleActions: true});
        },

        onChooseHuntedPlayer: function (event) {
            dojo.stopEvent(event);
            var playerBoard = event.target;
            while (!playerBoard.id.startsWith("overall_player_board_")) {
                playerBoard = playerBoard.parentElement;
            }
            var playerId = playerBoard.id.split('_')[3];
            switch (this.gamedatas.gamestate.name) {
                case 'theSource':
                    this.bgaPerformAction('actRegainWill',{playerId: playerBoard.id.split('_')[3]});
                    break;
                case 'detour':
                    this.detour = {lock: true};
                    this.detour.huntedId = playerId;
                    if (this.gamedatas.players[playerId].playedPlaces.length === 1) {
                        this.detour.origin = this.gamedatas.players[playerId].playedPlaces[0];
                        this.showDetourDestinations();
                    } else { // If the hunted played 2 places with the artefact, we move one of them
                        dojo.byId("pagemaintitletext").innerHTML = _("Detour: this player controls 2 Hunted with the Artefact. Select which one you moves.");
                        for (var i = 0; i < this.gamedatas.players[playerId].playedPlaces.length; i++) {
                            dojo.addClass('artemia_item_' + this.gamedatas.players[playerId].playedPlaces[i], 'selectable');
                        }
                    }
                    dojo.query(".player-board.selectable").forEach(function (element) {
                        dojo.removeClass(element, 'selectable');
                    });
                    break;
                default:
                    this.bgaPerformAction('actChooseHuntedPlayer', {huntedPlayerId: playerId});
                    break;
            }
        },

        onTakeBackDiscardedCards: function (event) {
            dojo.stopEvent(event);
            this.bgaPerformAction('actTakeBackDiscardedPlaceCards');
        },

        onCopyCreaturePlace: function (event) {
            dojo.stopEvent(event);
            var place = event.target.id.split('_')[1];
            this.bgaPerformAction('actCopyCreaturePlace', {place: place});
        },

        onTakeBackPlayedPlaceOnly: function (event) {
            dojo.stopEvent(event);
            var place = event.target.id.split('_')[1];
            this.bgaPerformAction('actTakeBackPlayedCard', {place: place});
        },

        onUseTheRiver: function (event) {
            dojo.stopEvent(event);
            this.bgaPerformAction('actTheRiver');
        },

        onChooseRiverPlaceCard: function (place) {
            this.bgaPerformAction('actChooseRiverPlaceCard', {place: place});
        },

        onUseTheBeach: function (event) {
            dojo.stopEvent(event);
            this.bgaPerformAction('actTheBeach');
        },

        onClickArtemiaPlace: function (event) {
            dojo.stopEvent(event);
            var placeElement = event.target;
            while (!placeElement.id.startsWith("artemia_item_")) {
                placeElement = placeElement.parentElement;
            }
            switch (this.gamedatas.gamestate.name) {
                case 'theRover':
                    this.onTakeNewPlace(placeElement);
                    break;
                case 'chooseRiverPlaceCard':
                    if (this.player.placePendingEffect === '3' && this.isCurrentPlayerActive()) {
                        this.onChooseRiverPlaceCard(placeElement.id.split('_')[2]);
                    }
                    break;
                case 'chooseArtefactPlaceCard':
                    this.onChooseArtefactPlaceCard(placeElement.id.split('_')[2]);
                    break;
                case 'gate':
                    this.onCopyAdjacentPlace(placeElement.id.split('_')[2]);
                    break;
                case 'hologram':
                case 'wrongTrack':
                case 'wrongTrackClone':
                    var place = placeElement.id.split('_')[2];
                    if (this.gamedatas.gamestate.token) {
                        this.onMoveHuntToken(place);
                    }
                    break;
                case 'cataclysm':
                    this.onChooseIneffectivePlace(placeElement.id.split('_')[2]);
                    break;
                case 'detour':
                    if (!this.detour) {
                        return;
                    }
                    if (this.detour.origin) {
                        this.detour.destination = placeElement.id.split('_')[2];
                        this.bgaPerformAction('actMoveHunted', {huntedId: this.detour.huntedId, origin: this.detour.origin, destination: this.detour.destination});
                    } else {
                        this.detour.origin = placeElement.id.split('_')[2];
                        dojo.query("#artemia .stockitem.selectable").forEach(function (element) {
                            dojo.removeClass(element, 'selectable');
                        });
                        this.showDetourDestinations();
                    }
                    break;
                case 'doubleBack':
                    console.log(placeElement.id);
                    this.bgaPerformAction('actTakeBackPlayedCard',{place: placeElement.id.split('_')[2]});
                    break;
            }
        },

        onTakeNewPlace: function (placeElement) {
            var place = placeElement.id.split('_')[2];
            this.bgaPerformAction('actTheRover', {place: place});
        },

        onUseTheSwamp: function (event) {
            dojo.stopEvent(event);
            var places;
            if (this.player.discardedPlaces.length <= 2) {
                places = this.player.discardedPlaces;
            } else if (this.player.discard.getSelectedItems().length < 2) {
                this.showMessage(_("Please select first 2 Place cards from your discard to take back in hand."), 'info')
                return;
            } else {
                places = this.player.discard.getSelectedItems().map(this.getId);
            }
            this.bgaPerformAction('actTheSwamp', {places: places.join(',')});
        },

        onUseTheShelter: function (event) {
            dojo.stopEvent(event);
            this.bgaPerformAction('actTheShelter');
        },

        onSurvivalCardChosen: function () {
            var selectedItems = this.choiceStock.getSelectedItems();
            if (selectedItems.length === 1) {
                var card = selectedItems[0].id;
                console.log(card);
                this.bgaPerformAction('actChooseSurvivalCard', {cardName: card});
            }
        },

        onUseTheWreck: function (event) {
            dojo.stopEvent(event);
            this.bgaPerformAction('actTheWreck');
        },

        onDrawSurvivalCard: function (event) {
            dojo.stopEvent(event);
            this.bgaPerformAction('actDrawSurvivalCard');
        },

        onUseTheArtefact: function (event) {
            dojo.stopEvent(event);
            this.bgaPerformAction('actTheArtefact');
        },

        onChooseArtefactPlaceCard: function (place) {
            this.bgaPerformAction('actChooseArtefactPlaceCard', {place: place});
        },

        onTakeBackDiscardedPlace: function () {
            var playerId = this.player_id;
            var selectedItems = this.gamedatas.players[playerId].discard.getSelectedItems();
            if (selectedItems.length !== 1) {
                this.showMessage(_("Please select first 1 Place card from your discard to take back in hand."), 'info')
            } else {
                var place = selectedItems[0].type;
                this.bgaPerformAction('actTakeBackDiscardedPlace', {place: place}).then(()=>{
                  this.playerHand.addToStockWithId(place, place, "discard_player_" + playerId);
                });
            }
        },

        discardPlaceCard: function (place) {
            if (this.gamedatas.gamestate.name === 'artemiaTokenEffects' && this.gamedatas.gamestate.args[this.player_id].length === 2) {
                return;
            }
            this.bgaPerformAction('actDiscardPlaceCard', {place: place});
        },

        onTakeBack2PlaceCards: function (event) {
            dojo.stopEvent(event);
            var selectedPlaces = this.player.discard.getSelectedItems().map(this.getId);
            if (selectedPlaces.length !== 2) {
                this.showMessage(_("Please select first 2 Place cards from your discard to take back in hand."), 'info')
            } else {
                this.bgaPerformAction('actTakeBack2PlaceCards', {places: selectedPlaces.join(',')});
            }
        },

        onDiscardSelection: function (event) {
            dojo.stopEvent(event);
            var selectedPlaces = this.playerHand.getSelectedItems().map(this.getId);
            this.bgaPerformAction('actDiscardPlaceCards', {places: selectedPlaces.join(',')}).then(()=>{
              for (var i = 0; i < selectedPlaces.length; i++) {
                  this.playerHand.removeFromStockById(selectedPlaces[i]);
              }
            });
        },

        onShowSelection: function (event) {
            dojo.stopEvent(event);
            var selectedPlaces = this.playerHand.getSelectedItems().map(this.getId);
            this.bgaPerformAction('actShowPlaces', {places: selectedPlaces.join(',')});
        },

        onConfirmReckoningPass: function () {
            this.confirmationDialog(_('Are you sure that you cannot use your place’s power?'), dojo.hitch(this, function () {
                this.onPass();
            }));
        },

        onPass: function () {
            if (!this.isCurrentPlayerActive() || !this.gamedatas.gamestate.possibleactions.includes('actPass')) {
                return;
            }
            this.bgaPerformAction('actPass');
        },

        onLoseWill: function () {
            this.bgaPerformAction('actLoseWill');
        },

        onCopyAdjacentPlace: function (place) {
            this.bgaPerformAction('actCopyAdjacentPlace', {place: place});
        },

        onMoveHuntToken: function (place) {
            this.bgaPerformAction('actMoveHuntToken', {tokenType: this.gamedatas.gamestate.token, place: place});
        },

        onChooseIneffectivePlace: function (place) {
            this.bgaPerformAction('actChooseIneffectivePlace', {place: place});
        },

        onClickHuntToken: function (event) {
            event.stopPropagation();
            var token = event.target.id.split('_')[0];
            if (this.gamedatas.gamestate.token) {
                dojo.addClass(this.gamedatas.gamestate.token + '_token', 'selectable');
                dojo.query("#artemia .stockitem.selectable").forEach(function (element) {
                    dojo.removeClass(element, 'selectable');
                });
            }
            this.gamedatas.gamestate.token = token;
            dojo.removeClass(token + '_token', 'selectable');
            var adjacentPlaces = this.gamedatas.gamestate.args[token];
            for (var t = 0; t < adjacentPlaces.length; t++) {
                dojo.addClass('artemia_item_' + adjacentPlaces[t], 'selectable');
            }
        },

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:

            In this method, you associate each of your game notifications with your local method to handle it.

            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your notalone.game.php file.

        */
        setupNotifications: function () {
            dojo.subscribe('setupComplete', this, 'notif_setupComplete');
            dojo.subscribe('huntCardsSeen', this, 'notif_huntCardsSeen');
            dojo.subscribe('explorationDone', this, 'notif_explorationDone');
            dojo.subscribe('myExplorationDone', this, 'notif_myExplorationDone');
            dojo.subscribe('explorationCancelled', this, 'notif_explorationCancelled');
            dojo.subscribe('placeCardsTakenBack', this, 'notif_placeCardsTakenBack');
            dojo.subscribe('giveUp', this, 'notif_giveUp');
            dojo.subscribe('tokenPlaced', this, 'notif_tokenPlaced');
            dojo.subscribe('tokenTakenBack', this, 'notif_tokenTakenBack');
            dojo.subscribe('anticipationTargetChosen', this, 'notif_anticipationTargetChosen');
            dojo.subscribe('placeCardsRevealed', this, 'notif_placeCardsRevealed');
            dojo.subscribe('cataclysmPlaceChosen', this, 'notif_cataclysmPlaceChosen');
            dojo.subscribe('discardPlacesTakenBack', this, 'notif_discardPlacesTakenBack');
            dojo.subscribe('markerCounterMoved', this, 'notif_markerCounterMoved');
            dojo.subscribe('rescueCounterMoved', this, 'notif_rescueCounterMoved');
            dojo.subscribe('assimilationCounterMoved', this, 'notif_assimilationCounterMoved');
            dojo.subscribe('chooseRiverPlaceCard', this, 'notif_chooseRiverPlaceCard');
            dojo.subscribe('roverUsed', this, 'notif_roverUsed');
            dojo.subscribe('discardedPlaceTakenBack', this, 'notif_discardedPlaceTakenBack');
            dojo.subscribe('playedPlaceTakenBack', this, 'notif_playedPlaceTakenBack');
            dojo.subscribe('willCounterLost', this, 'notif_willCounterLost');
            dojo.subscribe('willCounterRegained', this, 'notif_willCounterRegained');
            dojo.subscribe('survivalCardSeen', this, 'notif_survivalCardSeen');
            dojo.subscribe('survivalCardDrawn', this, 'notif_survivalCardDrawn');
            dojo.subscribe('shelterUsed', this, 'notif_shelterUsed');
            dojo.subscribe('riverUsed', this, 'notif_riverUsed');
            dojo.subscribe('artefactUsed', this, 'notif_artefactUsed');
            dojo.subscribe('survivalCardChosen', this, 'notif_survivalCardChosen');
            dojo.subscribe('huntCardPlayed', this, 'notif_huntCardPlayed');
            dojo.subscribe('survivalCardPlayed', this, 'notif_survivalCardPlayed');
            dojo.subscribe('survivalCardDiscarded', this, 'notif_survivalCardDiscarded');
            dojo.subscribe('huntCardLost', this, 'notif_huntCardLost');
            dojo.subscribe('placeDiscarded', this, 'notif_placeDiscarded');
            dojo.subscribe('placesRevealed', this, 'notif_placesRevealed');
            dojo.subscribe('placeCardSwapped', this, 'notif_placeCardSwapped');
            dojo.subscribe('huntTokenMoved', this, 'notif_huntTokenMoved');
            dojo.subscribe('huntedMoved', this, 'notif_huntedMoved');
            dojo.subscribe('placeCopied', this, 'notif_placeCopied');
            dojo.subscribe('toxinDiscardAvoided', this, 'notif_toxinDiscardAvoided');
            dojo.subscribe('smokescreenDissipates', this, 'notif_smokescreenDissipates');
            dojo.subscribe('information', this, 'notif_information');
            dojo.subscribe('survivalDeckShuffled', this, 'notif_survivalDeckShuffled');
            dojo.subscribe('huntDeckShuffled', this, 'notif_huntDeckShuffled');

            this.notifqueue.setSynchronous('giveUp', 1000);
            this.notifqueue.setSynchronous('tokenPlaced', 2000);
            this.notifqueue.setSynchronous('willCounterLost', 1000);
            this.notifqueue.setSynchronous('willCounterRegained', 1000);
            this.notifqueue.setSynchronous('rescueCounterMoved', 4000);
            this.notifqueue.setSynchronous('assimilationCounterMoved', 4000);
            this.notifqueue.setSynchronous('placeCardsRevealed', 1000);
            this.notifqueue.setSynchronous('huntCardPlayed');
            this.notifqueue.setSynchronous('survivalCardPlayed');
            this.notifqueue.setSynchronous('placesRevealed');
        },

        notif_setupComplete: function (notification) {
            this.gamedatas.boardSide = notification.args.boardSide;
            this.displayBoard();
            this.setPlayersInitialHandsSize();
            if (this.gamedatas.creaturePlayer.toString() !== this.player_id.toString()) {
                for (var placeNumber = 1; placeNumber <= 5; placeNumber++) {
                    this.playerHand.addToStockWithId(placeNumber, placeNumber);
                }
                this.playerHand.addToStockWithId(11, notification.args.survivalCard.type);
            }
        },

        notif_huntCardsSeen: function (notification) {
            dojo.setStyle("hand_zone", "display", "block");
            for (var huntCardId in notification.args.huntCards) {
                if (notification.args.huntCards.hasOwnProperty(huntCardId)) {
                    var huntCard = notification.args.huntCards[huntCardId];
                    this.playerHand.addToStockWithId(12, huntCard.type);
                }
            }
        },

        notif_explorationDone: function (notification) {
            var playerId = notification.args.playerId;
            this.addToPlayerPlaceCardsSize(playerId, -parseInt(notification.args.played));
            if (playerId.toString() !== this.player_id.toString()) {
                for (var i = 0; i < parseInt(notification.args.played); i++) {
                    this.addPlayedPlace(playerId, '?' + i, 'place_cards_count_player_' + playerId);
                }
            }
        },

        notif_myExplorationDone: function (notification) {
            for (var i = 0; i < notification.args.places.length; i++) {
                var place = notification.args.places[i];
                this.addPlayedPlace(this.player_id, place, 'hand_item_' + place);
                this.playerHand.removeFromStockById(place);
            }
            this.player.playedPlaces = notification.args.places;
            this.displayPlayerOnArtemia(this.player, true);
        },

        notif_explorationCancelled: function () {
            if (this.player.playedPlaces) {
                this.player.playedPlaces = [];
                this.playerHand.unselectAll();
            }
        },

        notif_placeCardsTakenBack: function (notification) {
            this.setPlayerPlaceCardsSize(notification.args.playerId, notification.args.total_place_cards);
            var player = this.gamedatas.players[notification.args.playerId];
            for (var i = 0; i < notification.args.places.length; i++) {
                var place = notification.args.places[i];
                if (player.discardedPlaces) {
                    player.discard.removeFromStockById(place);
                    player.discardedPlaces.splice(player.discardedPlaces.indexOf(place.toString()), 1);
                }
                if (notification.args.playerId.toString() === this.player_id.toString()) {
                    this.playerHand.addToStockWithId(place, place, 'discard_player_' + this.player_id);
                }
            }
        },

        notif_giveUp: function (notification) {
            var player = this.gamedatas.players[notification.args.playerId];
            this.setPlayerPlaceCardsSize(notification.args.playerId, notification.args.total_place_cards);
            if (notification.args.playerId.toString() === this.player_id.toString()) {
                var discardPlaces = player.discard.getAllItems();
                for (var i = 0; i < discardPlaces.length; i++) {
                    this.playerHand.addToStockWithId(discardPlaces[i].type, discardPlaces[i].type, 'discard_player_' + this.player_id);
                }
            }
            player.discard.removeAll();
            player.discardedPlaces = [];
            player.playedPlaces = [];
            var willCounter = this.willCounters[notification.args.playerId];
            for (var j = willCounter.count(); j < notification.args.quantity; j++) {
                willCounter.addToStock(1, 'artemia');
            }
        },

        notif_tokenPlaced: function (notification) {
            this.gamedatas[notification.args.tokenType + 'Token'] = notification.args.position;
            dojo.query("#artemia_zone .huntToken.placeholder." + notification.args.tokenType).forEach(dojo.destroy);

            var tmpobj = {
                id: notification.args.tokenType + '_token'
            };
            tmpobj['class'] = notification.args.tokenType + ' huntToken showLater';

            dojo.create("div", tmpobj, "artemia_tokens_" + notification.args.position);
            this.slideTemporaryObject('<div class="' + notification.args.tokenType + ' huntToken temp zoom-in"></div>', 'overall-content', 'overall_player_board_' + this.gamedatas.creaturePlayer, notification.args.tokenType + '_token', 1000);
            this.addTooltip(notification.args.tokenType + '_token', _(notification.args.tokenType === 'creature' ? 'Creature token' : notification.args.tokenType === 'artemia' ? 'Artemia token' : 'Target token'), '');
            playSound('notalone-' + notification.args.tokenType);
        },

        notif_tokenTakenBack: function (notification) {
            dojo.destroy(notification.args.tokenType + '_token');
            this.addCardNameLogTooltip(notification.args.cardNameId, notification.args.card);
        },

        notif_anticipationTargetChosen: function (notification) {
            this.addCardNameLogTooltip(notification.args.cardNameId, 'anticipation');
        },

        notif_placeCardsRevealed: function (notification) {
            var player = this.gamedatas.players[notification.args.playerId];
            player.playedPlaces = notification.args.places;
            this.displayPlayerOnArtemia(player, true);
            for (var i = 0; i < player.playedPlaces.length; i++) {
                player.discard.removeFromStock(0);
                this.addPlayedPlace(notification.args.playerId, player.playedPlaces[i]);
            }
        },

        notif_cataclysmPlaceChosen: function (notification) {
            this.addCardNameLogTooltip(notification.args.cardNameId, 'cataclysm');
        },

        notif_discardPlacesTakenBack: function (notification) {
            this.setPlayerPlaceCardsSize(notification.args.playerId, notification.args.total_place_cards);
            var player = this.gamedatas.players[notification.args.playerId];
            var places = player.discardedPlaces;
            for (var i = 0; i < places.length; i++) {
                if (notification.args.playerId === this.player_id.toString()) {
                    this.playerHand.addToStockWithId(places[i], places[i], 'discard_player_' + this.player_id);
                }
                player.discard.removeFromStockById(places[i]);
            }
            player.discardedPlaces = [];
        },

        notif_markerCounterMoved: function (notification) {
            this.gamedatas.markerCounter = notification.args.markerCounter;
            dojo.attr("marker_counter", "data-value", notification.args.markerCounter);
        },

        notif_rescueCounterMoved: function (notification) {
            this.moveBoardIntoView();
            for (var huntedPlayerId in this.gamedatas.players) {
                if (this.gamedatas.players.hasOwnProperty(huntedPlayerId) && huntedPlayerId !== this.gamedatas.creaturePlayer) {
                    this.scoreCtrl[huntedPlayerId].incValue(1);
                }
            }
            var self = this;
            setTimeout(function () {
                dojo.attr("rescueCounter", "data-value", notification.args.rescueCounter);
                dojo.attr("rescueCounter_temp", "data-value", notification.args.rescueCounter);
            }, 1500);
            setTimeout(function () {
                self.putBoardBackIntoPosition();
            }, 2500);
            this.gamedatas.rescueCounter = notification.args.rescueCounter;
        },

        notif_assimilationCounterMoved: function (notification) {
            this.moveBoardIntoView();
            this.scoreCtrl[this.gamedatas.creaturePlayer].incValue(1);
            var self = this;
            setTimeout(function () {
                dojo.attr("assimilationCounter", "data-value", notification.args.assimilationCounter);
                dojo.attr("assimilationCounter_temp", "data-value", notification.args.assimilationCounter);
            }, 1500);
            setTimeout(function () {
                self.putBoardBackIntoPosition();
            }, 2500);
            this.gamedatas.assimilationCounter = notification.args.assimilationCounter;
        },

        notif_playedPlaceTakenBack: function (notification) {
            dojo.query('#artemia_item_' + notification.args.place + ' .huntedName[data-value="' + notification.args.playerId + '"]').forEach(dojo.destroy);
            this.addToPlayerPlaceCardsSize(notification.args.playerId, 1);
            var player = this.gamedatas.players[notification.args.playerId];
            player.playedPlaces = player.playedPlaces.filter(function (place) {
                return place !== notification.args.place;
            });
            if (notification.args.playerId.toString() === this.player_id.toString()) {
                this.playerHand.addToStockWithId(notification.args.place, notification.args.place, 'discard_player_' + this.player_id + '_item_' + notification.args.place);
                player.discard.removeFromStockById(notification.args.place);
            } else {
                player.discard.removeFromStockById(notification.args.place, 'place_cards_count_player_' + notification.args.playerId);
            }
        },

        notif_chooseRiverPlaceCard: function (notification) {
            this.addToPlayerPlaceCardsSize(notification.args.playerId, 1);
            if (notification.args.playerId.toString() !== this.player_id.toString()) {
                this.gamedatas.players[notification.args.playerId].discard.removeFromStock(0, 'place_cards_count_player_' + notification.args.playerId);
            } else {
                var placeBackInHand = this.player.playedPlaces[0].toString() === notification.args.place.toString() ? this.player.playedPlaces[1] : this.player.playedPlaces[0];
                this.playerHand.addToStockWithId(placeBackInHand, placeBackInHand, 'discard_player_' + this.player_id + '_item_' + placeBackInHand);
                this.player.discard.removeFromStockById(placeBackInHand);
                this.player.playedPlaces = [notification.args.place];
                dojo.query('#artemia_item_' + placeBackInHand + ' .huntedName[data-value="' + this.player_id + '"]').forEach(dojo.destroy);
            }
        },

        notif_roverUsed: function (notification) {
            this.gamedatas.placesReserve[notification.args.place].quantity = notification.args.quantityLeft;
            dojo.byId("reserve_" + notification.args.place).textContent = 'x' + notification.args.quantityLeft;
            this.addToPlayerPlaceCardsSize(notification.args.player, 1);
            if (notification.args.player.toString() === this.player_id.toString()) {
                this.playerHand.addToStockWithId(notification.args.place, notification.args.place, 'artemia_item_' + notification.args.place);
            }
        },

        notif_discardedPlaceTakenBack: function (notification) {
            this.addToPlayerPlaceCardsSize(notification.args.playerId, 1);
            var player = this.gamedatas.players[notification.args.playerId];
            if (player.discardedPlaces) {
                player.discard.removeFromStockById(notification.args.place);
                player.discardedPlaces.splice(player.discardedPlaces.indexOf(notification.args.place), 1);
            }
            if (notification.args.playerId === this.player_id.toString()) {
                this.playerHand.addToStockWithId(notification.args.place, notification.args.place, 'discard_player_' + notification.args.playerId);
            }
        },

        notif_willCounterLost: function (notification) {
            this.willCounters[notification.args.playerId].removeFromStock(1, 'artemia');
            if (notification.args.cardNameId) {
                this.addCardNameLogTooltip(notification.args.cardNameId, notification.args.card);
            }
        },

        notif_willCounterRegained: function (notification) {
            this.willCounters[notification.args.playerId].addToStock(1, 'artemia');
        },

        notif_survivalCardSeen: function (notification) {
            this.playerHand.addToStockWithId(11, notification.args.survivalCard.type);
        },

        notif_survivalCardDrawn: function (notification) {
            this.gamedatas.survivalDeckSize--;
            this.survivalDeckCounter.setValue(this.gamedatas.survivalDeckSize);
            this.addToPlayerSurvivalCardsSize(notification.args.playerId, 1);
        },

        notif_shelterUsed: function () {
            this.gamedatas.survivalDeckSize -= 2;
            this.survivalDeckCounter.setValue(this.gamedatas.survivalDeckSize);
        },

        notif_riverUsed: function (notification) {
            this.gamedatas.players[notification.args.playerId].placePendingEffect = '3';
        },

        notif_artefactUsed: function (notification) {
            this.gamedatas.players[notification.args.playerId].placePendingEffect = '10';
        },

        notif_survivalCardChosen: function (notification) {
            this.addToPlayerSurvivalCardsSize(notification.args.playerId, 1);
            this.survivalDiscard.addToStockWithId(11, notification.args.discardedCard);
            this.addCardNameLogTooltip(notification.args.cardNameId, notification.args.discardedCard);
            if (notification.args.playerId.toString() === this.player_id.toString()) {
                var card = this.choiceStock.getAllItems().find(function(card) {
                    return card.id !== notification.args.discardedCard
                });
                this.playerHand.addToStockWithId(11, card.id, "choice_item_" + card.id);
                dojo.destroy("choice");
                dojo.addClass("choice_zone", "hidden");
            }
        },

        notif_huntCardPlayed: function (notification) {
            this.addToPlayerHuntCardsSize(notification.args.playerId, -1);
            if (notification.args.playerId.toString() === this.player_id.toString()) {
                if (notification.args.flashback) {
                    this.huntDiscard.addToStockWithId(12, 'flashback', 'hand_item_flashback');
                    this.playerHand.removeFromStockById('flashback');
                } else {
                    this.huntDiscard.addToStockWithId(12, notification.args.card, 'hand_item_' + notification.args.card);
                    this.playerHand.removeFromStockById(notification.args.card);
                }
                endnotif();
            } else {
                this.huntDiscard.addToStockWithId(12, notification.args.flashback ? 'flashback' : notification.args.card, 'hunt_cards_count');

                var tmpobj = {};
                tmpobj['class'] = 'cardsWrapper';

                var wrapper = dojo.create("div", tmpobj);
                if (notification.args.flashback) {

                    var tmpobj = {};
                    tmpobj['class'] = 'huntCard';

                    var flashbackDiv = dojo.create("div", tmpobj, wrapper);
                    this.setupHuntCard(flashbackDiv, 'flashback');
                }

                var tmpobj = {};
                tmpobj['class'] = 'huntCard';

                var cardDiv = dojo.create("div", tmpobj, wrapper);
                this.setupHuntCard(cardDiv, notification.args.card);
                this.displayDialog(dojo.string.substitute(_(notification.log), notification.args), wrapper);
            }
            if (notification.args.ongoingEffect) {
                dojo.removeClass('ongoing_effects_zone', 'hidden');
                this.ongoingEffects.addToStockWithId(12, notification.args.card);
            }
            this.addCardNameLogTooltip(notification.args.cardNameId, notification.args.card);
            if (notification.args.flashback) {
                this.addCardNameLogTooltip(notification.args['flashbackNameId'], 'flashback');
            }
        },

        notif_survivalCardPlayed: function (notification) {
            this.addToPlayerSurvivalCardsSize(notification.args.playerId, -1);
            if (notification.args.playerId.toString() === this.player_id.toString()) {
                this.survivalDiscard.addToStockWithId(11, notification.args.card, 'hand_item_' + notification.args.card);
                this.playerHand.removeFromStockById(notification.args.card);
                endnotif();
            } else {
                this.survivalDiscard.addToStockWithId(11, notification.args.card, 'survival_cards_count_player_' + notification.args.playerId);

                var tmpobj = {};
                tmpobj['class'] = 'cardsWrapper';

                var wrapper = dojo.create("div", tmpobj);

                var tmpobj = {};
                tmpobj['class'] = 'survivalCard';

                var cardDiv = dojo.create("div", tmpobj, wrapper);
                this.setupSurvivalCard(cardDiv, notification.args.card);
                this.displayDialog(dojo.string.substitute(_(notification.log), notification.args), wrapper);
            }
            if (notification.args.card === 'smokescreen' && this.player_id.toString() === this.gamedatas.creaturePlayer.toString()) {
                for (var playerId in this.gamedatas.players) {
                    if (this.gamedatas.players.hasOwnProperty(playerId) && this.gamedatas.players[playerId].discard) {
                        this.gamedatas.players[playerId].discard.removeAll();
                        delete this.gamedatas.players[playerId].discardedPlaces;
                    }
                }
            }
            if (notification.args.ongoingEffect) {
                dojo.removeClass('ongoing_effects_zone', 'hidden');
                this.ongoingEffects.addToStockWithId(11, notification.args.card);
            }
            this.addCardNameLogTooltip(notification.args.cardNameId, notification.args.card);
        },

        notif_survivalCardDiscarded: function (notification) {
            this.addToPlayerSurvivalCardsSize(notification.args.playerId, -1);
            if (notification.args.playerId.toString() === this.player_id.toString()) {
                this.playerHand.removeFromStockById(notification.args.card);
            }
            this.addCardNameLogTooltip(notification.args.cardNameId, notification.args.card);
        },

        notif_huntCardLost: function (notification) {
            this.addToPlayerHuntCardsSize(notification.args.playerId, -1);
            this.gamedatas.huntDeckSize++;
            this.huntDeckCounter.setValue(this.gamedatas.huntDeckSize);
            if (this.player_id.toString() === notification.args.playerId.toString()) {
                this.playerHand.removeFromStockById(notification.args.card);
            }
            this.addCardNameLogTooltip(notification.args.cardNameId, notification.args.card);
        },

        notif_placeDiscarded: function (notification) {
            var playerId = notification.args.playerId;
            var player = this.gamedatas.players[playerId];
            this.addToPlayerPlaceCardsSize(playerId, -1);
            if (player.discardedPlaces) {
                this.addPlaceToDiscard(playerId, notification.args.place, player.discardedPlaces.length);
                player.discardedPlaces.push(notification.args.place);
            }
            if (playerId.toString() === this.player_id.toString()) {
                this.playerHand.removeFromStockById(notification.args.place, "discard_player_" + this.player_id);
            }
        },

        notif_placesRevealed: function (notification) {
            var wrapper = dojo.create("div", {id: 'dialogPlaces'});
            for (var i = 0; i < notification.args.places.length; i++) {

                var tmpobj = {};
                tmpobj['class'] = 'placeNumber';


                var placeNumber = dojo.create("div", tmpobj, wrapper);
                dojo.setStyle(placeNumber, 'background-position-x', '-' + (notification.args.places[i] - 1) % 5 * 136 + 'px');
                dojo.setStyle(placeNumber, 'background-position-y', '-' + Math.floor((notification.args.places[i] - 1) / 5) * 196 + 'px');
            }
            this.displayDialog(dojo.string.substitute(_("${player_name} reveals ${quantity} Place cards"), notification.args), wrapper);
        },

        notif_placeCardSwapped: function (notification) {
            var playerId = notification.args.playerId;
            var player = this.gamedatas.players[playerId];
            if (!player.discardedPlaces) {
                return; // Smokescreen
            }
            var swappedIndex = player.discardedPlaces.indexOf(notification.args.place);
            player.discard.items[swappedIndex] = {id: notification.args.swappedPlace, type: notification.args.swappedPlace};
            if (this.player_id.toString() === playerId) {
                player.discard.items[player.discard.items.length - 1] = {id: notification.args.place, type: notification.args.place};
            }
            player.discard.updateDisplay();
            if (this.player_id.toString() === playerId) {
                dojo.removeClass('discard_player_' + playerId + '_item_' + notification.args.swappedPlace, 'played');
                dojo.addClass('discard_player_' + playerId + '_item_' + notification.args.place, 'played');
                this.removeTooltip('discard_player_' + playerId + '_item_' + notification.args.swappedPlace);
                this.removeTooltip('discard_player_' + playerId + '_item_' + notification.args.place);
                this.addTooltip('discard_player_' + playerId + '_item_' + notification.args.swappedPlace,
                  dojo.string.substitute(_('${place} is in your discard.'), {place: this.getPlaceName(notification.args.swappedPlace)}), '');
                this.addTooltip('discard_player_' + playerId + '_item_' + notification.args.place,
                  dojo.string.substitute(_('You played ${place} this turn.'), {place: this.getPlaceName(notification.args.place)}), '');
            } else {
                this.addTooltip('discard_player_' + playerId + '_item_' + notification.args.swappedPlace,
                  dojo.string.substitute(_('${place} is in ${player}’s discard.'), {
                      place: this.getPlaceName(notification.args.swappedPlace),
                      player: player.name
                  }), '');
            }
            player.discardedPlaces[swappedIndex] = notification.args.swappedPlace;
        },

        notif_huntTokenMoved: function (notification) {
            this.attachToNewParent($(notification.args.token), $('artemia_tokens_' + notification.args.place));
        },

        notif_huntedMoved: function (notification) {
            var huntedName = dojo.query('#artemia_item_' + notification.args.origin + ' .huntedName[data-value="' + notification.args.playerId + '"]')[0];
            this.attachToNewParent(huntedName, $('artemia_item_' + notification.args.destination));
        },

        notif_placeCopied: function (notification) {
            this.addCardNameLogTooltip(notification.args.cardNameId, 'gate');
        },

        notif_toxinDiscardAvoided: function (notification) {
            this.addCardNameLogTooltip(notification.args.cardNameId, 'toxin');
        },

        notif_smokescreenDissipates: function (notification) {
            for (var playerId in notification.args) {
                if (notification.args.hasOwnProperty(playerId)) {
                    var player = this.gamedatas.players[playerId];
                    player.discard.removeAll();
                    player.discardedPlaces = notification.args[playerId];
                    for (var i = 0; i < player.discardedPlaces.length; i++) {
                        this.addPlaceToDiscard(playerId, player.discardedPlaces[i], i);
                    }
                }
            }
        },

        notif_information: function (notification) {
            this.displayDialog(_("Information"), dojo.create("p", {innerHTML: notification.log}));
        },

        notif_survivalDeckShuffled: function () {
            this.gamedatas.survivalDeckSize += this.survivalDiscard.count();
            this.survivalDeckCounter.setValue(this.gamedatas.survivalDeckSize);
            this.survivalDiscard.removeAll();
        },

        notif_huntDeckShuffled: function () {
            this.gamedatas.huntDeckSize += this.huntDiscard.count();
            this.huntDeckCounter.setValue(this.gamedatas.huntDeckSize);
            this.huntDiscard.removeAll();
        }
    });
});