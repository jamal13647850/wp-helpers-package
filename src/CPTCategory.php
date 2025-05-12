<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();



class CPTCategory{

	private string $catname,$catdesc,$caturl;
	private $catimage,$catenname;
	public function __construct(private int $catid,private string $taxonomyname){
		$this->getCategory();
		$post_id = $taxonomyname."_".$catid;
		//web24_product_cat_image
		$this->catimage = get_field('web24_product_cat_image', $post_id);
		$this->catenname = get_field('web24_pr_cat_en_name', $post_id);
	}
	

	private function getCategory(){
		$category = get_term_by( 'id', $this->catid, $this->taxonomyname );

		if ( ! is_wp_error( $category ) ) {

		    	$this->catname = $category->name;
		    	$this->catdesc = $category->description;
			$this->caturl = get_term_link( $category );
		}
	}

	public function getCatId(): int {
        	return $this->catid;
    	}

    	public function getCatName(): string {
        	return $this->catname;
    	}


    	public function getCatDesc(): string {
		return $this->catdesc;
	}

	public function getCatImage():array{
		return $this->catimage;
	}

	public function getCatEnName():string{
		return $this->catenname;
	}

	public function getCatUrl():string{
		return $this->caturl;
	}
}

