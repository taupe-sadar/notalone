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
 * material.inc.php
 *
 * NotAlone game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->placeCards = array(
    1 => array(
        'name' => clienttranslate('The Lair'),
        'description' => array(
            clienttranslate('Take back to your hand the Place cards from your discard pile OR copy the power of the place with the Creature token.'),
            clienttranslate('Lose 1 extra Will if caught by the Creature token.')
        )
    ),
    2 => array(
        'name' => clienttranslate('The Jungle'),
        'description' => array(clienttranslate('Take back to your hand this Place card and 1 Place card from your discard pile.'))
    ),
    3 => array(
        'name' => clienttranslate('The River'),
        'description' => array(clienttranslate('Next turn, play 2 Place cards. Before revealing, choose one and return the second to your hand.'))
    ),
    4 => array(
        'name' => clienttranslate('The Beach'),
        'description' => array(
            clienttranslate('Place the Marker counter on the Beach OR remove it to move the Rescue counter forward 1 space.'),
            clienttranslate('(max 1x/turn)')
        )
    ),
    5 => array(
        'name' => clienttranslate('The Rover'),
        'description' => array(clienttranslate('Take from the reserve 1 Place card you do not own and add it to your hand.'))
    ),
    6 => array(
        'name' => clienttranslate('The Swamp'),
        'description' => array(clienttranslate('Take back to your hand this Place card and 2 Place cards from your discard pile.'))
    ),
    7 => array(
        'name' => clienttranslate('The Shelter'),
        'description' => array(clienttranslate('Draw 2 Survival cards, choose one and discard the second.'))
    ),
    8 => array(
        'name' => clienttranslate('The Wreck'),
        'description' => array(
            clienttranslate('Move the Rescue counter forward 1 space.'),
            clienttranslate('(max 1x/turn)')
        )
    ),
    9 => array(
        'name' => clienttranslate('The Source'),
        'description' => array(clienttranslate('The Hunted of your choice (you or another player) regains 1 Will OR you draw 1 Survival card.'))
    ),
    10 => array(
        'name' => clienttranslate('The Artefact'),
        'description' => array(clienttranslate('Next turn, play 2 Place cards. Resolve both places. You may not copy the Artefact.'))
    ),
);

$this->huntCards = array(
    'despair' => array(
        'name' => clienttranslate('Despair'),
        'symbol' => 'Artemia',
        'description' => clienttranslate('No Survival cards may be played or drawn for the remainder of the turn.'),
        'phase' => 1,
        'ongoingEffect' => true
    ),
    'force-field' => array(
        'name' => clienttranslate('Force Field'),
        'symbol' => 'Target',
        'description' => clienttranslate('Before the Hunted play, target 2 adjacent places. Neither may be played this turn.'),
        'phase' => 1,
        'ongoingEffect' => true
    ),
    'anticipation' => array(
        'name' => clienttranslate('Anticipation'),
        'symbol' => null,
        'description' => clienttranslate('Choose one Hunted. If you catch him with the Creature token, move the Assimilation counter forward 1 extra space.'),
        'phase' => 2,
        'ongoingEffect' => true
    ),
    'ascendancy' => array(
        'name' => clienttranslate('Ascendancy'),
        'symbol' => null,
        'description' => clienttranslate('Force one Hunted to discard all but 2 Place cards from his hand.'),
        'phase' => 2,
        'ongoingEffect' => false
    ),
    'fierceness' => array(
        'name' => clienttranslate('Fierceness'),
        'symbol' => null,
        'description' => clienttranslate('Hunted caught by the Creature token lose 1 extra Will.'),
        'phase' => 2,
        'ongoingEffect' => true
    ),
    'forbidden-zone' => array(
        'name' => clienttranslate('Forbidden Zone'),
        'symbol' => null,
        'description' => clienttranslate('All Hunted discard 1 Place card simultaneously.'),
        'phase' => 2,
        'ongoingEffect' => false
    ),
    'interference' => array(
        'name' => clienttranslate('Interference'),
        'symbol' => null,
        'description' => clienttranslate('The powers of the Beach and the Wreck are ineffective.'),
        'phase' => 2,
        'ongoingEffect' => true
    ),
    'persecution' => array(
        'name' => clienttranslate('Persecution'),
        'symbol' => null,
        'description' => clienttranslate('Each Hunted may only take back 1 Place card when using the power of a Place card.'),
        'phase' => 2,
        'ongoingEffect' => true
    ),
    'mutation' => array(
        'name' => clienttranslate('Mutation'),
        'symbol' => 'Artemia',
        'description' => clienttranslate('In addition to its effects, the Artemia token inflicts the loss of 1 Will.'),
        'phase' => 2,
        'ongoingEffect' => true
    ),
    'phobia' => array(
        'name' => clienttranslate('Phobia'),
        'symbol' => 'Artemia',
        'description' => clienttranslate('Force one Hunted to show you all but 2 Place cards from his hand.'),
        'phase' => 2,
        'ongoingEffect' => false
    ),
    'virus' => array(
        'name' => clienttranslate('Virus'),
        'symbol' => 'Artemia',
        'description' => clienttranslate('Target 2 adjacent places. Apply the effects of the Artemia token on both places.'),
        'phase' => 2,
        'ongoingEffect' => false
    ),
    'clone' => array(
        'name' => clienttranslate('Clone'),
        'symbol' => 'Target',
        'description' => clienttranslate('Consider the Target token as a second Creature token.'),
        'phase' => 2,
        'ongoingEffect' => true
    ),
    'mirage' => array(
        'name' => clienttranslate('Mirage'),
        'symbol' => 'Target',
        'description' => clienttranslate('Target 2 adjacent places. Both are ineffective.'),
        'phase' => 2,
        'ongoingEffect' => true
    ),
    'scream' => array(
        'name' => clienttranslate('Scream'),
        'symbol' => 'Target',
        'description' => clienttranslate('Each Hunted on the targeted place must discard 2 Place cards or lose 1 Will.'),
        'phase' => 2,
        'ongoingEffect' => true
    ),
    'toxin' => array(
        'name' => clienttranslate('Toxin'),
        'symbol' => 'Target',
        'description' => clienttranslate('Each Hunted on the targeted place discards 1 Survival card. The power of the place is ineffective.'),
        'phase' => 2,
        'ongoingEffect' => true
    ),
    'cataclysm' => array(
        'name' => clienttranslate('Cataclysm'),
        'symbol' => null,
        'description' => clienttranslate('The place’s power of your choice is ineffective.'),
        'phase' => 3,
        'ongoingEffect' => true
    ),
    'detour' => array(
        'name' => clienttranslate('Detour'),
        'symbol' => null,
        'description' => clienttranslate('After the Hunted reveal their Place cards, move one Hunted to an adjacent place.'),
        'phase' => 3,
        'ongoingEffect' => true
    ),
    'stasis' => array(
        'name' => clienttranslate('Stasis'),
        'symbol' => null,
        'description' => clienttranslate('Prevent the Rescue counter moving forward during this phase.'),
        'phase' => 4,
        'ongoingEffect' => false
    ),
    'tracking' => array(
        'name' => clienttranslate('Tracking'),
        'symbol' => null,
        'description' => clienttranslate('Next turn, you may play up to 2 Hunt cards.'),
        'phase' => 4,
        'ongoingEffect' => true
    ),
    'flashback' => array(
        'name' => clienttranslate('Flashback'),
        'symbol' => null,
        'description' => clienttranslate('Copy the last Hunt card you discarded.'),
        'phase' => clienttranslate('Phase of the copied card'),
        'ongoingEffect' => false
    )
);

$this->survivalCards = array(
    'adrenaline' => array(
        'name' => clienttranslate('Adrenaline'),
        'description' => clienttranslate('Regain 1 Will'),
        'phase' => 1,
        'ongoingEffect' => false
    ),
    'ingenuity' => array(
        'name' => clienttranslate('Ingenuity'),
        'description' => clienttranslate('Place the Marker counter on the Beach.'),
        'phase' => 1,
        'ongoingEffect' => false
    ),
    'sacrifice' => array(
        'name' => clienttranslate('Sacrifice'),
        'description' => clienttranslate('Discard 1 Place card. No Hunt card may be played this turn.'),
        'phase' => 1,
        'ongoingEffect' => true
    ),
    'sixth-sense' => array(
        'name' => clienttranslate('Sixth Sense'),
        'description' => clienttranslate('Take back to your hand 2 Place cards from your discard pile.'),
        'phase' => 1,
        'ongoingEffect' => false
    ),
    'smokescreen' => array(
        'name' => clienttranslate('Smokescreen'),
        'description' => clienttranslate('All the Hunted hide their discarded Place cards until the end of the turn.'),
        'phase' => 1,
        'ongoingEffect' => true
    ),
    'strike-back' => array(
        'name' => clienttranslate('Strike Back'),
        'description' => clienttranslate('Take 2 random Hunt cards from the Creature’s hand and put them at the bottom of the Hunt deck.'),
        'phase' => 1,
        'ongoingEffect' => false
    ),
    'vortex' => array(
        'name' => clienttranslate('Vortex'),
        'description' => clienttranslate('Swap your played Place card for one Place card from your discard pile.'),
        'phase' => 2,
        'ongoingEffect' => false
    ),
    'detector' => array(
        'name' => clienttranslate('Detector'),
        'description' => clienttranslate('Avoid the effects of the Artemia token.'),
        'phase' => 3,
        'ongoingEffect' => true
    ),
    'dodge' => array(
        'name' => clienttranslate('Dodge'),
        'description' => clienttranslate('Avoid the effects of the Creature token.'),
        'phase' => 3,
        'ongoingEffect' => true
    ),
    'drone' => array(
        'name' => clienttranslate('Drone'),
        'description' => clienttranslate('Instead of using the power of your Place card, copy the power of the Rover.'),
        'phase' => 3,
        'ongoingEffect' => false
    ),
    'gate' => array(
        'name' => clienttranslate('Gate'),
        'description' => clienttranslate('Instead of using the power of your Place card, copy the power of an adjacent place.'),
        'phase' => 3,
        'ongoingEffect' => false
    ),
    'hologram' => array(
        'name' => clienttranslate('Hologram'),
        'description' => clienttranslate('Move the Artemia token to an adjacent place.'),
        'phase' => 3,
        'ongoingEffect' => false
    ),
    'wrong-track' => array(
        'name' => clienttranslate('Wrong Track'),
        'description' => clienttranslate('Move the Creature token to an adjacent place.'),
        'phase' => 3,
        'ongoingEffect' => false
    ),
    'amplifier' => array(
        'name' => clienttranslate('Amplifier'),
        'description' => clienttranslate('Remove the Marker counter from the Beach to immediately move the Rescue counter forward 1 space.'),
        'phase' => 4,
        'ongoingEffect' => false
    ),
    'double-back' => array(
        'name' => clienttranslate('Double Back'),
        'description' => clienttranslate('Take back the Place card you just played.'),
        'phase' => 4,
        'ongoingEffect' => false
    ),
);