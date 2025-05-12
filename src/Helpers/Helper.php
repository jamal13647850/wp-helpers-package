<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Helpers;

defined('ABSPATH') || exit();


class Helper
{

	public function __construct() {}

	public function getRecentPosts(int $posts_per_page = 4, string $post_type = 'post')
	{
		require_once 'jdf.php';
		$jalali_date = jdate('l j F، Y');

		$recent_posts = new \WP_Query(array(
			'posts_per_page' => $posts_per_page,
			'post_type'      => $post_type
		));

		$post_data = array();
		if ($recent_posts->have_posts()) :
			while ($recent_posts->have_posts()) : $recent_posts->the_post();
				$post_data[] = array(
					'title'     => get_the_title(),
					'content'   => get_the_content(),
					'excerpt'   => get_the_excerpt(),
					'featured_image' => get_the_post_thumbnail_url(),
					'date'      => get_the_date('Y-m-d H:i'),
					'jalali_date' => $jalali_date,
					'link'      => get_permalink()
				);
			endwhile;
			wp_reset_postdata();
		endif;
		return $post_data;
	}
}
