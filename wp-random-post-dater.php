<?php
/*
Plugin Name: WP Random Post Dater
Plugin URI: http://example.com
Description: Set posts to a random post date in the past or future.
Version: 0.9
Author: iSplash
Author URI: http://google.com
License: GPL2
*/


class wp_random_post_dater {


	public $abs_max_posts = 9999; // to not slow down the site! 
	public $earliest;

	function __construct() {

		$this->earliest_day = 5;
		$this->latest_day = 20;
		

	}

	protected function get_all_posts() {

		// very quickly work out if we are sorting by cat  or tag
		$cat = '';
		if ($_POST['incategory'] != '') {
			$cat = '&cat=' . (int)$_POST['incategory'];
		}

		$tag = '';
		if ($_POST['tagid'] != '') {
			$tag = "&tag_id=" . (int) $_POST['tagid'];
		}

			$all_posts = array();
			$all_query = new WP_Query('showposts=' . $this->abs_max_posts . $cat . $tag);
					
		
			while($all_query->have_posts()) {
				$all_query->the_post();
				$orig_date = get_the_date("Y-m-d H:i:s");
				$i++;
				$all_posts[get_the_ID()] = array
					(
						"link" => get_permalink(), 
						"title" => the_title('','',false),
						"orig_date" => $orig_date,
					);
			}

			return $all_posts;

	}

	public function redate_posts() {


		if (isset($_POST['latest_day']) && is_numeric($_POST['latest_day'])) {
			$this->latest_day = (int) $_POST['latest_day'];
		}

		if (isset($_POST['earliest_day']) && is_numeric($_POST['earliest_day'])) {
			$this->earliest_day = (int) $_POST['earliest_day'];
		}


		if ($this->earliest_day == 0 && $this->latest_day == 0) {

			echo "<p>Please go back - you cannot set latest and earliest at 0</p>";
			return;

		}


		if ($this->earliest_day < 0 || $this->latest_day < 0 ) {

			echo "<p>Please go back - you cannot either value as lower than 0</p>";
			return;

		}

		if ($_POST['tagid'] !='' && $_POST['incategory'] != '') {

			echo "<p>Please go back - you cannot set both slug and category!</p>";
			return;


		}



		$total_diff_in_days = $this->latest_day + $this->earliest_day;

		echo "<h1>Reordering</h1>";

		echo "<p>The earliest day is {$this->earliest_day} days ago. The latest day is {$this->latest_day} days in the future</p>";

		$allposts = $this->get_all_posts();
		if (count($allposts) > 0) {

			$doneDays = array();

			echo "<table style='width: 90%;' border=1>";
			foreach($allposts as $id => $postData) {

				$link = $postData['link'];
				$title = $postData['title'];




				$random_days_behind = rand(0,$total_diff_in_days);


				$random_days_behind = (int) $random_days_behind;

				$day_diff = $random_days_behind - ($this->earliest_day );

				if ($day_diff > 0) {

					$plus_minus = '+';

				}
				else {


					$day_diff = abs($day_diff); // get positive
					$plus_minus = '-';


				}

				$doneDays[str_replace("+","",$plus_minus) . $day_diff]++;



				$strtotimestring = $plus_minus . $day_diff . ' days';
				
				$date_time = (strtotime($strtotimestring)); // H:i:s



				// get the year/mo/day from strtotime(- xx days)
				$newDate = date('Y-m-d', $date_time);
				// randomly select a hh:mm:ss to post
				$newDate.= " " . str_pad(   rand(0,23)    , 2, '0', STR_PAD_LEFT)    . ":" . str_pad(  rand(0,59)     , 2, '0', STR_PAD_LEFT)    . ":".  str_pad(   rand(0,59)   , 2, '0', STR_PAD_LEFT)   ;

				$updateArray = array(


					'ID' => $id,
					'post_date' => $newDate,
					'post_date_gmt' => $newDate,
					'edit_date' => true,

				);

				$result = wp_update_post($updateArray);


				echo "<tr>
					<td ><a href='post.php?post=$id&action=edit'>$id</a></td>
					<td><a href='$link'>$link</a></td>
					<td>$title</td>
					<td>Original date: {$postData['orig_date']}</td>
					<td>Changed date to : $newDate</td>

					</tr>";


			}
			echo "</table>";

			echo "<h2>Counter:</h2>";

			ksort($doneDays);

			echo "<p>This shows the frequency of each post being posted x days ago/in future. It doesn't show every single day - only the ones with at least one post</p>";
			//change for for() to show all days? might be handy


			echo "<table><thead><th>+/- days</th><th>Number of posts</th></thead>";
			foreach($doneDays as $id => $val) {


				if ($id > 0) {
					$id = "+" . $id;
				}

				echo "<tr><td>$id days</td><td>$val posts</td></tr>";
			}

			echo "</table>";


		}
		else {

			echo "<p>Error - Could not find any posts to reorder!</p>";

		}

	}


	protected function get_all_cats_options() {



		 $args = array(
			'type'                     => 'post',
			'child_of'                 => 0,
			'parent'                   => '',
			'orderby'                  => 'name',
			'order'                    => 'ASC',
			'hide_empty'               => 1,
			'hierarchical'             => 1,
			'exclude'                  => '',
			'include'                  => '',
			'number'                   => '',
			'taxonomy'                 => 'category',
			'pad_counts'               => true );


		 $categories = get_categories( $args ); 

		 $returnhtml = '';
		 foreach($categories as $cat) {

			 $id = $cat->cat_ID;
			 $name = $cat->name;
			 $c = $cat->category_count;
			 $returnhtml .= "<option value='$id'>$name ($c)</option>";

		 }

		 return $returnhtml;

				


	}

		protected function get_all_tags_options() {




			$tags = get_tags();
			$returnhtml = '';
			if ($tags) {
			foreach ($tags as $tag) {

				$id = $tag->term_id;
				$name = $tag->name;
				$c = $tag->count;
				$returnhtml .= "<option value='$id'>$name ($c)</option>";

			}


			}
			else {

				$returnhtml = "<option value=''>(you have no tags!)</option>";

			}

			
			return $returnhtml;

		}
	public function show_options() {


?>

<h1>Ash's Random Post Dater</h1>

<p>Use this form to set ALL posts in the selected category to a random date between these two values.</p>

<p><u>This will update the post dates on ALL posts within the selected category/tag (or ALL posts in your wordpress blog if both are set to default). Please do not use if you do not want to reset ALL post dates. I do not recommend using this on a site with lots of live posts - especially if you use a permalink structure that includes the date in the url. I use it on new sites (add all content, then set all posts to random date in the past). Use at your own risk! You will lose the current post date time (it only changes the date posted - nothing else)</u></p>
	<form method='post' action='<?=$_SERVER['REQUEST_URI'];?>'>


<p>Only use one of the following two. Leave both at their defaults ("ALL CATEGORIES"/"ALL TAGS") to reorder ALL posts</p>

<table border='1' cellpadding='5' border-width='1'><tr><td>
<p>Reorder post dates in this category:</p>

<select name='incategory'>
<option value=''>ALL CATEGORIES (All posts)</option>
<?
		echo $this->get_all_cats_options();
?>
</select><br /> (leave on "ALL CATEGORIES" if you don't want to reorder all within a certain category)
</td><td>OR</td>
<td>

<p>Reorder post dates with this tag slug (use the tag slug, not the tag):</p>


<select name='tagid'>
<option value=''>ALL TAGS (all posts)</option>
<?

		echo $this->get_all_tags_options();

?>

</select>
<br />

(leave on "ALL TAGS" if you don't want to reorder all within a certain category)
</td></tr></table>

<p><b>Earliest Day</b> - how many days back do you want the maximum one?</p>
<input type='text' name='earliest_day' value='30' /> (put at 0 to put all posts in future)

<p><b>Latest Day</b> - Leave at 0 to make sure all posts are BACK dated. </p>
<input type='text' name='latest_day' value='0' /> (leave 0 to put all posts in past)

<p>For example if you wanted your posts randomly set to a date in the next 30 days set the latest day as 30, the earliest day at 0</p>

<p> If you wanted your posts randomly set to a day in the previous 180 days then set the latest day as 0 and the earliest day as 180.</p>

<p>It randomly sets each post - the posts are not spaced out at exact intervals. It will readjust ALL your posts. Please make sure that you want ALL your posts to be randomised.</p>


<input type='submit' name='random_post_dater' value='Randomly change the value of ALL posts in selected category' /> (Clicking this will randomly reorder all posts)

</form>

<?

	}

}




add_action('admin_menu','random_post_dater_admin');

function random_post_dater_admin() {
	add_options_page(__('Random Post Dater'),__('Random Post Dater'),'manage_options','random-post-dater','random_post_dater_page');
}

function random_post_dater_page() {

	$random_post_dater = new wp_random_post_dater();
	if (isset($_POST['random_post_dater'] )) {

		$random_post_dater->redate_posts();

	}
	else {

		$random_post_dater->show_options();

	}

}



