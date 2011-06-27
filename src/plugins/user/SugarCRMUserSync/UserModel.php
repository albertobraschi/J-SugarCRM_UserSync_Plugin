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
 * Define User Model for SugarCRM User Sync Plugin.
 * 
 * @package com.wordpress.musarra.JoomlaPlugin
 * @author amusarra
 */
class UserModel {
	/**
	 * SugarCRM User Object Id
	 * @var string UUID Identifier
	 */
	public $id;
	
	/**
	 * SugarCRM User Object Status
	 * @var string
	 */
	public $status;
	
	/**
	 * User Name of the User
	 * @var string
	 */
	public $userName;
	
	/**
	 * Password of the User
	 * @var string
	 */
	public $password;
	
	/**
	 * Email of the User
	 * @var string
	 */
	public $email;
	
	/**
	 * First name of the User
	 * @var string
	 */
	public $first_name;
	
	/**
	 * Last name of the User
	 * @var string
	 */
	public $last_name;
	
	/**
	 * Full Name of the User
	 * @var string
	 */
	public $full_name;
	
	/**
	 * Check if is Admin
	 * @var boolean
	 */
	public $idAdmin;

	/**
	 * Title of the User
	 * @var string
	 */
	public $title;

	/**
	 * Department of the User
	 * @var string
	 */
	public $department;
	
	/**
	 * Registration date
	 * @var string
	 */
	public $registerDate;
	
	/**
	 * User blocked (from Joomla)
	 * @var string
	 */
	public $block;
}