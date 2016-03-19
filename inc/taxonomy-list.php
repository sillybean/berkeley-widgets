<?php

// Widget for taxonomy term list

 class Berkeley_Taxonomy_List_Widget extends WP_Widget {

	public function __construct() {
		$widget_ops = array( 'description' => __('Add a simple list of taxonomy terms to your sidebar.') );
		parent::__construct( 'tax_term_list', __('Taxonomy List'), $widget_ops );
	}


	public function widget( $args, $instance ) {
		// Get taxonomy
		
		$tax = ! empty( $instance['tax'] ) ? $instance['tax'] : false;

		if ( !$tax || ! taxonomy_exists( $tax ) )
			return;

		$term_ids = get_terms( $tax, array( 'fields' => 'ids' ) );
		if ( !$term_ids )
			return;
		
		$tax_obj = get_taxonomy( $tax );
		
		// if this is a shared taxonomy, narrow the list to posts of the currently viewed type
		if ( count( $tax_obj->object_type ) > 1 )
			$term_ids =	get_term_ids_limited_to_post_type( $tax, get_query_var( 'post_type' ) );
		
		if ( is_taxonomy_hierarchical( $tax ) )
			$term_ids = get_terms_parent_ids( $term_ids, $tax );
		
		if ( !$term_ids )
			return;
		
		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo $args['before_widget'];

		if ( !empty($instance['title']) )
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		
		$labels = get_taxonomy_labels( $tax_obj );
		
		$tax_args = array(
			'taxonomy'	=> $tax,
			'title_li'	=> '',
			'show_option_none' => $labels->not_found,
			'include' => $term_ids,
		);

		$tax_args = apply_filters( 'widget_tax_menu_args', $tax_args );

		add_filter( 'term_link', 'taxonomy_link_for_post_type', 10, 3 );

		printf( '<ul class="tax-term-list">%s</ul>', wp_list_categories( $tax_args ) );

		remove_filter( 'term_link', 'taxonomy_link_for_post_type', 10 );
		
		echo $args['after_widget'];
	}


	public function update( $new_instance, $old_instance ) {
		$instance = array();
		if ( ! empty( $new_instance['title'] ) ) {
			$instance['title'] = sanitize_text_field( stripslashes( $new_instance['title'] ) );
		}
		if ( ! empty( $new_instance['tax'] ) ) {
			$instance['tax'] = sanitize_text_field( stripslashes( $new_instance['tax'] ) );
		}
		return $instance;
	}


	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$tax = isset( $instance['tax'] ) ? $instance['tax'] : '';

		$args = array(
		  'public'   => true,
		); 
		$output = 'objects'; // names or objects
		$operator = 'and'; // 'and' or 'or'
		$taxonomies = get_taxonomies( $args, $output, $operator );

		?>
		<div class="tax-list-widget-form-controls" <?php if ( empty( $taxonomies ) ) { echo ' style="display:none" '; } ?>>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'tax' ); ?>"><?php _e( 'Select Taxonomy:' ); ?></label><br>
				<select id="<?php echo $this->get_field_id( 'tax' ); ?>" name="<?php echo $this->get_field_name( 'tax' ); ?>" class="widefat">
					<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
					<?php foreach ( $taxonomies as $taxonomy ) : ?>
						<option value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php selected( $tax, $taxonomy->name ); ?>>
							<?php echo esc_html( $taxonomy->label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
		</div>
		<?php
	}
}


function taxonomy_link_for_post_type( $termlink, $term, $taxonomy ) {
	
	$tax_obj = get_taxonomy( $taxonomy );
	
	// if this is not a shared taxonomy, bail
	if ( count( $tax_obj->object_type ) == 1 )
		return $termlink;
	
	if ( is_tax() ) {
		// we're on another term's archive, where post type might not be set but can be inferred
		$current_term = get_queried_object();
		$tax_obj = get_taxonomy( $current_term->taxonomy );
		if ( count( $tax_obj->object_type ) == 1 )
			$post_type = $tax_obj->object_type[0];
	}
	else
		$post_type = get_query_var( 'post_type' );
	
	if ( !isset( $post_type ) || 'any' == $post_type )
		return $termlink;
	
	$termlink = add_query_arg( 'post_type', $post_type, $termlink );
	
	return $termlink;
}


// get an array of taxonomy term IDs associated with a specific post type (for shared taxonomies)
function get_term_ids_limited_to_post_type( $taxonomies, $post_types ) {
	
	$transient = sanitize_key( 'limited_term_ids_' . $taxonomies . '_for_' . $post_types );
	
	// Get any existing copy of our transient data
	if ( false === ( $terms_for_post_type = get_transient( $transient ) ) ) {
	    global $wpdb;

		if ( is_array($post_types) ) {
			$post_types = implode( "','", $post_types );
		}
		
		//if (current_user_can('manage_options')) { var_dump($posts_in); exit; }

	    $query = $wpdb->prepare(
	        "SELECT t.term_id from $wpdb->terms AS t
	        INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
	        INNER JOIN $wpdb->term_relationships AS r ON r.term_taxonomy_id = tt.term_taxonomy_id
	        INNER JOIN $wpdb->posts AS p ON p.ID = r.object_id
			WHERE p.post_type IN(%s) AND tt.taxonomy IN('%s')
			GROUP BY t.term_id",
	        $post_types,
	        $taxonomies
	    );
	
		//if (current_user_can('manage_options')) var_dump( $query );
	
	     $terms_for_post_type = $wpdb->get_col( stripslashes( $query ) );
	     set_transient( $transient, $terms_for_post_type, HOUR_IN_SECONDS );
	}
	
    return $terms_for_post_type;

}

function get_terms_parent_ids( $limited_term_ids, $taxonomy ) {
	
	$parent_ids = array();
	foreach ( $limited_term_ids as $child_term ) {
		$ancestors = get_ancestors( $child_term, $taxonomy );
		$parent_ids = array_merge( $parent_ids, $ancestors );
	}
	
	return array_merge( $limited_term_ids,  $parent_ids );
}

function get_term_post_count_by_type( $term_ids, $taxonomy, $post_type = '' ) {
	
	if ( !isset( $post_type ) || empty( $post_type ) )
		$post_type = get_query_var( 'post_type' );
	
	if ( !isset( $post_type ) )
		return -1;
		
    $args = array( 
        'fields' => 'ids',
        'posts_per_page' => -1,
        'post_type' => $post_type
    );
	if ( !empty( $taxonomy ) ) {
		$args['tax_query'] = array(
            array(
                'taxonomy' => $taxonomy,
                'field' => 'id',
                'terms' => $term_ids
            )
		);
	}
    $posts = get_posts( $args );
	//if ( current_user_can('manage_options') ) { var_dump($args); exit; }
    
	return count( $posts ); 
}
