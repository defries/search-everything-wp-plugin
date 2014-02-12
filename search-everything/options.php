<?php

Class se_admin {

	function se_admin() {
		// Load language file
		$locale = get_locale();
		$meta = se_get_meta();
		if ( !empty($locale) )
			load_textdomain('SearchEverything', SE_PLUGIN_DIR .'lang/se-'.$locale.'.mo');

		add_action( 'admin_enqueue_scripts', array(&$this,'se_register_plugin_scripts_and_styles'));
		add_action( 'admin_menu', array(&$this, 'se_add_options_panel'));
		add_action( 'add_meta_boxes', array(&$this,'se_meta_box_add' ));

		if ( isset( $_GET['se_notice'] ) && 0 == $_GET['se_notice'] ) {
			$meta['show_options_page_notice'] = false;
			se_update_meta($meta);
 		}
		if ( $meta['show_options_page_notice'] ) {
 			add_action( 'all_admin_notices', array( &$this, 'se_options_page_notice' ) );
 		}
	}

	/**
	 * Register style sheet.
	 */
	function se_register_plugin_scripts_and_styles() {
		wp_register_style( 'search-everything', SE_PLUGIN_URL . '/static/css/admin.css' );
		wp_enqueue_style( 'search-everything' );

		wp_register_script( 'search-everything', SE_PLUGIN_URL . '/static/js/searcheverything.js');
		wp_enqueue_script('search-everything');
	}



	/*
	* Add metabox for search widget on editor
	*/

	function se_meta_box_add()
	{
		add_meta_box( 'se-meta-box-id', 'Re-Search Everything', array(&$this,'se_meta_box_cb'), 'post', 'normal', 'high' );
	}

	function se_meta_box_cb( $post )
	{
		$values = get_post_custom( $post->ID );
		$text = isset( $values['se-meta-box-text'] ) ? esc_attr( $values['se-meta-box-text'][0] ) : '';
		wp_nonce_field( 'se-meta-box-nonce', 'meta_box_nonce' );
		?>
		<p>
			<label for="se-meta-box-text">Do your re-search</label>
			<input type="text" name="se-meta-box-text" id="se-meta-box-text" value="<?php echo $text; ?>" />
			<input id="se-meta-search-button" type="button" value="Digg in!" class="button button-info"/>
		</p>
		<?php	
	}


	function se_meta_box_search( $post_id )
	{
		// Bail if we're doing an auto save
		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		
		// if our nonce isn't there, or we can't verify it, bail
		if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'se-meta-box-nonce' ) ) return;
		
		// if our current user can't edit this post, bail
		if( !current_user_can( 'edit_post' ) ) return;
		

	}


	function se_add_options_panel() {
		add_options_page('Search', 'Search Everything', 'manage_options', 'extend_search', array(&$this, 'se_option_page'));
	}


	//build admin interface
	function se_option_page() {
		global $wpdb, $table_prefix, $wp_version;

		$new_options = array(
			'se_exclude_categories'		=> (isset($_POST['exclude_categories']) && !empty($_POST['exclude_categories'])) ? $_POST['exclude_categories'] : '',
			'se_exclude_categories_list'		=> (isset($_POST['exclude_categories_list']) && !empty($_POST['exclude_categories_list'])) ? $_POST['exclude_categories_list'] : '',
			'se_exclude_posts'			=> (isset($_POST['exclude_posts'])) ? $_POST['exclude_posts'] : '',
			'se_exclude_posts_list'			=> (isset($_POST['exclude_posts_list']) && !empty($_POST['exclude_posts_list'])) ? $_POST['exclude_posts_list'] : '',
			'se_use_page_search'			=> (isset($_POST['search_pages']) && $_POST['search_pages']) ,
			'se_use_comment_search'		=> (isset($_POST['search_comments']) && $_POST['search_comments']) ,
			'se_use_tag_search'			=> (isset($_POST['search_tags']) && $_POST['search_tags'] ),
			'se_use_tax_search'			=> (isset($_POST['search_taxonomies']) && $_POST['search_taxonomies']),
			'se_use_category_search'		=> (isset($_POST['search_categories']) && $_POST['search_categories']),
			'se_approved_comments_only'		=> (isset($_POST['appvd_comments']) && $_POST['appvd_comments'] ),
			'se_approved_pages_only'		=> (isset($_POST['appvd_pages']) && $_POST['appvd_pages']),
			'se_use_excerpt_search'		=> (isset($_POST['search_excerpt']) && $_POST['search_excerpt']),
			'se_use_draft_search'			=> (isset($_POST['search_drafts']) && $_POST['search_drafts']),
			'se_use_attachment_search'		=> (isset($_POST['search_attachments']) && $_POST['search_attachments']),
			'se_use_authors'			=> (isset($_POST['search_authors']) && $_POST['search_authors']),
			'se_use_cmt_authors'			=> (isset($_POST['search_cmt_authors']) && $_POST['search_cmt_authors']),
			'se_use_metadata_search'		=> (isset($_POST['search_metadata']) && $_POST['search_metadata']),
			'se_use_highlight'			=> (isset($_POST['search_highlight']) && $_POST['search_highlight']),
			'se_highlight_color'			=> (isset($_POST['highlight_color'])) ? $_POST['highlight_color'] : '',
			'se_highlight_style'			=> (isset($_POST['highlight_style'])) ? $_POST['highlight_style'] : ''
		);

		if(isset($_POST['action']) && $_POST['action'] == "save") {
			echo "<div class=\"updated fade\" id=\"limitcatsupdatenotice\"><p>" . __('Your default search settings have been <strong>updated</strong> by Search Everything. </p><p> What are you waiting for? Go check out the new search results!', 'SearchEverything') . "</p></div>";
			se_update_options($new_options);
		}

		if(isset($_POST['action']) && $_POST['action'] == "reset") {
			echo "<div class=\"updated fade\" id=\"limitcatsupdatenotice\"><p>" . __('Your default search settings have been <strong>updated</strong> by Search Everything. </p><p> What are you waiting for? Go check out the new search results!', 'SearchEverything') . "</p></div>";
			$default_options = se_get_default_options();

			se_update_options($default_options);
		}

		$options = se_get_options();
		$meta = se_get_meta();

		include(se_get_view('options_page'));

	}	//end se_option_page

	function se_options_page_notice() {
		$screen = get_current_screen();
		if ( 'settings_page_extend_search' == $screen->id ) {
			$close_url = admin_url( $screen->parent_file );
			$close_url = add_query_arg( array(
				'page' => 'extend_search',
				'se_notice' => 0,
			), $close_url );
			include(se_get_view('options_page_notice'));
		}
	}
}