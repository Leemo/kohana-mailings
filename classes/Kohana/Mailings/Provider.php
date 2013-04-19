<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Kohana_Mailings_Provider {

	/**
	 * Class constructor
	 *
	 * @param string $instance
	 */
	public function __construct($instance, array $config)
	{
		$this->_instance_name = $instance;
		$this->_default_list  = $config['default_list'];

		// Append instance parameter
		foreach ($config['parameters'] as $parameter => $value)
		{
			$this->{'_'.$parameter} = $value;
		}
	}

	/**
	 * Returns all available lists
	 *
	 * @return array
	 */
	public abstract function get_lists();

	/**
	 * Creates a default mailings list
	 *
	 * @param string $title
	 */
	public abstract function create_list($title);

	/**
	 * Returns a default list id.
	 * If it doesn't exist, creates it.
	 *
	 * @return integer
	 */
	public abstract function get_default_list_id();

	/**
	 * Creates a contact in a list
	 *
	 * @param integer $list_id
	 * @param string  $email
	 */
	public abstract function create_contact($list_id, $email);

	/**
	 * Returns array of all list contacts
	 *
	 * @param integet $list_id
	 */
	public abstract function get_list_contacts($list_id);

	/**
	 * Synchronize list contacts
	 *
	 * @param  integer $list_id
	 * @param  array   $add
	 * @param  array   $delete
	 * @return array
	 */
	public abstract function sync_list_contacts($list_id, array $add, array $delete);

	/**
	 * Starts a campaign
	 *
	 * @param integer $list_id
	 * @param string  $sender_name
	 * @param string  $subject
	 * @param string  $html
	 * @param string  $text
	 */
	public abstract function send($list_id, $sender_name, $sender_email, $subject, $html, $text = NULL);

	/**
	 * Check campaign status
	 *
	 * @param  integer $campaign_id
	 * @return array
	 */
	public abstract function get_mailing_status($campaign_id);

	/**
	 * Returns campaign info
	 *
	 * @param  integer $campaign_id
	 * @return array
	 */
	public abstract function get_mailing_info($campaign_id);

} // End Kohana_Mailings_Provider
