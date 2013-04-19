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

		$curr_dir = pathinfo($css_file, PATHINFO_DIRNAME).DIRECTORY_SEPARATOR;

		$html = iconv(mb_detect_encoding($html), 'UTF-8//TRANSLIT', $html);

		$htmldoc = new InlineStyle($html);
		$htmldoc->applyStylesheet(file_get_contents($css_file));

		$html = self::_process_inline_images($htmldoc->getHTML(), $curr_dir);

		// Then remove unused cass parameters
		$html = preg_replace('/ class=".*?"/', '', $html);
		$html = preg_replace('/class=".*?"/', '', $html);

		return $html;
	}

	protected static function _process_inline_images($contents, $curr_dir)
	{
		preg_match_all('/url\(\s*[\'"]?(\S*\.(?:jpe?g|gif|png))[\'"]?\s*\)/i', $contents, $matches);

		foreach (array_keys($matches[0]) as $i)
		{
			$media_file = $curr_dir.DIRECTORY_SEPARATOR.$matches[1][$i];

			$compressed = self::_file_to_data_uri($media_file);

			if ($compressed)
			{
				$contents = str_replace($matches[1][$i], $compressed, $contents);
			}
		}

		return $contents;
	}

	/**
	 * Convert media file to base64 hash
	 *
	 * @param   type   $filename
	 * @return  string
	 */
	protected static function _file_to_data_uri($filename)
	{
		$extension = pathinfo($filename, PATHINFO_EXTENSION);

		$mime     = File::mime_by_ext($extension);
		$contents = file_get_contents($filename);

		return 'data:'.$mime.';base64,'.base64_encode($contents);
	}

} // End Kohana_Mailings