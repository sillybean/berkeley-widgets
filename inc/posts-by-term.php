<?php
// Widget for post list by term

 class Berkeley_Term_Posts_Widget extends WP_Widget {

	public function __construct() {
		$widget_ops = array( 'description' => __('List all posts with a specified taxonomy term.') );
		parent::__construct( 'posts_by_term', __('Posts by Term'), $widget_ops );
	}


	public function widget( $args, $instance ) {
		// Get taxonomy
		
		$tax = ! empty( $instance['tax'] ) ? $instance['tax'] : false;

		if ( !$tax || ! taxonomy_exists( $tax ) )
			return;
		
		$term = ! empty( $instance['term'] ) ? $instance['term'] : false;
		
		if ( !$term || ! term_exists( $term ) )
			return;
		
		$show_on_term = ! empty( $instance['show_on_term'] ) ? $instance['show_on_term'] : false;
		
		if ( $show_on_term && !has_term( $term, $tax, get_the_ID() ) )
			return;

		$post_type = ! empty( $instance['post_type'] ) ? $instance['post_type'] : 'any';

		$post_args = array(
			'post_type' => $post_type,
			'posts_per_page' => $instance['posts_per_page'],
			$tax => $term,
			'orderby' => 'title',
			'order' => 'ASC',
		);
		
		$post_args = apply_filters( 'posts_by_term_widget_args', $post_args );
		
		$posts = get_posts( $post_args );
		
		if ( !count( $posts ) )
			return;
		
		$totalposts = count( $posts );
		if ( -1 !== $instance['posts_per_page'] ) {
			$post_args['posts_per_page'] = -1;
			$post_args['fields'] = 'ids';
			$totalposts = count( get_posts( $post_args ) );
		}
		
		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo $args['before_widget'];

		if ( !empty($instance['title']) )
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		
		echo '<ul class="posts-by-term">';
		
		foreach( $posts as $post ) :
			
			$class = '';
			if ( $post->ID == get_the_ID() )
				$class = ' class="current-page"';
				
			printf( '<li %s><a href="%s">%s</a></li>', $class, get_the_permalink( $post->ID ), apply_filters( 'the_title', $post->post_title ) );
			
		endforeach;
		
		if ( $totalposts > count( $posts ) ) {
			if ( 'any' == $post_type )
				$post_type = 'post';
			$postobj = get_post_type_object( 'post' );
			$termobj = get_term( $term, $tax );
			printf( '<li><a href="%s" title="Show all %s labeled %s">%s</li>', get_term_link( $term, $tax ), $postobj->labels->name, $termobj->name, __( 'More...' ) );
		}
		
		echo '</ul>';
		
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
		if ( ! empty( $new_instance['term'] ) ) {
			$instance['term'] = sanitize_text_field( stripslashes( $new_instance['term'] ) );
		}
		if ( ! empty( $new_instance['post_type'] ) ) {
			$instance['post_type'] = sanitize_text_field( stripslashes( $new_instance['post_type'] ) );
		}
		if ( ! empty( $new_instance['posts_per_page'] ) ) {
			$instance['posts_per_page'] = (int) $new_instance['posts_per_page'];
		}
		if ( ! empty( $new_instance['show_on_term'] ) ) {
			$instance['show_on_term'] = (int) $new_instance['show_on_term'];
		}
		return $instance;
	}


	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$tax = isset( $instance['tax'] ) ? $instance['tax'] : 0;
		$term = isset( $instance['term'] ) ? $instance['term'] : 0;
		$type = isset( $instance['post_type'] ) ? $instance['post_type'] : '';
		$num = isset( $instance['posts_per_page'] ) ? $instance['posts_per_page'] : '5';
		$show = isset( $instance['show_on_term'] ) ? $instance['show_on_term'] : false;
		
		$args = array(
		  'public'   => true,
		); 
		$output = 'objects'; // names or objects
		$operator = 'and'; // 'and' or 'or'
		$taxonomies = get_taxonomies( $args, $output, $operator );
		$post_types = get_post_types( $args, $output, $operator );
		?>
		<div class="posts-by-term-widget-form-controls" <?php if ( empty( $taxonomies ) ) { echo ' style="display:none" '; } ?>>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'tax' ); ?>"><?php _e( 'Select Taxonomy:' ); ?></label><br>
				<select id="<?php echo $this->get_field_id( 'tax' ); ?>" name="<?php echo $this->get_field_name( 'tax' ); ?>" class="widefat taxonomy-select">
					<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
					<?php foreach ( $taxonomies as $taxonomy ) : ?>
						<option value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php selected( $tax, $taxonomy->name ); ?>>
							<?php echo esc_html( $taxonomy->label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'term' ); ?>"><?php _e( 'Select Term:' ) ?></label>
				<select id="<?php echo $this->get_field_id( 'term' ); ?>" name="<?php echo $this->get_field_name( 'term' ); ?>" class="widefat term-select">
					<?php echo berkeley_widgets_get_term_options( $tax, $term ); ?>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e( 'Limit to Post Type:' ); ?></label><br>
				<select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>" class="widefat">
					<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
					<?php foreach ( $post_types as $post_type ) : ?>
						<option value="<?php echo esc_attr( $post_type->name ); ?>" <?php selected( $type, $post_type->name ); ?>>
							<?php echo esc_html( $post_type->label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'posts_per_page' ); ?>">
				<input type="text" style="width: 2em" id="<?php echo $this->get_field_id( 'posts_per_page' ); ?>" name="<?php echo $this->get_field_name( 'posts_per_page' ); ?>" value="<?php echo esc_attr( $num ); ?>"/><?php _e( ' Posts shown (-1 for all)' ) ?></label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'show_on_term' ); ?>">
				<input type="checkbox" id="<?php echo $this->get_field_id( 'show_on_term' ); ?>" name="<?php echo $this->get_field_name( 'show_on_term' ); ?>" value="1" <?php checked(1, $show) ?> /><?php _e( 'Show only on posts with selected term' ) ?></label>
			</p>
		</div>
		<?php
	}
}

function berkeley_widgets_get_term_options( $taxonomy, $selected_term = 0 ) {
	
	$output = sprintf( '<option value="0">%s</option>'."\n", __( '&mdash; Select &mdash;' ) );
	
	if ( !$taxonomy || !taxonomy_exists( $taxonomy ) )
		return $output;
		
	$args = array(
		'fields' => 'id=>name',
		'hide_empty' => false,
	);
	
	$terms = get_terms( $taxonomy, $args );
	
	foreach ( $terms as $id => $term ) :
		$output .= sprintf( '<option value="%d" %s>%s</option>%s', $id, selected( $id, $selected_term, false ), $term, "\n" );
	endforeach; 
	
	return $output;
}


add_action('wp_ajax_ajax-taxonomy-terms', 'berkeley_widgets_ajax_get_terms');

function berkeley_widgets_ajax_get_terms() {

	$output = '';

	// Check the type of request
	if ( !isset( $_REQUEST['tax_slug'] ) )
		wp_die( "No taxonomy selected." );

	$output = berkeley_widgets_get_term_options( $_REQUEST['tax_slug'] );

	wp_die( $output );
}