{OVERALL_GAME_HEADER}

<!--
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- NotAlone implementation : © Romain Fromi <romain.fromi@gmail.com>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->

<div id="hand_zone" class="cardsZone">
    <div id="hand"></div>
</div>

<div id="choice_zone" class="cardsZone hidden">
    <h2>{CHOOSE_A_CARD}</h2>
</div>

<div id="artemia_zone" class="cardsZone">
    <div id="artemia_wrap">
        <h2>{ARTEMIA}</h2>
        <div id="artemia_tokens_11" class="huntTokensWrapper adjacentPlaces firstRow"></div>
        <div id="artemia_tokens_12" class="huntTokensWrapper adjacentPlaces firstRow"></div>
        <div id="artemia_tokens_13" class="huntTokensWrapper adjacentPlaces firstRow"></div>
        <div id="artemia_tokens_14" class="huntTokensWrapper adjacentPlaces firstRow"></div>
        <div id="artemia_tokens_15" class="huntTokensWrapper adjacentPlaces"></div>
        <div id="artemia_tokens_16" class="huntTokensWrapper adjacentPlaces"></div>
        <div id="artemia_tokens_17" class="huntTokensWrapper adjacentPlaces"></div>
        <div id="artemia_tokens_18" class="huntTokensWrapper adjacentPlaces"></div>
        <div id="artemia_tokens_19" class="huntTokensWrapper adjacentPlaces"></div>
        <div id="artemia_tokens_20" class="huntTokensWrapper adjacentPlaces secondRow"></div>
        <div id="artemia_tokens_21" class="huntTokensWrapper adjacentPlaces secondRow"></div>
        <div id="artemia_tokens_22" class="huntTokensWrapper adjacentPlaces secondRow"></div>
        <div id="artemia_tokens_23" class="huntTokensWrapper adjacentPlaces secondRow"></div>

        <div id="artemia"></div>
    </div>
    <div id="place_cards_reserve">
        <p id="reserve_6"></p>
        <p id="reserve_7"></p>
        <p id="reserve_8"></p>
        <p id="reserve_9"></p>
        <p id="reserve_10"></p>
    </div>
</div>

<div id="ongoing_effects_zone" class="cardsZone hidden">
    <h2>{ONGOING_EFFECTS}</h2>
    <div id="ongoingEffects"></div>
</div>

<div id="hunt_survival_cards_discard_zone" class="cardsZone">
    <h2>{DISCARDED}</h2>
    <div id="huntDiscard"></div>
    <div id="survivalDiscard"></div>
</div>

<script type="text/javascript">
    // Javascript HTML templates
    var jstpl_board = '\<div id="board${temp}" class="board">\
            <img src="{GAMETHEMEURL}img/board_${boardSide}.jpg"/>\
            <div id="rescueCounter${temp}" class="counter rescueCounter" data-value="${rescueCounter}"></div>\
            <div id="assimilationCounter${temp}" class="counter assimilationCounter" data-value="${assimilationCounter}"></div>\
        </div>';

    var jstpl_creature_board = '\<div class="playerElements">\
            <span id="hunt_cards_count" class="cardsCount huntCardsCount">0</span>\
            <span id="hunt_deck_count" class="deckCount huntDeckCount">0</span>\
            <span id="survival_deck_count" class="deckCount survivalDeckCount">0</span>\
        </div>';

    var jstpl_hunted_board = '\<div class="playerElements">\
            <span id="place_cards_count_player_${id}" class="cardsCount placeCardsCount">0</span>\
            <span id="survival_cards_count_player_${id}" class="cardsCount survivalCardsCount">0</span>\
            <div id="will_counters_player_${id}" class="willCounters"></div>\
        </div>\
        <div class="playerDiscardStock" id="discard_player_${id}"></div>';
</script>

{OVERALL_GAME_FOOTER}
