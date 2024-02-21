<?php
/*
Plugin Name: Websentia Sort PMPro Directory
Plugin URI: https://websentia.com/add-body-class-with-acf/
Description: Via PMPro Support
Author: Steve Horn / Andrew Lima
Version: 1.0
Author URI: http://websentia.com
*/

/**
 * Creates a widget called "My PMPro Directory Widget".
 * 
 * Requires a theme that supports Widget functionality.
 * Search for "///" in the code on where to adjust values. It is highly recommended that a developer assists with this snippet when wanting to add more options.
 * DOESN'T WORK WITH MULTISELECT/SELECT2 OR ANY VALUES STORED AS AN ARRAY. SUPPORTS SINGLE VALUE STORED OPTIONS FROM A DROPDOWN OR TEXT FIELD.
 *
 * Difficulty: Intermediate - Advanced
 *
 * Add this code to your site by following this guide - https://www.paidmembershipspro.com/create-a-plugin-for-pmpro-customizations/
 */
class My_PMPro_Directory_Widget extends WP_Widget {

	/**
	 * Sets up the widget
	 */
	public function __construct() {
		parent::__construct(
			'my_pmpro_directory_widget',
			'My PMPro Directory Widget',
			array( 'description' => 'Filter the PMPro Member Directory' )
		);
	}

	/**
	 * Code that runs on the frontend.
	 *
	 * Modify the content in the <li> tags to
	 * create filter inputs in the sidebar
	 */
	public function widget( $args, $instance ) {
		// If we're not on a page with a PMPro directory, return.
		global $post;

		// Are we on a post or page, if not bail.
		if ( empty( $post->ID ) ) {
			return;
		}

		// Doesn't have the block or shortcode, bail.
		if ( ! has_shortcode( $post->post_content, 'pmpro_member_directory' ) && ! has_block( 'pmpro-member-directory/directory' ) ) {
			return;
		}
		?>
		<style>
			.my_pmpro_directory_widget form ul li {
				list-style-type: none;
			}
		</style>
		<aside id="my_pmpro_directory_widget" class="widget my_pmpro_directory_widget">
			<h3 class="widget-title">Filter Directory</h3>
			<form>
				<ul>
	
					<li class="sortByProfession">
						<strong>Profession:</strong><br/>
						<?php
						// Set up values to filter for. - /// ADJUST THIS ARRAY FOR MORE PROFESSION OPTIONS
						$profession = array(
							'Family Law Attorney' => 'Family Law Attorney',
                            'Mediator' => 'Mediator',
                            'Financial Specialist' => 'Financial Specialist',
							'Coach' => 'Coach',
							'Civil Law Attorney' => 'Civil Law Attorney',
                            'Child Specialist' => 'Child Specialist',
                            'Other' => 'Other',
						);

						foreach ( $profession as $key => $value ) {
							// Check if this value should default to be checked.
							$checked_modifier = '';
							if ( ! empty( $_REQUEST['profession'] ) && in_array( $key, $_REQUEST['profession'] ) ) {
								$checked_modifier = ' checked';
							}
							// Add checkbox.
							echo '<input type="checkbox" name="profession[]" value="' . $key . '"' . $checked_modifier . '><label> ' . $value . '</label><br/>';
						}
						?>
					</li>
					
					
					<li class="sortByProfession">
						<strong>Counties Served:</strong><br/>
						<?php
						// Set up values to filter for. - /// ADJUST THIS ARRAY FOR MORE PROFESSION OPTIONS
						$counties_served = array(
							'Asotin County' => 'Asotin County',
                            'Benton County' => 'Benton County',
                            'Chelan County' => 'Chelan County',
                            'Kitsap County' => 'Kitsap County',
						);

						foreach ( $counties_served as $key => $value ) {
							// Check if this value should default to be checked.
							$checked_modifier = '';
							if ( ! empty( $_REQUEST['counties_served'] ) && in_array( $key, $_REQUEST['counties_served'] ) ) {
								$checked_modifier = ' checked';
							}
							// Add checkbox.
							echo '<input type="checkbox" name="counties_served[]" value="' . $key . '"' . $checked_modifier . '><label> ' . $value . '</label><br/>';
						}
						?>
					</li>					
					
					
					
					<li><input type="submit" value="Filter"></li>
				</ul>
			</form>
		</aside>
		<?php
	}

} // End of Class

/**
 * Check $_REQUEST for parameters from the widget. Add to SQL query.
 */
function my_pmpro_directory_widget_filter_sql_parts( $sql_parts, $levels, $s, $pn, $limit, $start, $end, $order_by, $order ) {
	global $wpdb;

	// Filter results based on ares of practice is selected.
	if ( ! empty( $_REQUEST['profession'] ) && is_array( $_REQUEST['profession'] ) ) {
		$sql_parts['JOIN']    .= " LEFT JOIN $wpdb->usermeta um_profession ON um_profession.meta_key = 'profession' AND u.ID = um_profession.user_id ";
		$sql_parts['WHERE']   .= ' AND ( ';
		$first_profession = true;
		foreach ( $_REQUEST['profession'] as $profession ) {
			if ( $first_profession ) {
				$first_profession = false;
			} else {
				$sql_parts['WHERE'] .= ' OR ';
			}
			$sql_parts['WHERE'] .= " um_profession.meta_value like '%{$profession}%' ";
		}
		$sql_parts['WHERE'] .= ' ) ';
	}


	
	// Filter results based on counties served as selected.
	if ( ! empty( $_REQUEST['counties_served'] ) && is_array( $_REQUEST['counties_served'] ) ) {
		$sql_parts['JOIN']    .= " LEFT JOIN $wpdb->usermeta um_counties ON um_counties.meta_key = 'counties_served' AND u.ID = um_counties.user_id ";
		$sql_parts['WHERE']   .= ' AND ( ';
		$first_profession = true;
		foreach ( $_REQUEST['counties_served'] as $counties_served ) {
			if ( $first_profession ) {
				$first_profession = false;
			} else {
				$sql_parts['WHERE'] .= ' OR ';
			}
			$sql_parts['WHERE'] .= " um_profession.meta_value like '%{$profession}%' ";
		}
		$sql_parts['WHERE'] .= ' ) ';
	}

	return $sql_parts;
	
	
	
	
}
add_filter( 'pmpro_member_directory_sql_parts', 'my_pmpro_directory_widget_filter_sql_parts', 10, 9 );

/**
 * Registers widget.
 */
function my_pmpro_register_directory_widget() {
	register_widget( 'My_PMPro_Directory_Widget' );
}
add_action( 'widgets_init', 'my_pmpro_register_directory_widget' );

/**
 * Remember filters being used while using "Next" and "Previous" buttons.
 *
 * @since 2020/06/25
 */
function my_pmpromd_pagination_url_filter_directory( $query_args ) {
	$directory_filters = array( 'membership_levels', 'profession' ); /// ADJUST THIS VALUE IF YOU ADD MORE FILTERS
	foreach ( $directory_filters as $directory_filter ) {
		if ( ! empty( $_REQUEST[ $directory_filter ] ) ) {
        		$query_args[ $directory_filter ] =  $_REQUEST[ $directory_filter ];
    		}
	}
	return $query_args;
}
add_filter( 'pmpromd_pagination_url', 'my_pmpromd_pagination_url_filter_directory' );