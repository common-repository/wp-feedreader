<?php
/*
Plugin Name: WP FeedReader
Plugin URI: http://hitormiss.org/projects/wp_feedreader
Description: A RSS Feed Reader that uses WordPress's Link Manager for source control.
Version: 1.0
Author: Matt Kingston
Author URI: http://hitormiss.org
*/
	$wpdb->linkfeeds = "{$table_prefix}linkfeeds";
	$updatefrequency = get_option("FR_updatefrequency"); if (!$updatefrequency) { $updatefrequency = 60; }
	$postdisplay = get_option("FR_postdisplay"); 

	if (!preg_match("/wp-admin/", $_SERVER["REQUEST_URI"])) { require_once (ABSPATH . '/wp-content/plugins/feedreader/updatefeeds.php'); }

	add_action('admin_menu', 'add_feedreader_page');
	add_action('admin_menu', 'add_feedreader_optionspage');
	function add_feedreader_page() { add_submenu_page('index.php', 'FeedReader', 'FeedReader', 5, 'feedreader/', 'feedreader_displaypage'); }
	function add_feedreader_optionspage() { add_options_page('FeedReader Options', 'FeedReader', 1, 'feedreader/index.php', 'feedreader_optionspage'); }

function feedreader_displaypage()
{
	FR_check_for_tables(); # check to see that feedreader table is installed

	global $wpdb;

	$category_id = $_GET[category_id];
	$feed_id = $_GET[feed_id];
	$hours = $_GET[hours];
	$days = $_GET[days];
	$search = $_GET[search];
	$page = get_option('siteurl')."/wp-admin/?page=feedreader/";

	$expandposts = get_option("FR_expandposts"); 

	# Show all feeds, just one category, or one feed?
	if ($category_id == "all") { $selection = ""; }
	elseif ($feed_id) { $selection = "AND $wpdb->links.link_id = '$feed_id'"; }
	elseif ($category_id) { $selection = "AND $wpdb->links.link_category = $category_id"; }
	else { $selection = "AND $wpdb->links.link_category = 0"; }

	# Show only new feeds or feed from X hours or days?
	if ($hours) { $time = "item_date > DATE_SUB(NOW(), INTERVAL $hours HOUR) "; }
	elseif ($days) { $time = "item_date > DATE_SUB(NOW(), INTERVAL $days DAY) "; }
	else { $time = "item_read = 0"; }

?>
<style><!--

#feedreader 	{ font-size: 100%; }
#maincolumn	{ margin-left: 200px;}

.PostButtons	{ padding: 2px; background: #fcc; border: 1px solid red; font-size: 75%; margin: 0px;}
.TimeButtons	{ padding: 2px; background: #ccf; border: 1px solid blue; font-size: 75%; margin: 0px;}
.OptionButtons	{ padding: 2px; background: #cfc; border: 1px solid green; font-size: 75%; margin: 0px;}
.SearchButtons	{ padding: 2px; background: #eee; border: 1px solid #999; font-size: 75%; margin: 0px;}

.Hidden	{ display: none; }
.Show	{ display: block; }

.itemOdd	{ margin: 2px 0px; padding: 10px; font-size: small; background: #eee;}
.itemEven	{ margin: 2px 0px; padding: 10px; font-size: small; background: #efe;}
.itemSectionHeader { font-size: large; font-weight: bold; margin: 10px 0px; }
.itemIcon	{ padding-right: 4px; width: 25px; }
.itemTime	{ padding-right: 5px; color: #999; width: 35px; font-size: 80%;}
.itemSlug	{ margin: 0px 0px 0px 0px; width: 100%; }
.itemTitle	{ font-weight: bold; }
.itemSource	{ }
.itemOdd div, .itemEven div	{ font-size: small; margin: 10px 0px 0px 60px; padding: 5px;}
.itemOdd a, .itemEven a		{ border-width: 0px; text-decoration: underline; }

#feedslist	{ float: left; margin: 0px 10px 10px 0px; padding: 0px 2px 0px 0px; border-right: 1px solid #ccc; width: 180px;  }
#feedslist a	{ border-width: 0px; }
#feedslist a:hover { text-decoration: underline; }
#feedslist ul	{ list-style-type: none; margin: 1px 0px 0px 0px; padding: 0px; font-size: 85%;}
#feedslist li	{ margin: 1px 0px 0px 0px; }
#feedslist img	{ padding-right: 4px; }
#feedslist ul ul { list-style-type: none; margin: 0px 0px 0px 20px; font-size: 85%; }
#feedslist ul ul ul { list-style-type: none; margin: 0px 0px 0px 0px; font-size: xx-small; }
-->
</style>

<script type="text/Javascript"><!-- 
function menuPlusMinus(menuID) 
{ 	if(document.images) 
	{
		if (document.getElementById(menuID).src=="<? echo get_option('siteurl'); ?>/wp-content/plugins/feedreader/folderopen.gif") { document.getElementById(menuID).src="<? echo get_option('siteurl'); ?>/wp-content/plugins/feedreader/folderclosed.gif"; } 
		else { document.getElementById(menuID).src="<? echo get_option('siteurl'); ?>/wp-content/plugins/feedreader/folderopen.gif"; } 
	}
} 
function expandcollapse(itemID) 
{ 	whichitem = document.getElementById(itemID); 
	if (whichitem.className=="Hidden") { whichitem.className="Show"; } else { whichitem.className="Hidden"; } 
} 
function show_all() 
{	tmp = document.getElementsByTagName('div');
	for (i=0;i<tmp.length;i++) { if (tmp[i].className == "Hidden") { tmp[i].className = "Show"; } }
}
function hide_all() 
{	tmp = document.getElementsByTagName('div');
	for (i=0;i<tmp.length;i++) { if (tmp[i].className == "Show") { tmp[i].className = "Hidden"; } }
}
function toggle_all() 
{	tmp = document.getElementsByTagName('div');
	for (i=0;i<tmp.length;i++) { 
		if (tmp[i].className == "Show") { tmp[i].className = "Hidden"; } 
		else if (tmp[i].className == "Hidden") { tmp[i].className = "Show"; } }
}
--></script>

<div class="wrap"> 
<h2>WP FeedReader</h2>
<div id="feedreader">
<div id="feedslist">
<?
	if ($search)
	{
		$items = $wpdb->get_results("SELECT $wpdb->linkfeeds.*, link_name, link_url, cat_id, cat_name, link_favicon 
			FROM $wpdb->links, $wpdb->linkcategories, $wpdb->linkfeeds
			WHERE $wpdb->links.link_id = $wpdb->linkfeeds.link_id AND
			$wpdb->links.link_category = $wpdb->linkcategories.cat_id 
			AND (item_title LIKE '%$search%' OR item_content LIKE '%$search%')
			ORDER BY item_date DESC");
	}
	else
	{
		$items = $wpdb->get_results("SELECT $wpdb->linkfeeds.*, link_name, link_url, cat_id, cat_name, link_favicon 
			FROM $wpdb->links, $wpdb->linkcategories, $wpdb->linkfeeds
			WHERE $wpdb->links.link_id = $wpdb->linkfeeds.link_id AND
			$wpdb->links.link_category = $wpdb->linkcategories.cat_id 
			AND $time $selection ORDER BY item_date DESC");
		if (!$days && !$hours)
		{
			$wpdb->get_results("update $wpdb->linkfeeds, $wpdb->links SET item_read=1 WHERE $wpdb->links.link_id=$wpdb->linkfeeds.link_id AND item_read <> 1 $selection");
		}
	}

	$unread_counts = $wpdb->get_results("SELECT SQL_BIG_RESULT $wpdb->linkcategories.cat_id, 
			$wpdb->linkcategories.cat_name, $wpdb->links.link_id, link_name, count(*) as num_unread 
			FROM $wpdb->links, $wpdb->linkfeeds, $wpdb->linkcategories 
			WHERE $wpdb->links.link_id = $wpdb->linkfeeds.link_id 
			AND $wpdb->links.link_category = $wpdb->linkcategories.cat_id 
			AND item_read=0 
			GROUP BY cat_name, link_name");
	if ($unread_counts)
	{
		foreach ($unread_counts as $unread_count)
		{
			$cat_id = $unread_count->cat_id; 
			$cat_name = $unread_count->cat_name; 
			$link_id = $unread_count->link_id; 
			$link_name = $unread_count->link_name; 
			$count = $unread_count->num_unread; 

			$total_unread += $count;
			$cat_unread[$cat_id] += $count;
			$link_unread[$link_id] += $count;
		}
	}

	$feeds = $wpdb->get_results("SELECT SQL_BIG_RESULT $wpdb->linkcategories.cat_id, 
		$wpdb->linkcategories.cat_name, $wpdb->links.link_id, link_name, link_url, link_favicon, link_rss 
		FROM $wpdb->links, $wpdb->linkcategories 
		WHERE $wpdb->links.link_category = $wpdb->linkcategories.cat_id 
		GROUP BY cat_name, link_name; ");
	if ($feeds)
	{
		print "<b><a href=\"$page&category_id=all\">All Feeds (".($total_unread+0).")</a></b>\n"; 
		$last_link =""; $last_cat="";
		foreach ($feeds as $feed)
		{
			$cat_id = $feed->cat_id; 
			$cat_name = $feed->cat_name; 
			$link_id = $feed->link_id; 
			$link_name = $feed->link_name; 
			$link_url = $feed->link_url; 
			$link_favicon = $feed->link_favicon; 
			$link_rss = $feed->link_rss; 
			if ($link_favicon == 'none') { $link_favicon = "./no_favicon.gif"; }

			if ($cat_name != $last_cat) 
			{
				if (!$last_cat) { print "<ul>\n"; }
				else { print "</ul>\n"; }

				if ($category_id == $cat_id) { $folderImage = get_option('siteurl')."/wp-content/plugins/feedreader/folderopen.gif"; $menuClass="Show"; }
				else { $folderImage = get_option('siteurl')."/wp-content/plugins/feedreader/folderclosed.gif"; $menuClass="Hidden"; }

				print "<li><img src=\"$folderImage\" alt=\"Open/Close Folder\" id=\"plus$cat_id\" onclick=\"expandcollapse('cat$cat_id');menuPlusMinus('plus$cat_id');\" onmouseover=\"this.style.cursor='pointer';\">";
				if ($cat_unread[$cat_id])  print "<b>"; 
				print "<a href=\"$page&category_id=$cat_id\">$cat_name</a>";
				if ($cat_unread[$cat_id])  print " (".$cat_unread[$cat_id].")</b>"; 
				print "</li>\n<ul class=\"$menuClass\" id=\"cat$cat_id\">";
			}
			if ($link_unread[$link_id])
			{
				print "<li><b><a href=\"$link_url\"><img src=\"".FR_cached_Favicon($link_favicon)."\" width=8 height=8 border=0></a> ";
				print "<a href=\"$page&feed_id=$link_id&category_id=$cat_id\">$link_name</a> (".$link_unread[$link_id].")</b></li>\n"; 
			}
			elseif (!$link_rss) 
			{
				print "<li><a href=\"$link_url\"><img src=\"".FR_cached_Favicon($link_favicon)."\" width=8 height=8 border=0></a> ";
				print "<a href=\"$link_url\">$link_name</a> (NO FEED)</li>\n"; 					
			}
			else
			{
				print "<li><a href=\"$link_url\"><img src=\"".FR_cached_Favicon($link_favicon)."\" width=8 height=8 border=0></a> ";
				print "<a href=\"$page&feed_id=$link_id&category_id=$cat_id\">$link_name</a></li>\n"; 
			}
			$last_link = $link_name; $last_cat = $cat_name;
		}
		if ($last_cat) { print "</ul>\n"; }
	}

	if ($search) { $title = "Search for: $search"; }
	elseif ($feed_id) { $title = "Feed: ".$wpdb->get_var("SELECT link_name FROM $wpdb->links WHERE link_id=$feed_id"); }
	elseif ($category_id) 
	{
		if ($category_id == "all") { $title = "All Feeds"; }
		else { $title = "Category: ".$wpdb->get_var("SELECT cat_name FROM $wpdb->linkcategories WHERE cat_id=$category_id"); }
	}
	if ($days) { $title .= ", last $days day(s)"; }
	elseif ($hours) { $title .= ", last $hours hour(s)"; }
?>
</div><!-- end feedlist -->

<div id="maincolumn">

<form action="<? print $page; ?>">
<a href="javascript:show_all()" title="Expand display of all posts" class="PostButtons">Expand</a> 
<a href="javascript:hide_all()" title="Collapse display of all posts" class="PostButtons">Collapse</a> 
<a href="<? print $page; ?>&category_id=<? print $category_id; ?>&feed_id=<? print $feed_id; ?>" title="Show only unread posts" class="TimeButtons">Unread</a> 
<a href="<? print $page; ?>&category_id=<? print $category_id; ?>&feed_id=<? print $feed_id; ?>&hours=1" title="Show posts from last hour" class="TimeButtons">1 hour</a> 
<a href="<? print $page; ?>&category_id=<? print $category_id; ?>&feed_id=<? print $feed_id; ?>&hours=3" title="Show posts from last 3 hours" class="TimeButtons">3 hours</a> 
<a href="<? print $page; ?>&category_id=<? print $category_id; ?>&feed_id=<? print $feed_id; ?>&days=1" title="Show posts from last day" class="TimeButtons">1 day</a> 
<a href="<? print $page; ?>&category_id=<? print $category_id; ?>&feed_id=<? print $feed_id; ?>&days=7" title="Show posts from last week" class="TimeButtons">7 days</a> 
<a href="<? print $page; ?>&category_id=<? print $category_id; ?>&feed_id=<? print $feed_id; ?>&days=15" title="Show all posts" class="TimeButtons">All</a> 
<a href="<? print get_option('siteurl'); ?>/wp-admin/options-general.php?page=feedreader/index.php" title="Set Options" class="OptionButtons">Options</a> 

<input type=hidden name="page" value="feedreader/"><input type=text name=search width=15 class="SearchButtons">
<input type=submit value="Search" class="SearchButtons">
</form>

<h3><? print $title; ?></h3>

<?
	$count = 1;
	if (!$items) {  print "<p>No entries</p>"; }
	else foreach ($items as $item)
	{
		$id = $item->id;
		$title = $item->item_title;
		if (!$title) { $title = "No Title"; }
		$link = $item->item_link;
		$content = $item->item_content;
		$date = $item->item_date;
		$creator = $item->item_creator;
		$subject = $item->item_subject;
		$guid = $item->item_guid;
		$read = $item->item_read;
		$link_id = $item->link_id;
		$link_name = $item->link_name;
		$link_url = $item->link_url;
		$cat_id = $item->cat_id;
		$cat_name = $item->cat_name;
		$link_favicon = $item->link_favicon;

		$timestamp = strtotime($date);
		$time = date ("H:i", $timestamp);
		$date = date('l F j, Y', $timestamp);

		if ($date != $last) 
		{ 	
			print "<h4>$date</a></h4>\n";
			$last = $date; 
		}

		if (FR_isEven($count)) { print "<div class=\"itemEven\">\n"; }
		else { print "<div class=\"itemOdd\">\n"; }
		#print "<span class=\"itemMark\"><input type=checkbox id=\"checkbox$id\" name=\"checkbox$id\" value=\"1\"></span> \n";
		print "<span class=\"itemTime\">$time</span> \n";
		print "<span class=\"itemIcon\"><a href=\"$link_url\"><img src=\"".FR_cached_Favicon($link_favicon)."\" alt=\"Visit Site\" height=16 border=0></a></span> \n";
		print "<strong><span class=\"itemTitle\" onclick=\"expandcollapse('item$id')\" onmouseover=\"this.style.cursor='pointer';\">$title</span></strong> \n";
		print "<span class=\"itemSource\"> &raquo; <a href=\"$link\" title=\"Permalink to this post $guid\">$link_name</a>";
		if ($creator) { print " ($creator)"; }

		if ($expandposts) { $classforcontent = "Show"; } else { $classforcontent = "Hidden"; }
		if ($content) { print "</span>\n<div class=\"$classforcontent\" id=\"item$id\">".trim($content)."</div>\n"; }

		#print "</span>\n";
		print "</div>\n\n";
		$count++;
	}
?>

<br clear="all">
</div><!-- end maincolumn -->
<br clear="all">
</div><!-- end feedread -->
</div><!-- end wrap -->

<?
}

function FR_isEven($num)
{
    return ($num % 2 == 0);
}

function FR_check_for_tables()
{
	global $wpdb, $table_prefix;

	# check to see if linkfeeds table is present, and if not create it
	$tables = $wpdb->get_col("show tables");
	if (!in_array("{$table_prefix}linkfeeds", $tables))
	{
		$create_table = "CREATE TABLE {$table_prefix}linkfeeds (
			id int(11) NOT NULL auto_increment,
			link_id int(11) NOT NULL,
			item_title varchar(255) NOT NULL,
			item_link text NOT NULL,
			item_content text,
			item_date datetime NOT NULL,
			item_creator varchar(255),
			item_subject varchar(255),
			item_guid tinytext NOT NULL,
			item_read tinyint(1) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY item_guid (item_guid(255)))";
		$wpdb->query($create_table);
		$tables = $wpdb->get_col("show tables");
		if (!in_array("{$table_prefix}linkfeeds", $tables))
		{
			die ("Can't create table for WP Feedreader");
		}
	}

	# check to see if link_favicon and rss_checked are present in links table is present, and if not create them
	$fields = $wpdb->get_col("describe $wpdb->links");
	if (!in_array("link_favicon", $fields))
	{
		$add_column = "alter table $wpdb->links add column link_favicon varchar(255) ";
		$wpdb->query($add_column);
		$fields = $wpdb->get_col("describe $wpdb->links");
		if (!in_array("link_favicon", $fields))
		{
			die ("Can't create column for link_favicon");
		}
	}
	if (!in_array("rss_checked", $fields))
	{
		$add_column = "alter table $wpdb->links add column rss_checked datetime default '0000-00-00 00:00:00' ";
		$wpdb->query($add_column);
		$fields = $wpdb->get_col("describe $wpdb->links");
		if (!in_array("rss_checked", $fields))
		{
			die ("Can't create column for rss_checked");
		}
	}
}

function FR_cached_Favicon($location, $none="/wp-content/plugins/feedreader/no_favicon.gif") 
{
	if((!$location) || ($location == "none")) { return get_option('siteurl').$none; }

	$cached_ico = "/wp-content/cache/favicons/" . md5($location) . ".ico" ;
	$cachetime = 24 * 60 * 60; // 1 day

	// Serve from the cache if it is younger than $cachetime
	if (file_exists(ABSPATH . $cached_ico) && (time() - $cachetime < filemtime(ABSPATH . $cached_ico))) return get_option('siteurl').$cached_ico ;
	if (!$data = @file_get_contents($location)) $data = @file_get_contents(ABSPATH . $none);
	if (stristr($data,'html')) $data = @file_get_contents(ABSPATH . $none);
	$fp = fopen(ABSPATH . $cached_ico,'w') ;
	fputs($fp,$data) ;
	fclose($fp) ;
	return get_option('siteurl').$cached_ico;
}

function feedreader_optionspage()
{
?>
<div class="wrap"> 
<h2>FeedReader Options</h2>
<div id="feedreader">
<?
	global $wpdb;
	$action = $_GET[action];
	$skip = $_GET[skip]; if (!$skip) $skip=0;
	$setexpandposts = $_GET[setexpandposts];
	$setupdatefrequency = $_GET[setupdatefrequency];

	$expandposts = get_option("FR_expandposts");
	$updatefrequency = get_option("FR_updatefrequency");

	$page = get_option('siteurl')."/wp-admin/options-general.php?page=feedreader/index.php";

	if ($action == "setoptions")
	{
		update_option("FR_updatefrequency", $setupdatefrequency);
		if ($setexpandposts) { update_option("FR_expandposts", 1); }
		else { update_option("FR_expandposts", 0); }

		print "<p><strong>Options updated.</strong></p>";

		$expandposts = get_option("FR_expandposts");
		$updatefrequency = get_option("FR_updatefrequency");
	}
	elseif ($action == "findrss")
	{
		print "<p>Searching for RSS Feeds (10 links at a time)</p>\n";

		$last_id = $wpdb->get_var("select link_id from $wpdb->links order by link_id desc limit 1");
		$links = $wpdb->get_results("SELECT link_id, link_name, link_url, link_rss from $wpdb->links WHERE link_id > $skip limit 10 ");
		if ($links)
		{
			print "<table border=1 cellpadding=5 cellspacing=0>\n";
			foreach ($links as $link)
			{
				$id = $link->link_id; 
				$name = $link->link_name; 
				$url = $link->link_url; 
				$rss = $link->link_rss; 

				if (!$rss)
				{
					$file = file_get_contents($url); 
					if( $newrss = FR_getRSSLocation(file_get_contents($url), $url) ) 
					{ $wpdb->query("UPDATE $wpdb->links SET link_rss = '$newrss' WHERE link_id = $id "); } 
				}

				print "<tr><td><a href=\"$url\">$name</a></td>\n";
				if ($rss) { print "<td><a href=\"$rss\">feed</a></td><td>Already in database</td>"; }
				elseif ($newrss) { print "<td><b><a href=\"$newrss\">feed</a></b></td><td>RSS Feed added</td>"; }
				else { print "<td>&nbsp;</td><td>No RSS Feed found</td>"; }

				print "</tr>\n";
				$newrss = "";
			}
			print "</table>\n";
		}
		if ($id < $last_id) { print "<p><a href=\"$page&action=findrss&skip=$id\">Search next 10 links for RSS Feeds</a></p>\n"; }
		else { print "<p>All links have been searched for RSS Feeds.</p>\n"; }
	}
	elseif ($action == "findfavicons")
	{
		print "<p>Searching for favicons (10 links at a time)</p>\n";

		$last_id = $wpdb->get_var("select link_id from $wpdb->links order by link_id desc limit 1");
		$links = $wpdb->get_results("SELECT link_id, link_name, link_url, link_favicon from $wpdb->links WHERE link_id > $skip limit 10 ");
		if ($links)
		{
			print "<table border=1 cellpadding=5 cellspacing=0>\n";
			foreach ($links as $link)
			{
				$id = $link->link_id; 
				$name = $link->link_name; 
				$url = $link->link_url; 
				$favicon = $link->link_favicon; 

				if ((!$favicon) || ($favicon == "none"))
				{
					$favicon = "";
					if( $newfavicon = FR_getLinkFavicon($url) ) 
					{ $wpdb->query("UPDATE $wpdb->links SET link_favicon = '$newfavicon' WHERE link_id = $id "); } 
					else
					{ $wpdb->query("UPDATE $wpdb->links SET link_favicon = 'none' WHERE link_id = $id "); }
				}

				print "<tr><td><a href=\"$url\">$name</a></td>\n";
				if ($favicon) { print "<td><img src=\"$favicon\"></td><td>Already in database</td>"; }
				elseif ($newfavicon) { print "<td><img src=\"$newfavicon\"></td><td>Favicon added</td>"; }
				else { print "<td></td><td>No favicon found</td>"; }

				print "</tr>\n";
				$newfavicon = "";
			}
			print "</table>\n";
		}
		if ($id < $last_id) { print "<p><a href=\"$page&action=findfavicons&skip=$id\">Search next 10 links for Favicons</a></p>\n"; }
		else { print "<p>All links have been searched for Favicons.</p>\n"; }
	}
?>
<fieldset class="options">
<legend>Actions</legend>
<ul>
<li><a href="<? print $page; ?>&action=findfavicons">Find Favicons</a>: automatically find the favicons for your links</li>
<li><a href="<? print $page; ?>&action=findrss">Find RSS Feeds</a>: automatically find the rss feeds for your links</li>
</ul>
<em>You can also <a href="./link-manager.php">edit your links</a> individually to add RSS feeds.</em>
</fieldset>

<form action="<? print $page; ?>">
<fieldset class="options">
<legend>Options</legend>
<input type=hidden name="page" value="feedreader/index.php">
<input type=hidden name="action" value="setoptions">
<ul>
<li>Update Frequency <input type=text size=5 name="setupdatefrequency" value="<? print $updatefrequency; ?>"> 
<em>in minutes (ex. "60" = 1 hour)</em></li>
<li>Expand posts by default <input type=checkbox name="setexpandposts" value="1" <? if ($expandposts) print "checked"; ?>>
<em>e.g Instead of clicking on "Expand" to see full posts</em></li>
</ul>
<div class="submit"><input type=submit value="Update Options"></div>
</fieldset></form>

</div><!-- end feedreader --->
</div><!-- end wrap --->

<?
}

# The next two functions borrowed from 'RSS auto-discovery with PHP' http://keithdevens.com/weblog/archive/2002/Jun/03/RSSAuto-DiscoveryPHP

function FR_getRSSLocation($html, $location){
    if(!$html or !$location){
        return false;
    }else{
        #search through the HTML, save all <link> tags
        # and store each link's attributes in an associative array
        preg_match_all('/<link\s+(.*?)\s*\/?>/si', $html, $matches);
        $links = $matches[1];
        $final_links = array();
        $link_count = count($links);
        for($n=0; $n<$link_count; $n++){
            $attributes = preg_split('/\s+/s', $links[$n]);
            foreach($attributes as $attribute){
                $att = preg_split('/\s*=\s*/s', $attribute, 2);
                if(isset($att[1])){
                    $att[1] = preg_replace('/([\'"]?)(.*)\1/', '$2', $att[1]);
                    $final_link[strtolower($att[0])] = $att[1];
                }
            }
            $final_links[$n] = $final_link;
        }
        #now figure out which one points to the RSS file
        for($n=0; $n<$link_count; $n++){
            if(strtolower($final_links[$n]['rel']) == 'alternate'){
                if(strtolower($final_links[$n]['type']) == 'application/rss+xml'){
                    $href = $final_links[$n]['href'];
                }
                if(!$href and strtolower($final_links[$n]['type']) == 'text/xml'){
                    #kludge to make the first version of this still work
                    $href = $final_links[$n]['href'];
                }
                if($href){
                    if(strstr($href, "http://") !== false){ #if it's absolute
                        $full_url = $href;
                    }else{ #otherwise, 'absolutize' it
                        $url_parts = parse_url($location);
                        #only made it work for http:// links. Any problem with this?
                        $full_url = "http://$url_parts[host]";
                        if(isset($url_parts['port'])){
                            $full_url .= ":$url_parts[port]";
                        }
                        if($href{0} != '/'){ #it's a relative link on the domain
                            $full_url .= dirname($url_parts['path']);
                            if(substr($full_url, -1) != '/'){
                                #if the last character isn't a '/', add it
                                $full_url .= '/';
                            }
                        }
                        $full_url .= $href;
                    }
                    return $full_url;
                }
            }
        }
        return false;
    }
}


# The next two functions borrowed from Favatars Plugin / http://svn.wp-plugins.org/favatars/
function FR_getLinkFavicon($url) 
{
	// Start by attempting to "guess" the favicon location
	$urlParts = parse_url($url);
	$faviconURL = $urlParts['scheme'].'://'.$urlParts['host'].'/favicon.ico';
	// Run a test to see if what we have attempted to get actually exists.
	if( $faviconURL_exists = FR_validate_url($faviconURL) ) { return $faviconURL; }

	// start by fetching the contents of the URL they left...
	if( $html = @file_get_contents($url) ) {

		if (preg_match('/<link[^>]+rel="(?:shortcut )?icon"[^>]+?href="([^"]+?)"/si', $html, $matches)) {
			
			// Attempt to grab a favicon link from their webpage url

			$linkUrl = html_entity_decode($matches[1]);
			if (substr($linkUrl, 0, 1) == '/') {
				$urlParts = parse_url($url);
				$faviconURL = $urlParts['scheme'].'://'.$urlParts['host'].$linkUrl;
			} else if (substr($linkUrl, 0, 7) == 'http://') {
				$faviconURL = $linkUrl;
			} else if (substr($url, -1, 1) == '/') {
				$faviconURL = $url.$linkUrl;
			} else {
				$faviconURL = $url.'/'.$linkUrl;
			}

		} 
		if( $faviconURL_exists = FR_validate_url($faviconURL) ) { return $faviconURL; }
	} 

	// Finally, if we haven't 'returned' yet then there is nothing to see here.
	return false;
}
function FR_validate_url( $link ) {  
		
	$url_parts = @parse_url( $link );

	if ( empty( $url_parts["host"] ) ) { return false; }

	if ( !empty( $url_parts["path"] ) ) {
		$documentpath = $url_parts["path"];
	} else {
		$documentpath = "/";
	}

	if ( !empty( $url_parts["query"] ) ) {
		$documentpath .= "?" . $url_parts["query"];
	}

	$host = $url_parts["host"];
	$port = $url_parts["port"];
	if ( empty($port) ) { $port = "80"; }

	$socket = @fsockopen( $host, $port, $errno, $errstr, 30 );
	
	if (!$socket) {
		return false;
	} else {
		fwrite ($socket, "HEAD ".$documentpath." HTTP/1.0\r\nHost: $host\r\n\r\n");
	
		$http_response = fgets( $socket, 22 );

		$responses = "/(200 OK)|(30[0-9] Moved)/";
		if ( preg_match($responses, $http_response) ) {
			return true;
			fclose($socket);
		} else {
			// echo "HTTP-Response: $http_response<br>";
			return false;
		}
	}

}

?>