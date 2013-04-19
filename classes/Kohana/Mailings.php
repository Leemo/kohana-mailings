<?php defined('SYSPATH') OR die('No direct access allowed.');

abstract class Kohana_Mailings {

	/**
	 * Default list name
	 *
	 * @var string
	 */
	protected $_default_list;

	/**
	 * Current instance name
	 *
	 * @var string
	 */
	protected $_instance_name;

	/**
	 * Creates a Mailings class instance
	 *
	 * @param   string   $instance
	 * @return  Mailings
	 */
	public static function instance($instance = 'default')
	{
		$config = Kohana::$config
			->load('mailings.'.$instance);

		// Set class name
		$provider = 'Mailings_Provider_'.$config['provider'];

		return new $provider($instance, $config);
	}

	/**
	 * Inserts inline styles in the html
	 *
	 * @param  string $html
	 * @param  string $css
	 * @return string
	 */
	public static function inline_style($html, $css_file)
	{
		/**
		 * This is idiocy, I know, but so far I have no time to write autoload-function...
		 *
		 * P.S.
		 * I am not from India
		 */
		$libraries = array
		(
			'CSSQuery' => 'CSSQuery/CSSQuery',
			'InlineStyle' => 'InlineStyle/InlineStyle'
 		);

		foreach ($libraries as $class => $path)
		{
			if ( ! class_exists($class, FALSE))
			{
				require Kohana::find_file('vendor', $path);
			}
		}

		$html = iconv(mb_detect_encoding($html), 'utf-8', $html);

		$htmldoc = new InlineStyle($html);
		$htmldoc->applyStylesheet(file_get_contents($css_file));

		$html = $htmldoc->getHTML();

		// Then remove unused cass parameters
		$html = preg_replace('/ class=".*?"/', '', $html);
		$html = preg_replace('/class=".*?"/', '', $html);

		return html_entity_decode($html, ENT_COMPAT, 'UTF-8');
	}

} // End Kohana_Mailings