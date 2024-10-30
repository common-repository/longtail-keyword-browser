<?php

class Longtail_Keyword_Browser
{
	var $title			= 'Longtail Browser';

	var $name			= 'longtail-keyword-browser';

	var $base_url		= '';

	var $admin_url		= '';

	var $layout			= 'default';

	var $action			= 'index';

	var $form_errors	= array();


	function Longtail_Keyword_Browser()
	{
		global $wpdb;

		$this->table = array(
		'cache'	=> $wpdb->base_prefix.'ltb_cache'
		);

		define('LTB_APP_PATH',		dirname(__FILE__));

		add_action('admin_init',	array(&$this, 'initAdmin'), 1);
		add_action('admin_head',	array(&$this, 'adminHead'));
		add_action('admin_menu',	array(&$this, 'adminMenu'));
		add_action('shutdown',		array(&$this, 'adminShutdown'));

		add_action('activate_'.$this->name.'/'.$this->name.'.php', array(&$this, 'upgradePlugin'));
	}

	function initAdmin()
	{
		global $wpdb;

		$this->base_url = rtrim(get_option('siteurl'), '/').'/wp-content/plugins/'.$this->name;

		$this->admin_url = $this->base_url.'/'.$this->name.'.php?page='.$this->name;

		if (isset($_GET['action']))
		{
			$this->action = preg_replace('/[^0-9a-zA-Z\_\-]+/is', '', strtolower($_GET['action']));
		}

		if (!$this->action)
		{
			$this->action = 'index';
		}

		$action_path = LTB_APP_PATH.'/sites/admin/actions/default.php';

		if (is_file($action_path))
		{
			require $action_path;
		}

		$action_path = LTB_APP_PATH.'/sites/admin/actions/'.$this->action.'.php';

		if (is_file($action_path))
		{
			require $action_path;
		}
	}

	function adminHead()
	{
		echo '<link rel="stylesheet" href="'.$this->base_url.'/includes/style_admin.css" type="text/css" media="all" />';
		echo '<script type="text/javascript" src="'.$this->base_url.'/includes/js/jstree/jquery.jstree.js"></script>';
	}

	function adminMenu()
	{
		add_menu_page($this->title, $this->title, 'update_core', $this->name, array(&$this, 'router'));
	}

	function router()
	{
		global $wpdb;

		if (isset($_REQUEST['campaign_id']) && (int)$_REQUEST['campaign_id'])
		{
			$campaign = $wpdb->get_row('SELECT * FROM '.$this->table['campaign'].' WHERE campaign_id = '.(int)$_REQUEST['campaign_id'], ARRAY_A);
		}

		$layout_path	= LTB_APP_PATH.'/sites/admin/layouts/'.$this->layout.'.php';
		$view_path		= LTB_APP_PATH.'/sites/admin/views/'.$this->action.'.php';

		if (is_file($layout_path))
		{
			require($layout_path);
		}
		else
		{
			exit('Invalid Layout: '.$layout_path);
		}

		if (!is_file($view_path))
		{
			exit('Invalid View: '.$view_path);
		}
	}

	function getLongtailTree($keyword, $country = 'us')
	{
		$keyword = trim($keyword);

		$result_list = array();

		$matches = $this->getLongtailTreeQuery($keyword.' ', $country);

		if (is_array($matches))
		{
			foreach ($matches as $t_rank => $t_keyword)
			{
				if (!isset($result_list[$t_keyword]))
				{
					$children = (boolean)$this->getLongtailTreeQuery($t_keyword.' ', $country);

					$result_list[$t_keyword] = array(
						'rank'			=> $t_rank + 1,
						'keyword'		=> $t_keyword,
						'has_children'	=> $children
					);
				}
			}

			if (count($matches) >= 10)
			{
				for ($i = 97; $i <= 122; $i++)
				{
					$matches = $this->getLongtailTreeQuery($keyword.' '.chr($i), $country);

					if (is_array($matches))
					{
						foreach ($matches as $t_rank => $t_keyword)
						{
							if (!isset($result_list[$t_keyword]))
							{
								$children = (boolean)$this->getLongtailTreeQuery($t_keyword.' ', $country);

								$result_list[$t_keyword] = array(
									'rank'			=> $t_rank + 1,
									'keyword'		=> $t_keyword,
									'has_children'	=> $children
								);
							}
						}
					}
				}
			}
		}

		return $result_list;
	}

	function getLongtailTreeQuery($keyword, $country = 'us')
	{
		$handle = str_replace(' ', '_', $keyword);
		$handle = str_replace('__', '_', $handle);
		$handle = str_replace('__', '_', $handle);

		$handle .= '-'.$country;

		$c_result_list = $this->cache_get($handle);

		if ($c_result_list)
		{
			return unserialize($c_result_list);
		}
		else
		{
			if ($country == 'uk')
			{
				$url = 'http://www.google.co.uk/complete/search?hl=en&client=suggest&js=true&q='.rawurlencode($keyword);
			}
			else
			{
				$url = 'http://www.google.com/complete/search?hl=en&client=suggest&js=true&q='.rawurlencode($keyword);
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_REFERER, "http://".get_option('siteurl'));
			$response = curl_exec($ch);
			curl_close($ch);

			$response = str_replace('\x22', '"', $response);
			$response = str_replace('\u003Cb\u003E', '', $response);
			$response = str_replace('\u003C\/b\u003E', '', $response);

			preg_match_all('/\[\"([^\[]+)\"\,\"\"\,\"([0-9a-z]+)\"\]/is', $response, $matches);

			$this->cache_put($handle, $matches[1]);

			if (is_array($matches[1]) && count($matches[1]))
			{
				return $matches[1];
			}
		}

		return FALSE;
	}

	function doRequest($url, $post = NULL)
	{
		if (intval(extension_loaded('curl')))
		{
			return $this->useCurl($url, $post);
		}
		else
		{
			return $this->useSocket($url, $post);
		}
	}

	function useCurl($url, $post = NULL)
	{
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

		if (is_array($post))
		{
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		}

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);

		$result = curl_exec($curl);

		curl_close($curl);

		return $result;
	}

	function useSocket($url, $post = null)
	{
		$result = '';

		$url_info = parse_url($url);

		$fp = fsockopen($url_info['host'], 80, $errno, $errstr, 5);

		if ($fp)
		{
			if (is_array($post))
			{
				foreach ($post as $name => $value)
				{
					$coded_post[] = urlencode($name).'='.urlencode($value);
				}

				$senddata = implode('&', $coded_post);

				$out = 'POST '.(isset($url_info['path'])?$url_info['path']:'/').(isset($url_info['query'])?'?'.$url_info['query']:'').' HTTP/1.0'."\r\n";
				$out .= 'Host: '.$url_info['host']."\r\n";
				$out .= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
				$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$out .= 'Content-Length: '.strlen( $senddata )."\r\n";
				$out .= 'Connection: Close'."\r\n\r\n";
				$out .= $senddata;
			}
			else
			{
				$out = 'GETT '.(isset($url_info['path'])?$url_info['path']:'/').(isset($url_info['query'])?'?'.$url_info['query']:'').' HTTP/1.0'."\r\n";
				$out .= 'Host: '.$url_info['host']."\r\n";
				$out .= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
				$out .= "Connection: Close\r\n\r\n";
			}

			fwrite($fp, $out);

			while (!feof($fp))
			{
				$contents .= fgets($fp, 1024);
			}

			list($headers, $result) = explode("\r\n\r\n", $contents, 2);
		}

		return trim($result);
	}

	function cache_get($handle, $ttl = 2592000)
	{
		global $wpdb;

		$sql = '
		SELECT
			data
		FROM
			'.$this->table['cache'].'
		WHERE
			handle = %s
		AND
			last_update > %d';

		$min_last_update = time() - (int)$ttl;

		$data = $wpdb->get_var($wpdb->prepare($sql, $handle, $min_last_update));

		if ($data)
		{
			return $data;
		}

		return NULL;
	}

	function cache_put($handle, $data)
	{
		global $wpdb;

		$handle = trim($handle);

		if (strlen($handle) > 100)
		{
			throw Exception('Cache:put - handle larger than 100 characters');
		}

		if (is_array($data))
		{
			$data = serialize($data);
		}

		$sql = '
		REPLACE INTO '.$this->table['cache'].'
			(`handle`, `data`, `last_update`)
		VALUES
			( %s, %s, %d )';

		$wpdb->query($wpdb->prepare($sql, $handle, $data, time()));
	}

	function upgradePlugin()
	{
		global $wpdb;

		if (file_exists(ABSPATH.'/wp-admin/upgrade-functions.php'))
		{
			require_once(ABSPATH.'/wp-admin/upgrade-functions.php');
		}
		else
		{
			require_once(ABSPATH.'/wp-admin/includes/upgrade.php');
		}

		dbDelta('CREATE TABLE `'.$this->table['cache'].'` (
			`cache_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`handle` varchar(125),
			`data` text,
			`last_update` int(11) unsigned NOT NULL,
			PRIMARY KEY (`cache_id`),
			UNIQUE KEY `idx_hash` (`handle`)
		);');
	}

	function adminShutdown()
	{
		if (get_option('ltb_last_sponsor_download') != date('Y-m-d', time()))
		{
			$ad1 = $this->doRequest('http://www.summitmediaconcepts.com/longtail_browser_ads.php?ad=1');
			update_option('ltb_sponsor_1', $ad1);

			$ad2 = $this->doRequest('http://www.summitmediaconcepts.com/longtail_browser_ads.php?ad=2');
			update_option('ltb_sponsor_2', $ad2);

			update_option('ltb_last_sponsor_download', date('Y-m-d', time()));
		}
	}
}