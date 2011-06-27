<?php
/**
 * SugarCRM User Sync Plugin.
 *
 * @category JoomlaUserPlugin
 * @package com.wordpress.musarra.JoomlaPlugin
 * @subpackage com.wordpress.musarra.JoomlaPlugin.CRMUserSyncClient
 * @author  Antonio Musarra <antonio.musarra@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @version 1.0
 *
 * @copyright  Antonio Musarra (http://musarra.wordpress.com)
 *
 */

/**
 * Class that implements ICRMUserSyncClient Interface for SugarCRM User
 *
 * @package com.wordpress.musarra.JoomlaPlugin
 * @author amusarra
 * 
 * @see ICRMUserSyncClient
 * @see CRMUserSyncClientFactory
 * 
 * @link JParameter
 * @link JLog
 */
class SOAP_SugarCRMUserSyncClient extends CRMUserSyncClientFactory implements ICRMUserSyncClient {
	/**
	 * JParameter Object
	 * @var JParameter
	 */
	private $pluginParams;
	
	/**
	 * SOAP Client Object
	 * @var SoapClient
	 */
	protected $soapClient;
	
	/**
	 * The SugarCRM SessionID
	 * @var string
	 */
	protected $sessionId;
	
	/**
	 * Enable or Disable Debug mode
	 * @var boolean
	 */
	protected $debug = false;
	
	/**
	 * Prevent the constructor being called normally
	 * 
	 * @param string $clientId The client type. Support only PHP_SOAP ClientId
	 * @param array $args The additional args
	 */
	final public function __construct($clientId, $args = null) {
		$pluginObject = $args['Options']['PluginParams'];
		if ($pluginObject instanceof JParameter) {
			$this->setPluginParams($args['Options']['PluginParams']);
			$this->debug = (boolean) $this->pluginParams->get('DebugEnabled');
		}
	}

	/**
	 * Prevent the object being called cloned
	 */
	final private function __clone() {
	}

	/**
	 * Setting a Plugin Params
	 * 
	 * @param JParameter $pluginParams
	 * @return void
	 */
	public function setPluginParams(JParameter $pluginParams) {
		$this->pluginParams = $pluginParams;
	}
	
	/**
	 * Return a JLog instance
	 * 
	 * @throws Exception
	 * @return JLog
	 */
	private function getJLogger() {
		if ($this->pluginParams instanceof JParameter) {
			$logFile = $this->pluginParams->get('LogFile');
			$logFile = (empty($logFile) || is_null($logFile)) ? 'user_crm_sync.log' : $logFile;
			$jlog = &JLog::getInstance($logFile);
			
			return $jlog;
		} else {
			throw new Exception('The Plugin Params is null or not JParameter Object', -101);
		}
	}
	
	/**
	 * End User Session base on SessionId
	 * 
	 *  @throws Exception
	 *  @return boolean
	 */
	private function endSessionLogin() {
		try {
			if (empty($this->sessionId) || is_null($this->sessionId)) {
				return true;
			} else {
				$this->soapClient->logout($this->sessionId);
				$this->getJLogger()->addEntry(array('comment' => "Logged out SugarCRM with SessionId: {$this->sessionId}", 'status' => '0'));
				return true;
			}
			return false;
		} catch (Exception $e) {
			throw $e;
		}
	}
	
	/**
	 * Get SOAP Client Connection to SugarCRM SOAP API.
	 * 
	 * @throws Exception
	 * @return SoapClient The SOAP Client SugarCRM API otherwise false
	 */
	private function getSoapClientConnection() {
		if ($this->soapClient instanceof SoapClient) {
			return $this->soapClient;
		}
		
		if ($this->pluginParams instanceof JParameter) {
			if (!extension_loaded('soap')) {
				throw new Exception('PHP SOAP Extension not loaded', -100);
			}
			
			$sugarcrmPortalUserAPI = $this->pluginParams->get('PortalUserAPI');
			$sugarcrmPortalUserAPIPassword = $this->pluginParams->get('PortalUserAPIPassword');
			$soapEndPoint = $this->pluginParams->get('SoapEndPoint');
			
			if (empty($sugarcrmPortalUserAPI) || empty($sugarcrmPortalUserAPIPassword)) {
				throw new Exception('The Portal User API or Portal User Password is empty. Verify the Plugin configuration.', -102);
			}
			
			if ($this->pluginParams->get('SugarCRMEd') == '0' || $this->pluginParams->get('SugarCRMEd') == '1' || $this->pluginParams->get('SugarCRMEd') == '2') {
				$sugarcrmPortalUserAPIPassword = md5($sugarcrmPortalUserAPIPassword);
			}
			
			if (empty($soapEndPoint) || is_null($soapEndPoint)) {
				throw new Exception('The Web Service End Point (WSDL) is empty. Verify the Plugin configuration.', -103);
			}
			
			// Set WSDL Cache
			ini_set("soap.wsdl_cache_enabled", $this->pluginParams->get('WSDLCache'));

			try {
				// Setup SOAP Client and Call Login SOAP Operation
				$this->soapClient = new SoapClient($this->pluginParams->get('SoapEndPoint'), array('trace' => 1, 'exceptions' => true));
				
				// Set SOAP Login Operation Params
				$auth_array = array('user_name' => $sugarcrmPortalUserAPI, 'password' => $sugarcrmPortalUserAPIPassword, 'version' => '2');
				
				// Call SOAP Login Operation
				$auth_result = $this->soapClient->login($auth_array, $this->pluginParams->get('ApplicationName'));
	
				// Save SugarCRM Users Session ID
				$this->sessionId = $auth_result->id;
				$userId = $auth_result->name_value_list[0]->value;
				
				if ($userId != 1) {
					throw new Exception('The Portal User API must be admin. Verify the Plugin configuration.', -104);
				}
				
				$this->getJLogger()->addEntry(array('comment' => "Logged In SugarCRM as {$sugarcrmPortalUserAPI} with SessionId: {$this->sessionId} UserID: {$userId}", 'status' => '0'));
				
				return $this->soapClient;
				
			} catch (Exception $e) {
				if ($this->debug) {
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP XML Request 		=> "  . $this->soapClient->__getLastRequest(), 'status' =>  $e->getCode()));
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP XML Respose 		=> "  . $this->soapClient->__getLastResponse(), 'status' =>  $e->getCode()));
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP Response Header  => "  . $this->soapClient->__getLastResponseHeaders(), 'status' =>  $e->getCode()));
				}
				throw $e;
			}			
		} else {
			throw new Exception('The Plugin Params is null or not JParameter Object', -101);
		}
	}

	/**
	 * Find User on SugarCRM
	 * 
	 * @throws Exception
	 * @param UserModel $user
	 * @return UserModel The UserModel of the User otherwise return false
	 */
	private function findUser(UserModel $user) {
		if ($this->getSoapClientConnection()) {
			if (empty($user->userName)) {
				throw new Exception('The User Name attribute on UserModel object is null or empty.', -105);
			}
			
			try {
				$result = $this->soapClient->get_entry_list($this->sessionId, "Users", "user_name='{$user->userName}'", "id", 0, array(), array(), 0, 0);
								
				if ($result->result_count == 0) {
					$this->getJLogger()->addEntry(array('comment' => "No found User on SugarCRM with User Name: {$user->userName}", "status" => -1));
					return false;
				} elseif ($result->result_count > 1) {
					throw new Exception("Check if {$user->userName} is duplicate on SugarCRM.", -106);
				} else {
					$this->getJLogger()->addEntry(array('comment' => "Found User on SugarCRM with UserID: {$result->entry_list[0]->id}", "status" => 0));
					$user->id = $result->entry_list[0]->id;
					foreach ($result->entry_list[0]->name_value_list as $key => $value) {
						if ($value->name == 'first_name') {
							$user->first_name = $value->value;
						}
						if ($value->name == 'last_name') {
							$user->last_name = $value->value;
						}
						if ($value->name == 'full_name') {
							$user->full_name = $value->value;
						}
						if ($value->name == 'email1') {
							$user->email = $value->value;
						}
						if ($value->name == 'is_admin') {
							$user->idAdmin = (bool)$value->value;
						}
						if ($value->name == 'date_entered') {
							$user->registerDate = $value->value;
						}
						if ($value->name == 'title') {
							$user->title = $value->value;
						}
						if ($value->name == 'department') {
							$user->department = $value->value;
						}				
						if ($value->name == 'status') {
							$user->status = $value->value;
						}				
					}
					// Return SugarCRM User Object
					return $user;
				}
			} catch (Exception $e) {
				throw $e;
			}
		}
	}
	
	/**
	 * Return an instance of this object
	 *
	 * @param string $clientId The client type. Support only SOAP_PHP ClientId
	 * @return SOAP_SugarCRMUserSyncClient
	 */
	public static function singleton($clientId, $args = null) {
		static $instances = array();
		if (!isset($instances[$clientId])) {
			$instances[$clientId] = new  self($clientId, $args);
		}
		return $instances[$clientId];
	}

	/**
	 * Change User Password
	 * 
	 * @param UserModel $user User Object 
	 * @return boolean Return a true if OK otherwise false
	 */
	public function changeUserPassword(UserModel $user) {
		throw new Exception("This method not implements.", -106);;
	}
	
	/**
	 * Create a User
	 * 
	 * @param UserModel $user User Object 
	 * @return boolean Return a true if OK otherwise false
	 */
	public function createUser(UserModel $user) {
		if ($this->getSoapClientConnection()) {
			try {
				$userClone = clone $user;
				
				if (!($sugarUserObject = $this->findUser($user))) {
					// Data for Insert is: email, password, full_name, status, department, user_name
					$insertData = array();
					
					if ((!empty($userClone->userName) || !is_null($userClone->userName))) {
						array_push($insertData, array('name' => 'user_name', 'value' => $userClone->userName));
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => Request add UserName to {$userClone->userName}", 'status' => 0));
					}
					if ((!empty($userClone->department) || !is_null($userClone->department))) {
						array_push($insertData, array('name' => 'department', 'value' => $userClone->department));
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => Request add Department to {$userClone->department}", 'status' => 0));
					}
					if ((!empty($userClone->full_name) || !is_null($userClone->full_name))) {
						array_push($insertData, array('name' => 'last_name', 'value' => $userClone->full_name));
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => Request add Full Name to {$userClone->full_name}", 'status' => 0));
					}
					if ((!empty($userClone->email) || !is_null($userClone->email)) && ($userClone->email != $sugarUserObject->email)) {
						array_push($insertData, array('name' => 'email1', 'value' => $userClone->email));
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => Request add email to {$userClone->email}", 'status' => 0));
					}
					if ((!empty($userClone->password) || !is_null($userClone->password)) && (strlen($userClone->password) > 0) && $this->pluginParams->get('SyncUserPassword') == '1') {
						array_push($insertData, array('name' => 'user_hash', 'value' => md5($userClone->password)));
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => Request add new password", 'status' => 0));
					}
					if (!empty($userClone->block) || !is_null($userClone->block)) {
						array_push($insertData, array('name' => 'status', 'value' => ($userClone->block) ? 'Inactive' : 'Active'));
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => Request add status to {$userClone->block}", 'status' => 0));
					}
					
					array_push($insertData, array('name' => 'description', 'value' => "The User {$userClone->full_name} created from Joomla CMS Portal"));
					$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => Request add description to {$userClone->full_name}", 'status' => 0));
					
					$result = $this->soapClient->set_entry($this->sessionId, 'Users', $insertData);
					$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => The SugarCRM User created (UserID: {$result->id})", 'status' => 0));
					
					if ($this->debug) {
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => " . serialize($sugarUserObject), 'status' => 0));
					}
				} else {
					//If user does not exist then nothing
				}
								
				$this->endSessionLogin($this->sessionId);
				return true;
								
			} catch (Exception $e) {
				if ($this->debug) {
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP XML Request 		=> "  . $this->soapClient->__getLastRequest(), 'status' =>  $e->getCode()));
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP XML Respose 		=> "  . $this->soapClient->__getLastResponse(), 'status' =>  $e->getCode()));
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP Response Header  => "  . $this->soapClient->__getLastResponseHeaders(), 'status' =>  $e->getCode()));
				}
				throw $e;
			}
		}		
	}
	
	/**
	 * Update a User
	 * 
	 * @param UserModel $user User Object 
	 * @return boolean Return a true if OK otherwise false
	 */
	public function updateUser(UserModel $user) {
		if ($this->getSoapClientConnection()) {
			try {
				$userClone = clone $user;
				
				if (!($sugarUserObject = $this->findUser($user))) {
					// If user does not exist then nothing. 
				} else {
					// Data for Update is: email, password, status, department
					$updateData = array();
					array_push($updateData, array('name' => 'id', 'value' => $sugarUserObject->id));
					
					if ((!empty($userClone->email) || !is_null($userClone->email)) && ($userClone->email != $sugarUserObject->email)) {
						array_push($updateData, array('name' => 'email1', 'value' => $userClone->email));
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => Request change email from {$sugarUserObject->email} to {$userClone->email}", 'status' => 0));
					}
					if ((!empty($userClone->password) || !is_null($userClone->password)) && (strlen($userClone->password) > 0) && $this->pluginParams->get('SyncUserPassword') == '1') {
						array_push($updateData, array('name' => 'user_hash', 'value' => md5($userClone->password)));
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => Request change password", 'status' => 0));
					}
					if (!empty($userClone->block) || !is_null($userClone->block)) {
						array_push($updateData, array('name' => 'status', 'value' => ($userClone->block) ? 'Inactive' : 'Active'));
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => Request change status from {$sugarUserObject->status} to {$userClone->block}", 'status' => 0));
					}
					if ((!empty($userClone->department) || !is_null($userClone->department))) {
						array_push($updateData, array('name' => 'department', 'value' => $userClone->department));
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => Request add Department to {$userClone->department}", 'status' => 0));
					}
					
					$result = $this->soapClient->set_entry($this->sessionId, 'Users', $updateData);
					$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => The SugarCRM User updated (UserID: {$result->id})", 'status' => 0));
					
					if ($this->debug) {
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => " . serialize($sugarUserObject), 'status' => 0));
					}
				}
								
				$this->endSessionLogin($this->sessionId);
				return true;
				
			} catch (Exception $e) {
				if ($this->debug) {
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP XML Request 		=> "  . $this->soapClient->__getLastRequest(), 'status' =>  $e->getCode()));
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP XML Respose 		=> "  . $this->soapClient->__getLastResponse(), 'status' =>  $e->getCode()));
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP Response Header  => "  . $this->soapClient->__getLastResponseHeaders(), 'status' =>  $e->getCode()));
				}
				throw $e;
			}
		}
	}
	
	/**
	 * Delete User
	 * 
	 * @param UserModel $user User Object 
	 * @return boolean Return a true if OK otherwise false
	 */
	public function deleteUser(UserModel $user) {
		if ($this->getSoapClientConnection()) {
			try {
				$userClone = clone $user;
				
				if (!($sugarUserObject = $this->findUser($user))) {
					//@TODO Define a action.
					return false;
				} else {
					// Data for Update is: email, password, status
					$updateData = array();
					array_push($updateData, array('name' => 'id', 'value' => $sugarUserObject->id));
					array_push($updateData, array('name' => 'deleted', 'value' => 1));
					
					$result = $this->soapClient->set_entry($this->sessionId, 'Users', $updateData);
					$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => The SugarCRM User deleted (UserID: {$result->id})", 'status' => 0));
					
					if ($this->debug) {
						$this->getJLogger()->addEntry(array('comment' => __METHOD__ . " => " . serialize($sugarUserObject), 'status' => 0));
					}
				}
								
				$this->endSessionLogin($this->sessionId);
				return true;
				
			} catch (Exception $e) {
				if ($this->debug) {
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP XML Request 		=> "  . $this->soapClient->__getLastRequest(), 'status' =>  $e->getCode()));
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP XML Respose 		=> "  . $this->soapClient->__getLastResponse(), 'status' =>  $e->getCode()));
					$this->getJLogger()->addEntry(array('comment' => "Exception on " . __METHOD__ . " SOAP Response Header  => "  . $this->soapClient->__getLastResponseHeaders(), 'status' =>  $e->getCode()));
				}
				throw $e;
			}
		}
	}		
}