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

require_once 'org/puremvc/php/multicore/patterns/observer/Notifier.php';
require_once 'org/puremvc/php/multicore/utilities/statemachine/State.php';
require_once 'org/puremvc/php/multicore/utilities/statemachine/StateMachine.php';	
/**
 * Creates and registers a StateMachine described in XML.
 * 
 * This allows reconfiguration of the StateMachine 
 * without changing any code, as well as making it 
 * easier than creating all the <b>State</b> 
 * instances and registering them with the 
 * <b>StateMachine</b> at startup time.
 * 
 * One way to acheive this setup is to use a command controller that 
 * could be added a application startup.  Such a controller could look 
 * like this:
 * <code>
 * //From the PureMVC AS3 StopWatch Demo
 * class InjectFSMCommand extends SimpleCommand
 * {
 *     public function execute ( INotification $notification )
 *     {
 *         // Create the FSM definition
 *         $fsmStr = <<<XML
 *         <fsm initial="StopWatch/states/ready">
 *             <state name="StopWatch/states/ready" entering="resetDisplay">
 *                 <transition action="StopWatch/actions/start" target="StopWatch/states/running"/>
 *             </state>
 *             <state name="StopWatch/states/running" entering="ensureTimer">
 *                 <transition action="StopWatch/actions/split" target="StopWatch/states/paused"/>
 *                 <transition action="StopWatch/actions/stop" target="StopWatch/states/stopped"/>
 *             </state>
 *             <state name="StopWatch/states/paused" entering="freezeDisplay">
 *                 <transition action="StopWatch/actions/unsplit" target="StopWatch/states/running"/>
 *                 <transition action="StopWatch/actions/stop" target="StopWatch/states/stopped"/>
 *             </state>
 *             <state name="StopWatch/states/stopped" entering="stopTimer">
 *                 <transition action="StopWatch/actions/reset" target="StopWatch/states/ready"/>
 *             </state>
 *         </fsm>XML;
 * 
 *         $fsm = new SimpleXMLElement($fsmStr);
 * 
 *         $injector = new FSMInjector($fsm);
 *         $injector->initializeNotifier('FSMInjectorTest');
 *         $injector->inject();
 *     }
 * }
 * </code>
 * 
 * @see State
 		org/puremvc/php/multicore/utilities/statemachine/State.php
 * @see StateMachine
 		org/puremvc/php/multicore/utilities/statemachine/StateMachine.php
 * 
 * @package org.puremvc.php.multicore.utilities.statemachine
 */
class FSMInjector extends Notifier
{
	/**
	 * The XML FSM definition
	 * @var SimpleXMLElement
	 */
	protected $fsm;
	
	/**
	 * The List of State objects
	 * @var array
	 */
	protected $stateList = null;
	
	/**
	 * Constructor.
	 * 
	 * @param SimpleXMLElement $fsm The XML FSM definition
	 * @return FSMInjector
	 */
	public function __construct( SimpleXMLElement $fsm ) 
	{
		$this->fsm = $fsm;
	}
	
	/**
	 * Inject the <b>StateMachine</b> into the PureMVC apparatus.
	 * 
	 * Creates the <b>StateMachine</b> instance, registers all the states
	 * and registers the <b>StateMachine</b> with the <b>IFacade</b>.
	 * 
	 * @return void
	 */
	public function inject()
	{
		// Create the StateMachine
		$stateMachine = new StateMachine();
		
		// Register all the states with the StateMachine
		foreach ( $this->getStates() as $state )
		{ 
			$stateMachine->registerState( $state, $this->isInitial( $state->name ) );
		}				
		// Register the StateMachine with the facade
		$this->facade()->registerMediator( $stateMachine );
	}

	
	/**
	 * Get the state definitions.
	 * 
	 * Creates and returns the array of State objects 
	 * from the FSM on first call, subsequently returns
	 * the existing array.
	 * 
	 * @return array
	 */
	protected function getStates()
	{
		if (!isset($this->stateList)) 
		{
			$this->stateList = array();
			
			//$stateDefs = $this->fsm->state;
			
			foreach($this->fsm->state as $stateDef)
			{
				$state = $this->createState( $stateDef );
				array_push($this->stateList, $state);
			}
		} 
		return $this->stateList;
	}

	/**
	 * Creates a <b>State</b> instance from its XML definition.
	 * 
	 * @param SimpleXMLElement $stateDef
	 * @return State
 	 */
	protected function createState( SimpleXMLElement $stateDef )
	{
		// Create State object
		$name = (string)$stateDef['name'];
		$entering = (string)$stateDef['entering'];
		$exiting = (string)$stateDef['exiting'];
		$changed = (string)$stateDef['changed'];
		$state = new State( $name, $entering, $exiting, $changed );
		
		// Create transitions
		foreach($stateDef->transition as $transDef)
		{
			$state->defineTrans( (string)$transDef['action'], (string)$transDef['target'] );
		}

		return $state;
	}

	/**
	 * Is the given state the initial state?
	 * 
	 * @param string $stateName
	 * @return bool
	 */
	protected function isInitial( $stateName )
	{
		$initial = (string)$this->fsm['initial'];
		return ($stateName == $initial);
	}
	
}
