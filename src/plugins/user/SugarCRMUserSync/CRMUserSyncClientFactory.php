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
 * SugarCRM User Sync Factory Abstract Class
 * 
 * @package com.wordpress.musarra.JoomlaPlugin
 * @author amusarra
 */

abstract class CRMUserSyncClientFactory {
	/**
	 * Prevent the constructor being called normally
	 */
	private function __construct() {
	}

	/**
	 * Prevent the object being called cloned
	 */
	private function __clone() {
	}

	/**
	 * Return an instance of this object
	 * 
	 * @abstract
	 * @param string $clientId The client type. Support only PHP_SOAP ClientId
	 * @param array $args The additional args
	 * @return Object of the Class that implements ICRMUserSyncClient
	 */
	abstract protected static function singleton($clientId, $args = null);
	
	/**
	 * Return an instance of the Class implements ICRMUserSyncClient
	 * 
	 * @param string $clientId The client type. Support only PHP_SOAP ClientId
	 * @param array $args The additional args
	 * @return ICRMUserSyncClient of the Class that implements ICRMUserSyncClient
	 */
	public static function factory($clientId, $args = null) {
		if ($clientId === 'PHP_SOAP') {
			return SOAP_SugarCRMUserSyncClient::singleton($clientId, $args);
		}
	}
}