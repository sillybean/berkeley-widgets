<?php
// Widget for Berkeley Calendar feed

 class Berkeley_Calendar_XML_Widget extends WP_Widget {


	public function __construct() {
		$widget_ops = array( 'description' => __('Display events from the Berkeley Events Calendar.') );
		parent::__construct( 'berkeley_calendar_xml', __('Berkeley Calendar Feed'), $widget_ops );
	}


	public function widget( $args, $instance ) {
		
		$response = wp_remote_request(
		     esc_url( $instance['url'] ),
		     array( 'ssl_verify' => true )
		);
		
		if ( is_wp_error( $response ) )
		    return current_user_can( 'manage_options' ) ? $response->get_error_message() : '';

		$content = trim( wp_remote_retrieve_body( $response ) );
		$content = new SimpleXMLElement( $content );
		
		$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo $args['before_widget'];

		if ( !empty($instance['title']) )
			echo $args['before_title'] . $instance['title'] . $args['after_title'] . '<ul>';

		$eventlist = array();
		
		if ( isset( $content->Event[0] ) )
			$format = 'xml';
		elseif ( isset( $content->channel ) )
			$format = 'rss';
		else
			break;
		
		if ( !isset( $instance['num'] ) || empty( $instance['num'] ) ) {
			if ( 'xml' == $format )
				$instance['num'] = count( $content->Event );
			else
				$instance['num'] = count( $content->channel->item );
		}
		
		for ( $i = 0; $i < $instance['num']; $i++ ) {
			
			if ( 'xml' == $format ) {
				$event = $content->Event[$i];
				
				if ( isset( $event->DateTime->RecurrenceDates ) )
					$date = $event->DateTime->RecurrenceDates->RecurrenceDate[0]->DistinctDate->Date;
				else 
					$date = $event->DateTime->StartDate;
				
				$url = sprintf( 'http://events.berkeley.edu/?event_ID=%d&date=%s&tab=all_events', $event->ID, $date );
				
				$title = sprintf( '<a href="%s">%s</a>', $url, $event->Title );
			}	
			else {
				$event = $content->channel->item[$i];
				$date = false;
				$url = $event->guid;
				$title = sprintf( '<a href="%s">%s</a>', $url, $event->title );
			}
			
			
			printf( '<li class="event"> <h4 class="event-title">%s</h4>', $title );
			
			if ( 'xml' == $format && $instance['display']['date'] ) {
				$fulldate = date_create_from_format( 'Y-m-d', $date );
				$formatteddate = date_format( $fulldate, get_option( 'date_format' ) );
				$times = '';
				if ( $instance['display']['times'] ) {
					if ( isset( $event->DateTime->FormattedStartTime ) && isset( $event->DateTime->FormattedEndTime ) )
						$times = $event->DateTime->FormattedStartTime . '&ndash;' . $event->DateTime->FormattedEndTime;
					if ( $formatteddate && $times )
						$times = ', ' . $times;
				}
				printf( '<p class="event-date">%s</p>', $formatteddate . $times );
			}
			
			if ( 'xml' == $format && $instance['display']['locations'] && isset( $event->Locations ) ) {
				$eventlocations = array();	
				foreach ( $event->Locations->Location as $location ) {
					 $eventlocations[] = $location->LocationName;
				}
				printf( '<p class="event-locations">%s</p>', implode( ', ', array_filter( $eventlocations ) ) );
			}
			
			if ( 'xml' == $format && $instance['display']['speaker'] && isset( $event->Performers ) ) {
				$speakers = array();	
				foreach ( $event->Performers->Performer as $performer ) {
					$speakerinfo = array();
					$speakerinfo[] = $performer->Name->FullName;
					if ( isset($performer->ProfessionalAffiliations) ) {
						foreach ( $performer->ProfessionalAffiliations->ProfessionalAffiliation as $affiliation ) {
							$speakerinfo[] = $affiliation->JobTitles->JobTitle;
							$speakerinfo[] = $affiliation->OrganizationName;
						}
					}
					$speakers[] = implode( ', ', array_filter( $speakerinfo ) );
				}
				printf( '<p class="event-speakers">%s</p>', implode( '<br/>', array_filter( $speakers ) ) );
			}
			
			if ( $instance['display']['desc'] ) {
				if ( 'xml' == $format && isset( $event->ShortDescription ) ) 	
					printf( '<p class="event-desc">%s</p>', $event->ShortDescription );
				elseif ( isset( $event->description ) ) {
					$desc = $event->description;
					if ( $instance['trim'] ) {
						$desc = str_replace( '<br />', "\n", $desc );
						$split = "\n";
						$desc = explode( $split, $desc );
						$desc = array_slice( array_filter( $desc, 'scl_not_empty' ), 0, $instance['trim'] );
						$desc = implode( '<br />', $desc );
					}
					printf( '<p class="event-desc">%s</p>', $desc );
				}
			}
			
			echo '</li>';
			
		} // for

		echo '</ul>' . $args['after_widget'];
	}


	public function update( $new_instance, $old_instance ) {
		$instance = array();
		if ( ! empty( $new_instance['title'] ) ) {
			$instance['title'] = sanitize_text_field( stripslashes( $new_instance['title'] ) );
		}
		if ( ! empty( $new_instance['url'] ) ) {
			$instance['url'] = $new_instance['url'];
		}
		if ( ! empty( $new_instance['trim'] ) ) {
			$instance['trim'] = (int)$new_instance['trim'];
		}
		if ( ! isset( $new_instance['num'] ) ) { // allow 0
			$instance['num'] = 5;
		}
		else {
			$instance['num'] = (int)$new_instance['num'];
		}
		if ( ! empty( $new_instance['display'] ) ) {
			$instance['display'] = $new_instance['display'];
		}
		return $instance;
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$url = isset( $instance['url'] ) ? $instance['url'] : '';
		$num = isset( $instance['num'] ) ? $instance['num'] : '';
		$trim = isset( $instance['trim'] ) ? $instance['trim'] : '';
		$display = isset( $instance['display'] ) ? $instance['display'] : array();

		?>
		<div class="events-widget-form-controls">
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'url' ); ?>"><?php _e( 'Events Feed URL (RSS or XML):' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" value="<?php echo esc_attr( $url ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'num' ); ?>"><?php _e( 'Number of events to display:' ) ?></label>
				<input type="text" class="inline" size="1" id="<?php echo $this->get_field_id( 'num' ); ?>" name="<?php echo $this->get_field_name( 'num' ); ?>" value="<?php echo esc_attr( $num ); ?>"/><br />
				<span class="description"><?php _e('Enter 0 to display all available events.'); ?></span>
			</p>
			<p>	<?php _e( 'Display: '); ?> <br />
				
				<label>	<input type="checkbox" class="widefat" value="1" name="<?php echo $this->get_field_name( 'display' ); ?>[date]" <?php checked( 1, $display['date'] ); ?> /> <?php _e( 'Date *' ) ?></label> 
				<br>
				<label>	<input type="checkbox" class="widefat" value="1" name="<?php echo $this->get_field_name( 'display' ); ?>[times]" <?php checked( 1, $display['times'] ); ?> /> <?php _e( 'Start and End Times *' ) ?></label>
				<br>
				<label>	<input type="checkbox" class="widefat" value="1" name="<?php echo $this->get_field_name( 'display' ); ?>[locations]" <?php checked( 1, $display['locations'] ); ?> /> <?php _e( 'Location(s) *' ) ?></label>
				<br>
				<label>	<input type="checkbox" class="widefat" value="1" name="<?php echo $this->get_field_name( 'display' ); ?>[speaker]" <?php checked( 1, $display['speaker'] ); ?> /> <?php _e( 'Speaker(s) *' ) ?></label>
				<br>
				<label>	<input type="checkbox" class="widefat" value="1" name="<?php echo $this->get_field_name( 'display' ); ?>[desc]" <?php checked( 1, $display['desc'] ); ?> /> <?php _e( 'Description' ) ?></label>
			</p>
			<p><span class="description"><?php _e( '* Options for XML feeds only.' ); ?></span></p>
			<p>
				<label for="<?php echo $this->get_field_id( 'num' ); ?>"><?php _e( 'Trim RSS descriptions to ' ) ?>
				<input type="text" class="inline" size="1" id="<?php echo $this->get_field_id( 'trim' ); ?>" name="<?php echo $this->get_field_name( 'trim' ); ?>" value="<?php echo esc_attr( $trim ); ?>"/> <?php _e( ' paragraphs' ); ?></label>
			</p>
			
		</div>
		<?php
	}
}

// helper function for the feed display widget
function scl_not_empty( $val ) {
	$val = str_replace( array( "\n", "\r", "\t" ), '', $val );
    return !empty( $val );
}