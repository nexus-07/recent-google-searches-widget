<?php
/*
Plugin Name: Recent Google Searches Widget
Plugin URI: http://www.blogseye.com
Description: Widget to display a list of recent search engine queries in a link to the wp search function.
Author: Keith P. Graham
Version: 1.40
Author URI: http://www.cthreepo.com
*/
function widget_kpg_collect_data_rgs() {
	// let's see if we are in a page referred by google or such	
	$ref='';
	if (array_key_exists('HTTP_REFERER',$_SERVER )) $ref=urldecode($_SERVER['HTTP_REFERER']);
	$q='';
	if ((strpos($ref,'google')>0||strpos($ref,'bing')>0 )&& (($pos=strpos($ref,'&q='))>0 || ($pos=strpos($ref,'?q='))>0)) {
		// search engine using q=
		$q=substr($ref, $pos+3);
		if (strpos($q,'&')>0) {
			$q=substr($q,0,strpos($q,'&'));
		}
	} else if (strpos($ref,'yahoo')>0&&strpos($ref,'&p=')>0) {
		$q=substr($ref,strpos($ref,'&p=')+3);
		if (strpos($q,'&')>0) {
			$q=substr($q,0,strpos($q,'&'));
		}
	} else if (strpos($ref,'yahoo')>0&&strpos($ref,'?p=')>0) {
		$q=substr($ref,strpos($ref,'?p=')+3);
		if (strpos($q,'&')>0) {
			$q=substr($q,0,strpos($q,'&'));
		}
	} else if (strpos($ref,'yandex')>0&&strpos($ref,'?text=')>0) {
		$q=substr($ref,strpos($ref,'?text=')+6);
		if (strpos($q,'&')>0) {
			$q=substr($q,0,strpos($q,'&'));
		}
	} else if (strpos($ref,'yandex')>0&&strpos($ref,'&text=')>0) {
		$q=substr($ref,strpos($ref,'&text=')+6);
		if (strpos($q,'&')>0) {
			$q=substr($q,0,strpos($q,'&'));
		}
	}
	
	require_once('a.charset.php');
	
	$q=charset_x_utf8($q);
//	echo $q;
	$q=trim($q);
	if ($q=='') return;
	
	// strip tags, esc_url_raw, remove_accents.
	$q=stripslashes($q);
	$q=urldecode($q);
	$q=strip_tags($q);
	$q=remove_accents($q);
	
	
	
	// if there is a search from the search engines, then we need to add it to our list
	// q has a legit search in it.
	// get the results of a search based on the parsed entry
	$q=str_replace('<',' ',$q); // just in case the striptags missed it
	$q=str_replace('>',' ',$q); // just in case the striptags missed it
	$q=str_replace('_',' ',$q); // underscores should be space
	$q=str_replace('.',' ',$q); // periods should be space 
	$q=str_replace('-',' ',$q); // dashes are wrong
	$q=str_replace('"',' ',$q); // dquotes are wrong
	$q=str_replace("'",' ',$q); // squotes are wrong
	$q=str_replace("`",' ',$q); // lquotes are wrong
	$q=str_replace('  ',' ',$q); // double spaces may have crept in
	$q=str_replace('  ',' ',$q); 
	$q=str_replace('  ',' ',$q); 
	
	$q=trim($q);
	if ($q=='') return;
	
	
	// this is code to get the options
	$options = (array) get_option('widget_kpg_rgs');
	if (empty($options)) $options=array();
	$title="";
	$history=array();
	$rgs_nofollow="";
	$maxlinks=30;
	if (array_key_exists('title',$options)) $title = $options['title'];
	if (array_key_exists('rgs_nofollow',$options)) $rgs_nofollow=$options['rgs_nofollow'];
	if (array_key_exists('history',$options)) $history=$options['history'];
    if (array_key_exists('maxlinks',$options)) $maxlinks=$options['maxlinks'];
	// end options code
	
	if (empty($maxlinks)||$maxlinks>30||$maxlinks<0) $maxlinks=5;
	// use the string as a key, date as the data
	$q=mysql_real_escape_string($q);
	$history[$q]=time();
	// sort the array on time
	arsort($history);
	// get rid of the oldest
	
	if (count($history)>$maxlinks) {
		$n=count($history);
		while ($n>$maxlinks) {
			array_pop($history);
			$n=count($history);
		}
	}
	$options['history']=$history;
	update_option('widget_kpg_rgs', $options);

}

function widget_kpg_rgs($args) {
	global $wpdb; // if we need to access the database - I don't think we do
	extract( $args );
	
	// this is code to get the options
	$options = (array) get_option('widget_kpg_rgs');
	if (empty($options)) $options=array();
	$title="";
	$history=array();
	$rgs_nofollow="";
	$maxlinks=5;
	if (array_key_exists('title',$options)) $title = $options['title'];
	if (array_key_exists('rgs_nofollow',$options)) $rgs_nofollow=$options['rgs_nofollow'];
	if (array_key_exists('history',$options)) $history=$options['history'];
    if (array_key_exists('maxlinks',$options)) $maxlinks=$options['maxlinks'];
	// end options code
	
	// repair the old format
	$up=false;
	foreach ($history as $key=>$data) {
		if ($key=='0'||$key=='1'||$key=='2'||$key=='3'||$key=='4') {
		    unset($history[$key]);
			$history[$data]=time();
			$up=true;
		}
	}
	if ($up) {
		$options['history']=$history;
		update_option('widget_kpg_rgs', $options);
	}
	
	echo "\n\n<!-- Recent Google Search Widget -->\n\n";
	if (count($history)>0) {
		echo $args['before_widget'];
		if ($title!='') echo $before_title . $title . $after_title; 
		// display the recent searches
		echo "<ul>";
		foreach ($history as $key=>$data) {
			$ll=urlencode(stripslashes($key));
			$nofollow="";
			if ($rgs_nofollow=='Y') {
				$nofollow='rel="nofollow"';
			}

		?>
			<li><a href="<?php echo bloginfo('url'); ?>/?s=<?php echo $ll; ?>" <?php echo $nofollow; ?>><?php echo $key; ?></a></li>	
		<?php
		}
		echo "</ul>";
		echo $args['after_widget'];
	}
	return;
}


function widget_kpg_rgs_control() {
	
	// this is code to get the options
	$options = (array) get_option('widget_kpg_rgs');
	if (empty($options)) $options=array();
	$title="";
	$history=array();
	$rgs_nofollow="";
	$maxlinks=5;
	if (array_key_exists('title',$options)) $title = $options['title'];
	if (array_key_exists('rgs_nofollow',$options)) $rgs_nofollow=$options['rgs_nofollow'];
	if (array_key_exists('history',$options)) $history=$options['history'];
    if (array_key_exists('maxlinks',$options)) $maxlinks=$options['maxlinks'];
	// end options code
	
	
	if (array_key_exists('kpg_rgs_submit',$_POST)) {
		$title=strip_tags(stripslashes($_POST['kpg_rgs_title']));
		$maxlinks=$_POST['kpg_rgs_maxlinks'];
		$rgs_nofollow=$_POST['kpg_rgs_nofollow'];
		if (empty($maxlinks)||$maxlinks>30||$maxlinks<0) $maxlinks=5;
		if (empty($rgs_nofollow)) $rgs_nofollow='N';
		$options['title']=$title;
		$options['maxlinks'] = $maxlinks;
		$options['rgs_nofollow'] = strip_tags($rgs_nofollow);
		update_option('widget_kpg_rgs', $options);
	}
?>
<div style="text-align:right">
			
	<label for="kpg_rgs_title" style="line-height:25px;display:block;">
		<?php _e('Widget title:', 'widgets'); ?> 
		<input style="width: 200px;" type="text" id="kpg_rgs_title" name="kpg_rgs_title" value="<?php echo $title; ?>" />
	</label>
  <label for="kpg_rgs_maxlinks" style="line-height:25px;display:block;">
  <?php _e('Links to display (max 30):', 'widgets'); ?>
	<input style="width: 200px;" type="text" name="kpg_rgs_maxlinks" 
						value="<?php echo $maxlinks; ?>" />
  </label>
  <label for="kpg_rgs_nofollow" style="line-height:25px;display:block;">
  <?php _e('Use NoFollow on links:', 'widgets'); ?>
  <input type="checkbox" name="kpg_rgs_nofollow" 
						value="Y" <?php if ($rgs_nofollow=='Y'){ echo 'checked'; }?>" />
  </label>
			
			<input type="hidden" name="kpg_rgs_submit" id="kpg_rgs_submit" value="1" />
			
			</div>
	<small>note: the widget will not display on a page until there has actually been a user arriving by a search engine query)</small>
<?php
}

// admin menu panel
function  widget_kpg_rgs_admin_control() {
// this is the display of information about the page.
	$bname=urlencode(get_bloginfo('name'));
	$burl=urlencode(get_bloginfo('url'));
	$bdesc=urlencode(get_bloginfo('description'));
?>
<h2>Recent Google Searches</h2>
<h4>The Recent Google Searches Widget is installed and working correctly.</h4>
<div style="position:relative;float:right;width:35%;background-color:ivory;border:#333333 medium groove;padding-left:6px;">

<p>This plugin is free and I expect nothing in return. If you would like to support my programming, you can buy my book of short stories.</p><p>Some plugin authors ask for a donation. I ask you to spend a very small amount for something that you will enjoy. eBook versions for the Kindle and other book readers start at 99&cent;. The book is much better than you might think, and it has some very good science fiction writers saying some very nice things. <br/>
 <a target="_blank" href="http://www.amazon.com/gp/product/1456336584?ie=UTF8&tag=thenewjt30page&linkCode=as2&camp=1789&creative=390957&creativeASIN=1456336584">Error Message Eyes: A Programmer's Guide to the Digital Soul</a></p>
 <p>A link on your blog to one of my personal sites would also be appreciated.</p>
 <p><a target="_blank" href="http://www.WestNyackHoney.com">West Nyack Honey</a> (I keep bees and sell the honey)<br />
	<a target="_blank" href="http://www.cthreepo.com/blog">Wandering Blog </a> (My personal Blog) <br />
	<a target="_blank" href="http://www.cthreepo.com">Resources for Science Fiction</a> (Writing Science Fiction) <br />
	<a target="_blank" href="http://www.jt30.com">The JT30 Page</a> (Amplified Blues Harmonica) <br />
	<a target="_blank" href="http://www.harpamps.com">Harp Amps</a> (Vacuum Tube Amplifiers for Blues) <br />
	<a target="_blank" href="http://www.blogseye.com">Blog&apos;s Eye</a> (PHP coding) <br />
	<a target="_blank" href="http://www.cthreepo.com/bees">Bee Progress Beekeeping Blog</a> (My adventures as a new beekeeper) </p>
</div>

<p>All options are set through the Widget Admin Panel</p>
<p>The Recent Google Searches Widget collects the query string from Google, Bing and Yahoo. It lists the last 5 as a sidebar widget so that users might click on them and find information using the WordPress search. In this way a user might find more pages that satisfy his search and other users may be interested in the same things that previous searchers used as queries.</p>

<p>The search engines will see the widget when they spider your site. They will then send you new traffic based on the traffic that you have received. This sets up a possitive feed back loop. I experienced a doubling of traffic within a week at one site.</p>
<p>There is a danger that your site will be ranked high for a popular keyword, but one that has little to do with your site and as a result the traffic will not be related to your core keywords. I would suggest adding content to match and give the searching public what they want.</p>
<h4>For questions and support please check my website <a href="http://www.blogseye.com/i-make-plugins/exit-screen-plugin/">BlogsEye.com</a>.</h4>
<?php
}


function widget_kpg_rgs_init() {
	register_sidebar_widget(array('Recent Gooogle Searches Widget', 'widgets'), 'widget_kpg_rgs');
	register_widget_control(array('Recent Gooogle Searches Widget', 'widgets'), 'widget_kpg_rgs_control');
}
function widget_kpg_rgs_admin_menu() {
   add_options_page('Recent Gooogle Searches', 'Recent Gooogle Searches', 'manage_options',__FILE__,'widget_kpg_rgs_admin_control');
}


// Delay plugin execution to ensure Dynamic Sidebar has a chance to load first
add_action('widgets_init', 'widget_kpg_rgs_init');
add_action('init', 'widget_kpg_collect_data_rgs');
add_action('admin_menu', 'widget_kpg_rgs_admin_menu');

?>
