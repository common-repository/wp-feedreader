<?
	# if you'd like to run this script with a cron job, uncomment the following line and specify the path to the file
	#require_once('/path/to/wp-blog-header.php');

	# Then, create a cron job to run this file once an hour with "debug" as a parameter.
	# ex. "/usr/bin/php /path/to/updatefeeds.php debug"

	require_once(ABSPATH . '/wp-content/plugins/feedreader/index.php');

	FR_check_for_tables(); # check to see that feedreader table is installed

	if ($_SERVER['argc'] == 2) { if ($_SERVER['argv'][1] == "debug") $debug = 1;  }

	if ($debug)
	{ 
		$links = $wpdb->get_results("SELECT link_id, link_name, link_rss FROM $wpdb->links WHERE link_rss <> '' "); 
		if ($links) { FR_check_feed($links, 1); }
	}
	else 
	{
		$links = $wpdb->get_results("SELECT link_id, link_name, link_rss FROM $wpdb->links WHERE link_rss <> '' and rss_checked < DATE_SUB(NOW(), INTERVAL 1 HOUR) limit 1");

		# run the process to update the next feed AFTER all content has been sent to the browser
		if ($links) { register_shutdown_function('FR_check_feed', $links); }
	}

function FR_check_feed($links, $debug = 0)
{
	global $wpdb;
	$updatefrequency = get_option("FR_updatefrequency"); if (!$updatefrequency) { $updatefrequency = 60; }

	require_once(ABSPATH.WPINC.'/rss-functions.php');
	# the following line fixes an omission in the WP MagpieRSS file
	if (!function_exists('error')) { function error($null) { } }

	# If you want to use your own MagpieRSS distributions, uncomment these lines and comment out the ones above
	#require_once('/path/to/rss_fetch.inc');
	#require_once('/path/to/rss_utils.inc');
	#require_once('/path/to/rss_cache.inc');


	# set some constants for the RSS parsing libraries
	define('MAGPIE_CACHE_AGE', 60*$updatefrequency); 
	define('MAGPIE_DEBUG', 0);
	define('MAGPIE_USER_AGENT', 'WordPress FeedReader');
	# If you use your own MagpieRSS files, uncomment the next two lines
	#define('MAGPIE_CACHE_ON', 1);
	#define('MAGPIE_CACHE_DIR', ABSPATH . 'wp-content/cache/feeds/');	// where the cache files are stored

	$two_weeks_ago = time()-(60*60*24*14);
	$yesterday = time()-(60*60*24*1);

	if ($debug) { print "<pre>\n"; }
	foreach ($links as $link)
	{
		$link_id = $link->link_id;
		$name = $link->link_name;
		$rss = $link->link_rss;
		if ($debug) { print "Reading $rss\n"; }
		if (!$debug) { $wpdb->query("update $wpdb->links set rss_checked = '".date("Y-m-d H:i:s")."' where link_id = $link_id "); }

		$cache_status = 0; $testcache = new RSSCache( MAGPIE_CACHE_DIR, MAGPIE_CACHE_AGE ); 
		if (!$cache->ERROR) { $cache_status = $testcache->check_cache($rss); }
		if ($cache_status == "HIT") {  }
		else
		{	$feed = fetch_rss($rss);
			if ($feed)
			{
				if ( !$feed->is_rss() && !$feed->is_atom() ) { continue; } 
	
				$first = 1;
				foreach ($feed->items as $item ) 
				{
					$post['link_id'] = $link_id;
					$post['title'] = addslashes($item[title]);
					$post['link'] = $item['link'];
					$post['guid'] = $item['guid'];
					if (!$post['link']) { $post['link'] = $item['guid']; }
					if (!$post['guid']) { $post['guid'] = $item['id']; }
					if (!$post['guid']) { $post['guid'] = $item['link']; }

					$timestamp = 0;
					if ($feed->is_rss()) 
					{
						$post['content'] = addslashes($item['description']); 
				                if ( isset($item['dc']['date']) ) 
						{
							$epoch = parse_w3cdtf($item['dc']['date']);
							if ($epoch and $epoch > 0) { $timestamp = $epoch; }
						}
				                elseif ( isset($item['pubDate']) ) 
						{
							$epoch = strtotime($item['pubDate']);
							if ($epoch > 0) { $timestamp = $epoch; }
						}
				                elseif ( isset($item['pubdate']) ) 
						{
							$epoch = strtotime($item['pubdate']);
							if ($epoch > 0) { $timestamp = $epoch; }
						}
					}
					elseif ($feed->is_atom()) 
					{ 
						$post['content'] = addslashes($item['atom_content']); 
				                if ( isset($item['issued']) ) 
						{
							$epoch = parse_w3cdtf($item['issued']);
						}
				                elseif ( isset($item['modified']) ) 
						{
							$epoch = strtotime($item['modified']);
						}
				                elseif ( isset($item['created']) ) 
						{
							$epoch = strtotime($item['created']);
						}
						if ($epoch and $epoch > 0) { $timestamp = $epoch; }
					}

					# if no timestamp, try to guess date from permalink structure
					if (!$timestamp) 
					{
						preg_match("/\/(\d\d\d\d\/\d\d\/\d\d)\//", $item['link'], $matches);
						if ($matches[0]) { $epoch = strtotime($matches[0]); }
						if ($epoch > 0) { $timestamp = $epoch; }
					}

					if (!$timestamp) { $timestamp = time(); }

					$post['subject'] = addslashes($item[dc]['subject']);
					if (!$post['subject']) { $post['subject'] = addslashes($item['category']); }
					$post['creator'] = addslashes($item[dc]['creator']);
					if (!$post['creator']) { $post['creator'] = addslashes($item['author_name']); }
					if (!$post['creator']) { $post['creator'] = addslashes($item['author']); }	

					$post['date'] = date("Y-m-d H:i:s", $timestamp);

					if ($timestamp > $two_weeks_ago)
					{ 
						$result = FR_add_post($post);
						if ($result)
						{
							$count++;
							if ($debug) { print "+ ".$post['link']."\n"; }
							if ($first && $timestamp) 
							{ 
								FR_update_link_freshness($link_id, $timestamp); 
								$first = 0; 
							}
						}
						else { continue; }
					}
					else { continue; }
				}
			}
		}
	}

	if ($debug) { print ($count+0)." total entries added to FeedReader\n"; }
	$wpdb->query("delete from $wpdb->linkfeeds where item_date < '".date("Y-m-d", $two_weeks_ago )."' ");
	if ($debug) { print "</pre>\n"; }
}

function FR_add_post($post) 
{
	global $wpdb;
	$postquery = sprintf(
		"INSERT IGNORE INTO $wpdb->linkfeeds (link_id, item_title, item_link, item_content, item_date, item_creator, item_subject, item_guid)
		VALUES (\"%s\", \"%s\", \"%s\", \"%s\", \"%s\", \"%s\", \"%s\", \"%s\")",
		$post['link_id'], $post['title'], $post['link'], $post['content'], $post['date'], $post['creator'], $post['subject'], $post['guid']);
	$result = $wpdb->query($postquery);
	return ($result);
}

function FR_update_link_freshness($link_id, $timestamp) 
{
	global $wpdb;
	$result = $wpdb->query("update $wpdb->links set link_updated=now() 
				WHERE link_id='$link_id' ");
	return $result;
}

?>