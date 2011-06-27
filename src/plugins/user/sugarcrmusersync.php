<?php
/**
 * @version		$Id$
 * @package		Joomla
 * @subpackage	JFramework
 * @author    	Antonio Musarra <antonio.musarra@gmail.com>
 * @copyright	Copyright(C) 2010 Antonio Musarra. All rights reserved.
 * @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link		http://musarra.wordpress.com
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.error.log');

/**
 * SugarCRM User Sync Plugin. When you create or update a user on Joomla, 
 * the creation or update operations are also migrated to SugarCRM.
 *
 * @package		Joomla
 * @subpackage	JFramework
 * @since 		1.5
 */
class plgUserSugarCRMUserSync extends JPlugin {

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */
	function plgUserSugarCRMUserSync(& $subject, $config)
	{
		parent::__construct($subject, $config);

		global $mainframe;

		// Register legacy classes for autoloading
		JLoader::register('CRMUserSyncClientFactory', dirname(__FILE__).DS.'SugarCRMUserSync'.DS.'CRMUserSyncClientFactory.php');
		JLoader::register('ICRMUserSyncClient', dirname(__FILE__).DS.'SugarCRMUserSync'.DS.'ICRMUserSyncClient.php');
		JLoader::register('SOAP_SugarCRMUserSyncClient', dirname(__FILE__).DS.'SugarCRMUserSync'.DS.'SOAP_SugarCRMUserSyncClient.php');
		JLoader::register('UserModel', dirname(__FILE__).DS.'SugarCRMUserSync'.DS.'UserModel.php');
	}

	/**
	 * Method is called before user data is stored in the database
	 *
	 * @param 	array		holds the old user data
	 * @param 	boolean		true if a new user is stored
	 */
	function onBeforeStoreUser($user, $isnew)
	{
		global $mainframe;

	}

	/**
	 * Method is called after user data is stored in the database
	 *
	 * @param 	array		holds the new user data
	 * @param 	boolean		true if a new user is stored
	 * @param	boolean		true if user was succesfully stored in the database
	 * @param	string		message
	 */
	function onAfterStoreUser($user, $isnew, $success, $msg)
	{
		global $mainframe;
		
		$logFile = $this->params->get('LogFile');
		$logFile = (empty($logFile) || is_null($logFile)) ? 'user_crm_sync.log' : $logFile;

		$jlog = &JLog::getInstance($logFile);
		$debug = (boolean) $this->params->get('DebugEnabled');

		// If the StoreUser operation is successful, then proceed with data migration to SugarCRM
		if ($success) {
			// Create a User Object
			$crmUserObject = new UserModel();
			$crmUserObject->email =  $user['email'];
			$crmUserObject->full_name = $user['name'];
			$crmUserObject->userName = $user['username'];
			$crmUserObject->password = $user['password_clear'];
			$crmUserObject->registerDate = $user['registerDate'];
			$crmUserObject->block = $user['block'];
			$crmUserObject->department = $user['usertype'];

			// If a new User then call Operation CreateUser otherwise call Operation UpdateUser
			if ($isnew)
			{
				try {
					$CRMUserClient = CRMUserSyncClientFactory::factory('PHP_SOAP', array('Options' => array('PluginParams' => $this->params)));
					$jlog->addEntry(array('comment' => __METHOD__ . " => Call CreateUser ({$crmUserObject->full_name})...", 'status' =>  '0'));
					if ($debug) {
						$jlog->addEntry(array('comment' => __METHOD__ . " => Call CreateUser ({$crmUserObject->full_name} - " . serialize($crmUserObject) . ")...", 'status' =>  '0'));
					}	
					if ($CRMUserClient->createUser($crmUserObject)) {
						$jlog->addEntry(array('comment' => __METHOD__ . " => Call CreateUser ({$crmUserObject->full_name})...[OK]", 'status' =>  '0'));
					}
				} catch (Exception $e) {
					$jlog->addEntry(array('comment' => __METHOD__ . " => Call CreateUser ({$crmUserObject->full_name}) Exception: {$e->getMessage()}", 'status' =>  $e->getCode()));
					if ($debug)
						$jlog->addEntry(array('comment' => __METHOD__ . " => Call CreateUser ({$crmUserObject->full_name}) exception: {$e->getTraceAsString()}", 'status' =>  $e->getCode()));
				}
			}
			else
			{
				try {
					$CRMUserClient = CRMUserSyncClientFactory::factory('PHP_SOAP', array('Options' => array('PluginParams' => $this->params)));
					$jlog->addEntry(array('comment' => __METHOD__ . " => Call UpdateUser ({$crmUserObject->full_name})...", 'status' =>  '0'));
					if ($debug) {	
						$jlog->addEntry(array('comment' => __METHOD__ . " => Call UpdateUser ({$crmUserObject->full_name} - " . serialize($crmUserObject) . ")...", 'status' =>  '0'));
					}
					
					if ($CRMUserClient->updateUser($crmUserObject)) {
						$jlog->addEntry(array('comment' => __METHOD__ . " => Call UpdateUser ({$crmUserObject->full_name})...[OK]", 'status' =>  '0'));
					}
				} catch (Exception $e) {
					$jlog->addEntry(array('comment' => __METHOD__ . " => Call UpdateUser ({$crmUserObject->full_name}) Exception: {$e->getMessage()}", 'status' =>  $e->getCode()));
					if ($debug)
						$jlog->addEntry(array('comment' => __METHOD__ . " => Call UpdateUser ({$crmUserObject->full_name}) exception: {$e->getTraceAsString()}", 'status' =>  $e->getCode()));
				}
			}
		}
	}

	/**
	 * Method is called before user data is deleted from the database
	 *
	 * @param 	array		holds the user data
	 */
	function onBeforeDeleteUser($user)
	{
		global $mainframe;
	}

	/**
	 * Method is called after user data is deleted from the database
	 *
	 * @param 	array		holds the user data
	 * @param	boolean		true if user was succesfully stored in the database
	 * @param	string		message
	 */
	function onAfterDeleteUser($user, $succes, $msg)
	{
		global $mainframe;

		$logFile = $this->params->get('LogFile');
		$logFile = (empty($logFile) || is_null($logFile)) ? 'user_crm_sync.log' : $logFile;

		$jlog = &JLog::getInstance($logFile);
		$debug = (boolean) $this->params->get('DebugEnabled');

		// If the StoreUser operation is successful, then proceed with data migration to SugarCRM
		if ($succes) {
			// Create a User Object
			$crmUserObject = new UserModel();
			$crmUserObject->email =  $user['email'];
			$crmUserObject->full_name = $user['name'];
			$crmUserObject->userName = $user['username'];
			$crmUserObject->password = $user['password_clear'];
			$crmUserObject->registerDate = $user['registerDate'];
			$crmUserObject->block = $user['block'];
				
			try {
				$CRMUserClient = CRMUserSyncClientFactory::factory('PHP_SOAP', array('Options' => array('PluginParams' => $this->params)));

				if ($debug) {
					$jlog->addEntry(array('comment' => __METHOD__ . " => Call DeleteUser ({$crmUserObject->full_name} - " . serialize($crmUserObject) . ")...", 'status' =>  '0'));
				}
				if ($CRMUserClient->deleteUser($crmUserObject)) {
					$jlog->addEntry(array('comment' => __METHOD__ . " => Call DeleteUser ({$crmUserObject->full_name})...[OK]", 'status' =>  '0'));
				}
			} catch (Exception $e) {
				$jlog->addEntry(array('comment' => __METHOD__ . " => Call CreateUser ({$crmUserObject->full_name}) Exception: {$e->getMessage()}", 'status' =>  $e->getCode()));
				if ($debug)
					$jlog->addEntry(array('comment' => __METHOD__ . " => Call CreateUser ({$crmUserObject->full_name}) exception: {$e->getTraceAsString()}", 'status' =>  $e->getCode()));
			}
		}
	}

	/**
	 * This method should handle any login logic and report back to the subject
	 *
	 * @access	public
	 * @param 	array 	holds the user data
	 * @param 	array    extra options
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function onLoginUser($user, $options)
	{
		// Initialize variables
		$success = true;

		// Here you would do whatever you need for a login routine with the credentials
		//
		// Remember, this is not the authentication routine as that is done separately.
		// The most common use of this routine would be logging the user into a third party
		// application.
		//
		// In this example the boolean variable $success would be set to true
		// if the login routine succeeds

		// ThirdPartyApp::loginUser($user['username'], $user['password']);

		return $success;
	}

	/**
	 * This method should handle any logout logic and report back to the subject
	 *
	 * @access public
	 * @param array holds the user data
	 * @return boolean True on success
	 * @since 1.5
	 */
	function onLogoutUser($user)
	{
		// Initialize variables
		$success = true;

		// Here you would do whatever you need for a logout routine with the credentials
		//
		// In this example the boolean variable $success would be set to true
		// if the logout routine succeeds

		// ThirdPartyApp::loginUser($user['username'], $user['password']);

		return $success;
	}
}
