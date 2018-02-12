<?php
// Widget for Berkeley Calendar feed

 class Berkeley_Calendar_XML_Widget extends WP_Widget {


	public function __construct() {
		$widget_ops = array( 'description' => esc_html__( 'Display events from the Berkeley Events Calendar.', 'berkeley-widgets' ) );
		parent::__construct( 'berkeley_calendar_xml', esc_html__( 'Berkeley Calendar Feed', 'berkeley-widgets' ) , $widget_ops );
	}


	public function widget( $args, $instance ) {
		
		$response = wp_remote_request(
		     $instance['url'],
		     array( 'ssl_verify' => true )
		);
		
		if ( is_wp_error( $response ) )
		    return current_user_can( 'manage_options' ) ? $response->get_error_message() : '';

		$content = trim( wp_remote_retrieve_body( $response ) );
		$content = new SimpleXMLElement( $content );
		
		$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		if ( filter_var( $instance['link'], FILTER_VALIDATE_URL ) ) {
		    $instance['title'] = sprintf( '<a href="%s">%s</a>', $instance['title'], esc_url( $instance['link'] ) );
		}

		echo $args['before_widget'];

		if ( !empty($instance['title']) )
			echo $args['before_title'] . $instance['title'] . $args['after_title'];

		echo '<ul>';

		$eventlist = array();
		
		if ( isset( $content->Event[0] ) )
			$format = 'xml';
		else
			$format = 'rss';
		
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
				
				$title = sprintf( '<a href="%s">%s</a>', $url, wp_kses_post( $event->Title ) );
			}	
			else {
				$event = $content->channel->item[$i];
				if ( is_object( $event ) ) {
					$date = false;
					$url = $event->guid;
					$title = sprintf( '<a href="%s">%s</a>', $url, $event->title );
				}
			}
			
			printf( '<li class="event"> <h4 class="event-title">%s</h4>', wp_kses_post( $title ) );
			
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
			
			if ( isset( $instance['display'] ) && $instance['display']['desc'] ) {
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
			$instance['url'] = esc_url_raw( $new_instance['url'] );
		}
		if ( ! empty( $new_instance['link'] ) ) {
			$instance['link'] = esc_url_raw( $new_instance['link'] );
		}
		if ( ! empty( $new_instance['trim'] ) ) {
			$instance['trim'] = absint($new_instance['trim'] );
		}
		if ( ! isset( $new_instance['num'] ) ) { // allow 0
			$instance['num'] = 5;
		}
		else {
			$instance['num'] = absint($new_instance['num'] );
		}
		if ( ! empty( $new_instance['display'] ) ) {
			$instance['display'] = array_map( 'absint', $new_instance['display'] );
		}
		return $instance;
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$url = isset( $instance['url'] ) ? $instance['url'] : '';
		$link = isset( $instance['link'] ) ? $instance['link'] : '';
		$num = isset( $instance['num'] ) ? $instance['num'] : '';
		$trim = isset( $instance['trim'] ) ? $instance['trim'] : '';
		$display = isset( $instance['display'] ) ? $instance['display'] : array(
			'num' => 5,
			'date' => '',
			'times' => '',
			'locations' => '',
			'speaker' => '',
			'desc' => ''
		);

		?>
		<div class="events-widget-form-controls">
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'berkeley-widgets' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'url' ); ?>"><?php esc_html_e( 'Events Feed URL (RSS or XML):' , 'berkeley-widgets' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" value="<?php echo esc_attr( $url ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'link' ); ?>"><?php esc_html_e( 'Link widget title to URL:' , 'berkeley-widgets' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'link' ); ?>" name="<?php echo $this->get_field_name( 'link' ); ?>" value="<?php echo esc_attr( $link ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'num' ); ?>"><?php esc_html_e( 'Number of events to display:' ) ?></label>
				<input type="text" class="inline" size="1" id="<?php echo $this->get_field_id( 'num' ); ?>" name="<?php echo $this->get_field_name( 'num' ); ?>" value="<?php echo esc_attr( $num ); ?>"/><br />
				<span class="description"><?php esc_html_e( 'Enter 0 to display all available events.', 'berkeley-widgets' ); ?></span>
			</p>
			<p>	<?php esc_html_e( 'Display: '); ?> <br />
				
				<label>	<input type="checkbox" class="widefat" value="1" name="<?php echo $this->get_field_name( 'display' ); ?>[date]" <?php checked( 1, $display['date'] ); ?> /> <?php esc_html_e( 'Date *', 'berkeley-widgets'  ) ?></label> 
				<br>
				<label>	<input type="checkbox" class="widefat" value="1" name="<?php echo $this->get_field_name( 'display' ); ?>[times]" <?php checked( 1, $display['times'] ); ?> /> <?php esc_html_e( 'Start and End Times *', 'berkeley-widgets'  ) ?></label>
				<br>
				<label>	<input type="checkbox" class="widefat" value="1" name="<?php echo $this->get_field_name( 'display' ); ?>[locations]" <?php checked( 1, $display['locations'] ); ?> /> <?php esc_html_e( 'Location(s) *', 'berkeley-widgets'  ) ?></label>
				<br>
				<label>	<input type="checkbox" class="widefat" value="1" name="<?php echo $this->get_field_name( 'display' ); ?>[speaker]" <?php checked( 1, $display['speaker'] ); ?> /> <?php esc_html_e( 'Speaker(s) *', 'berkeley-widgets'  ) ?></label>
				<br>
				<label>	<input type="checkbox" class="widefat" value="1" name="<?php echo $this->get_field_name( 'display' ); ?>[desc]" <?php checked( 1, $display['desc'] ); ?> /> <?php esc_html_e( 'Description', 'berkeley-widgets'  ) ?></label>
			</p>
			<p><span class="description"><?php esc_html_e( '* Options for XML feeds only.', 'berkeley-widgets'  ); ?></span></p>
			<p>
				<label for="<?php echo $this->get_field_id( 'trim' ); ?>"><?php esc_html_e( 'Trim RSS descriptions to ', 'berkeley-widgets'  ) ?>
				<input type="text" class="inline" size="1" id="<?php echo $this->get_field_id( 'trim' ); ?>" name="<?php echo $this->get_field_name( 'trim' ); ?>" value="<?php echo esc_attr( $trim ); ?>"/> <?php esc_html_e( ' paragraphs', 'berkeley-widgets'  ); ?></label>
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