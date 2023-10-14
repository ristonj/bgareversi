<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * reversiristonj implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * reversiristonj.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class reversiristonj extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );        
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "reversiristonj";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = array( "ffffff", "000000" );
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( ',', $values );
        self::DbQuery( $sql );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
       
        // Init the board
        $sql = "INSERT INTO board (board_x,board_y,board_player) VALUES ";
        $sql_values = array();
        list( $blackplayer_id, $whiteplayer_id ) = array_keys( $players );
        for( $x=1; $x<=8; $x++ )
        {
            for( $y=1; $y<=8; $y++ )
            {
                $token_value = "NULL";
                if( ($x==4 && $y==4) || ($x==5 && $y==5) )  // Initial positions of white player
                    $token_value = "'$whiteplayer_id'";
                else if( ($x==4 && $y==5) || ($x==5 && $y==4) )  // Initial positions of black player
                    $token_value = "'$blackplayer_id'";
                    
                $sql_values[] = "('$x','$y',$token_value)";
            }
        }
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_color color FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        // Get reversi board token
        $result['board'] = self::getObjectListFromDB( "SELECT board_x x, board_y y, board_player player
                                                       FROM board
                                                       WHERE board_player IS NOT NULL" );
  
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
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function getBoard()
    {
        $board = self::getObjectListFromDB( 
            "SELECT board_x x, board_y y, board_player player
            FROM board" );
        return $board;
    }
    function getOpponentPlayerId()
    {
        $player_ids = array_keys($this->loadPlayersBasicInfos());
        $opposite_player_id = self::getCurrentPlayerId();
        if($player_ids[0] == self::getCurrentPlayerId())
        {
            $opposite_player_id = $player_ids[1];
        }
        else
        {
            $opposite_player_id = $player_ids[0];
        }
        return $opposite_player_id;
    }
    function getPlayerByPosition($board,$x,$y)
    {
        if(self::isOutOfBounds($x) || self::isOutOfBounds($y))
        {
            return null;
        }
        return array_values(
            array_filter($board, function($v) use ($x, $y)
            {
                return ($v['x'] == $x && $v['y'] == $y);
            }))[0]['player'];
    }
    function getPossibleMoves($active_player_id)
    {
        $board = self::getBoard();
        $result = array();
        for($x = 1; $x <= 8; $x++)
        {
            for($y = 1; $y <= 8; $y++)
            {
                if(self::getPlayerByPosition($board, $x, $y) == null)
                {
                    $turnedOverDiscs = self::getTurnedOverDiscs($board, $active_player_id, $x, $y);
                    if( count($turnedOverDiscs) > 0)
                    {
                        array_push($result, array("x" => $x, "y" => $y));
                    }
                }
            }
        }
        self::dump("Result array: ", $result);
        return $result;
    }
    function getTurnedDiscsByDirection($board,$active_player_id,$x,$y,$i,$j)
    {
        $new_x = $x+$i;
        $new_y = $y+$j;
        $space_player_id = self::getPlayerByPosition($board,$new_x,$new_y);
        $turned_disc_positions = array();

        while($space_player_id != $active_player_id)
        {
            if($space_player_id == null)
            {
                return array();
            }
            array_push($turned_disc_positions, array('x'=>$new_x,'y'=>$new_y));
            $new_x += $i;
            $new_y += $j;
            $space_player_id = self::getPlayerByPosition($board,$new_x,$new_y);
        }
        return $turned_disc_positions;
    }
    function getTurnedOverDiscs($board,$active_player_id,$x,$y)
    {
        $turned_discs = array();
        for($i = -1; $i <= 1; $i++)
        {
            for($j = -1; $j <= 1; $j++)
            {
                array_push($turned_disc, self::getTurnedDiscsByDirection(
                    $board,
                    $active_player_id,
                    $x,
                    $y,
                    $i,
                    $j));
            }
        }
        return $turned_discs;
    }
    function isOutOfBounds($index)
    {
        return ($index < 1) || ($index > 8);
    }

    



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in reversiristonj.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */
    function playDisc( $x, $y )
    {
        // Check that this player is active and that this action is possible at this moment
        self::checkAction( 'playDisc' );
        $board = self::getBoard();
        $player_id = self::getActivePlayerId();
        $turnedOverDiscs = self::getTurnedOverDiscs( $board, $player_id, $x, $y);
        
        if( count( $turnedOverDiscs ) > 0 )
        {
            $sql = "UPDATE board SET board_player='$player_id'
                    WHERE ( board_x, board_y) IN ( ";
            
            foreach( $turnedOverDiscs as $turnedOver )
            {
                $sql .= "('".$turnedOver['x']."','".$turnedOver['y']."'),";
            }
            $sql .= "('$x','$y') ) ";
                       
            self::DbQuery( $sql );
            $sql = "UPDATE player
                    SET player_score = (
                    SELECT COUNT( board_x ) FROM board WHERE board_player=player_id
                    )";
            self::DbQuery( $sql );
            
            // Statistics
            self::incStat( count( $turnedOverDiscs ), "turnedOver", $player_id );
            if( ($x==1 && $y==1) || ($x==8 && $y==1) || ($x==1 && $y==8) || ($x==8 && $y==8) )
                self::incStat( 1, 'discPlayedOnCorner', $player_id );
            else if( $x==1 || $x==8 || $y==1 || $y==8 )
                self::incStat( 1, 'discPlayedOnBorder', $player_id );
            else if( $x>=3 && $x<=6 && $y>=3 && $y<=6 )
                self::incStat( 1, 'discPlayedOnCenter', $player_id );
                        // Update scores according to the number of disc on board
                        $sql = "UPDATE player
                        SET player_score = (
                        SELECT COUNT( board_x ) FROM board WHERE board_player=player_id
                        )";
                self::DbQuery( $sql );
                
                // Statistics
                self::incStat( count( $turnedOverDiscs ), "turnedOver", $player_id );
                if( ($x==1 && $y==1) || ($x==8 && $y==1) || ($x==1 && $y==8) || ($x==8 && $y==8) )
                    self::incStat( 1, 'discPlayedOnCorner', $player_id );
                else if( $x==1 || $x==8 || $y==1 || $y==8 )
                    self::incStat( 1, 'discPlayedOnBorder', $player_id );
                else if( $x>=3 && $x<=6 && $y>=3 && $y<=6 )
                    self::incStat( 1, 'discPlayedOnCenter', $player_id );
    
                // Notify
                self::notifyAllPlayers( "playDisc", clienttranslate( '${player_name} plays a disc and turns over ${returned_nbr} disc(s)' ), array(
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'returned_nbr' => count( $turnedOverDiscs ),
                    'x' => $x,
                    'y' => $y
                ) );
    
                self::notifyAllPlayers( "turnOverDiscs", '', array(
                    'player_id' => $player_id,
                    'turnedOver' => $turnedOverDiscs
                ) );
                
                $newScores = self::getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
                self::notifyAllPlayers( "newScores", "", array(
                    "scores" => $newScores
                ) );
            // Then, go to the next state
            $this->gamestate->nextState( 'playDisc' );
        }
        else
            throw new BgaSystemException( "Impossible move" );
    }

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

    function argPlayerTurn()
    {
        return array(
            'possibleMoves' => self::getPossibleMoves( self::getActivePlayerId() )
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    function stNextPlayer()
    {
        // Active next player
        $player_id = self::activeNextPlayer();

        // Check if both player has at least 1 discs, and if there are free squares to play
        $player_to_discs = self::getCollectionFromDb( "SELECT board_player, COUNT( board_x )
                                                       FROM board
                                                       GROUP BY board_player", true );

        if( ! isset( $player_to_discs[ null ] ) )
        {
            // Index 0 has not been set => there's no more free place on the board !
            // => end of the game
            $this->gamestate->nextState( 'endGame' );
            return ;
        }
        else if( ! isset( $player_to_discs[ $player_id ] ) )
        {
            // Active player has no more disc on the board => he looses immediately
            $this->gamestate->nextState( 'endGame' );
            return ;
        }
        
        // Can this player play?

        $possibleMoves = self::getPossibleMoves( $player_id );
        if( count( $possibleMoves ) == 0 )
        {

            // This player can't play
            // Can his opponent play ?
            $opponent_id = self::getUniqueValueFromDb( "SELECT player_id FROM player WHERE player_id!='$player_id' " );
            if( count( self::getPossibleMoves( $opponent_id ) ) == 0 )
            {
                // Nobody can move => end of the game
                $this->gamestate->nextState( 'endGame' );
            }
            else
            {            
                // => pass his turn
                $this->gamestate->nextState( 'cantPlay' );
            }
        }
        else
        {
            // This player can play. Give him some extra time
            self::giveExtraTime( $player_id );
            $this->gamestate->nextState( 'nextTurn' );
        }

    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
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
    
    function upgradeTableDb( $from_version )
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
