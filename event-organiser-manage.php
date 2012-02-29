<?php
/**
 * Functions altering the CPT Event table
 *
 * @since 1.0.0
 */

/**
 * Adds custom columns to Event CPT table
 * @since 1.0.0
 */
add_filter('manage_edit-event_columns', 'eventorganiser_event_add_columns');
function eventorganiser_event_add_columns($columns) {

	unset($columns['date']);//Unset unnecessary columns

	//Set 'title' column title
	$columns['title'] =__('Event','eventorganiser');

	//If displaying 'author', change title
	if(isset($columns['author']))
		$columns['author'] = __('Organiser','eventorganiser');

	$columns['venue'] = __('Venue','eventorganiser');
	$columns['eventcategories'] = __('Categories');
	$columns['datestart'] = __('Start Date/Time','eventorganiser');
	$columns['dateend'] = __('End Date/Time', 'eventorganiser');
	$columns['reoccurence'] = __('Reoccurrence','eventorganiser'); 

	return $columns;
}


/**
 * Registers the custom columns in Event CPT table to be sortable
 * @since 1.0.0
 */
add_filter( 'manage_edit-event_sortable_columns', 'eventorganiser_event_sortable_columns' );
function eventorganiser_event_sortable_columns( $columns ) {
	$columns['datestart'] = 'eventstart';
	$columns['dateend'] = 'eventend';
	return $columns;
}


/**
 * What to display in custom columns of Event CPT table
 * @since 1.0.0
 */
add_action('manage_event_posts_custom_column', 'eventorganiser_event_sort_columns', 10, 2);
function eventorganiser_event_sort_columns($column_name, $id) {
	global $post;

	$series_id = (empty($post->event_id) ? $id :'');
	$EO_Venue =new EO_Venue((int)eo_get_venue($series_id));

	$phpFormat = 'M, jS Y';
	if(!eo_is_all_day($series_id))
		$phpFormat .= '\<\/\b\r\>'. get_option('time_format');
	
	switch ($column_name) {
		case 'venue':
		    	$terms = get_the_terms($post->ID, 'event-venue');
 			
			if ( !empty($terms) ) {
       	 		foreach ( $terms as $term )
			            $post_terms[] = "<a href='".add_query_arg( 'event-venue', $term->slug)."'>".esc_html(sanitize_term_field('name', $term->name, $term->term_id,'event-venue', 'display'))."</a>";
			        echo join( ', ', $post_terms );
				echo "<input type='hidden' value='".$term->term_id."'/>";
			}
			break;

		case 'datestart':
			eo_the_start($phpFormat,$series_id );
			break;
		
		case 'dateend':
			eo_the_end($phpFormat,$series_id );
			break;

		case 'reoccurence':
			eo_display_reoccurence($series_id );
			break;

		case 'eventcategories':
		    	$terms = get_the_terms($post->ID, 'event-category');
 			
			if ( !empty($terms) ) {
       	 		foreach ( $terms as $term )
			            $post_terms[] = "<a href='".add_query_arg( 'event-category', $term->slug)."'>".esc_html(sanitize_term_field('name', $term->name, $term->term_id,'event-category', 'display'))."</a>";
			        echo join( ', ', $post_terms );
			}
			break;

	default:
		break;
	} // end switch
}

/**
 * Adds a drop-down filter to the Event CPT table by category
 * @since 1.0.0
 */
add_action( 'restrict_manage_posts', 'restrict_events_by_category' );
function restrict_events_by_category() {

    // only display these taxonomy filters on desired custom post_type listings
    global $typenow,$wp_query;
    if ($typenow == 'event') {
	eo_event_category_dropdown(array('hide_empty'=>false,'show_option_all' => __('View all categories')));
    }
}

/**
 * Adds a drop-down filter to the Event CPT table by venue
 * @since 1.0.0
 */
add_action('restrict_manage_posts','restrict_events_by_venue');
function restrict_events_by_venue() {
	global $typenow;

	//Only add if CPT is event
	if ($typenow=='event') :	
		 eo_event_venue_dropdown(array('hide_empty'=>false,'show_option_all' => __('View all venues','eventorganiser')));
	endif;
}

/**
 * Adds a drop-down filter to the Event CPT table by intervals
 * @since 1.2.0
 */
add_action( 'restrict_manage_posts', 'eventorganiser_display_occurrences' );
function eventorganiser_display_occurrences() {
	global $typenow,$wp_query;
	if ($typenow == 'event'):
		$intervals = array(
			'all'=>__('View all events','eventorganiser'),
			'future'=>__('Future events','eventorganiser'),
			'expired'=>__('Expired events','eventorganiser'),
			'P1D'=>__('Events within 24 hours', 'eventorganiser'),
			'P1W'=>__('Events within 1 week','eventorganiser'),
			'P2W'=> sprintf(__('Events within %d weeks','eventorganiser'), 2),
			'P1M'=>__('Events within 1 month','eventorganiser'),
			'P6M'=> sprintf(__('Events within %d months','eventorganiser'), 6),
			'P1Y'=>__('Events within 1 year','eventorganiser'),
		);
		$current = (!empty($wp_query->query_vars['eo_interval']) ? $wp_query->query_vars['eo_interval'] : 'all');	
?>
		<select style="width:150px;" name='eo_interval' id='show-events-in-interval' class='postform'>
			<?php foreach ($intervals as $id=>$interval): ?>
				<option value="<?php echo $id; ?>" <?php selected($current,$id)?>> <?php echo $interval;?> </option>
			<?php endforeach; ?>
		</select>
<?php
	endif;//End if CPT is event
}


add_action('quick_edit_custom_box',  'eventorganiser_quick_edit_box', 10, 2);
 function eventorganiser_quick_edit_box($column_name, $post_type) {
	if ($column_name != 'venue' || $post_type !='event') return;?>

	<fieldset class="inline-edit-col-left"><div class="inline-edit-col">
	<?php wp_nonce_field('eventorganiser_event_quick_edit','_eononce');?>
		<label class="">
			<span class="title">Event Venue</span><?php
			$args = array('show_option_all' =>'No venue','orderby'=> 'name','hide_empty' => 0, 'name'=> 'eo_input[event-venue]','id'=> 'eventorganiser_venue','taxonomy'=> 'event-venue');
			 wp_dropdown_categories( $args ); ?>
	</label>
	</div></fieldset>
	<?php
}

add_action('bulk_edit_custom_box',  'eventorganiser_bulk_edit_box', 10, 2);
 function eventorganiser_bulk_edit_box($column_name, $post_type) {
	if ($column_name != 'venue' || $post_type !='event') return;?>

	<fieldset class="inline-edit-col-left"><div class="inline-edit-col">
	<?php wp_nonce_field('eventorganiser_event_bulk_edit','_eononce');?>
		<label class="">
			<span class="title">Event Venue</span><?php
			$args = array('show_option_none' => __( '&mdash; No Change &mdash;' ),'orderby'=> 'name','hide_empty' => 0, 'name'=> 'eo_input[event-venue]','id'=> 'eventorganiser_venue_bulk','taxonomy'=> 'event-venue');
			 wp_dropdown_categories( $args ); ?>
	</label>
	</div></fieldset>
	<?php
}


add_action('load-edit.php','eventorganiser_bulk_edit_handler');
function eventorganiser_bulk_edit_handler(){
	$action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : '');

	//Make sure we a bulk-editing events
	if($action!='edit' || !isset($_REQUEST['bulk_edit']) || !isset($_REQUEST['post_type']) || $_REQUEST['post_type'] != 'event' )
		return;
				
	//Check permissions
	if(!current_user_can( 'edit_events' ) || !current_user_can('manage_venues'))
		wp_die( __('You are not allowed to edit posts.'));

	//Check nonces
	check_admin_referer('bulk-posts');

	if(!isset($_REQUEST['_eononce']) || !wp_verify_nonce($_REQUEST['_eononce'],'eventorganiser_event_bulk_edit' ))
		return;

	//Sanitize and Check venue id / posts ids.
	$venue_id =(isset($_REQUEST['eo_input']['event-venue']) ? (int) $_REQUEST['eo_input']['event-venue']: 0);
	$post_ids = array_map( 'intval', (array) $_REQUEST['post'] );
	if($venue_id < 1|| empty($post_ids)) return;

	global $wpdb,$eventorganiser_events_table;

	foreach ($post_ids as $post_id){
		if(!current_user_can('edit_event', $post_id ))
			continue;
		wp_set_post_terms( $post_id, array($venue_id), 'event-venue', false );
		$upd = $wpdb->update( $eventorganiser_events_table, array('Venue'=>$venue_id), array( 'post_id' => $post_id ));
	}
}


add_action('save_post','eventorganiser_quick_edit_save');
function eventorganiser_quick_edit_save($post_id) {
	global $wpdb,$eventorganiser_events_table;

	// verify this is not an auto save routine. 
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;

	//make sure data came from our meta box
	if(!isset($_POST['_eononce']) || !wp_verify_nonce($_POST['_eononce'],'eventorganiser_event_quick_edit' ))
		return;

	//authentication checks
	if (!current_user_can('edit_event', $post_id)) return $post_id;

	$raw_data = (isset($_POST['eo_input']) ? $_POST['eo_input'] : array());
	$venue_id =(isset($raw_data['event-venue']) ? (int) $raw_data['event-venue'] : 0);

	//Update venue
	$r = wp_set_post_terms( $post_id, array($venue_id), 'event-venue', false );
	$upd = $wpdb->update( $eventorganiser_events_table, array('Venue'=>$venue_id), array( 'post_id' => $post_id ));
}


add_action('admin_head-edit.php', 'quick_add_script');
function quick_add_script() { ?>
    <script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('a.editinline').live('click', function() {
		jQuery('#eventorganiser_venue option[value=0]').attr('selected', 'selected');
            var id = inlineEditPost.getId(this);
            var val = parseInt(jQuery('#post-' + id + ' td.column-venue input').val());
		jQuery('#eventorganiser_venue option[value="'+val+'"]').attr('selected', 'selected');
        });
    });
    </script>
    <?php
}

?>