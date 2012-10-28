<?php
/**
 * PureMVC PHP MultiCore Utility - StateMachine
 * 
 * A PHP port of Cliff Hall
 * PureMVC AS3/MultiCore Utility - StateMachine 1.1
 * 
 * Created on August 2, 2009
 * 
 * @version 1.0.0
 * @author Michel Chouinard <michel.chouinard@gmail.com>
 * @copyright PureMVC - Copyright(c) 2006-2008 Futurescale, Inc., Some rights reserved.
 * @license http://creativecommons.org/licenses/by/3.0/ Creative Commons Attribution 3.0 Unported License
 * @package org.puremvc.php.multicore.utilities.statemachine
 */
/**
 * 
 */
require_once 'org/puremvc/php/multicore/interfaces/INotification.php';
require_once 'org/puremvc/php/multicore/patterns/mediator/Mediator.php';

/**
 * A Finite State Machine implimentation.
 * 
 * Handles regisistration and removal of state definitions, 
 * which include optional entry and exit commands for each 
 * state.
 * 
 * @see Mediator
 		org/puremvc/php/multicore/patterns/mediator/Mediator.php 		 
 * 
 * @package org.puremvc.php.multicore.utilities.statemachine
 */
class StateMachine extends Mediator
{
	const NAME = "StateMachine";

	/**
	 * Action Notification name. 
	 */ 
	const ACTION = "StateMachine/notes/action";

	/**
	 *  Changed Notification name  
	 */ 
	const CHANGED = "StateMachine/notes/changed";
	
	/**
	 *  Cancel Notification name  
	 */ 
	const CANCEL = "StateMachine/notes/cancel";
	
	/**
	 * Map of States objects by name.
	 * @var array
	 */
	protected $states = array();
	
	/**
	 * The initial state of the FSM.
	 * @var State
	 */
	protected $initial;
	
	/**
	 * The transition has been canceled.
	 * @var bool
	 */
	protected $canceled;

	/**
	 * Constructor.
	 * 
	 * @return StateMachine
	 */
	public function __construct( )
	{
		parent::__construct( StateMachine::NAME );
	}
	
    /**
     * onRegister event
     *
     * Called by the <b>View</b> when the <b>Mediator</b> is registered.
     *
     * @return void
     */
	public function onRegister()
	{
		if ( isset($this->initial) )
		{
			$this->transitionTo( $this->initial, null );
		}	
	}
	
	/**
	 * Registers the entry and exit commands for a given state.
	 * 
	 * @param State $state the state to which to register the above commands
	 * @param bool $initial boolean telling if this is the initial state of the system
	 * @return void
	 */
	public function registerState( State $state, $initial=false )
	{
		if ( is_null($state) || isset($this->states[ $state->name ]) ) return;
		$this->states[ $state->name ] = $state;
		if ( $initial ) $this->initial = $state; 
	}
	
	/**
	 * Remove a state mapping. 
	 * 
	 * Removes the entry and exit commands for a given state 
	 * as well as the state mapping itself.
	 * 
	 * @param  string stateName
	 * @return void
	 */
	public function removeState( $stateName )
	{
		unset($this->states[ $stateName ]);
	}
	
	/**
	 * Transitions to the given state from the current state.
	 * 
	 * Sends the <b>exiting</b> notification for the current state 
	 * followed by the <b>entering</b> notification for the new state.
	 * Once finally transitioned to the new state, the <b>changed</b> 
	 * notification for the new state is sent.
	 * 
	 * If a data parameter is provided, it is included as the body of all
	 * three state-specific transition notes.
	 * 
	 * Finally, when all the state-specific transition notes have been
	 * sent, a <b>StateMachine.CHANGED</b> note is sent, with the
	 * new <b>State</b> object as the <b>body</b> and the name of the 
	 * new state in the <b>type</b>.
	 * 
	 * @param State $nextState the next State to transition to.
	 * @param object data is the optional Object that was sent in the <b>StateMachine.ACTION</b> notification body
	 * @return void
	 */
	protected function transitionTo( State $nextState, $data=null )
	{
		// Going nowhere?
		if ( $nextState == null ) return;
		
		// Clear the cancel flag
		$this->canceled = false;
			
		// Exit the current State 
		if ( $this->getCurrentState() && $this->getCurrentState()->exiting ) $this->sendNotification( $this->getCurrentState()->exiting, $data, $nextState->name );
		
		// Check to see whether the exiting guard has canceled the transition
		if ( $this->canceled ) 
		{
			$this->canceled = false;
			return;
		}
		
		// Enter the next State 
		if ( $nextState->entering ) $this->sendNotification( $nextState->entering, $data );
		
		
		// Check to see whether the entering guard has canceled the transition
		if ( $this->canceled ) 
		{
			$this->canceled = false;
			return;
		}
		
		// change the current state only when both guards have been passed
		$this->setCurrentState($nextState);
		
		// Send the notification configured to be sent when this specific state becomes current 
		if ( $nextState->changed ) $this->sendNotification( $this->getCurrentState()->changed, $data );

		// Notify the app generally that the state changed and what the new state is 
		$this->sendNotification( StateMachine::CHANGED, $this->getCurrentState(), $this->getCurrentState()->name );
	
	}
	
	/**
	 * Notification interests for the StateMachine.
	 */
	public function listNotificationInterests()
	{
		return array( StateMachine::ACTION,
					  StateMachine::CANCEL);
	}
	
	/**
	 * Handle notifications the <b>StateMachine</b> is interested in.
	 * 
	 * <b>StateMachine::ACTION</b>: Triggers the transition to a new state.<BR>
	 * <b>StateMachine::CANCEL</b>: Cancels the transition if sent in response to the exiting note for the current state.<BR>
     * 
     * @param INotification $notification The <b>INotification</b> to be handled.
     * @return void
	 */
	public function handleNotification( INotification $notification )
	{
		switch( $notification->getName() )
		{
			case StateMachine::ACTION:
				$action = $notification->getType();
				$target = $this->getCurrentState()->getTarget( $action );
				$newState = $this->states[ $target ];
				if ( $newState ) $this->transitionTo( $newState, $notification->getBody() );  
				break;
				
			case StateMachine::CANCEL:
				$this->canceled = true;
				break;
		}
	}
	
	/**
	 * Get the current state.
	 *  
	 * @return State A State defining the machine's current state
	 */
	protected function getCurrentState()
	{
		return $this->viewComponent;
	}

	/**
	 * Set the current state.
	 * 
	 * @param State $state The current state to use
	 */
	protected function setCurrentState( State $state )
	{
		$this->viewComponent = $state;
	}
	
}
