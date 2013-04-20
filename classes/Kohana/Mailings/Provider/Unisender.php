<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Unisender mailings provider
 *
 * @package    Application
 * @author     Leemo studio
 * @copyright  (c) 2010-2013 Leemo studio
 * @link       http://leemo-studio.net
 */
class Kohana_Mailings_Provider_Unisender extends Mailings_Provider {

	/**
	 * Api key
	 *
	 * @var string
	 */
	protected $_api_key;

	/**
	 * API language
	 *
	 * @var string
	 */
	protected $_lang = 'en';

	/**
	 * Obtains a list of existing mailing lists
	 *
	 * @see http://www.unisender.com/en/help/api/getLists.html
	 * @return array
	 */
	public function get_lists()
	{
		return $this->_request('getLists');
	}

	/**
	 * Creates list with specified title
	 *
	 * @see http://www.unisender.com/en/help/api/createList.html
	 * @param  string $title
	 * @return mixed
	 */
	public function create_list($title)
	{
		return $this->_request('createList', array('title' => $title));
	}

	/**
	 * Returns a default list. If it doesn't exist, create it.
	 *
	 * @return array
	 */
	public function get_default_list_id()
	{
		$lists = $this->get_lists();

		foreach ($lists as $list)
		{
			if ($list['title'] == $this->_default_list)
			{
				return $list['id'];
			}
		}

		return Arr::get($this->create_list($this->_default_list), 'id');
	}

	/**
	 * Creates a contact in a list
	 *
	 * @param integer $list_id
	 * @param string  $email
	 */
	public function create_contact($list_id, $email)
	{

	}

	/**
	 * Returns array of all list contacts
	 *
	 * @param integet $list_id
	 */
	public function get_list_contacts($list_id)
	{
		return Arr::pluck(Arr::get($this->_export_contacts($list_id), 'data'), '0');
	}

	/**
	 * Synchronize list contacts
	 *
	 * @param  integer $list_id
	 * @param  array   $add
	 * @param  array   $delete
	 * @return array
	 */
	public function sync_list_contacts($list_id, array $add, array $delete)
	{
		$emails = array();

		$result = array
		(
			'total'      => 0,
			'inserted'   => 0,
			'updated'    => 0,
			'deleted'    => 0,
			'new_emails' => 0,
			'log'        => array()
		);

		foreach (array('add', 'delete') as $param)
		{
			$to_delete = (int) ($param == 'delete');

			foreach ($$param as $email)
			{
				$emails[] = array
				(
					$list_id,
					$to_delete,
					$email
				);
			}
		}

		$emails_per_iteration = 500;

		$all = sizeof($emails);

		$iterations = $all / $emails_per_iteration;

		for ($i = 0; $i < $iterations; $i++)
		{
			$from = $i * $emails_per_iteration;
			$to   = $from + $emails_per_iteration;

			if ($to > $all)
			{
				$to = $all;
			}

			$data = array
			(
				'field_names' => array
				(
					'email_list_ids',
					'delete',
					'email'
				),

				'data'         => array_slice($emails, $from, $to - $from),
				'double_optin' => 1,
			);

			$res = $this->_request('importContacts', $data);

			foreach (array('total', 'inserted', 'updated', 'deleted', 'new_emails') as $param)
			{
				$result[$param] += $res[$param];
			}

			$result['log'] = Arr::merge($result['log'], $res['log']);
		}

		// Get new email addresses
		$emails_to_activate = $this->_export_contacts($list_id, array('email'), 'new');

		// Activate list if needed
		if (sizeof($emails_to_activate['data']) > 0)
		{
			$unisender->activate_list($list_id);
		}

		return $result;
	}

	/**
	 * Starts a campaign
	 *
	 * @param integer $list_id
	 * @param string  $sender_name
	 * @param string  $subject
	 * @param string  $html
	 * @param string  $text
	 */
	public function send($list_id, $sender_name, $sender_email, $subject, $html, $text = NULL)
	{
		$message_id = $this->_create_email_message($list_id, $sender_name, $sender_email, $subject, $html, $text);

		return $this->_create_campaign($message_id);
	}

	/**
	 * Check campaign status
	 *
	 * @param  integer $campaign_id
	 * @return array
	 */
	public function get_mailing_status($campaign_id)
	{
		return $this->_request('getCampaignStatus', array(
			'campaign_id' => $campaign_id
			));
	}

	/**
	 * Returns campaign info
	 *
	 * @param  integer $campaign_id
	 * @return array
	 */
	public function get_mailing_info($campaign_id)
	{
		return $this->_request('getCampaignAggregateStats', array(
			'campaign_id' => $campaign_id
			));
	}

	/**
	 * Activates one or few lists by ids
	 *
	 * @see http://www.unisender.com/en/help/api/activateContacts.html
	 * @param  integer|array $list_ids
	 * @return array
	 */
	protected function _activate_list($list_ids)
	{
		if (is_array($list_ids))
		{
			$list_ids = implode(',', $list_ids);
		}

		return $this->_request('activateContacts', array(
			'list_ids'     => $list_ids,
			'contact_type' => 'email'
			));
	}

	protected function _export_contacts($list_id, array $field_names = array('email'), $email_status = NULL)
	{
		$allowed_statuses = array
		(
			'new',
			'invited',
			'active',
			'inactive',
			'unsubscribed',
			'blocked',
			'activation_requested'
		);

		if ($email_status !== NULL AND ! in_array($email_status, $allowed_statuses))
		{
			throw new Unisender_Exception('Undefined status: :status', array(
				':status' => $email_status
				));
		}

		$parameters = array
		(
			'list_id'     => $list_id,
			'field_names' => $field_names
		);

		if ($email_status !== NULL)
		{
			$parameters['email_status'] = $email_status;
		}

		return $this->_request('exportContacts', $parameters);
	}

	/**
	 * Creates an email message
	 *
	 * @see     http://www.unisender.com/ru/help/api/createEmailMessage.html
	 * @param   integer  $list_id
	 * @param   string   $sender_name
	 * @param   string   $sender_email
	 * @param   string   $subject
	 * @param   string   $html
	 * @param   string   $text
	 * @return  integer
	 */
	protected function _create_email_message($list_id, $sender_name, $sender_email, $subject, $html, $text = NULL)
	{
		$parameters = array
		(
			'list_id'      => (int) $list_id,
			'sender_name'  => $sender_name,
			'sender_email' => $sender_email,
			'subject'      => $subject,
			'body'         => $html
		);

		if ($text !== NULL)
		{
			$parameters = Arr::merge($parameters, array(
				'text_body'     => $text,
				'generate_text' => 1
			));
		}

		return (int) Arr::get($this->_request('createEmailMessage', $parameters), 'message_id');
	}

	/**
	 * Creates and starts campaign
	 *
	 * @see     http://www.unisender.com/ru/help/api/createCampaign.html
	 * @param   integer  $message_id
	 * @param   boolean  $track_read
	 * @param   boolean  $track_links
	 * @return  integer
	 */
	protected function _create_campaign($message_id)
	{
		$parameters = array
		(
			'message_id'  => $message_id,
			'track_read'  => ! empty($this->_track_read),
			'track_links' => ! empty($this->_track_links)
		);

		return (int) Arr::get($this->_request('createCampaign', $parameters), 'campaign_id');
	}

	/**
	 * API URL
	 *
	 * @var string
	 */
	protected $_api_url_pattern = 'https://api.unisender.com/:lang/api/:uri';

	/**
	 * Sends request to specified URL
	 *
	 * @param  string $method
	 * @param  string $uri
	 * @param  array  $data
	 * @return mixed
	 */
	protected function _request($uri, array $data = NULL)
	{
		// Compile URL from pattern
		$url = strtr($this->_api_url_pattern, array(
			':lang' => $this->_lang,
			':uri'  => $uri
			));

		// Apply API key to request data
		$data['api_key'] = $this->_api_key;

		// Define response format
		$data['format'] = 'json';

		// Send request
		$result = Request::factory($url)
			->client($this->_client())
			->method(Request::POST)
			->query($data)
			->execute()
			->body();

		$json = json_decode($result, TRUE);

		// It is ok?
		if ( ! empty($json['error']))
		{
			$error = iconv(mb_detect_encoding($json['error']), 'UTF-8//TRANSLIT', $json['error']);

			throw new Mailings_Exception($error);
		}

		// Return result
		return $json['result'];
	}

	/**
	 * Initialize a CURL request client with disabled SSL verifycation
	 *
	 * @return Request_Client_Curl
	 */
	protected function _client()
	{
		return Request_Client_External::factory(array(), 'Request_Client_Curl')
			->options(CURLOPT_SSL_VERIFYPEER, FALSE)
			->options(CURLOPT_RETURNTRANSFER, TRUE)
			->options(CURLOPT_TIMEOUT, 30);
	}

} // End Unisender
