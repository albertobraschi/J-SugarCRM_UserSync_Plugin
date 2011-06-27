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
 * Define Interface for SugarCRM User Sync Plugin.
 * 
 * @package com.wordpress.musarra.JoomlaPlugin
 * @author amusarra
 */

interface ICRMUserSyncClient {
	
	/**
	 * Change User Password
	 * 
	 * @param UserModel $user User Object 
	 * @return boolean Return a true if OK otherwise false
	 */
	public function changeUserPassword(UserModel $user);
	
	/**
	 * Create a User
	 * 
	 * @param UserModel $user User Object 
	 * @return boolean Return a true if OK otherwise false
	 */
	public function createUser(UserModel $user);
	
	/**
	 * Update a User
	 * 
	 * @param UserModel $user User Object 
	 * @return boolean Return a true if OK otherwise false
	 */
	public function updateUser(UserModel $user);
	
	/**
	 * Delete User
	 * 
	 * @param UserModel $user User Object 
	 * @return boolean Return a true if OK otherwise false
	 */
	public function deleteUser(UserModel $user);
}