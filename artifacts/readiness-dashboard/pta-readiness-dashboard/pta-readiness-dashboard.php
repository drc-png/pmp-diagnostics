<?php
/**
 * Plugin Name: PMP Training Academy Readiness Dashboard
 * Description: Displays authenticated learner readiness results from the private PMP score service.
 * Version: 1.3.0
 * Author: PMP Training Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pta_readiness_webapp_url() {
	if ( defined( 'PTA_SCORE_WEBAPP_URL' ) ) {
		return PTA_SCORE_WEBAPP_URL;
	}

	return (string) get_option( 'pta_score_webapp_url', '' );
}

function pta_readiness_webapp_token() {
	if ( defined( 'PTA_SCORE_WEBAPP_TOKEN' ) ) {
		return PTA_SCORE_WEBAPP_TOKEN;
	}

	return (string) get_option( 'pta_score_webapp_token', '' );
}

function pta_readiness_score_access_key() {
	if ( defined( 'PTA_SCORE_ACCESS_KEY' ) ) {
		return PTA_SCORE_ACCESS_KEY;
	}

	return (string) get_option( 'pta_score_access_key', 'pmp-diagnostic-2026-report' );
}

add_action(
	'admin_init',
	function () {
		register_setting(
			'pta_readiness_settings',
			'pta_score_webapp_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
		register_setting(
			'pta_readiness_settings',
			'pta_score_access_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'pmp-diagnostic-2026-report',
			)
		);
		register_setting(
			'pta_readiness_settings',
			'pta_score_webapp_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
	}
);

add_action(
	'admin_menu',
	function () {
		add_options_page(
			'PMP Readiness Dashboard',
			'PMP Readiness Dashboard',
			'manage_options',
			'pta-readiness-dashboard',
			'pta_readiness_settings_page'
		);
	}
);

function pta_readiness_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>PMP Readiness Dashboard</h1>
		<p>Connect the Assessment Center to the private Google Apps Script score service.</p>
		<form action="options.php" method="post">
			<?php settings_fields( 'pta_readiness_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="pta_score_webapp_url">Web app URL</label></th>
					<td>
						<input class="regular-text code" id="pta_score_webapp_url" name="pta_score_webapp_url" type="url" value="<?php echo esc_attr( pta_readiness_webapp_url() ); ?>" required>
						<p class="description">Use the deployed Google Apps Script URL ending in <code>/exec</code>.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pta_score_webapp_token">Dashboard token</label></th>
					<td>
						<input class="regular-text" id="pta_score_webapp_token" name="pta_score_webapp_token" type="password" value="<?php echo esc_attr( pta_readiness_webapp_token() ); ?>" autocomplete="new-password" required>
						<p class="description">This must exactly match the <code>PTA_DASHBOARD_TOKEN</code> Script Property. It is stored in WordPress and is never sent to the learner's browser.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pta_score_access_key">Score submission key</label></th>
					<td>
						<input class="regular-text" id="pta_score_access_key" name="pta_score_access_key" type="password" value="<?php echo esc_attr( pta_readiness_score_access_key() ); ?>" autocomplete="new-password" required>
						<p class="description">This must exactly match the <code>PTA_SCORE_ACCESS_KEY</code> Script Property.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Save connection' ); ?>
		</form>
	</div>
	<?php
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'pta/v1',
			'/readiness',
			array(
				'methods'             => 'GET',
				'callback'            => 'pta_readiness_response',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
		register_rest_route(
			'pta/v1',
			'/practice-score',
			array(
				'methods'             => 'POST',
				'callback'            => 'pta_practice_score_response',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}
);

function pta_practice_score_response( WP_REST_Request $request ) {
	$webapp_url = pta_readiness_webapp_url();
	$access_key = pta_readiness_score_access_key();
	if ( '' === $webapp_url || '' === $access_key ) {
		return new WP_Error( 'pta_not_configured', 'Score submission is not configured.', array( 'status' => 503 ) );
	}

	$data       = (array) $request->get_json_params();
	$checkpoint = sanitize_text_field( isset( $data['checkpoint'] ) ? $data['checkpoint'] : '' );
	$allowed    = array(
		'Practice Set 1 - Teams, Stakeholders and Conflict',
	);
	if ( ! in_array( $checkpoint, $allowed, true ) ) {
		return new WP_Error( 'pta_invalid_checkpoint', 'The practice-set identifier is not recognized.', array( 'status' => 400 ) );
	}

	$percent = function ( $key ) use ( $data ) {
		$value = isset( $data[ $key ] ) ? (float) $data[ $key ] : 0;
		return max( 0, min( 100, $value ) );
	};
	$score = function ( $key ) use ( $data ) {
		return sanitize_text_field( isset( $data[ $key ] ) ? $data[ $key ] : '' );
	};
	$user  = wp_get_current_user();
	$body  = array(
		'accessKey'           => $access_key,
		'checkpoint'          => $checkpoint,
		'studentName'         => $user->display_name,
		'email'               => strtolower( trim( $user->user_email ) ),
		'overallPct'          => $percent( 'overallPct' ),
		'overallScore'        => $score( 'overallScore' ),
		'peoplePct'           => $percent( 'peoplePct' ),
		'peopleScore'         => $score( 'peopleScore' ),
		'processPct'          => $percent( 'processPct' ),
		'processScore'        => $score( 'processScore' ),
		'businessPct'         => $percent( 'businessPct' ),
		'businessScore'       => $score( 'businessScore' ),
		'topicDetails'        => isset( $data['topicDetails'] ) && is_array( $data['topicDetails'] ) ? $data['topicDetails'] : array(),
		'answeredCount'       => isset( $data['answeredCount'] ) ? absint( $data['answeredCount'] ) : 0,
		'unansweredCount'     => isset( $data['unansweredCount'] ) ? absint( $data['unansweredCount'] ) : 0,
		'completionStatus'    => 'Completed',
		'assessmentVersion'   => 'practice-set-1-refined-v1',
		'submissionReference' => 'PTA-' . wp_generate_uuid4(),
		'submittedAt'         => gmdate( 'c' ),
	);

	$response = wp_remote_post(
		$webapp_url,
		array(
			'timeout'     => 20,
			'redirection' => 3,
			'headers'     => array( 'Content-Type' => 'text/plain;charset=utf-8' ),
			'body'        => wp_json_encode( $body ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'pta_score_service', 'The score service could not be reached.', array( 'status' => 502 ) );
	}

	$result = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $result ) || empty( $result['ok'] ) ) {
		return new WP_Error( 'pta_score_service', 'The score service rejected the submission.', array( 'status' => 502 ) );
	}

	return rest_ensure_response(
		array(
			'ok'                   => true,
			'submissionReference'  => $body['submissionReference'],
		)
	);
}

function pta_readiness_response() {
	$webapp_url   = pta_readiness_webapp_url();
	$webapp_token = pta_readiness_webapp_token();
	if ( '' === $webapp_url || '' === $webapp_token ) {
		return new WP_Error( 'pta_not_configured', 'The readiness dashboard is not configured.', array( 'status' => 503 ) );
	}

	$user = wp_get_current_user();
	$url  = add_query_arg(
		array(
			'email' => strtolower( trim( $user->user_email ) ),
			'token' => $webapp_token,
		),
		$webapp_url
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 15,
			'redirection' => 3,
		)
	);
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'pta_score_service', 'Readiness results are temporarily unavailable.', array( 'status' => 502 ) );
	}

	$status = wp_remote_retrieve_response_code( $response );
	$data   = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== $status || ! is_array( $data ) || ! empty( $data['error'] ) ) {
		return new WP_Error( 'pta_score_service', 'Readiness results are temporarily unavailable.', array( 'status' => 502 ) );
	}

	return rest_ensure_response( $data );
}

add_shortcode(
	'pta_practice_embed',
	function ( $attributes ) {
		if ( ! is_user_logged_in() ) {
			return '<p>Please sign in to begin this practice set and save your score.</p>';
		}

		$attributes = shortcode_atts(
			array(
				'src'    => 'https://drc-png.github.io/pmp-diagnostics/practice-set-1-refined-direct.html',
				'height' => '1500',
			),
			$attributes,
			'pta_practice_embed'
		);
		$src        = esc_url( $attributes['src'] );
		$height     = max( 900, min( 4000, absint( $attributes['height'] ) ) );
		$endpoint   = rest_url( 'pta/v1/practice-score' );
		$nonce      = wp_create_nonce( 'wp_rest' );
		$instance   = 'pta-practice-' . wp_generate_uuid4();
		ob_start();
		?>
		<div class="pta-practice-embed" id="<?php echo esc_attr( $instance ); ?>" data-endpoint="<?php echo esc_url( $endpoint ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<iframe
				title="PMP focused practice set"
				src="<?php echo esc_url( $src ); ?>"
				loading="eager"
				style="display:block;width:100%;height:<?php echo esc_attr( $height ); ?>px;border:0;background:#fff;"
				allow="fullscreen"
			></iframe>
			<p class="pta-practice-embed__status" role="status" aria-live="polite">Your score will be saved automatically when you submit.</p>
		</div>
		<script>
		(function(){
			const root=document.getElementById(<?php echo wp_json_encode( $instance ); ?>);
			if(!root)return;
			const frame=root.querySelector('iframe');
			const status=root.querySelector('.pta-practice-embed__status');
			window.addEventListener('message',function(event){
				if(event.origin!=='https://drc-png.github.io'||event.source!==frame.contentWindow)return;
				const message=event.data||{};
				if(message.type!=='ptaPracticeResult'||!message.payload)return;
				status.textContent='Saving your score…';
				fetch(root.dataset.endpoint,{
					method:'POST',
					credentials:'same-origin',
					headers:{'Content-Type':'application/json','X-WP-Nonce':root.dataset.nonce},
					body:JSON.stringify(message.payload)
				})
					.then(response=>response.json().then(data=>({ok:response.ok,data:data})))
					.then(result=>{
						if(!result.ok||!result.data.ok)throw new Error('save_failed');
						status.textContent='Score saved to your learner dashboard. Reference: '+result.data.submissionReference;
						frame.contentWindow.postMessage({type:'ptaPracticeSaveStatus',ok:true,reference:result.data.submissionReference},'https://drc-png.github.io');
					})
					.catch(()=>{
						status.textContent='Your score is visible above but could not be saved. Please save the PDF and contact support.';
						frame.contentWindow.postMessage({type:'ptaPracticeSaveStatus',ok:false},'https://drc-png.github.io');
					});
			});
		})();
		</script>
		<style>
			.pta-practice-embed__status{margin:12px 0;padding:12px 15px;border-left:4px solid #c49a27;background:#faf7f0;color:#1b2e5e;font-weight:700}
		</style>
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

		$endpoint = rest_url( 'pta/v1/readiness' );
		$nonce    = wp_create_nonce( 'wp_rest' );
		ob_start();
		?>
		<div class="pta-ready" data-endpoint="<?php echo esc_url( $endpoint ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<div class="pta-ready__status">Loading your readiness results…</div>
			<div class="pta-ready__content" hidden>
				<div class="pta-ready__grid">
					<article><span>Overall readiness</span><strong data-field="overall_readiness">—</strong></article>
					<article><span>People</span><strong data-field="people">—</strong></article>
					<article><span>Process</span><strong data-field="process">—</strong></article>
					<article><span>Business Environment</span><strong data-field="business_environment">—</strong></article>
				</div>
				<p class="pta-ready__latest"><strong>Latest checkpoint:</strong> <span data-field="latest_assessment">—</span> · <span data-field="trend_points">—</span></p>
				<div class="pta-ready__history"></div>
			</div>
		</div>
		<style>
			.pta-ready{font-family:"Source Sans 3","Source Sans Pro",Calibri,Arial,sans-serif;color:#22252b}
			.pta-ready__grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:18px 0}
			.pta-ready__grid article{background:#fff;border:1px solid #e3ded2;border-top:4px solid #c49a27;border-radius:7px;padding:16px}
			.pta-ready__grid span{display:block;color:#667085;font-size:14px;font-weight:700}
			.pta-ready__grid strong{display:block;color:#1b2e5e;font-family:Georgia,serif;font-size:30px;margin-top:5px}
			.pta-ready__latest{background:#faf7f0;border-left:4px solid #1b2e5e;padding:12px 15px}
			.pta-ready table{border-collapse:collapse;width:100%;margin-top:16px}
			.pta-ready th{background:#1b2e5e;color:#fff;text-align:left;padding:9px 10px}
			.pta-ready td{border-bottom:1px solid #e3ded2;padding:9px 10px}
			@media(max-width:720px){.pta-ready__grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
		</style>
		<script>
		(function(){
			const root=document.currentScript.previousElementSibling.previousElementSibling;
			const status=root.querySelector('.pta-ready__status');
			const content=root.querySelector('.pta-ready__content');
			const percent=value=>value===null||value===undefined?'—':Number(value).toFixed(Number(value)%1?1:0)+'%';
			fetch(root.dataset.endpoint,{headers:{'X-WP-Nonce':root.dataset.nonce},credentials:'same-origin'})
				.then(response=>{if(!response.ok)throw new Error('unavailable');return response.json();})
				.then(data=>{
					const summary=data.summary||{};
					if(!data.history||!data.history.length){
						status.textContent='No assessment results are available yet. Complete your first assigned checkpoint to establish a readiness baseline.';
						return;
					}
					['overall_readiness','people','process','business_environment'].forEach(key=>{
						root.querySelector('[data-field="'+key+'"]').textContent=percent(summary[key]);
					});
					root.querySelector('[data-field="latest_assessment"]').textContent=summary.latest_assessment||'—';
					root.querySelector('[data-field="trend_points"]').textContent=summary.trend_points===null||summary.trend_points===undefined?'Trend pending':(summary.trend_points>=0?'↑ +':'↓ ')+summary.trend_points+' points';
					root.querySelector('.pta-ready__history').innerHTML='<table><thead><tr><th>Checkpoint</th><th>Overall</th><th>People</th><th>Process</th><th>Business</th></tr></thead><tbody>'+data.history.map(row=>'<tr><td>'+escapeHtml(row.checkpoint)+'</td><td>'+percent(row.overall_percent)+'</td><td>'+percent(row.people_percent)+'</td><td>'+percent(row.process_percent)+'</td><td>'+percent(row.business_percent)+'</td></tr>').join('')+'</tbody></table>';
					status.hidden=true;content.hidden=false;
				})
				.catch(()=>{status.textContent='Your readiness results are temporarily unavailable. Please refresh or try again later.';});
			function escapeHtml(value){const node=document.createElement('div');node.textContent=value||'';return node.innerHTML;}
		})();
		</script>
		<?php
		return ob_get_clean();
	}
);

add_action(
	'wp_footer',
	function () {
		if ( ! is_user_logged_in() || ! is_page( 'assessments' ) ) {
			return;
		}

		$config = array(
			'endpoint' => rest_url( 'pta/v1/readiness' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
		);
		?>
		<script>
		(function(config){
			const snapshot=document.querySelector('.pmp-snapshot .pmp-panel');
			if(!snapshot)return;
			const metrics=snapshot.querySelectorAll('.pmp-metric-value');
			const tag=snapshot.querySelector('.pmp-readiness-tag');
			if(metrics.length<3||!tag)return;
			const score=value=>value===null||value===undefined?'—':Number(value).toFixed(Number(value)%1?1:0)+'%';
			const trend=value=>{
				if(value===null||value===undefined)return 'Pending';
				const number=Number(value);
				if(number===0)return 'No change';
				return (number>0?'↑ +':'↓ ')+Math.abs(number).toFixed(Math.abs(number)%1?1:0)+' pts';
			};
			tag.textContent='Loading your private score history…';
			fetch(config.endpoint,{headers:{'X-WP-Nonce':config.nonce},credentials:'same-origin'})
				.then(response=>{if(!response.ok)throw new Error('unavailable');return response.json();})
				.then(data=>{
					const summary=data.summary||{};
					metrics[0].textContent=summary.completed_assessments||0;
					metrics[1].textContent=score(summary.latest_score);
					metrics[2].textContent=trend(summary.trend_points);
					if(!data.history||!data.history.length){
						tag.textContent='Complete your first assigned assessment to establish a score baseline.';
						return;
					}
					tag.textContent='Latest: '+(summary.latest_assessment||'Assessment')+' · Results matched to your course email';
				})
				.catch(()=>{tag.textContent='Scores are temporarily unavailable. Refresh or try again later.';});
		})(<?php echo wp_json_encode( $config ); ?>);
		</script>
		<?php
	},
	99
);
