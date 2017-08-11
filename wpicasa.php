<?php
/* 
Plugin Name: WPicasa
Plugin URI: http://beaucollins.com/wpicasa/
Description: Integrates Picasa 2 with your WordPress blog
Author: Beau Collins
Author URI: http://beaucollins.com
Version: 0.3 beta

Comments: This is distributed under the GNU Public License.  

*/



	/*------------------------------------------------------
			WPicasa TemplateTags
	-------------------------------------------------------*/
	
function wpicasa_thumbnails($args=''){
	echo wpicasa_get_thumbnails();
}

function wpicasa_get_thumbnails($page=0, $number=0, $type='list', $images='on'){
	global $wpicasa;
	$thumbs = $wpicasa->imagearray;
	$currentpage = ($page) ? $page : $wpicasa->albumpage ;
	$quantity = ($number) ? $number : $wpicasa->thumbsperpage;
	
	$start = ($currentpage-1) * $quantity;
	
	for($i=$start;$i<$quantity + $start;$i++){
		if (!$thumbs[$i]) break;
		$img = wpicasa_get_thumbnail($thumbs[$i][1]);
		$link = get_permalink($thumbs[$i][1]);
		$imgtitle = get_the_title($thumbs[$i][1]);
		$label = ($images == 'on') ? $img : $imgtitle;
		$li = "<li><a href=\"$link\" title=\"$imgtitle\">$label</a></li>\n";
		
		$menu .= $li;
	}
	
	return $menu;
}

function wpicasa_get_thumbnail($id=0){
	global $wpicasa;
	$thumbfilename = get_post_meta($id, 'wpicasa_imgfilename',true);
	if ($thumbfilename) $thumbsrc = $wpicasa->albumpath.'thumbnails/'.$thumbfilename;
	$imgsize = @getimagesize($thumbsrc);
	$thumbsrc = "<img src=\"$thumbsrc\" $imgsize[3] alt=\"$thumbfilename\" />";
	return $thumbsrc;
	
}

function wpicasa_thumbnail($id=0){
	echo wpicasa_get_thumbnail($id);
}

function wpicasa_thumbpage_menu($args=''){
	echo wpicasa_get_thumbpage_menu();
}

function wpicasa_get_thumbpage_menu($selectedclass='selected'){
	global $wpicasa;
	$current = $wpicasa->albumpage;
	$total = $wpicasa->albumpagetotal;
	$url = $wpicasa->albumurl;
	$qpre = (strpos($url,'?')) ? '&' : '?' ;
	if($total>1){	
		for($i=1;$i<$total + 1;$i++){
			if($current == $i){
				$menu .= "<li class=\"$selectedclass\">$i</li>";
			}else{
				$link .= (get_settings('wpicasa_albumrewritestructure') ? $url."page/$i/" : $url.$qpre."paged=$i");
				$menu .= "<li><a href=\"$link\">$i</a></li>";
				$link = '';
			}
		}
	}
	
	return $menu;
}

function wpicasa_photo($args=''){
	echo wpicasa_get_photo();
}

function wpicasa_get_photo($id=0, $class='wpicasaphoto'){
	global $wpicasa;
	if(!$id){
		$photosrc = $wpicasa->photosrc;
		$title = $wpicasa->phototitle;
	}else{
		$filename = rawurlencode(get_post_meta($id,'wpicasa_imgfilename',true));
		$photosrc = $wpicasa->albumpath.'images/'.$filename;
		$title = get_the_title($id);
	}
	
	$imgsize = @getimagesize($photosrc);
	
	$imgtag = "<img class=\"$class\" src=\"$photosrc\" alt=\"$title\" $imgsize[3] />";
	
	return $imgtag;
	
}

function wpicasa_next_photo_link(){
	echo wpicasa_get_next_photo_link();
}

function wpicasa_get_next_photo_link($text="Next Photo &raquo;", $title='Next Photo', $class="nextphoto"){
	global $wpicasa;
	$index = $wpicasa->photoindex;
	$index ++;
	if($nextid = $wpicasa->imagearray[$index][1]){
		$link = get_permalink($nextid);
		$linktag = "<a class=\"$class\" href=\"$link\">$text</a>";
		
		return $linktag;
	}
}

function wpicasa_previous_photo_link(){
	echo wpicasa_get_previous_photo_link();
}

function wpicasa_get_previous_photo_link($text='&laquo; Previous Photo', $title="Previous Photo", $class="previousphoto"){
	global $wpicasa;
	$index = $wpicasa->photoindex;
	$index --;
	if($previd = $wpicasa->imagearray[$index][1]){
		$link = get_permalink($previd);
		$linktag = "<a class=\"$class\" href=\"$link\">$text</a>";
		return $linktag;
	}
}

function wpicasa_album_link(){
	echo wpicasa_get_album_link();
}

function wpicasa_get_album_link($text=false,$class='albumlink'){
	global $wpicasa;
	if (!$text) $text = $wpicasa->albumname;
	$url = $wpicasa->albumurl;
	$currentpage = $wpicasa->albumpage;
	$qpre = (strpos($url,'?')) ? '&' : '?' ;
	if(get_settings('wpicasa_albumrewritestructure')){
		$url = ($wpicasa->albumpagetotal > 1) ? $url."page/$currentpage/" : $url ;
	}else{
		$url = ($wpicasa->albumpagetotal > 1) ? $url.$qpre."paged=$currentpage" : $url ;
	}
	$link = "<a href=\"$url\" class=\"$class\" >$text</a>";
	
	return $link;
}

function wpicasa_initialize($posts){
	global $wpicasa;
	if(count($posts) === 1){
		$postid = $posts[0]->ID;
		if(get_post_meta($postid,'wpicasa_imagearray',true) || get_post_meta($postid,'wpicasa_imgfilename',true)){
			$wpicasa = new WPicasa($postid);
			$wp_query->is_single = true;
		}
	}
	return $posts;
}

function wpicasa_photo_count(){

	echo wpicasa_get_photo_count();

}

function wpicasa_get_photo_count(){
	global $wpicasa;
	return $wpicasa->totalthumbs;
}

function wpicasa_autogallery($content){
	global $wpicasa, $post, $posts;
	
	if(!empty($post->post_password)){//if there's a password
		if(stripslashes($_COOKIE['wp-postpass_'.COOKIEHASH]) != $post->post_password){
			return $content;
		}
	}
	
	if($wpicasa && count($posts) === 1){
	
		if($wpicasa->isthumbs){
			//construct the thumnail page
			$menu = wpicasa_get_thumbpage_menu();
			$thumbnails = wpicasa_get_thumbnails();
			if($menu) $menu = "<div class=\"wpicasamenu\"><ul><li class=\"menulabel\">Page:</li>$menu</ul></div>\n";
			if($thumbnails) $thumbnails = "<div class=\"wpicasathumbnails\"><ul>$thumbnails</ul><div style=\"clear: both; height: 10px;\"></div></div>\n";
			$content = $menu.$thumbnails.$menu.$content;
			
		}elseif($wpicasa->isphoto){
			//putout the photo
			$previous = wpicasa_get_previous_photo_link();
			$next = wpicasa_get_next_photo_link();
			$album = wpicasa_get_album_link();
			
			if ($previous || $album || $next) $menu = "<div class=\"wpicasamenu\">$previous | $album | $next</div>";
			
			if($photo = wpicasa_get_photo()) $photo = "<div class=\"wpicasaphoto\">$photo</div>";;
			$content = $menu.$photo.$menu.$content;
		}
	
	}
	
	return $content;
}

function wpicasa_archivethumb($content){
	global $id, $wpicasa, $post, $posts;
	
	if(!empty($post->post_password)){//if there's a password
		if(stripslashes($_COOKIE['wp-postpass_'.COOKIEHASH]) != $post->post_password){
			return $content;
		}		
	}
	
	if(get_post_meta($id, 'wpicasa_imagearray',true)){
		if(!$wpicasa){
		
			$wpicasa = new WPicasa($id);//wpicasa only automaticcaly called on a single post
			$thumbs = wpicasa_get_thumbnails(false,get_settings('wpicasa_archivethumbsperpost'));
			$albumlink= get_permalink();
			$total = wpicasa_get_photo_count();
			$label = ($total > 1 || $total != 0) ? 'photos' : 'photo' ; 
			if($thumbs) $thumbs .= "<li class=\"wpicasaalbumlink\"><a href=\"$albumlink\"/>$total $label &raquo;</a></li>";
			if($thumbs) $content = "<ul class=\"wpicasapreview\">$thumbs</ul>\n$content";
			$wpicasa = false;
		}
	}
	return $content;
}

add_action('the_posts','wpicasa_initialize');
add_action('the_content','wpicasa_autogallery');//this will be the function that will be turned on/off with the autogallery option
add_action('the_content','wpicasa_archivethumb');//this will be the function that adds thumbs to the archive/summary posts
/*------------------------------------------------------------------------------------
	WPicasa
------------------------------------------------------------------------------------*/


class WPicasa {

	var $iswpicasa; // boolean determine if this is relevant to the post
	var $albumid;
	var $albumname;//name of the gallery post
	var $albumurl;//the url of the gallery
	var $albumpage;//the current thumb page being viewed or the thumbpage the photo being viewed is on
	var $albumfolder;//the folder for this Picasa Export
	var $albumpath;//the path to the imagefolder
	var $imagearray;//the images in the gallery with their respective id's
	var $totalthumbs; //total number of images
	var $thumbsperpage;
	var $albumpagetotal;//the number of pages that we'll have thumbs/thumbperpage
	var $isthumbs;//true|false if it is a thumbnail page
	var $isphoto;//true|false if it is a individual photo page
	
	var $photofilename;//filename of the image
	var $photosrc;//the source for the photo
	var $photoindex;//index of the image array
	var $photoid;//post id for the photo
	var $phototitle;
	var $thumblargestside;

	var $wpicasafolder;
	
	function WPicasa($postid){
		//first try to get photoid or albumid
		$this->wpicasafolder = rawurlencode(get_settings('wpicasa_folder'));
		if($this->albumid = get_post_meta($postid,'wpicasa_album',true)){
			$this->photoid = $postid;
			$this->isphoto = true;
			$this->isthumbs = false;
			$this->iswpicasa = true;
			$this->get_photoInfo();
			
		}elseif(get_post_meta($postid,'wpicasa_albumfolder',true)){
			$this->albumid = $postid;
			$this->isthumbs = true;
			$this->iswpicasa = true;
			$this->get_albumInfo();
		}
			
	}
	
	function get_albumInfo(){
		$id = $this->albumid;
		//we have the id, now we just want to fill in those details
		$this->imagearray = unserialize(get_post_meta($id,'wpicasa_imagearray',true));
		$this->albumname = get_the_title($id);
		$this->albumurl = get_permalink($id);
		$this->albumpage = (!$_GET['paged']) ? 1 : $_GET['paged'];
		$this->albumfolder = rawurlencode(get_post_meta($id,'wpicasa_albumfolder',true));
		$this->albumpath = get_settings('siteurl').'/wp-content/'.$this->wpicasafolder.'/'.$this->albumfolder.'/';
		$this->totalthumbs = count($this->imagearray);
		$this->thumbsperpage = get_settings('wpicasa_thumbsperpost');
		$this->albumpagetotal = ceil($this->totalthumbs/$this->thumbsperpage);
		$this->getThumbSize();
	}
	
	function get_photoInfo(){
		$this->get_albumInfo();
		$id = $this->photoid;
		$this->photofilename = rawurlencode(get_post_meta($id,'wpicasa_imgfilename',true));
		$this->photosrc = $this->albumpath.'images/'.$this->photofilename;
		$this->phototitle = get_the_title($id);
		for($i=0;$i<$this->totalthumbs;$i++){
			if($this->imagearray[$i][1]==$id){
				$this->photoindex = $i;
				break;
			}
		}
		$this->albumpage = ceil(($this->photoindex + 1)/$this->thumbsperpage);		
	}
	
	function getThumbSize(){	
		$thumbsrc = $this->albumpath.'thumbnails/'.$this->imagearray[0][0];
		$thumbsize = getimagesize($thumbsrc);
		$this->thumblargestside = ($thumbsize[0] >= $thumbsize[1]) ? $thumbsize[0] : $thumbsize[1] ;	
	}
	
	/*------------------------------------------------------
	CSS For Autogallery
	--------------------------------------------------------*/
	
	function gallery_css(){
		global $wpicasa;
		$thumbsize = ($wpicasa->thumblargestside + 10) . 'px';
		echo "<style>
		ul.wpicasapreview {
		 float: left;
		 list-style: none !important;
		 padding: 0 !important;
		 margin: 10px !important;
		}
		
		ul.wpicasapreview li{
		 list-style: none !important;
		 padding: 0 !important;
		 margin: 0 !important;
		}
		
		li.wpicasaalbumlink {
		 border-top: 1px solid #ccc;
		}
		
		li.wpicasaalbumlink a {
		 display: block;
		 text-decoration: none;
		 padding: 0 5px;
		 text-align: center;
		 background: #eee;
		}

		.wpicasamenu {
		 clear: both;
		 padding: 3px 0;
		 text-align: center;
		}
		
		.wpicasamenu .selected {
		 font-weight: bold;
		}
		
		.wpicasamenu ul {
		 list-style: none !important;
		 padding: 0 !important;
		 margin: 0 !important;
		}
		
		.wpicasamenu li {
		 display: inline;
		 margin: 3px;
		}
		
		.wpicasathumbnails {
		 text-align: center;
		}

		.wpicasathumbnails ul {
		 padding: 0 !important;
		 margin: 0 !important;
		 list-style: none;
		}
		
		.wpicasathumbnails li {
		 float: left;
		}
		
		.wpicasathumbnails a {
		 display: block;
		 width: $thumbsize;
		 height: $thumbsize;
		 text-align: center;
		}
		
		.wpicasathumbnails img {
		 display: block;
		 padding: 0;
		 border: 0;
		 margin: auto;
		}
		
		.wpicasaphoto img {
		 display: block;
		 margin: auto;
		}

		</style>";		

	}
	
	function photoPermalink($url, $post){//Total ripoff of get_permalink, wish I didn't have to copy it
		global $wpdb;
		$postid = $post->ID;
		if($photoalbum = get_post_meta($postid,'wpicasa_album',true)){
			if(($translate = get_settings('wpicasa_photorewritestructure')) && get_settings('permalink_structure')){
				//find and replace
				$url=WPicasa::permalinkTranslate($post->ID);
			}else{
				$url = get_settings('siteurl').'?wpicasaphoto=true&amp;p='.$postid;
			}
		}elseif($photoalbum = get_post_meta($postid,'wpicasa_albumfolder',true)){
			if(($translate = get_settings('wpicasa_albumrewritestructure')) && get_settings('permalink_structure')){
				$url = WPicasa::permalinkTranslate($post->ID);
			}else{
				$url = get_settings('siteurl').'?wpicasaalbum=true&amp;p='.$postid;
			}
		}
		return $url;
	}
	
	function permalinkTranslate($postid){//we know its a photo/album and we're using rewrite
		$rewritecode = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			'%postname%',
			'%post_id%',
			'%category%',
			'%author%',
			'%pagename%',
			'%photoname%',
			'%albumname%',
			'%photo_id%',
			'%album_id%'
		);
		
		$post = & get_post($postid);
		if($albumid = get_post_meta($post->ID,'wpicasa_album',true)){//photo
			$permalink = get_settings('wpicasa_photorewritestructure');
			$albumdata = & get_post($albumid);
			$albumname = $albumdata->post_name;
			$photoname = $post->post_name;
			$photoid = $post->ID;
		}elseif(get_post_meta($post->ID,'wpicasa_albumfolder',true)){//album
			$permalink = get_settings('wpicasa_albumrewritestructure');
			$albumname = $post->post_name;
			$albumid = $post->ID;
			$photoname = false;
			$photoid = false;
		}
		$unixtime = strtotime($post->post_date);
		if(strstr($permalink, '%category%')) {
			$cats = get_the_category($post->ID);
			$category = $cats[0]->category_nicename;
			if($parent=$cats[0]->category_parent) $category = get_category_parents($parent, FALSE, '/', TRUE) . $category;
		}
		
		$authordata = get_userdata ($post->post_author);
		$author = $authordata->user_nicename;
		$rewritereplace = 
		array(
			date('Y', $unixtime),
			date('m', $unixtime),
			date('d', $unixtime),
			date('H', $unixtime),
			date('i', $unixtime),
			date('s', $unixtime),
			$post->post_name,
			$post->ID,
			$category,
			$author,
			$post->post_name,
			$photoname,
			$albumname,
			$photoid,
			$albumid
		);
		
		return get_settings('home').str_replace($rewritecode, $rewritereplace, $permalink);
		
	}
	
	function customTemplate(){
		global $posts, $wpicasa;
		if(is_single() && get_post_meta($posts[0]->ID, 'wpicasa_imagearray', true) && ($template = get_settings('wpicasa_gallerytemplate')) && !$_GET['feed']){
			include(TEMPLATEPATH."/$template");
			exit();
		}elseif(is_single() && get_post_meta($posts[0]->ID, 'wpicasa_imgfilename', true) && ($template = get_settings('wpicasa_phototemplate')) && !$_GET['feed']){
			include(TEMPLATEPATH."/$template");
			exit();
		}elseif($wpicasa && get_single_template() && !$_GET['feed']){
			include(get_single_template());
			exit();
		}
		
	}
	
	function photofilter($where){//this will exclude photo posts from the chronological stuff remove if we're looking for a photo, we may actually have to change the where to include object posts that have our meta
		global $wpdb, $wp_query;
		extract($_GET);
		$pm = $wpdb->postmeta;
		$pt = $wpdb->posts;
		if(($wpicasaalbum || $wpicasaphoto)){
			if($wpicasaphoto){
				//this is where we we figure out if we need the album id
				if($albumname || $albumid){
					if (!$albumid) $albumid = WPicasa::findAlbumID($albumname);
					$where .= " AND $pm.meta_key = 'wpicasa_album' AND $pm.meta_value=$albumid";
				}
				
				$where .= " AND $pt.post_status = 'object'";
			}
		}else{
			$where .= " AND (($pm.meta_key != 'wpicasa_imgfilename' AND $pm.meta_key != 'wpicasa_album' AND $pm.meta_key != 'wpicasa_folder') OR $pm.meta_key IS NULL) ";
		}
		//echo $where;
		return $where;
	}

	function joinmeta($join){
		global $wpdb;
		extract($_GET);
		$pm = $wpdb->postmeta;
		$pt = $wpdb->posts;
		if(($wpicasaalbum || $wpicasaphoto)){
			$join .= " JOIN $pm ON $pm.post_id = $pt.ID ";
		}else{
			$join .= " LEFT JOIN $pm ON $pm.post_id = $pt.ID ";
		}
		return $join;
	}
	
	function findAlbumID($albumname){
		global $wpdb;
		$id=$wpdb->get_results("SELECT $wpdb->posts.ID FROM $wpdb->posts, $wpdb->postmeta WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = 'wpicasa_albumfolder' AND $wpdb->posts.post_name = '$albumname'; ");
		return $id[0]->ID;
	}

}

if(get_settings('wpicasa_autogallery')=='on' && get_settings('wpicasa_css')=='on') add_action('wp_head',array('WPicasa','gallery_css'));
add_filter('post_link',array('WPicasa','photoPermalink'),9,2);
add_filter('template_redirect',array('WPicasa','customTemplate'));
add_filter('posts_where',array('WPicasa','photofilter'));
add_filter('posts_join',array('WPicasa','joinmeta'));

/*------------------------------------------------------------------------------------
	WPicasaReader
------------------------------------------------------------------------------------*/
class WPicasaReader{

	var $parser;
	var $depth;
	var $albumname;
	var $albumcaption;
	var $albumcount;
	var $albumsize;
	var $images;
	var $imagecaptions;
	var $currentnode;
	var $path;
	var $wpicasa_album;
	var $exists;
	var $iswpicasadirectory;
	var $hosttype;//internal vs. external
	
	function WPicasaReader($album, $type='internal'){
		
		//$this->path = ABSPATH.'wp-content/'.get_settings('wpicasa_folder').'/'.$album.'/';
		
		if($type=='internal'){
			$this->path = get_settings('siteurl').'/wp-content/'.rawurlencode(get_settings('wpicasa_folder')).'/'.rawurlencode($album).'/';
			$this->wpicasa_album = $album;
		}elseif($type=='external'){
			$this->path = $album;
			$this->wpicasa_url = $album;
		}
		$this->initiateParser();	
		$this->streamData($this->path.'index.xml');
		$this->releaseParser();
		//$this->setAlbumSize();
		$this->checkAlbum();

	}
	
	function initiateParser(){//creates the parser and registers the functions to be used by the parser

		$this->depth = 0;
		$this->parser = xml_parser_create();
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING,false);
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser,'openTag','closeTag');
		xml_set_character_data_handler($this->parser,'readData');
	
	}
	
	function releaseParser(){
		xml_parser_free($this->parser);
	}
	
	function openTag($parser, $tag, $attributes){//fired off when a new node is opened
	
		$this->depth ++;
		$this->currentnode = $tag;
	
	}
	
	function closeTag($parser, $tag){//fired off when a node is closed
	
		$this->depth --;
		$this->currentnode = false;
	
	}
	
	function readData($parser, $cdata){//fired off when cdata hits the parser
		
		$cdata = trim($cdata);
		if($cdata){
			if($this->currentnode == 'albumName') $this->albumname = $cdata;
			if($this->currentnode == 'albumItemCount') $this->albumcount = $cdata;
			if($this->currentnode == 'itemName') $this->images[] = $cdata;
			if($this->currentnode == 'itemCaption') $this->imagecaptions[] = $cdata;
			if($this->currentnode == 'albumCaption') $this->albumcaption = $cdata;
		}
		
	}
	
	function streamData($datasource){//open the xml file and stream 'er on in
	
		if(@fopen($datasource,"r")){
			$this->iswpicasadirectory = true;
			if($stream = fopen($datasource, "r")){

				while($data = fread($stream, 4096)){

					if(!xml_parse($this->parser, $data, feof($stream))){
						//echo "error with $datasource<br/>";
						$this->iswpicasadirectory = false;
					}

				}

			}

		}else{
		
			$this->iswpicasadirectory = false;
		
		}
	
	}
	
	function setAlbumSize(){
	
		if(is_array($this->images)){
			foreach($this->images as $image){
				$size[] = filesize($this->path.'images/'.$image);
			}
		}
			
		if(is_array($size)) $filesize = array_sum($size);
			
		$units = array(' B',' KB',' MB',' GB',' TB');
		if($filesize){
		
			for($i=0;$filesize>1024;$i++){
				$filesize /= 1024;
			}
	
			$filesize = round($filesize, 2).$units[$i];
		
		}else{
		
			$filesize = '0';
		}
		
		$this->albumsize = $filesize;
	}

	function checkAlbum(){
		global $wpdb;
		$test = $wpdb->get_var("SELECT post_ID FROM $wpdb->postmeta WHERE meta_key = 'wpicasa_albumfolder' AND meta_value = '$this->wpicasa_album';");
		if($test){
			$this->exists = true;
		}else{
			$this->exists = false;
		}
	}

	function publishAlbum($status='publish', $password=FALSE, $slug=FALSE){
		global $user_ID, $wpdb;
		if(!$this->exists){
		
			//first we're going to create each photo post

		
			$post['post_title']= addslashes($this->albumname);
			$post['post_content']= addslashes($this->albumcaption);
			$post['post_status']=$status;
			$post['post_category']=array(1);
			$post['post_author']=$user_ID;
			if($slug) $post['post_name'] = $slug;

			for($i=0;$i<count($this->images);$i++){
				$photoid = $this->publishPhoto($i);
				$photoids[] = $photoid;
				$imagearray[] = array($this->images[$i],$photoid);
			}	

			$albumid = wp_insert_post($post);

			foreach($photoids as $photo){
				add_post_meta($photo,'wpicasa_album',$albumid, true);
			}
			
			
			add_post_meta($albumid,'wpicasa_albumfolder',$this->wpicasa_album,true);
			add_post_meta($albumid,'wpicasa_imagearray',addslashes(serialize($imagearray)),true);
			
			if($password) $wpdb->query("UPDATE $wpdb->posts SET post_password = '$password' WHERE ID = $albumid LIMIT 1;");
			
			return true;
			
		}else{
			return false;
		}
		
	}
	
	function publishPhoto($i){
		global $user_ID;
		$post['post_title'] = addslashes($this->images[$i]);//($this->imagecaptions[$i]) ? addslashes($this->imagecaptions[$i]) : $this->images[$i];
		$post['post_author'] = $user_ID;
		$post['post_status'] = 'object';
		$post['post_category'] = array(1);
		$post['post_content'] = addslashes($this->imagecaptions[$i]);	
		$photoid = wp_insert_post($post);
		add_post_meta($photoid,'wpicasa_imgfilename',addslashes($this->images[$i]), true);
		add_post_meta($photoid,'wpicasa_folder',addslashes($this->wpicasa_album), true);
		return $photoid;
	
	}

	function previewThumbs(){
	
		$imgpath = $this->path.'thumbnails/';
		$imgs = $this->images;
		$captions = $this->imagecaptions;
		$index = 0;
		if($imgs):foreach($imgs as $img){
		
			$alt= $imgsrc;
			$title = $captions[$index];
			$title = ($title != '') ? " title=\"$title\" " : '';
			$imgsrc = $imgpath.$img;
			$imgsize = getimagesize($imgsrc);
			$li = "\n\t<li><img $title src=\"$imgsrc\" $imgsizep[3]/></li>";
			
			$list .= $li;
			$index ++;
		
		}endif;
		
		if($list) $list = "<ul class=\"wpicasapreview\">\n$list</ul>";
		
		echo $list;
	}
	
	function deleteAlbum(){
		unlink($path);
	}

}
/*------------------------------------------------------------------------------------
	WPicasaAdmin
------------------------------------------------------------------------------------*/
class WPicasaAdmin {

	function adminmenu(){

		add_options_page('WPicasa','WPicasa',9,'wpicasa.php',array('WPicasaAdmin', 'options'));
		add_management_page('WPicasa','WPicasa',5,'wpicasa.php',array('WPicasaAdmin', 'router'));

	}
	
	function folderList($folder=false){
		
		if($folder===false){
			$folder = get_settings('wpicasa_folder');
		}
	
		$dir = ABSPATH.'wp-content/'.$folder.'/';
		$dh = opendir($dir);
		while(false !== ($filename = readdir($dh))) {
		
			if(is_dir($dir.$filename) && $filename != '.' && $filename != '..'){
				$files[] = $filename;
			}
		}
		
		if(is_array($files)){
			sort($files);
		}
		
		return $files;	
	}
	
	function usedFolders(){
	
		global $wpdb;
		$folders = $wpdb->get_col("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'wpicasa_albumfolder';");
		return $folders;
	}
	
	function unusedFolders(){
	
		$list = WPicasaAdmin::folderList();
		$used = WPicasaAdmin::usedFolders();
		if(is_array($list)){//if there are folders
		
			if(is_array($used)){
				foreach($list as $f){
					if(!in_array($f, $used)) $folders[] = $f;
				}
			}else{
				$folders = $list;
			}
			
		}
		
		return $folders;
	}
	
	
	function settings(){
	
		//Default settings for WPicasaPlugin --- (name/required[boolean]/default value/)
		$settings[] = array('wpicasa_folder',true);
		$settings[] = array('wpicasa_thumbsperpost', true, 20);
		$settings[] = array('wpicasa_gallerytemplate',false);
		$settings[] = array('wpicasa_phototemplate',false);
		$settings[] = array('wpicasa_archivethumbsperpost',true,1);
		$settings[] = array('wpicasa_autogallery',true,'on');
		$settings[] = array('wpicasa_css',true,'on');
		return $settings;
	
	}
	
	function updateOptions(){
	
		$settings = WPicasaAdmin::settings();

		foreach($settings as $setting){
			if ($setting[1] && get_settings($setting[0])===false){
				update_option($setting[0],$setting[2]);
			}
		}
	
	}
	
	function checkRequiredOptions(){
	
		$settings = WPicasaAdmin::settings();
		$run = true;
		foreach($settings as $setting){
		
			if(get_settings($setting[0])===false && $setting[1]){
				$run = false;			
			}
		
		}
		
		return $run;
	
	}
	
	function albumQueue($e = true){//create two links now, an autopublish link, and a publish w/options link
	
		$albums = WPicasaAdmin::unusedFolders();
		
		if($albums){
			foreach($albums as $album){
				
				$tempalbum = new WPicasaReader($album);
				if(!$tempalbum->exists && $tempalbum->iswpicasadirectory){
					$autopublish = "?page=$_GET[page]&mode=albums&action=publish&album=$album";
					$optionpublish = "?page=$_GET[page]&mode=prepublish&album=$album";
					$previewlink = "?page=$_GET[page]&mode=preview&album=$album";
					$photolabel = ($tempalbum->albumcount==1) ? 'Photo':'Photos';
					$queue .= "
					<li><div class=\"infobox\"><a href=\"$previewlink\" title=\"Preview thumbnails for $tempalbum->albumname\"><strong>$tempalbum->albumname</strong></a><br/><small>$tempalbum->albumcount $photolabel in $tempalbum->wpicasa_album</small></div>
						<div class=\"publishbox\">Publish &raquo;
						<a href=\"$autopublish\" class=\"albumautopublish\" title=\"Publish $tempalbum->albumname directly to blog\">Auto</a>
						<a href=\"$optionpublish\" class\"albumoptionpublish\" title=\"Configure post-specific options before posting $tempalbum->albumname\">Options</a></div>
					</li>";
	
				}			
			}
		}
		
		if ($queue) $queue = "<div class=\"albumqueue\"><h2>Queued Albums</h2> <ul>\n$queue\n</ul><hr /></div>";
		
		if($e){
			echo $queue;
		}else{
			return $queue;
		}
	}
	
	function albumlist($m=false){
		//this will generate the latest x number of albums, similar to manage-posts view, but grid style
	}
	
	function albumUnzip($filename){
		$extract_dir = ABSPATH.'wp-content/'.get_settings('wpicasa_folder').'/';
		$zipfile = $extract_dir."$filename";
		WPicasaAdmin::unzip($extract_dir, $zipfile);
		
	}
	
	function zipcheck($zipfile){
		if($zip = @zip_open($zipfile)){
			$zip_entry = zip_read($zip);
			$nametocheck = explode('/',zip_entry_name($zip_entry));
			if($folderlist = WPicasaAdmin::folderList()){
				if(in_array($nametocheck[0],$folderlist)){
					return $nametocheck[0];
				}else{
					return false;
				}
			}else{
				return false;
			}
		}else{
			return "+ziperror";
		}
	}
	
	function unzip($dir,$zipfile,$rootreplace=false) {
		if ($zip = zip_open($zipfile)) {
	  		while($zip_entry = zip_read($zip)){
	  			if(zip_entry_open($zip, $zip_entry, 'r')){
					$tmp_path = zip_entry_name($zip_entry);
					if($rootreplace){
						$tmp_ar = explode('/',$tmp_path);
						$tmp_ar[0] = $rootreplace;
						$tmp_path = implode('/',$tmp_ar);
					}
					$tmp_dir = dirname($tmp_path);
					$tmp_filename = basename($tmp_path);
					$filestocheck = explode('/',$tmp_dir);
					$i = 0;
					foreach($filestocheck as $file){						
						if(!is_dir($dir.$uplevel.$file)){
							mkdir($dir.$uplevel.$file);
						}
						$uplevel .= $file.'/';
						$i++;
					}
					$uplevel = false;

					if(strpos($tmp_filename,'.')){
						$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
						$fp = fopen($dir.$tmp_path,'w');
						fwrite($fp, $buf);
						zip_entry_close($zip_entry);
					}
	  			}else{
	  				echo "no name";
	  			}
	  		}	  		
		}else{
			echo "no zip";
		}
	}
	
	function router(){//this determines which screen to view
		$mode = $_GET['mode']; //this is the "view"
		$checkrun = WPicasaAdmin::checkRequiredOptions();//determines if WPicasa is configured
		
		if($checkrun){

		//run the update function that interperates POST/GET variables, possibly change mode
		
			if($_GET['action'] == 'publish'){
				$album = new WPicasaReader($_GET['album']);
				if($album->publishAlbum()) echo '<div class="updated"><p><strong>Album Published.</strong></p></div>';
				//echo updated;
			}
			
			if($_POST['action'] == 'publishwithoptions' && !$_POST['wpicasa_cancel']){
				$album = new WPicasaReader($_POST['album']);  
				if($album->publishAlbum($_POST['post_status'], $_POST['postpassword'], $_POST['postslug'])) echo '<div class="updated"><p><strong>Album Published.</strong></p></div>';
				//echo update;
			}
			
			if($_POST['action'] == 'compressedalbum'){
				$uploaded_file = $_FILES['wpicasaziparchive'];
				$wpicasa_path = ABSPATH.'wp-content/'.get_settings('wpicasa_folder').'/';
				if($uploaded_file['error'] === 0){
					if($foldername = WPicasaAdmin::zipcheck($uploaded_file['tmp_name'])){
						if($foldername != '+ziperror'){
							$i = 1;
							$new_foldername = $foldername.'_'.$i;
							while(in_array($new_foldername, WPicasaAdmin::folderList())){
								$i ++;
								$new_foldername = $foldername.'_'.$i;
							}
							WPicasaAdmin::unzip($wpicasa_path, $uploaded_file['tmp_name'],$new_foldername);
							echo "<div class=\"updated\"><p><strong>Unzipped album to <em>$new_foldername</em></strong></p></div>";
						}else{
							echo "<div class=\"error\"><p><strong>Wrong File Type</strong> - uploaded file not a zip file.</p></div>";
						}
					}else{
						WPicasaAdmin::unzip($wpicasa_path, $uploaded_file['tmp_name']);
						echo "<div class=\"updated\"><p><strong>Album successfully unzipped.</strong></p></div>";
					}
				}else{
					if($uploaded_file['error']==2){
						echo '<div class="error"><p><strong>Error</strong> The file your are trying to upload is too large. You can change this setting on the <a href="options-misc.php">miscellaneous options panel</a>.</p></div>';
					}else{
						echo "<div class='error'><p><strong>Error</strong> There was a problem uploading your file [error code $uploaded_file[error]].</p></div>";
					}
				}
			}
			
			if(!$mode){
				$mode = 'albums';//default view
			}
			
		}else{
			$mode = 'setup';
		}
				
		if($mode == 'setup'){
			//method that says to configure the options	
		}elseif($mode == 'albums'){
			WPicasaAdmin::managealbumsMode();//method that displays published and unpublished albums
		}elseif($mode == 'upload'){
			WPicasaAdmin::uploadzipMode();//method that displays the upload zip tool
		}elseif($mode == 'external'){
			WPicasaAdmin::externalMode();//method that displays external album tool
		}elseif($mode == 'prepublish'){
			WPicasaAdmin::prepublishMode();//method to display options before publishing
		}elseif($mode == 'preview'){
			WPicasaAdmin::previewMode();//preview thumbnails before publishing album
		}
	}
	
	function managemenu($mode, $e = true){
		$menus[] = array('Photo Albums','albums');
		$menus[] = array('Upload Zip','upload');
		//$menus[] = array('External Album','external');
		
		foreach($menus as $menu){
			if($mode == $menu[1]){
				$setclass = 'class="selected"';
			}else{
				$setclass = ' ';
			}
		
			$menu_ul .= "<li $setclass><a href=\"?page=$_GET[page]&amp;mode=$menu[1]\">$menu[0]</a></li>";
		}
		
		if($menu_ul) $menu_ul = "<ul class=\"wpicasatools\">$menu_ul</ul>";
		
		if($e){
			echo $menu_ul;
		}else{
			return $menu_ul;
		}
	}
	
	function managealbumsMode(){
	
	?>
	<div class="wrap clearwrap">
	<?php WPicasaAdmin::managemenu('albums');?>
		<div class="wpicasamain">
	<?php WPicasaAdmin::albumQueue() ?>
	<?php WPicasaAdmin::albumList($_GET['m']) ?>
		</div><!-- end wpicasamain -->
	</div><!-- end wrap -->
	<?php
	}

	function uploadzipMode(){
		if(function_exists('zip_open')):
		?>
		<div class="wrap clearwrap">
		<?php WPicasaAdmin::managemenu('upload') ?>
		<div class="wpicasamain">
			<h2>ZIP File Upload</h2>
			<form enctype="multipart/form-data" method="post" action="?page=<?php echo $_GET['page'];?>">
			<input type="hidden" name="action" value="compressedalbum" />
			<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo get_settings('fileupload_maxk')*1024;?>" />
			<fieldset class="options" style="float: left; clear: both;">
				<legend>ZIP File</legend>
				<input type="file" name="wpicasaziparchive" id="wpicasaziparchive" />
				<p>Max File Upload Size: <strong><?php echo round(get_settings('fileupload_maxk') / 1024,2);?> <abbr title="Megabytes">MB</abbr></strong> <small>| <a href="options-misc.php?">Change</a></small></p>
			</fieldset>
				<p class="submit" style="float: left; clear: both;"><input style="float: none;" type="submit" name="uploadcompressedalbum" value="Upload Album" /></p>
			</form>
			<hr style="clear:both; visibility: hidden;" />
			<p><strong>Instructions:</strong></p>
			<ol>
				<li>Create xml photo album in Picasa 2</li>
				<li>Go to Picasa Web Exports folder</li>
				<li>Right-click name of photo album and select "Send To -> compressed file"</li>
				<li>Upload the new *.zip file through this form.</li>
			</ol>
		</div>
		</div>
		<?php else: ?>
		<div class="wrap clearwrap">
			<?php WPicasaAdmin::managemenu('upload'); ?>
			<div class="wpicasamain">
			<p>Your server is not configured to handle extracting ZIP files.</p>
			</div>
		</div>
		<?php endif;
	}

	function externalMode(){
		?>
		<div class="wrap clearwrap">
		<?php WPicasaAdmin::managemenu('external') ?>
			<div class="wpicasamain">
			</div>
		</div>
		<?php
	}
	
	function prepublishMode(){
	
	?>
	<div class="wrap clearwrap">
	<?php WPicasaAdmin::managemenu('albums') ?>
	<div class="wpicasamain">
	<?php
	
			if($_GET['album']){
				$tempalbum = new WPicasaReader($_GET['album']);
				$type = 'internal';
			}elseif($_GET['external_album']){
				$tempalbum = new WPicasaReader($_GET['external_album'],'external');
				$type = 'external';
			}
			
			if($tempalbum->iswpicasadirectory):
			
		?>
		<h2>Custom Plublish: <em><?php echo $tempalbum->albumname;?></em></h2>
		<form method="post" action="edit.php?page=<?php echo $_GET['page'];?>">
			<input type="hidden" name="action" value="publishwithoptions" />
			<input type="hidden" name="album" value="<?php echo $tempalbum->wpicasa_album ;?>" />
			<input type="hidden" name="albumtype" value="<?php echo $type?>" />
		<fieldset class="options">
			<?php if($type=='internal'):?><legend>Post with options</legend><?php endif;?>
			<?php if($type=='external'):?><legend>Post External Album</legend><?php endif;?>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
				<tr>
					<th width="33%" valign="top" scope="row">Post Status:</th>
					<td><select name="post_status">
						<option value="publish">Publish</option>
						<option value="draft">Draft</option>
						<option value="private">Private</option>
					</select></td>
				</tr>
				<tr>
					<th width="33%" valign="top" scope="row">Post Password:</th>
					<td><input type="text" name="postpassword" size="25" /></td>
				</tr>
				<tr>
					<th width="33%" valign="top" scope="row">Post Slug:</th>
					<td><input type="text" value="<?php echo sanitize_title($tempalbum->albumname) ;?>" name="postslug" size="25"/></td>
				</tr>
			</table>
	
			
			<p class="submit"><input type="submit" value="Post" name="wpicasa_optionspublish"/> <input type="submit" value="Cancel" name="wpicasa_cancel" /></p>
		</fieldset>
		</form>
		<?php else: ?>
		<p>Error loading XML file. <a href="?page=<?php echo $_GET['page'];?>">Go back &raquo;</a></p>
	<?php endif;?>
	</div>
	</div>
	<?php
	}

	function previewMode(){
		if($_GET['album']){
			$album = new WPicasaReader($_GET['album']);
			$autolink = "?page=$_GET[page]&mode=albums&action=publish&album=$album->wpicasa_album";
			$optionslink = "?page=$_GET[page]&mode=prepublish&album=$album->wpicasa_album";
			$backlink = "?page=$_GET[page]";
		}elseif($_GET['external_album']){
			$album = new WPicasaReader($_GET['external_album'],'external');
			$autolink = "?page=$_GET[page]&publish=".rawurlencode($album->path)."&type=external";
			$optionslink = "?page=$_GET[page]&action=prepublish&external_album=".rawurlencode($album->path);
			$backlink = "?page=$_GET[page]";
		}
	?>
	<div class="wrap clearwrap">
		<?php WPicasaAdmin::managemenu('albums') ?>
		<div class="wpicasamain">
			<h2>Preview: <em><?php echo $album->albumname;?></em></h2>
				<span class="wpicasaactions">Publish &raquo; <a href="<?php echo $autolink;?>" title="Publish <?php echo $album->albumname;?> directly to blog">Auto</a>
					<a href="<?php echo $optionslink;?>" title="Configure post-specific options before posting <?php echo $album->albumname;?>">Options</a> |
					<a href="<?php echo $backlink;?>" title="Return to album view">Back</a>
				</span>
		<?php $album->previewThumbs();?>
		</div>
	</div>
	<?php
	
	}
	
	function options(){

		if($_POST['wpicasa_setup_form']){
			$path = ABSPATH.'wp-content/';
			if($folder = $_POST['wpicasa_folder']){
				if(is_dir($path.$folder)){
					if(update_option('wpicasa_folder',$folder)) echo "<div class=\"updated\"><p>Folder Saved - to start using WPicasa, upload your album exports into this folder:<br/><em>$path$folder/</em></p></div>";
				}else{
					echo "<div class=\"updated\">The folder that you chose does not exist, please select a different folder or create a new one.</div>";
				}
			}else{
				if($folder = $_POST['wpicasa_newfolder']){
					if(!is_dir($path.$folder)){
						if(mkdir($path.$folder)){
							update_option('wpicasa_folder',$folder);
							echo "<div class=\"updated\"><p><strong>Directory Created</strong> - to start using WPicasa, upload your album exportsto this folder:<br/><em>$path$folder/</em></p></div>";
						}else{
							echo "<div class=\"updated\"><p><strong>Alert</strong> - WPicasa was not able to create the new directory.</p></div>";
						}
					}else{
						echo "<div class=\"updated\"><p><strong>Error</strong> - The folder you chose to create (<code>$folder</code>) already exists within the <code>wp-content</code> directory.</p></div>";
					}
				}else{
					echo "<div class=\"updated\"><p><strong>Alert</strong> - the folder name you chose is not valid.</div>";
				}
			}
		}

		$run = WPicasaAdmin::checkRequiredOptions();
		
		if($_POST['wpicasa_autogallery_form']){
		
			$optionstoset = array(
					'wpicasa_autogallery',
					'wpicasa_thumbsperpost',
					'wpicasa_archivethumbsperpost',
					'wpicasa_css'
				);
		
			foreach($optionstoset as $option){
				
				$value = $_POST[$option];
				if(($option == 'wpicasa_autogallery' || $option == 'wpicasa_css' ) && !$_POST[$option]) $value = 'off';
				update_option($option, $value);
			
			}
			
			echo '<div class="updated"><p>Autogallery settings saved.</p></div>';
			WPicasaAdmin::updateOptions();//automatically sets default options for required options if removed
		}
		
		if($_POST['wpicasa_template_form']){
			$optionstoset = array(
				'wpicasa_gallerytemplate',
				'wpicasa_phototemplate'
			);
			
			foreach($optionstoset as $option){
				$value = $_POST[$option];
				update_option($option, $value);
			}
			
			echo '<div class="updated"><p><strong>Template settings saved.</strong></p></div>';
			WPicasaAdmin::updateOptions();//automatically sets default options for required options if removed
			
		}	
		
		if($_POST['wpicasa_rewrite_form']){
			$optionstoset = array(
				'wpicasa_albumrewritestructure',
				'wpicasa_photorewritestructure'
			);
			
			foreach($optionstoset as $option){
				$value = $_POST[$option];
				update_option($option, $value);
			}
			save_mod_rewrite_rules();
			echo '<div class="updated"><p><strong>Permalink settings saved.</strong></p></div>';
			WpicasaAdmin::updateOptions();
		}
		
		switch ($run):
		
			case true;

	?>
	<div class="wrap">

	<h2>WPicasa Options</h2>
		<form id="wpicasa_rewrite_form" method="post">
		<input type="hidden" name="wpicasa_rewrite_form" value="true" />
		<fieldset class="options">
			<legend>Permalink Options</legend>
			<p>These permalink settings use the same conventions as the regular permalink settings.</p>
			<p>The <strong>Album Permalink</strong> applies to the main photo album post for each Picasa album. Instead of using <em>%postname%</em> and <em>%post_id%</em>  use <em>%albumname%</em> and <em>%albumid%</em>, the rest are the same as regular permalinks.</p>
			<dl>
				<dt>Structure: /photoalbums/%albumname%/</dt>
				<dd><strong>Result:</strong> <?php echo get_settings('home');?>/photoablums/album-name/</dd>
			</dl>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
					<tr>
						<th width="33%" valign="top" scope="row">Album Permalink:</th> 
						<td>
							<input type="text" name="wpicasa_albumrewritestructure" value="<?php echo get_settings('wpicasa_albumrewritestructure');?>" size="50" maxlength="100"/><br/>
						</td>
					</tr>
				</table>
			<p>For the <strong>Single Photo Permalink</strong> setting you can use <em>%albumname%</em> and <em>%album_id%</em>, as well as <em>%photoname%</em> and <em>%photoid%</em>.
			<dl>
				<dt>Structure: /photoalbums/%albumname%/%photoname%/</dt>
				<dd><strong>Result:</strong> <?php echo get_settings('home');?>/photoablums/name-of-album/name-of-photo/</dd>
			</dl>			
				<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
					<tr>
						<th width="33%" valign="top" scope="row">Single Photo Permalink:</th> 
						<td>
							<input type="text" name="wpicasa_photorewritestructure" value="<?php echo get_settings('wpicasa_photorewritestructure');?>" size="50" maxlength="100"/><br/>
						</td>
					</tr>					
				</table>
				<p class="submit"><input type="submit" name="wpicasa_submit" value="Save Rewrite Structure" /></p>
		</fieldset>
		
	</form>
	<form id="wpicasa_autogallery_form" method="post">
		<input type="hidden" name="wpicasa_autogallery_form" value="true" />
		<fieldset class="options">
			<legend>Photo Display </legend>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
					<tr>
						<th width="33%" valign="top" scope="row"><label for="autogallery_cb">Autogallery:</label></th> 
						<td>
							<input type="checkbox" name="wpicasa_autogallery" value="on" id="autogallery_cb" <?php if(get_settings('wpicasa_autogallery')=='on') echo 'checked="checked"' ;?> /><br/>
							<small>Automatically display thumbnails and photos in posts.</small>
						</td>
					</tr>					<tr>
						<th width="33%" valign="top" scope="row">Thumbnails Per Page:</th> 
						<td>
							<input type="text" name="wpicasa_thumbsperpost" value="<?php echo get_settings('wpicasa_thumbsperpost');?>" size="2" maxlength="2"/><br/>
							<small>Splits thumbnails into pages of chosen amount.</small>
						</td>
					</tr>
					<tr>
						<th width="33%" valign="top" scope="row">Thumbnails Per Summary/Archive:</th> 
						<td>
							<input type="text" name="wpicasa_archivethumbsperpost" value="<?php echo get_settings('wpicasa_archivethumbsperpost');?>" size="2" maxlength="2"/><br/>
							<small>Thumbnails to show when viewing more than one post or viewing post excerpt.</small>
						</td>
					</tr>
					<tr>
						<th width="33%" valign="top" scope="row">Use Default Styling (CSS):</th> 
						<td>
							<input type="checkbox" name="wpicasa_css" <?php if(get_settings('wpicasa_css')=='on') echo 'checked="checked"' ;?> value="on"/><br/>
							<small>Use default WPicasa <acronym title="Cascading Style Sheets">CSS</acronym></small>
						</td>
					</tr>
					</table>
				<p class="submit"><input type="submit" name="wpicasa_submit" value="Save Photo Display Settings" /></p>
		</fieldset>
		
	</form>
		<form name="wpicasa_presentation" method="post" action="">
		<input type="hidden" name="wpicasa_template_form" value="true" />
		<fieldset class="options">
			<legend>Templates</legend>
				<p style="text-align: center; border-bottom: 1px solid #ccc; padding-bottom: 5px;">When using custom templates with WPicasa template tags, it is recommended to turn off the Autogallery option.</p>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
					<tr>
						<th width="33%" valign="top" scope="row">Photo Album Template:</th> 
						<td>
							<select name="wpicasa_gallerytemplate">
							<option value=""></option>
							<?php page_template_dropdown(get_settings('wpicasa_gallerytemplate'));?>
							</select><br/>
							<small>Choose a custom template for WPicasa posts.</small>
						</td>
					</tr>
					<tr>
						<th width="33%" valign="top" scope="row">Single Photo Template:</th> 
						<td>
							<select name="wpicasa_phototemplate">
							<option value=""></option>
							<?php page_template_dropdown(get_settings('wpicasa_phototemplate'));?>
							</select><br/>
							<small>Use a custom template to view single photos.</small>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" name="wpicasa_submit" value="Save Template Settings" /></p>
		</fieldset>
	</form>
		
	</div>
	<?php
	
			break;
			
			case false;
	?>
	<div class="wrap">
		<h2>WPicasa - Setup</h2>
		<form method="post" action="">
		<input type="hidden" name="wpicasa_setup_form" value="true" />
		<fieldset class="options">
		<legend>Photo Storage</legend>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
					<tr>
						<th width="33%" valign="top" scope="row">WPicasa Photo Folder:</th> 
						<td>
							<select name="wpicasa_folder" id="wpicasa_folder_dd">
							<?php if($folders = WPicasaAdmin::folderList('')){
							
								foreach($folders as $folder){
									echo "<option value=\"$folder\">$folder</option>";
								}
							
							};?>	
							<option value="">- Create New Folder</option>
							</select><br/>
							<small>Choose the folder inside your <code>wp-content</code> folder to use as your photo album storage.</small>
						</td>
					</tr>
					<tr id="newalbum_row">
						<th width="33%" valign="top" scope="row" id="wpicasa_newfolder_row">New Folder Name:</th>
						<td>
							<input type="text" name="wpicasa_newfolder" id="wpicasa_newfolder_tb" size="20" /><br/>
							<small>If <em>Create New Folder</em> is selected, type the name of the new folder here.</small>
						</td>
					</tr>
				</table>
				<script type="text/javascript">
				//<!--
				document.getElementById('wpicasa_folder_dd').onchange = function (){
					setState();
				}
				
				function setState(){
					var dd = document.getElementById('wpicasa_folder_dd');
					var tb = document.getElementById('wpicasa_newfolder_tb');
					var therow = document.getElementById('newalbum_row');
					
					if(dd.value==''){
						tb.disabled = false;
						therow.style.visibility = 'visible';
					}else{
						tb.disabled = 'disabled';
						therow.style.visibility = 'hidden';
					}
				}
				
				setState();
				//-->
				</script>
				<p class="submit"><input type="submit" value="Choose Folder" name="wpicasa" /></p>
		</fieldset>
		</fom>
	</div>
	<?php
			
			break;
	
		endswitch;

	}
	
	function modrewrite($rules){//this should modify the rewrite rules to the settings in the permalink settings
		global $wp_rewrite;
		//first lets do it for albums
		$tmp_rules = new WP_Rewrite();
		$tmp_rules->rewritecode[] = '%albumname%';
		$tmp_rules->rewritecode[] = '%album_id%';
		$tmp_rules->rewritereplace[] = '([^/]+)'; 
		$tmp_rules->rewritereplace[] = '([0-9]+)';
		$tmp_rules->queryreplace[] = 'wpicasaalbum=true&name=';
		$tmp_rules->queryreplace[] = 'wpicasaalbum=true&p=';
		$addrules = $tmp_rules->generate_rewrite_rules(get_settings('wpicasa_albumrewritestructure'));
		//now for photos
		$tmp_rules = new WP_Rewrite();
		$tmp_rules->rewritecode[] = '%albumname%';
		$tmp_rules->rewritecode[] = '%album_id%';
		$tmp_rules->rewritecode[] = '%photoname%';
		$tmp_rules->rewritecode[] = '%photo_id%';
		$tmp_rules->rewritereplace[] = '([^/]+)'; 
		$tmp_rules->rewritereplace[] = '([0-9]+)';
		$tmp_rules->rewritereplace[] = '([^/]+)'; 
		$tmp_rules->rewritereplace[] = '([0-9]+)';
		$tmp_rules->queryreplace[] = 'albumname=';
		$tmp_rules->queryreplace[] = 'albumid=';
		$tmp_rules->queryreplace[] = 'wpicasaphoto=true&name=';
		$tmp_rules->queryreplace[] = 'wpicasaphoto=true&p=';
		$addrules += $tmp_rules->generate_rewrite_rules(get_settings('wpicasa_photorewritestructure'));
		return $rules + $addrules;
	}

	function deleteAlbum($albumid){//when album is deleted all of the photo posts are deleted as well
		global $wpdb;
		$photos = $wpdb->get_col("SELECT DISTINCT(post_ID) FROM $wpdb->postmeta WHERE meta_key = 'wpicasa_album' AND meta_value='$albumid';");
		if($photos){
			foreach($photos as $photo){
				echo "delete $photo <br/>";
				wp_delete_post($photo);
			}
		}
		return $albumid;
		
	}

	function keepObject($photoid){//when a photo is saved it will be marked as a draft, this will turn it back to an object
		global $wpdb;
		if(get_post_meta($photoid, 'wpicasa_album', true)){//we want the guid to be what the permalink would be
			$guid = get_permalink($photoid);
			$wpdb->query("UPDATE $wpdb->posts SET post_status = 'object' WHERE ID = $photoid LIMIT 1;");
		}
		
		return $photoid;
	}
	
	function adminCSS(){
	
		echo "
		
		<style type=\"text/css\">
		
			.clearwrap {
				background: none;
				border: none;
				position: relative;
				padding: 0;
			}
		
			.wpicasamain {
				position: relative;
				top: -1px;
				background: #FFF;
				border: 1px solid #CCC;
				padding: 5px 10px;
				height: 125px;
				min-height: 125px;
				clear: both;
				z-index: 1;
			}
			
			.wpicasamain[class] {
				height: auto;
			}
		
			.wpicasatools {
				position: relative;
				list-style: none;
				margin: 0;
				padding: 0;
				z-index: 2;			
			}
		 	
		 	.wpicasatools li {
		 		margin: 0;
		 		padding: 0;
		 		float: left;
		 	}
		 	
		 	.wpicasatools a {
		 		border: none;
		 		display: block;
		 		background: #DFDFDF;
		 		padding: 3px 7px 2px 7px;
		 		color: #666;
		 		font-size: .9em;
		 	}
		 	
		 	.wpicasatools a:hover {
		 		background: #AAA;
		 		color: #FFF;
		 		border: none;
		 	}
		 	
		 	.wpicasatools .selected a, .wpicasatools .selected a:hover {
		 		background: #FFF;
		 		border: 1px solid #CCC;
		 		border-width: 1px 1px 0 1px;
		 		border-right: 2px solid #999;
		 		color: #000;
		 		font-weight: bold;
		 		cursor: default;
		 		padding-bottom: 2px;
		 	}
		 	
		 	.albumqueue hr {
		 		clear: both;
		 		visibility: hidden;
		 	}
		 	
		 	.albumqueue ul, .albumlist ul {
		 		clear: both;
		 		list-style: none;
		 		padding: 0;
		 		margin: 10px 5px 5px 5px;
		 	}
		 	
		 	.albumqueue li, .albumlist li {
		 		float: left;
		 		margin: 5px;
		 	}
		 
		 	.wpicasapreview {
		 		list-style: none;
		 		padding: 0;
		 		margin: 15px 0;
		 	}
		 	
		 	.wpicasapreview li {
		 		display: inline;
		 		padding: 10px;
		 	}
		 	
		 	.wpicasapreview img {
		 		text-align: center;
		 		vertical-align: middle;
		 		margin: 5px auto;
		 	}
		 	
		 	.infobox {
		 		background: #FFF;
		 		border: 1px solid #CCC;
		 		border-color: #CCC #999 #999 #CCC;
		 	}
		 	
		 	.infobox a {
				display: block;
				padding: 2px 4px;
				background: #f0f8ff;
		 	}
		 	
		 	.infobox a:hover{
		 		background: #90C5EE;
		 		color: #FFF;
		 	}
		 	
		 	.infobox br {
		 		display: none;
		 	}
		 	
		 	.infobox small {
		 		display: block;
		 		padding: 2px 4px;
		 	}
		 	
		 	.publishbox {
		 		font-size: .75em;
		 		text-align: right;
		 		padding: 0 2px;
		 		background: #DDD;
				border: 1px solid #999;
				border-width: 0 1px 1px 0;
		 		float: left;
		 	}
		 
		 	.error {
				background: #f8f0f0;
				border: 1px solid #F00;
				margin: 1em 5% 10px;
				padding: 0 1em 0 1em;
		 	}
		 
		</style>
		
		";
	}
}

WPicasaAdmin::updateOptions();
add_action('edit_post',array('WPicasaAdmin','keepObject'));
add_action('delete_post',array('WPicasaAdmin','deleteAlbum'));
add_action('admin_menu',array('WPicasaAdmin', 'adminmenu'));
add_action('admin_head',array('WPicasaAdmin','adminCSS'));
add_filter('rewrite_rules_array',array('WPicasaAdmin','modrewrite'));

/*

	For embedding movies
	
<object id="MediaPlayer1" CLASSID="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95" codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701"
	standby="Loading Microsoft Windows® Media Player components..." type="application/x-oleobject" width="280" height="256">
	<param name="fileName" value="Kick_Baby_2.mpg">
	<param name="animationatStart" value="true">
	<param name="transparentatStart" value="true">
	<param name="autoStart" value="true">
	<param name="showControls" value="true">
	<param name="Volume" value="-450">
	<embed type="application/x-mplayer2" pluginspage="http://www.microsoft.com/Windows/MediaPlayer/" src="Kick_Baby_2.mpg" name="MediaPlayer1" width=280 height=256 autostart=1 showcontrols=1 volume=-450>
</object>

*/

?>