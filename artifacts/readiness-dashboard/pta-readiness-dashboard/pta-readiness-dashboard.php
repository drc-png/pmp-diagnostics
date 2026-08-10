<?php
/**
 * Plugin Name: PMP Training Academy Readiness Dashboard
 * Description: Stores authenticated learner assessment results in WordPress and optionally mirrors them to Google Sheets.
 * Version: 2.5.5
 * Author: PMP Training Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PTA_READINESS_DB_VERSION', '2.5.5' );

function pta_readiness_table() {
	global $wpdb;
	return $wpdb->prefix . 'pta_assessment_attempts';
}

function pta_readiness_install() {
	global $wpdb;
	$table_name      = pta_readiness_table();
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE $table_name (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL,
		assessment_key varchar(100) NOT NULL,
		checkpoint varchar(191) NOT NULL,
		server_reference varchar(100) NOT NULL,
		client_reference varchar(100) NOT NULL DEFAULT '',
		completion_status varchar(20) NOT NULL DEFAULT 'Completed',
		overall_percent decimal(5,2) DEFAULT NULL,
		overall_score varchar(40) NOT NULL DEFAULT '',
		people_percent decimal(5,2) DEFAULT NULL,
		people_score varchar(40) NOT NULL DEFAULT '',
		process_percent decimal(5,2) DEFAULT NULL,
		process_score varchar(40) NOT NULL DEFAULT '',
		business_percent decimal(5,2) DEFAULT NULL,
		business_score varchar(40) NOT NULL DEFAULT '',
		topic_details longtext NULL,
		answered_count int(10) unsigned NOT NULL DEFAULT 0,
		unanswered_count int(10) unsigned NOT NULL DEFAULT 0,
		completion_seconds int(10) unsigned NOT NULL DEFAULT 0,
		average_seconds decimal(8,2) DEFAULT NULL,
		flagged_count int(10) unsigned NOT NULL DEFAULT 0,
		answer_change_count int(10) unsigned NOT NULL DEFAULT 0,
		assessment_version varchar(100) NOT NULL DEFAULT '',
		sheet_mirrored tinyint(1) unsigned NOT NULL DEFAULT 0,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY server_reference (server_reference),
		KEY user_created (user_id,created_at),
		KEY user_assessment (user_id,assessment_key),
		KEY client_reference (user_id,client_reference)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	update_option( 'pta_readiness_db_version', PTA_READINESS_DB_VERSION );
	pta_readiness_register_routes();
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'pta_readiness_install' );

add_action(
	'plugins_loaded',
	function () {
		if ( get_option( 'pta_readiness_db_version' ) !== PTA_READINESS_DB_VERSION ) {
			pta_readiness_install();
		}
	}
);

function pta_readiness_catalog() {
	return apply_filters(
		'pta_readiness_assessment_catalog',
		array(
			'baseline'       => array(
				'title'      => 'Checkpoint 1: Baseline Diagnostic',
				'src'        => 'https://readiness.pmptrainingacademy.com/readiness-assessment.html?v=20260810-2',
				'checkpoint' => 'PMP 2026 Readiness Assessment - Lead Diagnostic',
			),
			'rapid-fire'     => array(
				'title'      => 'Rapid Fire 180',
				'src'        => 'https://readiness.pmptrainingacademy.com/rapid-fire-180.html',
				'checkpoint' => 'Rapid Fire 180',
			),
			'practice-set-1' => array(
				'title'      => 'Practice Set 1: Teams, Stakeholders and Conflict',
				'src'        => 'https://readiness.pmptrainingacademy.com/practice-set-1-refined-direct.html',
				'checkpoint' => 'Practice Set 1 - Teams, Stakeholders and Conflict',
			),
			'practice-set-2' => array(
				'title'      => 'Practice Set 2: Planning and Scope Control',
				'src'        => 'https://readiness.pmptrainingacademy.com/practice-set-2-refined-direct.html',
				'checkpoint' => 'Practice Set 2 - Planning and Scope Control',
			),
			'practice-set-3' => array(
				'title'      => 'Practice Set 3: Execution and Monitoring',
				'src'        => 'https://readiness.pmptrainingacademy.com/practice-set-3-refined-direct.html',
				'checkpoint' => 'Practice Set 3 - Execution and Monitoring',
			),
			'practice-set-4' => array(
				'title'      => 'Practice Set 4: Agile and Hybrid Deep Dive',
				'src'        => 'https://readiness.pmptrainingacademy.com/practice-set-4-refined-direct.html',
				'checkpoint' => 'Practice Set 4 - Agile and Hybrid Deep Dive',
			),
			'practice-set-5' => array(
				'title'      => 'Practice Set 5: Business Environment and Strategic Alignment',
				'src'        => 'https://readiness.pmptrainingacademy.com/practice-set-5-refined-direct.html',
				'checkpoint' => 'Practice Set 5 - Business Environment and Strategic Alignment',
			),
			'diagnostic-1'   => array(
				'title'      => 'Assessment 1',
				'src'        => 'https://readiness.pmptrainingacademy.com/diagnostic-1-full.html?v=20260810-2',
				'checkpoint' => 'Full-Length Readiness Diagnostic 1 - PMP 2026',
			),
			'diagnostic-2'   => array(
				'title'      => 'Assessment 2',
				'src'        => 'https://readiness.pmptrainingacademy.com/diagnostic-2-full.html?v=20260810-2',
				'checkpoint' => 'Practice Readiness Assessment 2',
				'aliases'    => array( 'Full-Length Readiness Diagnostic 2 - PMP 2026' ),
			),
			'midpoint'       => array(
				'title'      => 'Checkpoint 2: Midpoint',
				'src'        => home_url( '/midpoint/' ),
				'checkpoint' => 'Checkpoint 2 (Midpoint)',
			),
			'diagnostic-3'   => array(
				'title'      => 'Assessment 3',
				'src'        => home_url( '/pmp-exam-simulator-practice-test-3/' ),
				'checkpoint' => 'Practice Readiness Assessment 3',
			),
			'diagnostic-4'   => array(
				'title'      => 'Assessment 4',
				'src'        => home_url( '/pmp-exam-simulator-practice-test-4/' ),
				'checkpoint' => 'Practice Readiness Assessment 4',
			),
			'diagnostic-5'   => array(
				'title'      => 'Assessment 5',
				'src'        => home_url( '/pmp-exam-simulator-practice-test-5/' ),
				'checkpoint' => 'Practice Readiness Assessment 5',
			),
		)
	);
}

function pta_readiness_masterstudy_menu_item( $menu_items, $menu_name = '' ) {
	if ( ! is_user_logged_in() || ! is_array( $menu_items ) ) {
		return $menu_items;
	}

	foreach ( $menu_items as $menu_item ) {
		if ( isset( $menu_item['id'] ) && 'pta_assessments' === $menu_item['id'] ) {
			return $menu_items;
		}
	}

	$is_instructor = class_exists( 'STM_LMS_Instructor' ) && STM_LMS_Instructor::is_instructor();
	$is_admin      = current_user_can( 'manage_options' );
	$is_active     = ! $is_admin && isset( $_GET['pta-assessments'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['pta-assessments'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$item          = array(
		'order'        => 149,
		'id'           => 'pta_assessments',
		'slug'         => 'pta-assessments',
		'menu_title'   => $is_admin ? 'Assessment Results' : 'Assessments',
		'menu_icon'    => 'stmlms-menu-enrolled-quizzes',
		'menu_url'     => $is_admin ? admin_url( 'users.php?page=pta-assessment-results' ) : add_query_arg( 'pta-assessments', '1', home_url( '/user-account/' ) ),
		'is_active'    => $is_active,
		'menu_place'   => $is_instructor ? 'main' : 'learning',
		'section'      => 'main',
	);

	$insert_at = count( $menu_items );
	foreach ( $menu_items as $index => $menu_item ) {
		if ( isset( $menu_item['id'] ) && in_array( $menu_item['id'], array( 'my_assignments', 'enrolled_quizzes' ), true ) ) {
			$insert_at = $index + 1;
		}
	}
	array_splice( $menu_items, $insert_at, 0, array( $item ) );

	return $menu_items;
}

add_filter( 'stm_lms_sorted_menu', 'pta_readiness_masterstudy_menu_item', 99, 2 );

function pta_readiness_register_routes() {
	add_rewrite_rule( '^pta-assessment/([^/]+)/?$', 'index.php?pta_assessment=$matches[1]', 'top' );
}

add_action( 'init', 'pta_readiness_register_routes' );
add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'pta_assessment';
		return $vars;
	}
);

function pta_readiness_webapp_url() {
	return defined( 'PTA_SCORE_WEBAPP_URL' ) ? PTA_SCORE_WEBAPP_URL : (string) get_option( 'pta_score_webapp_url', '' );
}

function pta_readiness_score_access_key() {
	return defined( 'PTA_SCORE_ACCESS_KEY' ) ? PTA_SCORE_ACCESS_KEY : (string) get_option( 'pta_score_access_key', '' );
}

function pta_readiness_import_token() {
	return defined( 'PTA_SCORE_IMPORT_TOKEN' ) ? PTA_SCORE_IMPORT_TOKEN : (string) get_option( 'pta_score_import_token', get_option( 'pta_score_webapp_token', '' ) );
}

function pta_readiness_mirror_enabled() {
	return (bool) get_option( 'pta_score_mirror_enabled', true );
}

function pta_readiness_email_enabled() {
	return (bool) get_option( 'pta_result_email_enabled', true );
}

add_action(
	'admin_init',
	function () {
		register_setting( 'pta_readiness_settings', 'pta_score_webapp_url', array( 'type' => 'string', 'sanitize_callback' => 'esc_url_raw', 'default' => '' ) );
		register_setting( 'pta_readiness_settings', 'pta_score_access_key', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'pta_readiness_settings', 'pta_score_import_token', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'pta_readiness_settings', 'pta_score_mirror_enabled', array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => true ) );
		register_setting( 'pta_readiness_settings', 'pta_result_email_enabled', array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => true ) );
	}
);

add_action(
	'admin_menu',
	function () {
		add_options_page( 'PMP Readiness Dashboard', 'PMP Readiness Dashboard', 'manage_options', 'pta-readiness-dashboard', 'pta_readiness_settings_page' );
		add_users_page( 'Assessment Results', 'Assessment Results', 'list_users', 'pta-assessment-results', 'pta_readiness_results_page' );
	}
);

function pta_readiness_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>PMP Readiness Dashboard</h1>
		<?php if ( isset( $_GET['pta_imported'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success"><p><?php echo esc_html( absint( $_GET['pta_imported'] ) . ' historical result(s) imported from Google Sheets.' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['pta_import_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-error"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['pta_import_error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['pta_manual_restored'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success"><p><?php echo esc_html( absint( $_GET['pta_manual_restored'] ) . ' assessment result(s) restored to WordPress.' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['pta_manual_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-error"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['pta_manual_error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
		<?php endif; ?>
		<p>WordPress is the system of record. Google Sheets can receive an optional reporting copy of each saved attempt.</p>
		<form action="options.php" method="post">
			<?php settings_fields( 'pta_readiness_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row">WordPress storage</th><td><strong>Active</strong><p class="description">Results are stored by immutable WordPress user ID.</p></td></tr>
				<tr><th scope="row"><label for="pta_score_mirror_enabled">Google Sheets reporting copy</label></th><td><input name="pta_score_mirror_enabled" type="hidden" value="0"><label><input id="pta_score_mirror_enabled" name="pta_score_mirror_enabled" type="checkbox" value="1" <?php checked( pta_readiness_mirror_enabled() ); ?>> Mirror new attempts to Google Sheets</label></td></tr>
				<tr><th scope="row"><label for="pta_result_email_enabled">Learner result email</label></th><td><input name="pta_result_email_enabled" type="hidden" value="0"><label><input id="pta_result_email_enabled" name="pta_result_email_enabled" type="checkbox" value="1" <?php checked( pta_readiness_email_enabled() ); ?>> Email the learner after a new completed assessment is saved</label><p class="description">Sent by WordPress and independent of the Google Sheets reporting copy.</p></td></tr>
				<tr><th scope="row"><label for="pta_score_webapp_url">Apps Script web app URL</label></th><td><input class="regular-text code" id="pta_score_webapp_url" name="pta_score_webapp_url" type="url" value="<?php echo esc_attr( pta_readiness_webapp_url() ); ?>"><p class="description">Optional deployed URL ending in <code>/exec</code>.</p></td></tr>
				<tr><th scope="row"><label for="pta_score_access_key">Sheet submission key</label></th><td><input class="regular-text" id="pta_score_access_key" name="pta_score_access_key" type="password" value="<?php echo esc_attr( pta_readiness_score_access_key() ); ?>" autocomplete="new-password"></td></tr>
				<tr><th scope="row"><label for="pta_score_import_token">Historical import token</label></th><td><input class="regular-text" id="pta_score_import_token" name="pta_score_import_token" type="password" value="<?php echo esc_attr( pta_readiness_import_token() ); ?>" autocomplete="new-password"><p class="description">Matches the Apps Script <code>PTA_DASHBOARD_TOKEN</code>. Used only for an administrator-triggered migration.</p></td></tr>
			</table>
			<?php submit_button( 'Save settings' ); ?>
		</form>
		<h2>Assessment Center shortcode</h2>
		<p>Place <code>[pta_assessment_center]</code> on the authenticated Assessment Center page. Students who are not signed in will see a login prompt.</p>
		<h2>Historical Google Sheets results</h2>
		<p>This imports every completed historical row for existing WordPress users, matched by normalized account email. It is safe to run more than once.</p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="pta_import_sheet_results">
			<?php wp_nonce_field( 'pta_import_sheet_results' ); ?>
			<?php submit_button( 'Import historical results', 'secondary', 'submit', false ); ?>
		</form>
		<p><button type="button" class="button button-secondary" id="pta-browser-history-import">Browser-assisted import</button> <span id="pta-browser-history-status" aria-live="polite"></span></p>
		<p class="description">Use the browser-assisted option if your web host blocks Google Apps Script redirects.</p>
		<h2>Restore a confirmed result</h2>
		<p>Use this only when a completed score report exists but the original submission did not reach WordPress. Domain fields may be left blank when they are unavailable.</p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="pta_restore_confirmed_result">
			<?php wp_nonce_field( 'pta_restore_confirmed_result' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="pta_restore_email">WordPress account email</label></th><td><input class="regular-text" id="pta_restore_email" name="email" type="email" required></td></tr>
				<tr><th scope="row"><label for="pta_restore_assessment">Assessment</label></th><td><select id="pta_restore_assessment" name="assessment_key" required><?php foreach ( pta_readiness_catalog() as $restore_key => $restore_item ) : ?><option value="<?php echo esc_attr( $restore_key ); ?>"><?php echo esc_html( $restore_item['title'] ); ?></option><?php endforeach; ?></select></td></tr>
				<tr><th scope="row"><label for="pta_restore_percent">Overall percent</label></th><td><input id="pta_restore_percent" name="overall_percent" type="number" min="0" max="100" step="0.01" required></td></tr>
				<tr><th scope="row"><label for="pta_restore_score">Overall score</label></th><td><input id="pta_restore_score" name="overall_score" type="text" placeholder="23 / 180"></td></tr>
				<tr><th scope="row"><label for="pta_restore_people">People percent</label></th><td><input id="pta_restore_people" name="people_percent" type="number" min="0" max="100" step="0.01"></td></tr>
				<tr><th scope="row"><label for="pta_restore_process">Process percent</label></th><td><input id="pta_restore_process" name="process_percent" type="number" min="0" max="100" step="0.01"></td></tr>
				<tr><th scope="row"><label for="pta_restore_business">Business Environment percent</label></th><td><input id="pta_restore_business" name="business_percent" type="number" min="0" max="100" step="0.01"></td></tr>
				<tr><th scope="row"><label for="pta_restore_reference">Submission reference</label></th><td><input class="regular-text code" id="pta_restore_reference" name="submission_reference" type="text" required><p class="description">The reference shown on the learner's completed score report.</p></td></tr>
				<tr><th scope="row"><label for="pta_restore_timestamp">Completed at</label></th><td><input id="pta_restore_timestamp" name="timestamp" type="datetime-local"></td></tr>
			</table>
			<?php submit_button( 'Restore confirmed result', 'secondary', 'submit', false ); ?>
		</form>
		<script>
		(function(){
			const button=document.getElementById('pta-browser-history-import');
			const status=document.getElementById('pta-browser-history-status');
			if(!button||!status)return;
			button.addEventListener('click',function(){
				const url=document.getElementById('pta_score_webapp_url').value.trim();
				const token=document.getElementById('pta_score_import_token').value;
				if(!url||!token){status.textContent='Save the Apps Script URL and historical import token first.';return;}
				button.disabled=true;
				status.textContent='Reading completed attempts from Google Sheets…';
				fetch(url+'?action=export&token='+encodeURIComponent(token),{redirect:'follow'})
					.then(function(response){if(!response.ok)throw new Error('Google export returned HTTP '+response.status);return response.json();})
					.then(function(data){if(data.error)throw new Error('Google export error: '+data.error);return fetch(<?php echo wp_json_encode( rest_url( 'pta/v1/admin-import' ) ); ?>,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':<?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>},body:JSON.stringify({attempts:data.attempts||[]})});})
					.then(function(response){return response.json().then(function(data){if(!response.ok||!data.ok)throw new Error(data.message||'WordPress import failed');return data;});})
					.then(function(data){status.textContent='Imported '+data.imported+'; duplicates '+data.duplicates+'; unmatched emails '+data.unmatched+'.';button.disabled=false;})
					.catch(function(error){status.textContent=error.message||'Browser-assisted import failed.';button.disabled=false;});
			});
		}());
		</script>
	</div>
	<?php
}

add_action( 'admin_post_pta_import_sheet_results', 'pta_readiness_import_sheet_results' );
add_action( 'admin_post_pta_restore_confirmed_result', 'pta_readiness_restore_confirmed_result' );

function pta_readiness_restore_confirmed_result() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You are not allowed to restore assessment results.' );
	}
	check_admin_referer( 'pta_restore_confirmed_result' );
	$catalog        = pta_readiness_catalog();
	$assessment_key = isset( $_POST['assessment_key'] ) ? sanitize_key( wp_unslash( $_POST['assessment_key'] ) ) : '';
	$email          = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$reference      = isset( $_POST['submission_reference'] ) ? sanitize_text_field( wp_unslash( $_POST['submission_reference'] ) ) : '';
	$redirect       = admin_url( 'options-general.php?page=pta-readiness-dashboard' );
	if ( ! $email || ! $reference || ! isset( $catalog[ $assessment_key ] ) ) {
		wp_safe_redirect( add_query_arg( 'pta_manual_error', rawurlencode( 'Email, assessment, and submission reference are required.' ), $redirect ) );
		exit;
	}
	$row = array(
		'email'                => $email,
		'checkpoint'           => $catalog[ $assessment_key ]['checkpoint'],
		'overall_percent'      => isset( $_POST['overall_percent'] ) ? sanitize_text_field( wp_unslash( $_POST['overall_percent'] ) ) : '',
		'overall_score'        => isset( $_POST['overall_score'] ) ? sanitize_text_field( wp_unslash( $_POST['overall_score'] ) ) : '',
		'people_percent'       => isset( $_POST['people_percent'] ) ? sanitize_text_field( wp_unslash( $_POST['people_percent'] ) ) : '',
		'process_percent'      => isset( $_POST['process_percent'] ) ? sanitize_text_field( wp_unslash( $_POST['process_percent'] ) ) : '',
		'business_percent'     => isset( $_POST['business_percent'] ) ? sanitize_text_field( wp_unslash( $_POST['business_percent'] ) ) : '',
		'submission_reference' => $reference,
		'timestamp'            => isset( $_POST['timestamp'] ) ? sanitize_text_field( wp_unslash( $_POST['timestamp'] ) ) : '',
		'assessment_version'   => 'Administrator confirmed recovery',
	);
	$stats = pta_readiness_import_rows( array( $row ) );
	if ( $stats['imported'] || $stats['duplicates'] ) {
		global $wpdb;
		$user             = get_user_by( 'email', $email );
		$client_reference = 'sheet-' . sha1( 'reference|' . $reference );
		$record           = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pta_readiness_table() . ' WHERE user_id=%d AND client_reference=%s ORDER BY id DESC LIMIT 1', $user ? $user->ID : 0, $client_reference ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $record ) {
			$wpdb->update( pta_readiness_table(), array( 'sheet_mirrored' => 0 ), array( 'id' => $record['id'] ), array( '%d' ), array( '%d' ) );
			if ( $user && pta_readiness_mirror_attempt( $record, $user ) ) {
				$wpdb->update( pta_readiness_table(), array( 'sheet_mirrored' => 1 ), array( 'id' => $record['id'] ), array( '%d' ), array( '%d' ) );
			}
		}
		wp_safe_redirect( add_query_arg( 'pta_manual_restored', $stats['imported'], $redirect ) );
		exit;
	}
	$error = $stats['unmatched'] ? 'No WordPress user matches that email address.' : 'The confirmed result could not be restored.';
	wp_safe_redirect( add_query_arg( 'pta_manual_error', rawurlencode( $error ), $redirect ) );
	exit;
}

function pta_readiness_import_rows( $rows ) {
	global $wpdb;
	$table   = pta_readiness_table();
	$catalog = pta_readiness_catalog();
	$stats   = array( 'imported' => 0, 'duplicates' => 0, 'unmatched' => 0, 'invalid' => 0, 'db_errors' => 0 );
	$users_by_email = array();
	foreach ( get_users( array( 'fields' => array( 'ID', 'user_email' ) ) ) as $user ) {
		$email = strtolower( trim( $user->user_email ) );
		if ( $email ) {
			$users_by_email[ $email ] = $user;
		}
	}
	foreach ( (array) $rows as $row ) {
		if ( ! is_array( $row ) ) {
			$stats['invalid']++;
			continue;
		}
		$email = strtolower( trim( isset( $row['email'] ) ? $row['email'] : '' ) );
		if ( ! isset( $users_by_email[ $email ] ) ) {
			$stats['unmatched']++;
			continue;
		}
				$user           = $users_by_email[ $email ];
				$checkpoint     = sanitize_text_field( isset( $row['checkpoint'] ) ? $row['checkpoint'] : '' );
				$assessment_key = '';
				foreach ( $catalog as $candidate_key => $candidate ) {
					if ( $candidate['checkpoint'] === $checkpoint || ( ! empty( $candidate['aliases'] ) && in_array( $checkpoint, $candidate['aliases'], true ) ) ) {
						$assessment_key = $candidate_key;
						break;
					}
				}
				if ( ! $assessment_key ) {
					$assessment_key = 'legacy-' . substr( md5( $checkpoint ), 0, 12 );
				}
				$timestamp        = sanitize_text_field( isset( $row['timestamp'] ) ? $row['timestamp'] : '' );
				$source_identity  = ! empty( $row['submission_reference'] ) ? 'reference|' . $row['submission_reference'] : ( ! empty( $row['attempt_id'] ) ? $row['attempt_id'] : $email . '|' . $checkpoint . '|' . $timestamp );
				$client_reference = 'sheet-' . sha1( $source_identity );
				$exists           = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE user_id=%d AND client_reference=%s LIMIT 1", $user->ID, $client_reference ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				if ( $exists ) {
					$stats['duplicates']++;
					continue;
				}
				$created = $timestamp ? strtotime( $timestamp ) : false;
				$inserted = $wpdb->insert(
					$table,
					array(
						'user_id'             => $user->ID,
						'assessment_key'      => $assessment_key,
						'checkpoint'          => $checkpoint,
						'server_reference'    => 'PTA-LEGACY-' . strtoupper( substr( sha1( $client_reference ), 0, 12 ) ),
						'client_reference'    => $client_reference,
						'completion_status'   => 'Completed',
						'overall_percent'     => pta_readiness_nullable_percent( $row, 'overall_percent' ),
						'overall_score'       => sanitize_text_field( isset( $row['overall_score'] ) ? $row['overall_score'] : '' ),
						'people_percent'      => pta_readiness_nullable_percent( $row, 'people_percent' ),
						'people_score'        => sanitize_text_field( isset( $row['people_score'] ) ? $row['people_score'] : '' ),
						'process_percent'     => pta_readiness_nullable_percent( $row, 'process_percent' ),
						'process_score'       => sanitize_text_field( isset( $row['process_score'] ) ? $row['process_score'] : '' ),
						'business_percent'    => pta_readiness_nullable_percent( $row, 'business_percent' ),
						'business_score'      => sanitize_text_field( isset( $row['business_score'] ) ? $row['business_score'] : '' ),
						'topic_details'       => isset( $row['topic_details'] ) ? wp_json_encode( $row['topic_details'] ) : '',
						'answered_count'      => absint( isset( $row['answered_count'] ) ? $row['answered_count'] : 0 ),
						'unanswered_count'    => absint( isset( $row['unanswered_count'] ) ? $row['unanswered_count'] : 0 ),
						'completion_seconds'  => absint( isset( $row['completion_seconds'] ) ? $row['completion_seconds'] : 0 ),
						'average_seconds'     => isset( $row['average_seconds'] ) && '' !== $row['average_seconds'] ? max( 0, (float) $row['average_seconds'] ) : null,
						'flagged_count'       => absint( isset( $row['flagged_count'] ) ? $row['flagged_count'] : 0 ),
						'answer_change_count' => absint( isset( $row['answer_change_count'] ) ? $row['answer_change_count'] : 0 ),
						'assessment_version'  => sanitize_text_field( isset( $row['assessment_version'] ) ? $row['assessment_version'] : '' ),
						'sheet_mirrored'      => 1,
						'created_at'          => $created ? gmdate( 'Y-m-d H:i:s', $created ) : current_time( 'mysql', true ),
					)
				);
				if ( false !== $inserted ) {
					$stats['imported']++;
				} else {
					$stats['db_errors']++;
				}
	}
	return $stats;
}

function pta_readiness_import_sheet_results() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You are not allowed to import assessment results.' );
	}
	check_admin_referer( 'pta_import_sheet_results' );
	$url   = pta_readiness_webapp_url();
	$token = pta_readiness_import_token();
	$error = '';
	$stats = array( 'imported' => 0, 'duplicates' => 0, 'unmatched' => 0, 'invalid' => 0, 'db_errors' => 0 );
	if ( ! $url || ! $token ) {
		$error = 'The Apps Script URL or historical import token is missing.';
	} else {
		$response = wp_remote_get(
			add_query_arg( array( 'action' => 'export', 'token' => $token ), $url ),
			array( 'timeout' => 60, 'redirection' => 10, 'headers' => array( 'User-Agent' => 'PMPTrainingAcademy-Readiness/2.3' ) )
		);
		if ( is_wp_error( $response ) ) {
			$error = 'WordPress could not reach Google Apps Script: ' . $response->get_error_message();
		} else {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $data ) || ! empty( $data['error'] ) || ! isset( $data['attempts'] ) ) {
				$error = 'Google Apps Script did not return a valid historical export.';
			} else {
				$stats = pta_readiness_import_rows( $data['attempts'] );
			}
		}
	}
	$args = array( 'pta_imported' => $stats['imported'] );
	if ( $error ) {
		$args['pta_import_error'] = $error;
	}
	wp_safe_redirect( add_query_arg( $args, admin_url( 'options-general.php?page=pta-readiness-dashboard' ) ) );
	exit;
}

function pta_readiness_results_page() {
	if ( ! current_user_can( 'list_users' ) ) {
		return;
	}
	global $wpdb;
	$table             = pta_readiness_table();
	$catalog           = pta_readiness_catalog();
	$student_filter    = isset( $_GET['pta_student'] ) ? sanitize_text_field( wp_unslash( $_GET['pta_student'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$assessment_filter = isset( $_GET['pta_assessment_filter'] ) ? sanitize_key( wp_unslash( $_GET['pta_assessment_filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$summary           = $wpdb->get_row( "SELECT COUNT(*) AS attempts,COUNT(DISTINCT user_id) AS learners,AVG(overall_percent) AS average_score FROM $table WHERE completion_status='Completed'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$learner_rows      = $wpdb->get_results( "SELECT a.*,u.display_name,u.user_email,tot.completed_count FROM $table a INNER JOIN (SELECT user_id,MAX(id) AS latest_id,COUNT(*) AS completed_count FROM $table WHERE completion_status='Completed' GROUP BY user_id) tot ON tot.latest_id=a.id LEFT JOIN {$wpdb->users} u ON u.ID=a.user_id ORDER BY a.created_at DESC LIMIT 250" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$where             = array( "a.completion_status='Completed'" );
	$params            = array();
	if ( $student_filter ) {
		$like     = '%' . $wpdb->esc_like( $student_filter ) . '%';
		$where[]  = '(u.display_name LIKE %s OR u.user_email LIKE %s OR u.user_login LIKE %s)';
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}
	if ( $assessment_filter && isset( $catalog[ $assessment_filter ] ) ) {
		$where[]  = 'a.assessment_key=%s';
		$params[] = $assessment_filter;
	}
	$sql  = "SELECT a.*,u.display_name,u.user_email FROM $table a LEFT JOIN {$wpdb->users} u ON u.ID=a.user_id WHERE " . implode( ' AND ', $where ) . ' ORDER BY a.created_at DESC,a.id DESC LIMIT 250';
	$rows = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) ) : $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	?>
	<div class="wrap pta-admin-results"><h1>Assessment Results</h1>
		<p>Administrator overview of every completed learner assessment stored in WordPress.</p>
		<style>.pta-result-cards{display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:14px;max-width:900px;margin:18px 0}.pta-result-card{background:#fff;border:1px solid #dcdcde;border-left:4px solid #d7a91e;padding:16px 18px}.pta-result-card span{display:block;color:#50575e;font-weight:600}.pta-result-card strong{display:block;color:#14244b;font-size:30px;line-height:1.2;margin-top:5px}.pta-admin-results .widefat{margin-top:10px}.pta-admin-results code{white-space:nowrap}@media(max-width:782px){.pta-result-cards{grid-template-columns:1fr}}</style>
		<div class="pta-result-cards">
			<div class="pta-result-card"><span>Learners with results</span><strong><?php echo esc_html( absint( $summary ? $summary->learners : 0 ) ); ?></strong></div>
			<div class="pta-result-card"><span>Completed attempts</span><strong><?php echo esc_html( absint( $summary ? $summary->attempts : 0 ) ); ?></strong></div>
			<div class="pta-result-card"><span>Average score</span><strong><?php echo esc_html( $summary && null !== $summary->average_score ? pta_readiness_percent_label( $summary->average_score ) : '—' ); ?></strong></div>
		</div>
		<h2>Learner overview</h2>
		<table class="widefat striped"><thead><tr><th>Learner</th><th>Completed</th><th>Latest assessment</th><th>Latest score</th><th>Latest completion</th><th>Domains</th></tr></thead><tbody>
		<?php foreach ( $learner_rows as $learner_row ) : ?>
			<tr><td><strong><?php echo esc_html( $learner_row->display_name ?: 'User #' . $learner_row->user_id ); ?></strong><br><?php echo esc_html( $learner_row->user_email ); ?></td><td><?php echo esc_html( absint( $learner_row->completed_count ) ); ?></td><td><?php echo esc_html( $learner_row->checkpoint ); ?></td><td><strong><?php echo esc_html( pta_readiness_percent_label( $learner_row->overall_percent ) ); ?></strong></td><td><?php echo esc_html( $learner_row->created_at ); ?></td><td><?php echo esc_html( 'Ppl ' . pta_readiness_percent_label( $learner_row->people_percent ) . ' · Proc ' . pta_readiness_percent_label( $learner_row->process_percent ) . ' · Bus ' . pta_readiness_percent_label( $learner_row->business_percent ) ); ?></td></tr>
		<?php endforeach; ?>
		<?php if ( ! $learner_rows ) : ?><tr><td colspan="6">No learner results have been saved yet.</td></tr><?php endif; ?>
		</tbody></table>
		<h2>Attempt history</h2>
		<form method="get" action="<?php echo esc_url( admin_url( 'users.php' ) ); ?>" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin:10px 0 14px">
			<input type="hidden" name="page" value="pta-assessment-results">
			<label>Student<br><input type="search" name="pta_student" value="<?php echo esc_attr( $student_filter ); ?>" placeholder="Name or email"></label>
			<label>Assessment<br><select name="pta_assessment_filter"><option value="">All assessments</option><?php foreach ( $catalog as $filter_key => $filter_item ) : ?><option value="<?php echo esc_attr( $filter_key ); ?>" <?php selected( $assessment_filter, $filter_key ); ?>><?php echo esc_html( $filter_item['title'] ); ?></option><?php endforeach; ?></select></label>
			<?php submit_button( 'Filter results', 'secondary', 'submit', false ); ?>
			<?php if ( $student_filter || $assessment_filter ) : ?><a class="button" href="<?php echo esc_url( admin_url( 'users.php?page=pta-assessment-results' ) ); ?>">Clear</a><?php endif; ?>
		</form>
		<p>Showing up to 250 matching completed attempts.</p>
		<table class="widefat striped"><thead><tr><th>Date</th><th>Student</th><th>Assessment</th><th>Overall</th><th>Domains</th><th>Reference</th><th>Sheet</th></tr></thead><tbody>
		<?php foreach ( $rows as $row ) : ?>
			<tr><td><?php echo esc_html( $row->created_at ); ?></td><td><strong><?php echo esc_html( $row->display_name ?: 'User #' . $row->user_id ); ?></strong><br><?php echo esc_html( $row->user_email ); ?></td><td><?php echo esc_html( $row->checkpoint ); ?></td><td><?php echo esc_html( pta_readiness_percent_label( $row->overall_percent ) ); ?></td><td><?php echo esc_html( 'Ppl ' . pta_readiness_percent_label( $row->people_percent ) . ' · Proc ' . pta_readiness_percent_label( $row->process_percent ) . ' · Bus ' . pta_readiness_percent_label( $row->business_percent ) ); ?></td><td><code><?php echo esc_html( $row->server_reference ); ?></code></td><td><?php echo $row->sheet_mirrored ? 'Mirrored' : 'WordPress only'; ?></td></tr>
		<?php endforeach; ?>
		<?php if ( ! $rows ) : ?><tr><td colspan="7">No attempts have been saved yet.</td></tr><?php endif; ?>
		</tbody></table>
	</div>
	<?php
}

function pta_readiness_percent_label( $value ) {
	return null === $value ? '—' : rtrim( rtrim( number_format( (float) $value, 1, '.', '' ), '0' ), '.' ) . '%';
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route( 'pta/v1', '/readiness', array( 'methods' => 'GET', 'callback' => 'pta_readiness_response', 'permission_callback' => 'is_user_logged_in' ) );
		register_rest_route( 'pta/v1', '/attempts', array( 'methods' => 'POST', 'callback' => 'pta_readiness_save_attempt', 'permission_callback' => 'is_user_logged_in' ) );
		register_rest_route( 'pta/v1', '/practice-score', array( 'methods' => 'POST', 'callback' => 'pta_readiness_save_attempt', 'permission_callback' => 'is_user_logged_in' ) );
		register_rest_route( 'pta/v1', '/admin-import', array( 'methods' => 'POST', 'callback' => 'pta_readiness_admin_import', 'permission_callback' => function () { return current_user_can( 'manage_options' ); } ) );
	}
);

function pta_readiness_admin_import( WP_REST_Request $request ) {
	$data = (array) $request->get_json_params();
	$rows = isset( $data['attempts'] ) && is_array( $data['attempts'] ) ? $data['attempts'] : array();
	if ( ! $rows ) {
		return new WP_Error( 'pta_import_empty', 'No historical attempts were provided.', array( 'status' => 400 ) );
	}
	$stats       = pta_readiness_import_rows( $rows );
	$stats['ok'] = true;
	return rest_ensure_response( $stats );
}

function pta_readiness_nullable_percent( $data, $key ) {
	if ( ! isset( $data[ $key ] ) || '' === $data[ $key ] || null === $data[ $key ] ) {
		return null;
	}
	return max( 0, min( 100, round( (float) $data[ $key ], 2 ) ) );
}

function pta_readiness_save_attempt( WP_REST_Request $request ) {
	global $wpdb;
	$data    = (array) $request->get_json_params();
	$catalog = pta_readiness_catalog();
	$key     = sanitize_key( isset( $data['assessment'] ) ? $data['assessment'] : '' );

	if ( ! $key && ! empty( $data['checkpoint'] ) ) {
		$incoming_checkpoint = sanitize_text_field( $data['checkpoint'] );
		foreach ( $catalog as $candidate_key => $candidate ) {
			if ( $candidate['checkpoint'] === $incoming_checkpoint || ( ! empty( $candidate['aliases'] ) && in_array( $incoming_checkpoint, $candidate['aliases'], true ) ) ) {
				$key = $candidate_key;
				break;
			}
		}
	}
	if ( ! isset( $catalog[ $key ] ) ) {
		return new WP_Error( 'pta_invalid_assessment', 'The assessment identifier is not recognized.', array( 'status' => 400 ) );
	}

	$user             = wp_get_current_user();
	$table            = pta_readiness_table();
	$client_reference = sanitize_text_field( isset( $data['submissionReference'] ) ? $data['submissionReference'] : ( isset( $data['submissionId'] ) ? $data['submissionId'] : '' ) );
	if ( $client_reference ) {
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT server_reference,sheet_mirrored FROM $table WHERE user_id=%d AND client_reference=%s ORDER BY id DESC LIMIT 1", $user->ID, $client_reference ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $existing ) {
			return rest_ensure_response( array( 'ok' => true, 'duplicate' => true, 'submissionReference' => $existing->server_reference, 'sheetMirrored' => (bool) $existing->sheet_mirrored ) );
		}
	}

	$topic_details = isset( $data['topicDetails'] ) ? wp_json_encode( $data['topicDetails'] ) : '';
	if ( strlen( $topic_details ) > 1000000 ) {
		return new WP_Error( 'pta_result_too_large', 'The result details are too large.', array( 'status' => 413 ) );
	}
	$server_reference = 'PTA-' . strtoupper( wp_generate_password( 12, false, false ) );
	$record           = array(
		'user_id'              => $user->ID,
		'assessment_key'       => $key,
		'checkpoint'           => $catalog[ $key ]['checkpoint'],
		'server_reference'     => $server_reference,
		'client_reference'     => $client_reference,
		'completion_status'    => ( isset( $data['completionStatus'] ) && 'Incomplete' === $data['completionStatus'] ) ? 'Incomplete' : 'Completed',
		'overall_percent'      => pta_readiness_nullable_percent( $data, 'overallPct' ),
		'overall_score'        => sanitize_text_field( isset( $data['overallScore'] ) ? $data['overallScore'] : '' ),
		'people_percent'       => pta_readiness_nullable_percent( $data, 'peoplePct' ),
		'people_score'         => sanitize_text_field( isset( $data['peopleScore'] ) ? $data['peopleScore'] : '' ),
		'process_percent'      => pta_readiness_nullable_percent( $data, 'processPct' ),
		'process_score'        => sanitize_text_field( isset( $data['processScore'] ) ? $data['processScore'] : '' ),
		'business_percent'     => pta_readiness_nullable_percent( $data, 'businessPct' ),
		'business_score'       => sanitize_text_field( isset( $data['businessScore'] ) ? $data['businessScore'] : '' ),
		'topic_details'        => $topic_details,
		'answered_count'       => absint( isset( $data['answeredCount'] ) ? $data['answeredCount'] : 0 ),
		'unanswered_count'     => absint( isset( $data['unansweredCount'] ) ? $data['unansweredCount'] : 0 ),
		'completion_seconds'   => absint( isset( $data['completedSeconds'] ) ? $data['completedSeconds'] : 0 ),
		'average_seconds'      => isset( $data['averageSecondsPerQuestion'] ) ? max( 0, (float) $data['averageSecondsPerQuestion'] ) : null,
		'flagged_count'        => absint( isset( $data['flaggedCount'] ) ? $data['flaggedCount'] : 0 ),
		'answer_change_count'  => absint( isset( $data['answerChangeCount'] ) ? $data['answerChangeCount'] : ( isset( $data['changedQuestionCount'] ) ? $data['changedQuestionCount'] : 0 ) ),
		'assessment_version'   => sanitize_text_field( isset( $data['assessmentVersion'] ) ? $data['assessmentVersion'] : ( isset( $data['examVersion'] ) ? $data['examVersion'] : '' ) ),
		'sheet_mirrored'       => 0,
		'created_at'           => current_time( 'mysql', true ),
	);

	$inserted = $wpdb->insert( $table, $record );
	if ( false === $inserted ) {
		return new WP_Error( 'pta_save_failed', 'The result could not be saved.', array( 'status' => 500 ) );
	}

	$mirrored = pta_readiness_mirror_attempt( $record, $user );
	if ( $mirrored ) {
		$wpdb->update( $table, array( 'sheet_mirrored' => 1 ), array( 'id' => $wpdb->insert_id ), array( '%d' ), array( '%d' ) );
	}
	$email_sent = pta_readiness_send_result_email( $record, $user );

	$response = rest_ensure_response( array( 'ok' => true, 'submissionReference' => $server_reference, 'sheetMirrored' => $mirrored, 'emailSent' => $email_sent ) );
	$response->set_status( 201 );
	return $response;
}

function pta_readiness_mirror_attempt( $record, $user ) {
	$url = pta_readiness_webapp_url();
	$key = pta_readiness_score_access_key();
	if ( ! pta_readiness_mirror_enabled() || ! $url || ! $key ) {
		return false;
	}
	$body = array(
		'accessKey'           => $key,
		'wordpressUserId'     => $user->ID,
		'assessmentKey'       => $record['assessment_key'],
		'checkpoint'          => $record['checkpoint'],
		'studentName'         => $user->display_name,
		'email'               => strtolower( trim( $user->user_email ) ),
		'overallPct'          => $record['overall_percent'],
		'overallScore'        => $record['overall_score'],
		'peoplePct'           => $record['people_percent'],
		'peopleScore'         => $record['people_score'],
		'processPct'          => $record['process_percent'],
		'processScore'        => $record['process_score'],
		'businessPct'         => $record['business_percent'],
		'businessScore'       => $record['business_score'],
		'topicDetails'        => json_decode( $record['topic_details'], true ),
		'answeredCount'       => $record['answered_count'],
		'unansweredCount'     => $record['unanswered_count'],
		'completionStatus'    => $record['completion_status'],
		'assessmentVersion'   => $record['assessment_version'],
		'submissionReference' => $record['server_reference'],
		'submittedAt'         => gmdate( 'c' ),
		'source'              => 'WordPress',
	);
	$response = wp_remote_post( $url, array( 'timeout' => 20, 'redirection' => 3, 'headers' => array( 'Content-Type' => 'text/plain;charset=utf-8' ), 'body' => wp_json_encode( $body ) ) );
	if ( is_wp_error( $response ) ) {
		return false;
	}
	$result = json_decode( wp_remote_retrieve_body( $response ), true );
	return is_array( $result ) && ! empty( $result['ok'] );
}

function pta_readiness_send_result_email( $record, $user ) {
	if ( ! pta_readiness_email_enabled() || ! $user || ! is_email( $user->user_email ) || 'Completed' !== $record['completion_status'] ) {
		return false;
	}
	$catalog          = pta_readiness_catalog();
	$assessment_title = isset( $catalog[ $record['assessment_key'] ] ) ? $catalog[ $record['assessment_key'] ]['title'] : $record['checkpoint'];
	$account_url      = add_query_arg( 'pta-assessments', '1', home_url( '/user-account/' ) );
	$lines            = array(
		'Hello ' . ( $user->display_name ? $user->display_name : 'PMP learner' ) . ',',
		'',
		'Your assessment result has been saved to your private PMP Training Academy learner record.',
		'',
		'Assessment: ' . $assessment_title,
		'Overall score: ' . pta_readiness_percent_label( $record['overall_percent'] ) . ( $record['overall_score'] ? ' (' . $record['overall_score'] . ')' : '' ),
	);
	if ( null !== $record['people_percent'] ) {
		$lines[] = 'People: ' . pta_readiness_percent_label( $record['people_percent'] );
	}
	if ( null !== $record['process_percent'] ) {
		$lines[] = 'Process: ' . pta_readiness_percent_label( $record['process_percent'] );
	}
	if ( null !== $record['business_percent'] ) {
		$lines[] = 'Business Environment: ' . pta_readiness_percent_label( $record['business_percent'] );
	}
	$lines[] = '';
	$lines[] = 'View your assessment record: ' . $account_url;
	$lines[] = 'Reference: ' . $record['server_reference'];
	$lines[] = '';
	$lines[] = 'PMP Training Academy';
	$subject = sprintf( 'Your %s result is ready', $assessment_title );
	return (bool) wp_mail( $user->user_email, wp_specialchars_decode( $subject ), implode( "\n", $lines ) );
}

function pta_readiness_response() {
	global $wpdb;
	$user     = wp_get_current_user();
	$table    = pta_readiness_table();
	$attempts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE user_id=%d AND completion_status='Completed' ORDER BY created_at DESC,id DESC", $user->ID ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$latest   = array();
	foreach ( $attempts as $attempt ) {
		if ( ! isset( $latest[ $attempt['assessment_key'] ] ) ) {
			$latest[ $attempt['assessment_key'] ] = $attempt;
		}
	}
	$history         = array_values( $latest );
	$assessment_keys = array( 'baseline', 'diagnostic-1', 'diagnostic-2', 'diagnostic-3', 'diagnostic-4', 'diagnostic-5' );
	$assessment_history = array_values(
		array_filter(
			$history,
			function ( $row ) use ( $assessment_keys ) {
				return in_array( $row['assessment_key'], $assessment_keys, true );
			}
		)
	);
	$current  = isset( $assessment_history[0] ) ? $assessment_history[0] : null;
	$previous = isset( $assessment_history[1] ) ? $assessment_history[1] : null;
	$number   = function ( $value ) { return null === $value ? null : (float) $value; };
	return rest_ensure_response(
		array(
			'student' => array( 'id' => $user->ID, 'name' => $user->display_name ),
			'summary' => array(
				'overall_readiness'     => $current ? $number( $current['overall_percent'] ) : null,
				'people'                => $current ? $number( $current['people_percent'] ) : null,
				'process'               => $current ? $number( $current['process_percent'] ) : null,
				'business_environment'  => $current ? $number( $current['business_percent'] ) : null,
				'completed_assessments' => count( $assessment_history ),
				'latest_assessment'     => $current ? $current['checkpoint'] : null,
				'latest_score'          => $current ? $number( $current['overall_percent'] ) : null,
				'trend_points'          => $current && $previous && null !== $current['overall_percent'] && null !== $previous['overall_percent'] ? round( (float) $current['overall_percent'] - (float) $previous['overall_percent'], 1 ) : null,
			),
			'history' => array_map(
				function ( $row ) use ( $number ) {
					return array(
						'timestamp'          => $row['created_at'],
						'assessment_key'     => $row['assessment_key'],
						'checkpoint'         => $row['checkpoint'],
						'overall_percent'    => $number( $row['overall_percent'] ),
						'people_percent'     => $number( $row['people_percent'] ),
						'process_percent'    => $number( $row['process_percent'] ),
						'business_percent'   => $number( $row['business_percent'] ),
						'submissionReference'=> $row['server_reference'],
					);
				},
				$history
			),
		)
	);
}

add_shortcode(
	'pta_assessment_center',
	function () {
		if ( ! is_user_logged_in() ) {
			return '<p>Please <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">sign in</a> to view your assessment center and private results.</p>';
		}
		$instance = 'pta-center-' . wp_generate_uuid4();
		$config   = array( 'endpoint' => rest_url( 'pta/v1/readiness' ), 'nonce' => wp_create_nonce( 'wp_rest' ), 'launchBase' => trailingslashit( home_url( '/pta-assessment/' ) ) );
		ob_start();
		?>
		<div id="<?php echo esc_attr( $instance ); ?>" class="pta-assessment-center" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<iframe title="PMP Training Academy Assessment Center" src="https://readiness.pmptrainingacademy.com/assessment-center.html?v=20260809-6" loading="eager" style="width:100%;height:900px;border:0;display:block"></iframe>
		</div>
		<script>
		(function(){const root=document.getElementById(<?php echo wp_json_encode( $instance ); ?>),frame=root.querySelector('iframe'),config=JSON.parse(root.dataset.config),origin='https://readiness.pmptrainingacademy.com';let readiness=null;function send(){if(!frame.contentWindow)return;frame.contentWindow.postMessage({type:'ptaAssessmentCenterConfig',launchBase:config.launchBase,readiness:readiness},origin);}frame.addEventListener('load',send);window.addEventListener('message',event=>{if(event.origin!==origin||event.source!==frame.contentWindow)return;if(event.data&&event.data.type==='ptaAssessmentCenterHeight'){const height=Number(event.data.height);if(height>500)frame.style.height=Math.ceil(height+10)+'px';}if(event.data&&event.data.type==='ptaAssessmentCenterReady')send();});fetch(config.endpoint,{credentials:'same-origin',headers:{'X-WP-Nonce':config.nonce}}).then(r=>{if(!r.ok)throw new Error();return r.json();}).then(data=>{readiness=data;send();}).catch(()=>{readiness={error:true};send();});})();
		</script>
		<?php
		return ob_get_clean();
	}
);

add_shortcode(
	'pta_readiness_dashboard',
	function () {
		if ( ! is_user_logged_in() ) {
			return '<p>Please sign in to view your readiness results.</p>';
		}
		return '<div class="pta-ready-summary" data-endpoint="' . esc_url( rest_url( 'pta/v1/readiness' ) ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'wp_rest' ) ) . '"><p>Loading your readiness results…</p></div><script>(function(){const r=document.currentScript.previousElementSibling,p=r.querySelector("p");fetch(r.dataset.endpoint,{credentials:"same-origin",headers:{"X-WP-Nonce":r.dataset.nonce}}).then(x=>x.json()).then(d=>{const s=d.summary||{};p.textContent=s.completed_assessments?"Completed assessments: "+s.completed_assessments+" · Latest score: "+s.latest_score+"%":"No assessment results are available yet.";}).catch(()=>p.textContent="Results are temporarily unavailable.");})();</script>';
	}
);

/**
 * Add the private Assessment Center to the MasterStudy user-account screen.
 *
 * The account link uses a query parameter so it works without changing
 * MasterStudy rewrite rules. Results still come from the authenticated
 * WordPress REST endpoint, which filters by the current user ID.
 */
add_action(
	'wp_footer',
	function () {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$config = array(
			'accountUrl' => add_query_arg( 'pta-assessments', '1', home_url( '/user-account/' ) ),
			'endpoint'   => rest_url( 'pta/v1/readiness' ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'launchBase' => trailingslashit( home_url( '/pta-assessment/' ) ),
			'frameUrl'   => 'https://readiness.pmptrainingacademy.com/assessment-center.html?v=20260809-6',
			'origin'     => 'https://readiness.pmptrainingacademy.com',
		);
		?>
		<style>
			.pta-account-assessments__heading{margin:0 0 8px;color:#1b2e5e;font:700 30px/1.2 system-ui,sans-serif}
			.pta-account-assessments__frame{display:block;width:100%;height:1200px;border:0;background:#fff}
		</style>
		<script>
		(function(config){
			const assessmentsView=new URLSearchParams(window.location.search).get('pta-assessments')==='1';
			let frame=null;
			let readiness=null;
			let readinessRequested=false;

			function ensureMenuLink(){
				const menuItems=Array.from(document.querySelectorAll('.masterstudy-account-menu__list-item'));
				const assignmentsItem=menuItems.find(function(item){return /my assignments/i.test(item.textContent||'');});
				const menuSection=assignmentsItem&&assignmentsItem.parentElement
					?assignmentsItem.parentElement
					:document.querySelector('.masterstudy-account-menu__list-section')
						||document.querySelector('.masterstudy-account-menu__list')
						||document.querySelector('.masterstudy-account-menu');
				if(!menuSection)return false;
				let link=document.querySelector('[data-pta-account-assessments], a[href*="pta-assessments=1"]');
				if(!link){
					link=document.createElement('a');
					link.className='masterstudy-account-menu__list-item';
					link.href=config.accountUrl;
					link.dataset.menuPlace='main';
					link.dataset.menuMode='on';
					link.dataset.ptaAccountAssessments='1';
					link.dataset.ptaVersion='2.4.0';
					link.innerHTML='<i class="stmlms-menu-enrolled-quizzes"></i><span class="masterstudy-account-menu__list-item-label">Assessments</span>';
				}
				link.dataset.ptaAccountAssessments='1';
				link.dataset.ptaVersion='2.4.0';
				if(assignmentsItem&&assignmentsItem.nextElementSibling!==link){
					assignmentsItem.insertAdjacentElement('afterend',link);
				}else if(!link.parentElement){
					menuSection.appendChild(link);
				}
				return true;
			}

			function send(){
				if(!frame||!frame.contentWindow)return;
				frame.contentWindow.postMessage({type:'ptaAssessmentCenterConfig',launchBase:config.launchBase,readiness:readiness},config.origin);
			}

			function requestReadiness(){
				if(readinessRequested)return;
				readinessRequested=true;
				fetch(config.endpoint,{credentials:'same-origin',headers:{'X-WP-Nonce':config.nonce}})
					.then(function(response){if(!response.ok)throw new Error();return response.json();})
					.then(function(data){readiness=data;send();})
					.catch(function(){readiness={error:true};send();});
			}

			function renderAssessmentCenter(){
				if(!assessmentsView)return true;
				const container=document.querySelector('.masterstudy-account-container');
				if(!container)return false;
				let center=container.querySelector('.pta-account-assessments');
				if(!center){
					document.querySelectorAll('.masterstudy-account-menu__list-item_active').forEach(function(item){
						item.classList.remove('masterstudy-account-menu__list-item_active');
					});
					container.innerHTML='<section class="pta-account-assessments"><h1 class="pta-account-assessments__heading">My Assessments</h1><iframe class="pta-account-assessments__frame" title="PMP Training Academy Assessment Center" src="'+config.frameUrl+'" loading="eager"></iframe></section>';
					center=container.querySelector('.pta-account-assessments');
					frame=center.querySelector('iframe');
					frame.addEventListener('load',send);
					requestReadiness();
				}else if(!frame){
					frame=center.querySelector('iframe');
				}
				const assessmentLink=document.querySelector('[data-pta-account-assessments]');
				if(assessmentLink)assessmentLink.classList.add('masterstudy-account-menu__list-item_active');
				return true;
			}

			function mount(){
				ensureMenuLink();
				renderAssessmentCenter();
			}

			window.addEventListener('message',function(event){
				if(!frame||event.origin!==config.origin||event.source!==frame.contentWindow)return;
				if(event.data&&event.data.type==='ptaAssessmentCenterHeight'){
					const height=Number(event.data.height);
					if(height>500)frame.style.height=Math.ceil(height+10)+'px';
				}
				if(event.data&&event.data.type==='ptaAssessmentCenterReady')send();
			});

			mount();
			document.addEventListener('DOMContentLoaded',mount);
			window.addEventListener('load',mount);
			const menuObserver=new MutationObserver(mount);
			menuObserver.observe(document.documentElement,{childList:true,subtree:true});
			let mountAttempts=0;
			const mountTimer=window.setInterval(function(){
				mount();
				mountAttempts+=1;
				if(mountAttempts>=60){
					window.clearInterval(mountTimer);
					menuObserver.disconnect();
				}
			},500);
		})(<?php echo wp_json_encode( $config ); ?>);
		</script>
		<?php
	},
	20
);

function pta_readiness_native_assessment_key() {
	$paths = array(
		'midpoint'                                   => 'midpoint',
		'pmp-exam-simulator-practice-test-2'         => 'diagnostic-2',
		'pmp-exam-simulator-practice-test-3'         => 'diagnostic-3',
		'pmp-exam-simulator-practice-test-4'         => 'diagnostic-4',
		'pmp-exam-simulator-practice-test-5'         => 'diagnostic-5',
	);
	foreach ( $paths as $page_slug => $assessment_key ) {
		if ( is_page( $page_slug ) ) {
			return $assessment_key;
		}
	}
	return '';
}

add_action(
	'wp_footer',
	function () {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$assessment_key = pta_readiness_native_assessment_key();
		if ( ! $assessment_key ) {
			return;
		}
		$config = array(
			'assessment' => $assessment_key,
			'endpoint'   => rest_url( 'pta/v1/attempts' ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
		);
		?>
		<script>
		(function(config){
			const originalFetch=window.fetch.bind(window);
			window.fetch=function(input,options){
				const url=typeof input==='string'?input:(input&&input.url)||'';
				const settings=options||{};
				if(url.indexOf('https://script.google.com/macros/')!==0||String(settings.method||'GET').toUpperCase()!=='POST'||!settings.body){
					return originalFetch(input,options);
				}
				let payload;
				try{payload=JSON.parse(settings.body);}catch(error){return originalFetch(input,options);}
				if(!payload||payload.overallPct===undefined||!payload.checkpoint){return originalFetch(input,options);}
				payload.assessment=config.assessment;
				return originalFetch(config.endpoint,{
					method:'POST',
					credentials:'same-origin',
					headers:{'Content-Type':'application/json','X-WP-Nonce':config.nonce},
					body:JSON.stringify(payload)
				}).then(function(response){
					if(!response.ok)throw new Error('pta_wordpress_save_failed');
					return response;
				});
			};
		})(<?php echo wp_json_encode( $config ); ?>);
		</script>
		<?php
	},
	1
);

add_action(
	'template_redirect',
	function () {
		if ( ! is_page( 'pmp-2026-full-length-readiness-diagnostic-1' ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		wp_safe_redirect( home_url( '/pta-assessment/diagnostic-1/' ), 302 );
		exit;
	},
	-1
);

add_action(
	'template_redirect',
	function () {
		$key = sanitize_key( get_query_var( 'pta_assessment' ) );
		if ( ! $key ) {
			return;
		}
		$catalog = pta_readiness_catalog();
		if ( ! isset( $catalog[ $key ] ) ) {
			status_header( 404 );
			exit( 'Assessment not found.' );
		}
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		$source_host = wp_parse_url( $catalog[ $key ]['src'], PHP_URL_HOST );
		$site_host   = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		if ( $source_host && $site_host && strtolower( $source_host ) === strtolower( $site_host ) ) {
			wp_safe_redirect( $catalog[ $key ]['src'] );
			exit;
		}
		$user     = wp_get_current_user();
		$endpoint = rest_url( 'pta/v1/attempts' );
		$nonce    = wp_create_nonce( 'wp_rest' );
		$origin   = wp_parse_url( $catalog[ $key ]['src'], PHP_URL_SCHEME ) . '://' . wp_parse_url( $catalog[ $key ]['src'], PHP_URL_HOST );
		$learner_session = substr( wp_hash( (string) $user->ID, 'auth' ), 0, 20 );
		$assessment_src  = add_query_arg( 'pta_learner', $learner_session, $catalog[ $key ]['src'] );
		nocache_headers();
		?><!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html( $catalog[ $key ]['title'] ); ?> | <?php bloginfo( 'name' ); ?></title><?php wp_head(); ?><style>html,body{margin:0;background:#eef1f5}.pta-frame{display:block;width:100%;height:100vh;border:0;background:#fff}.pta-save{position:fixed;right:16px;bottom:16px;z-index:99999;max-width:480px;padding:11px 15px;border-radius:6px;background:#1b2e5e;color:#fff;font:700 14px/1.4 system-ui,sans-serif;box-shadow:0 5px 24px #0003}.pta-save:empty{display:none}</style></head><body <?php body_class( 'pta-assessment-launch' ); ?>><?php wp_body_open(); ?><iframe id="pta-assessment-frame" class="pta-frame" title="<?php echo esc_attr( $catalog[ $key ]['title'] ); ?>" src="<?php echo esc_url( $assessment_src ); ?>" allow="fullscreen" loading="eager"></iframe><div id="pta-save-status" class="pta-save" role="status" aria-live="polite"></div><script>
		(function(){const frame=document.getElementById('pta-assessment-frame'),status=document.getElementById('pta-save-status'),assessment=<?php echo wp_json_encode( $key ); ?>,endpoint=<?php echo wp_json_encode( $endpoint ); ?>,nonce=<?php echo wp_json_encode( $nonce ); ?>,frameOrigin=<?php echo wp_json_encode( $origin ); ?>,learner=<?php echo wp_json_encode( array( 'name' => $user->display_name, 'email' => $user->user_email ) ); ?>;function sendContext(){frame.contentWindow.postMessage({type:'ptaLearnerContext',learner:learner},frameOrigin);}frame.addEventListener('load',sendContext);window.addEventListener('message',event=>{if(event.origin!==frameOrigin||event.source!==frame.contentWindow)return;const message=event.data||{};if(message.type==='pmpDiagnosticHeight'&&Number(message.height)>500)frame.style.height=Math.ceil(Number(message.height)+10)+'px';if((message.type!=='ptaPracticeResult'&&message.type!=='ptaAssessmentResult')||!message.payload)return;status.textContent='Saving your score…';fetch(endpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':nonce},body:JSON.stringify(Object.assign({},message.payload,{assessment:assessment}))}).then(async response=>({ok:response.ok,data:await response.json()})).then(result=>{if(!result.ok||!result.data.ok)throw new Error();status.textContent='Score saved to your private learner record. Reference: '+result.data.submissionReference;frame.contentWindow.postMessage({type:'ptaAssessmentSaveStatus',ok:true,reference:result.data.submissionReference},frameOrigin);frame.contentWindow.postMessage({type:'ptaPracticeSaveStatus',ok:true,reference:result.data.submissionReference},frameOrigin);}).catch(()=>{status.textContent='Your score is visible but could not be saved. Keep your local report and contact support.';frame.contentWindow.postMessage({type:'ptaAssessmentSaveStatus',ok:false},frameOrigin);frame.contentWindow.postMessage({type:'ptaPracticeSaveStatus',ok:false},frameOrigin);});});})();
		</script><?php wp_footer(); ?></body></html><?php
		exit;
	},
	0
);
