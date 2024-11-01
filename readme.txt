=== WP Feedreader  ===

Tags: feedreader, xml, rss, atom, blogroll
Contributors: MattKingston

The WP Feedreader plugin allows you to read the RSS/Atom feeds of your blogs on your WP Blogroll.  Many improvements will come as I continue to work on this.


== Installation ==

Prerequisite:  You must download and install MagpieRSS somewhere on your server (available from http://magpierss.sourceforge.net/)

1. Create a subdirectory in /wp-content/plugins directory called "feedreader."

2. Copy the files (index.php, updatefeeds.php, no_favicon.gif, folderclosed.gig, and folderopen.gif) to that folder.

3. Under /wp-content, create a subdirectory called "cache" if you do not already have one.

4. Under /wp-content/cache, create a subdirectories called "favicons" and change it to be world-writable (0777).

5. On your WP Administration Page, select Options > FeedReader.  If this page does not load, follow the steps on this bug report: http://mosquito.wordpress.org/view.php?id=902

6. You can use the FeedReader Options page to search for RSS feeds and Favicons for the links on your WP Link List.  You will need to go through this process again anytime you add more links to your WP Link List.

7. On the WP Dashboard, select FeedReader.  

8. To read posts from the weblogs on your WP Link List, select categories or individual links from the sidebar (clicking on the folder icon opens the category to show indiviual links).

9. You can also read posts from the past few hours or days.  You can also search for words in the posts from your links.

Note: The WP FeedReader only keeps the past 2 weeks of posts from your links.

Note: The WP FeedReader updates in the background (1 link per load of your blog's pages), so it may take a while for the WP FeedRead to be populated with content.

Option: If you'd like to use use own copy of the MagpieeRSS (http://magpierss.sourceforge.net/) functions instead of WP's built-in version, look for comments in updatefeeds.php for which lines to edit.

Option: If you'd like to schedule a cron job to update your feeds on a schedule, look for comments in updatefeeds.php for which lines to edit.


== Frequently Asked Questions == 

= Do I really need to use this plugin? =

Not really -- there are plenty of browser-based Feed Readers out there.  But this one uses your WP Link List for management, so you don't have to keep track of multiple lists of links.


== Future Improvements == 

1. Better handling of feed load errors
2. Better options menu
3. Individual editing of link favicons
