/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * reversiristonj implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * reversiristonj.js
 *
 * reversiristonj user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.reversiristonj", ebg.core.gamegui, {
        constructor: function(){
            console.log('reversiristonj constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

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
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                         
                // TODO: Setting up players boards if needed
            }
            
            // TODO: Set up your game interface here, according to "gamedatas"
            for( var i in gamedatas.board )
            {
                var square = gamedatas.board[i];
                
                if( square.player !== null )
                {
                    this.addTokenOnBoard( square.x, square.y, square.player );
                }
            }

            dojo.query( '.square' ).connect( 'onclick', this, 'onPlayDisc' );

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
                case 'playerTurn':
                    this.updatePossibleMoves( args.args.possibleMoves );
                    break;
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
                case 'dummmy':
                    break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
        addTokenOnBoard: function( x, y, player )
        {
            dojo.place( this.format_block( 'jstpl_token', {
                x_y: x+'_'+y,
                color: this.gamedatas.players[ player ].color
            } ) , 'tokens' );
            
            this.placeOnObject( 'token_'+x+'_'+y, 'overall_player_board_'+player );
            this.slideToObject( 'token_'+x+'_'+y, 'square_'+x+'_'+y ).play();
        },
        updatePossibleMoves: function( possibleMoves )
        {
            // Remove current possible moves
            dojo.query( '.possibleMove' ).removeClass( 'possibleMove' );

            for( var i=0; i<possibleMoves.length; i++)
            {
                dojo.addClass( 'square_'+possibleMoves[i].x+'_'+possibleMoves[i].y, 'possibleMove' );
            }
                        
            this.addTooltipToClass( 'possibleMove', '', _('Place a disc here') );
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

        onPlayDisc: function( evt )
        {
            // Stop this event propagation
            dojo.stopEvent( evt );

            // Get the clicked square x and y
            // Note: square id format is "square_X_Y"
            var coords = evt.currentTarget.id.split('_');
            var x = coords[1];
            var y = coords[2];

            if( ! dojo.hasClass( 'square_'+x+'_'+y, 'possibleMove' ) )
            {
                // This is not a possible move => the click does nothing
                return ;
            }
            
            if( this.checkAction( 'playDisc' ) )    // Check that this action is possible at this moment
            {            
                this.ajaxcall( "/reversiristonj/reversiristonj/playDisc.html", {
                    x:x,
                    y:y
                }, this, function( result ) {} );
            }            
        },
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/reversiristonj/reversiristonj/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your reversiristonj.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            dojo.subscribe( 'playDisc', this, "notif_playDisc" );
            this.notifqueue.setSynchronous( 'playDisc', 500 );
            dojo.subscribe( 'turnOverDiscs', this, "notif_turnOverDiscs" );
            this.notifqueue.setSynchronous( 'turnOverDiscs', 1500 );
            dojo.subscribe( 'newScores', this, "notif_newScores" );
            this.notifqueue.setSynchronous( 'newScores', 500 );
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods

        notif_playDisc: function( notif )
        {
            // Remove current possible moves (makes the board more clear)
            dojo.query( '.possibleMove' ).removeClass( 'possibleMove' );        
        
            this.addTokenOnBoard( notif.args.x, notif.args.y, notif.args.player_id );
        },
        notif_turnOverDiscs: function( notif )
        {
            // Get the color of the player who is returning the discs
            var targetColor = this.gamedatas.players[ notif.args.player_id ].color;

            // Make these discs blink and set them to the specified color
            for( var i in notif.args.turnedOver )
            {
                var token = notif.args.turnedOver[ i ];
                
                // Make the token blink 2 times
                var anim = dojo.fx.chain( [
                    dojo.fadeOut( { node: 'token_'+token.x+'_'+token.y } ),
                    dojo.fadeIn( { node: 'token_'+token.x+'_'+token.y } ),
                    dojo.fadeOut( { 
                                    node: 'token_'+token.x+'_'+token.y,
                                    onEnd: function( node ) {

                                        // Remove any color class
                                        dojo.removeClass( node, [ 'tokencolor_000000', 'tokencolor_ffffff' ] );
                                        // ... and add the good one
                                        dojo.addClass( node, 'tokencolor_'+targetColor );
                                                             
                                    } 
                                  } ),
                    dojo.fadeIn( { node: 'token_'+token.x+'_'+token.y  } )
                                 
                ] ); // end of dojo.fx.chain

                // ... and launch the animation
                anim.play();                
            }
        },
        notif_newScores: function( notif )
        {
            for( var player_id in notif.args.scores )
            {
                var newScore = notif.args.scores[ player_id ];
                this.scoreCtrl[ player_id ].toValue( newScore );
            }
        },
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
   });             
});
