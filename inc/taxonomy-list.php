<?php

// Widget for taxonomy term list

 class Berkeley_Taxonomy_List_Widget extends WP_Widget {

	public function __construct() {
		$widget_ops = array( 'description' => esc_html__('Add a simple list of taxonomy terms to your sidebar.') );
		parent::__construct( 'tax_term_list', esc_html__('Taxonomy List'), $widget_ops );
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
		
		if ( function_exists( 'berkeley_find_post_type' ) )
			$type = berkeley_find_post_type();
		else
			$type = get_query_var( 'post_type' );
		
		// if this is a shared taxonomy, narrow the list to posts of the currently viewed type
		if ( count( $tax_obj->object_type ) > 1 )
			$term_ids =	get_term_ids_limited_to_post_type( $tax, $type );
		
		if ( is_taxonomy_hierarchical( $tax ) && function_exists( 'get_terms_parent_ids' ) )
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
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'tax' ); ?>"><?php esc_html_e( 'Select Taxonomy:' ); ?></label><br>
				<select id="<?php echo $this->get_field_id( 'tax' ); ?>" name="<?php echo $this->get_field_name( 'tax' ); ?>" class="widefat">
					<option value="0"><?php esc_html_e( '&mdash; Select &mdash;' ); ?></option>
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