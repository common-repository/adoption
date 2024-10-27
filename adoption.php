<?php
/**
 * Plugin Name:	Adoption
 * Description: Shows user registration statistics as a dashboard widget.
 * Version: 	0.1.1
 * Author: 		Chris Aprea
 * Author URI: 	http://twitter.com/chrisaprea
 * License: 	GPL2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit; 

class Adoption {

	private $periods = array();

	function __construct() {
	
		// Hook into the 'wp_dashboard_setup' action to register our other functions
		add_action( 'wp_dashboard_setup', array( $this, 'adoption_add_dashboard_widgets' ) );

		// Enqueue scripts only for dashboard (i.e. /wp-admin/)
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_resources' ) );
		
		// Handle ajax request for logged in users
		add_action( 'wp_ajax_adoption-get-users', array( $this, 'process_users' ) );
		
		// Manage Permissions
		add_action( 'init', array( $this, 'handle_permissions' ) );
		
		// Manage languages and i18n
		add_action( 'init', array( $this, 'handle_i18n' ) );
	
		$this->periods = array(
				'this_week' 			=> __( 'This Week', 'adoption' ),
				'last_week' 			=> __( 'Last Week', 'adoption' ),
				'this_month' 			=> __( 'This Month', 'adoption' ) ,
				'last_month' 			=> __( 'Last Month', 'adoption' ),
				'last_three_months' 	=> __( 'Last 3 Months', 'adoption' ),
				'last_six_months' 		=> __( 'Last 6 Months', 'adoption' ),
				'last_twelve_months' 	=> __( 'Last 12 Months', 'adoption' )
			);

	}
	
	function handle_i18n(){

		load_plugin_textdomain( 'adoption', false, basename( dirname( __FILE__ ) ) . '/languages' );

	}
	
	// Integration with Justin Tadlocks "members" plugin
	function handle_permissions(){
		
		if ( function_exists( 'members_get_capabilities' ) ){

			add_filter( 'members_get_capabilities', array( $this, 'extra_caps' ) );
		
		}
	
	}
	
	// Add our custom capability
	function extra_caps( $caps ){

		$caps[] = 'view_adoption_widget';

		return $caps;

	}
	
	function enqueue_dashboard_resources( $hook ){
	
		if( $hook != 'index.php' ) 
			return;
	
		// Enqueue our scripts scripts
		wp_enqueue_script( 'dashboard_script', plugins_url( '/js/dashboard_script.js' , __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'jqplot', plugins_url( '/js/jquery.jqplot.js' , __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'jqplot_bar_renderer', plugins_url( '/js/jqplot.barRenderer.min.js' , __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'jqplot_category_access_renderer', plugins_url( '/js/jqplot.categoryAxisRenderer.min.js' , __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'jqplot_highlighter', plugins_url( '/js/jqplot.highlighter.min.js' , __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'ajax_spinner', plugins_url( '/js/spin.min.js' , __FILE__ ), array( 'jquery' ) );
		
		// Enqueue Styles
		wp_enqueue_style( 'jqplot', plugins_url( '/css/jquery.jqplot.css' , __FILE__ ) );
		wp_enqueue_style( 'adoption', plugins_url( '/css/style.css' , __FILE__ ) );
		
		// Add our ajax error message
		echo '<script type="text/javascript">var adoption_error_message = \'' . __( 'An error occured, please visit the support forums on wordpress.org and lodge a detailed ticket for this plugin.', 'adoption' ) . '\';</script>';
	
	}

	// Create the function to output the contents of our Dashboard Widget
	function adoption_dashboard_widget() { 

		$counts = $this->get_user_counts();

		extract( $counts, EXTR_SKIP );

		global $current_user;

		get_currentuserinfo();
	
		$period = $this->get_user_period_preference( $current_user );

		$result = count_users();

		?>

		<div class="select_range">
	
			<span><?php _e( 'Select period', 'adoption' ); ?>:</span>
		
				<select>

					<?php
					
						foreach( $this->periods as $key => $value ){ ?>
						
							<option value="<?php echo $key; ?>"<?php echo ($period == $key ? ' selected="selected" ' : '' ); ?>><?php echo $value; ?></option>

						<?php

						}

					?>

				</select>

		</div>
	
		<div id="plot" style="width: 100%; height: 175px; display: block;">
		
			<noscript class="no_js"><?php _e( 'The graphing engine requires JavaScript to be enabled.', 'adoption' ); ?></noscript>
		
		</div>
		
		<div class="stats_info">
		
			<div class="table_content">
		
				<p class="sub"><?php _e( 'Quick Statistics', 'adoption' ); ?></p>
				
				<div class="quick-stats">
				
					<table>
						<tbody>
							<tr><td><?php _e( 'Today', 'adoption' ); ?>:</td><td><?php echo $today; ?></td><td><?php _e( 'Last Month', 'adoption' ); ?>:</td><td><?php echo $last_month; ?></td></tr>
							<tr><td><?php _e( 'Yesterday', 'adoption' ); ?>:</td><td><?php echo $yesterday; ?></td><td><?php _e( 'Last 3 Months', 'adoption' ); ?>:</td><td><?php echo $last_three_months; ?></td></tr>
							<tr><td><?php _e( 'This Week', 'adoption' ); ?>:</td><td><?php echo $this_week; ?></td><td><?php _e( 'Last 6 Months', 'adoption' ); ?>:</td><td><?php echo $last_six_months; ?></td></tr>
							<tr><td><?php _e( 'Last Week', 'adoption' ); ?>:</td><td><?php echo $last_week; ?></td><td><?php _e( 'Last 12 Months', 'adoption' ); ?>:</td><td><?php echo $last_twelve_months; ?></td></tr>
							<tr><td><?php _e( 'This Month', 'adoption' ); ?>:</td><td><?php echo $this_month; ?></td><td><?php _e( 'All Time', 'adoption' ); ?>:</td><td><?php echo $total_users; ?></td></tr>
						</tbody>
					</table>
				
				</div>
			
			</div>
			
			<div class="table_content breakdown">
		
				<p class="sub"><?php _e( 'User Breakdown', 'adoption' ); ?></p>
				
					<table>
						<tbody>
							<?php
							
								foreach( $result['avail_roles'] as $role => $count ){ 
								
									$role = ucwords( $role );
								
									?>

									<tr>
									
										<td><?php 
										
												if( $count > 1 ){

													_e( $role . 's', 'adoption' );
												
												}
												else{
												
													_e( $role, 'adoption' );
												
												} ?>

										</td>

										<td><?php echo number_format_i18n( $count ); ?></td>

									</tr>

								<?php

								}

							?>
						</tbody>
					</table>
			
			</div>

		</div>
	
	<?php
	}
	
	// Create the function use in the action hook
	function adoption_add_dashboard_widgets() {

		if ( current_user_can( 'install_plugins' ) || ( function_exists( 'members_check_for_cap' ) && members_check_for_cap( 'view_adoption_widget' ) ) ){
	
			wp_add_dashboard_widget( 'adoption_dashboard_widget', __( 'User Registration Statistics', 'adoption' ), array( $this, 'adoption_dashboard_widget' ) );
			
		}

	}
	
	function get_users( $args ) {
	
		global $wpdb;
		
		$defaults = array(
						'from_date' => '1000-01-01 00:00:00',
						'to_date'	=> current_time('mysql'),
						'count'		=> false
						);
		
		$merged_args = wp_parse_args( $args, $defaults );
		
		extract( $merged_args, EXTR_SKIP );
	
		if( $count ){
		
			$query = "SELECT COUNT(*) user_registered from $wpdb->users where user_registered >= '$from_date' and user_registered <= '$to_date' order by user_registered asc";
			
			return $wpdb->get_var( $query );
		
		}
		else{
		
			$query = "SELECT user_registered from $wpdb->users where user_registered >= '$from_date' and user_registered <= '$to_date' order by user_registered asc";
			
			return $wpdb->get_results( $query, ARRAY_N );
		
		}

	}
	
	function get_count_queries(){
	
		$current_time = new DateTime( current_time( 'mysql' ) );
	
		/**
		 * As design choice when we calculate the last few months (i.e. last 3, 6, 12) we return the specified
		 * amount plus the current month, so we're actually returning the specified amount +1.
		 * This option allow users to only few the specified amount.
		 */
		 
		$minus_one_month = 0;
		
		$minus_one_month = apply_filters( 'adoption_minus_one_month', $minus_one_month );
	
		// Today
		$today = clone $current_time;
		
		$today->setTime( 00, 00 );
		
		$count_queries['today']['start'] = $today->format( 'Y-m-d H:i:s' );

		$count_queries['today']['end'] = current_time( 'mysql' );
		
		// Yesterday
		$yesterday_start = clone $current_time;

		$yesterday_start->modify( '-1 day' );
		
		$yesterday_start->setTime( 00, 00 );
		
		$yesterday_end = clone $yesterday_start;
		
		$yesterday_end->setTime( 23, 59, 59 );
		
		$count_queries['yesterday']['start'] = $yesterday_start->format( 'Y-m-d H:i:s' );

		$count_queries['yesterday']['end'] = $yesterday_end->format( 'Y-m-d H:i:s' );
		
		// This week
		$this_week_start = clone $current_time;
		
		// Need to correct start/end days for php - php assumes week starts on Sunday, we want the start day to be Monday
		if( $this_week_start->format( 'D' ) == 'Sun' ){
			$this_week_start->modify( '-1 day' );
		}
		
		$this_week_start->modify( 'Monday this week' );
		
		$this_week_start->setTime( 00, 00 );
		
		$count_queries['this_week']['start'] = $this_week_start->format( 'Y-m-d H:i:s' );

		$count_queries['this_week']['end'] = current_time( 'mysql' );
		
		// Last Week
		$last_week_start = clone $current_time;
		
		// Need to correct start/end days for php - php assumes week starts on Sunday, we want the start day to be Monday
		if( $last_week_start->format( 'D' ) == 'Sun' ){
			$last_week_start->modify( '-1 day' );
		}
		
		$last_week_start->modify( 'Monday last week' );
		
		$last_week_start->setTime( 00, 00 );
		
		$last_week_end = clone $last_week_start;
		
		$last_week_end->modify( '+6 days' );
		
		$last_week_end->setTime( 23, 59, 59 );
		
		$count_queries['last_week']['start'] = $last_week_start->format( 'Y-m-d H:i:s' );

		$count_queries['last_week']['end'] = $last_week_end->format( 'Y-m-d H:i:s' );
		
		// This Month
		$this_month_start = clone $current_time;
		
		$this_month_start->modify( 'First day of this month' );
		
		$this_month_start->setTime( 00, 00 );
		
		$count_queries['this_month']['start'] = $this_month_start->format( 'Y-m-d H:i:s' );

		$count_queries['this_month']['end'] = current_time( 'mysql' );
		
		// Last Month
		$last_month_start = clone $current_time;
		
		$last_month_start->modify( 'First day of last month' );
		
		$last_month_start->setTime( 00, 00 );
		
		$last_month_end = clone $last_month_start;
		
		$last_month_end->setTime( 23, 59, 59 );
		
		$count_queries['last_month']['start'] = $last_month_start->format( 'Y-m-d H:i:s' );

		$count_queries['last_month']['end'] = $last_month_end->format( 'Y-m-t H:i:s' );
		
		// Last Three Months
		$last_three_months_start = clone $current_time;
		
		$month_count = 3 - $minus_one_month;
		
		$last_three_months_start->modify( "First day of -$month_count month" );
		
		$last_three_months_start->setTime( 00, 00 );
		
		$count_queries['last_three_months']['start'] = $last_three_months_start->format( 'Y-m-d H:i:s' );

		$count_queries['last_three_months']['end'] = current_time( 'mysql' );
		
		// Last Six Months
		$last_six_months_start = clone $current_time;
		
		$month_count = 6 - $minus_one_month;
		
		$last_six_months_start->modify( "First day of -$month_count month" );
		
		$last_six_months_start->setTime( 00, 00 );
		
		$count_queries['last_six_months']['start'] = $last_six_months_start->format( 'Y-m-d H:i:s' );

		$count_queries['last_six_months']['end'] = current_time( 'mysql' );
	
		// Last Twelve Months
		$last_twelve_months_start = clone $current_time;
		
		$month_count = 12 - $minus_one_month;
		
		$last_twelve_months_start->modify( "First day of -$month_count month" );
		
		$last_twelve_months_start->setTime( 00, 00 );
		
		$count_queries['last_twelve_months']['start'] = $last_twelve_months_start->format( 'Y-m-d H:i:s' );

		$count_queries['last_twelve_months']['end'] = current_time( 'mysql' );
		
		return apply_filters( 'adoption_periods', $count_queries );
	
	}
	
	function get_user_counts(){
	
		$count_queries = $this->get_count_queries();
		
		foreach( $count_queries as $key => $value ){
		
			$counts[$key] = number_format_i18n( $this->get_users( array( 'from_date' => $value['start'], 'to_date' => $value['end'], 'count' => true ) ) );
		
		}
		
		// All time stats
		$total_users = count_users();
		
		$counts['total_users'] = $total_users['total_users'];
		
		return $counts;
	
	}
	
	function get_user_period_preference( $current_user ){
	
		$period = get_user_meta( $current_user->ID, 'adoption_period_preference', true );
		
		if( empty( $period ) ){
		
			$period = 'this_week';
		
		}
		
		return $period;
	
	}

	function process_users(){
	
		$period = $_POST['type'];
		
		$count_queries = $this->get_count_queries();
		
		if( $period != 'load' && ! array_key_exists( $period, $count_queries ) ){
		
			echo json_encode( array( 'error' => '1' ) );
			
			exit;
		
		}
	 
		global $current_user;
		
		get_currentuserinfo();
		
		if( $period == 'load' ){
			
			$period = $this->get_user_period_preference( $current_user );

		}
		
		update_user_meta( $current_user->ID, 'adoption_period_preference', $period );
	 
		$users = $this->get_users( array( 'from_date' => $count_queries[$period]['start'], 'to_date' => $count_queries[$period]['end'] ) );
		
		$data = array( 'iterations' => array(), 'registrations' => array() );
		
		if( $period == 'last_six_months' || $period == 'last_three_months' || $period == 'last_twelve_months' ){
		
			$date_ticks = 'M';
			
			$additional_iterations = 'first day of next month';
		
		}
		else{
			
			if( $period == 'this_week' || $period == 'last_week' ){
			
				$date_ticks = 'D jS';
			
			}
			else{
			
				$date_ticks = 'jS';
			
			}
		
			$additional_iterations = '+1 day';
		
		}
		
		// Process user registration, organise data for easily plugging with jqPlot
		foreach( $users as $user ){
			
			$date = new DateTime( $user[0] );
			
			if( empty( $data['iterations'] ) ){
			
				$data['iterations'][] = $date->format( $date_ticks );
				
				$data['registrations'][] = 1;
			
			}
			else{
			
				if( $data['iterations'][ count( $data['iterations'] ) - 1 ] != $date->format( $date_ticks ) ){
					
					$last_date->modify( $additional_iterations );
					
					while( $last_date->format( $date_ticks ) != $date->format( $date_ticks ) ){
						
						$data['iterations'][] = $last_date->format( $date_ticks );

						$data['registrations'][ count( $data['iterations'] ) - 1 ] = 0;
						
						$last_date->modify( $additional_iterations );
						
					}
					
					$data['iterations'][] = $date->format( $date_ticks );

					$data['registrations'][ count( $data['iterations'] ) - 1 ] = 1;
					
				}
				else{
				
					++$data['registrations'][ count( $data['iterations'] ) - 1 ];
				
				}
				
			}
			
			$last_date = $date;

		}
		
		if( empty( $user ) ){
		
			$current_time = new DateTime( current_time('mysql') );
		
			$data['iterations'][] = $current_time->format( 'D jS' );
			
			$data['registrations'][] = 0;
		
		}
		
		echo json_encode( $data );

		// IMPORTANT: don't forget to "exit"
		exit;

	}

}

// Start up this plugin
add_action( 'init', 'Adoption' );
function Adoption() {
	global $Adoption;
	$Adoption = new Adoption();
}

?>