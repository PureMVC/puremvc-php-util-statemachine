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

/**
 * Defines a State.
 * 
 * @package org.puremvc.php.multicore.utilities.statemachine
 */
class State
{
	/**
	 * The state name
	 * @var string
	 */
	public $name;
	
	/**
	 * The notification to dispatch when entering the state
	 * @var string
	 */
	public $entering;
	
	/**
	 * The notification to dispatch when exiting the state
	 * @var string
	 */
	public $exiting;

	
	/**
	 * The notification to dispatch when the state has actually changed 
	 * @var string
	 */
	public $changed;
	
	/**
	 *  Transition map of actions to target states
	 *  @var array
	 */ 
	protected $transitions = array();
	
	/**
	 * Constructor.
	 * 
	 * @param string $name the name of the state
	 * @param string $entering an optional notification name to be sent when entering this state
	 * @param string $exiting an optional notification name to be sent when exiting this state
	 * @param string $changed an optional notification name to be sent when fully transitioned to this state
	 * @return State
	 */
	public function __construct( $name, $entering=null, $exiting=null, $changed=null )
	{
		$this->name = $name;
		if ( !is_null($entering) ) $this->entering = $entering;
		if ( !is_null($exiting) ) $this->exiting  = $exiting;
		if ( !is_null($changed) ) $this->changed = $changed;
	}

	/** 
	 * Define a transition. 
	 * 
	 * @param string $action the name of the StateMachine::ACTION Notification type.
	 * @param string $target the name of the target state to transition to.
	 * @return void;
	 */
	public function defineTrans( $action, $target )
	{
		if ( $this->getTarget( $action ) != null ) return;	
		$this->transitions[ $action ] = $target;
	}

	/** 
	 * Remove a previously defined transition.
	 * 
	 * @param string $action the name of the StateMachine::ACTION Notification type.
	 * @return void;
	 */
	public function removeTrans( $action )
	{
		unset($this->transitions[ $action ]);	
	}	
	
	/**
	 * Get the target state name for a given action.
	 * 
	 * @param string $action the name of the StateMachine::ACTION Notification type.
	 * @return string
	 */
	public function getTarget( $action )
	{
		return isset($this->transitions[ $action ]) ? $this->transitions[ $action ] : null;
	}
	
}
