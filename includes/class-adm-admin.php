<?php
/**
 * Interfaccia di amministrazione del plugin.
 *
 * @package AI_Discovery_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe ADM_Admin.
 *
 * Costruisce la pagina di amministrazione con interfaccia a tab, gestisce il
 * salvataggio, la validazione e l'anteprima in tempo reale via AJAX.
 */
class ADM_Admin {

	/**
	 * Impostazioni.
	 *
	 * @var ADM_Settings
	 */
	private $settings;

	/**
	 * Generatore.
	 *
	 * @var ADM_Generator
	 */
	private $generator;

	/**
	 * Server.
	 *
	 * @var ADM_Server
	 */
	private $server;

	/**
	 * Slug della pagina di menu.
	 *
	 * @var string
	 */
	private $page_slug = 'ai-discovery-manager';

	/**
	 * Costruttore.
	 *
	 * @param ADM_Settings  $settings  Impostazioni.
	 * @param ADM_Generator $generator Generatore.
	 * @param ADM_Server    $server    Server.
	 */
	public function __construct( ADM_Settings $settings, ADM_Generator $generator, ADM_Server $server ) {
		$this->settings  = $settings;
		$this->generator = $generator;
		$this->server    = $server;
	}

	/**
	 * Registra gli hook di amministrazione.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_adm_save', array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_adm_preview', array( $this, 'ajax_preview' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ADM_PLUGIN_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Aggiunge la voce di menu.
	 */
	public function add_menu() {
		add_menu_page(
			__( 'AI Discovery Manager', 'ai-discovery-manager' ),
			__( 'AI Discovery', 'ai-discovery-manager' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_page' ),
			'dashicons-superhero',
			80
		);
	}

	/**
	 * Aggiunge il link "Impostazioni" nella lista plugin.
	 *
	 * @param array $links Link esistenti.
	 * @return array
	 */
	public function action_links( $links ) {
		$url  = admin_url( 'admin.php?page=' . $this->page_slug );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Impostazioni', 'ai-discovery-manager' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	/**
	 * Carica gli asset (CSS/JS) solo nella pagina del plugin.
	 *
	 * @param string $hook Hook della pagina corrente.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_' . $this->page_slug !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'adm-admin',
			ADM_PLUGIN_URL . 'assets/admin.css',
			array(),
			ADM_VERSION
		);

		wp_enqueue_script(
			'adm-admin',
			ADM_PLUGIN_URL . 'assets/admin.js',
			array( 'jquery' ),
			ADM_VERSION,
			true
		);

		wp_localize_script(
			'adm-admin',
			'ADM',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'adm_preview' ),
				'i18n'    => array(
					'loading' => __( 'Generazione anteprima...', 'ai-discovery-manager' ),
					'error'   => __( 'Errore durante la generazione dell\'anteprima.', 'ai-discovery-manager' ),
				),
			)
		);
	}

	/**
	 * Gestisce il salvataggio del form.
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permessi insufficienti.', 'ai-discovery-manager' ) );
		}

		check_admin_referer( 'adm_save_settings', 'adm_nonce' );

		$raw       = isset( $_POST['adm'] ) ? wp_unslash( $_POST['adm'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$sanitized = $this->settings->sanitize( $raw );
		$errors    = $this->settings->validate( $sanitized );

		$active_tab = isset( $_POST['adm_active_tab'] ) ? sanitize_key( $_POST['adm_active_tab'] ) : 'llms';

		if ( ! empty( $errors ) ) {
			set_transient( 'adm_notice', array(
				'type'     => 'error',
				'messages' => $errors,
			), 60 );
			$this->redirect_back( $active_tab );
			return;
		}

		// Salva le impostazioni.
		$this->settings->save( $sanitized );

		// Rigenera i file su disco.
		$results = $this->generator->generate_all();

		$failed = array_keys( array_filter( $results, function ( $ok ) {
			return ! $ok;
		} ) );

		if ( ! empty( $failed ) ) {
			set_transient( 'adm_notice', array(
				'type'     => 'warning',
				'messages' => array(
					sprintf(
						/* translators: %s: elenco file */
						__( 'Impostazioni salvate, ma non è stato possibile scrivere i seguenti file su disco: %s. I file restano comunque accessibili dinamicamente via URL grazie al sistema di rewrite.', 'ai-discovery-manager' ),
						implode( ', ', $failed )
					),
				),
			), 60 );
		} else {
			set_transient( 'adm_notice', array(
				'type'     => 'success',
				'messages' => array( __( 'Impostazioni salvate e file rigenerati correttamente.', 'ai-discovery-manager' ) ),
			), 60 );
		}

		$this->redirect_back( $active_tab );
	}

	/**
	 * Reindirizza alla pagina del plugin mantenendo il tab attivo.
	 *
	 * @param string $tab Tab attivo.
	 */
	private function redirect_back( $tab ) {
		$url = add_query_arg(
			array(
				'page' => $this->page_slug,
				'tab'  => $tab,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Endpoint AJAX per l'anteprima in tempo reale.
	 */
	public function ajax_preview() {
		check_ajax_referer( 'adm_preview', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permessi insufficienti.', 'ai-discovery-manager' ) ) );
		}

		$raw       = isset( $_POST['adm'] ) ? wp_unslash( $_POST['adm'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$sanitized = $this->settings->sanitize( $raw );

		$previews = array(
			'llms'   => $this->generator->build_llms_txt( $sanitized['llms'] ),
			'skills' => $this->generator->build_skills_md( $sanitized['skills'] ),
			'index'  => $this->generator->build_index_json( $sanitized['index'] ),
		);

		wp_send_json_success( $previews );
	}

	/* =====================================================================
	 * RENDERING PAGINA
	 * ===================================================================== */

	/**
	 * Renderizza la pagina di amministrazione.
	 */
	public function render_page() {
		$data       = $this->settings->get_all();
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'llms'; // phpcs:ignore WordPress.Security.NonceVerification
		$urls       = $this->server->get_public_urls();
		$writable   = $this->generator->is_writable();

		// Mostra eventuali notice.
		$this->render_notice();
		?>
		<div class="wrap adm-wrap">
			<h1><span class="dashicons dashicons-superhero"></span> <?php esc_html_e( 'AI Discovery Manager', 'ai-discovery-manager' ); ?></h1>
			<p class="adm-intro">
				<?php esc_html_e( 'Gestisci i file che aiutano LLM e agenti AI a scoprire e comprendere il tuo sito. Compila i campi, controlla l\'anteprima in tempo reale e salva per rigenerare automaticamente i file.', 'ai-discovery-manager' ); ?>
			</p>

			<?php $this->render_status_box( $urls, $writable ); ?>

			<h2 class="nav-tab-wrapper adm-tabs">
				<a href="#tab-llms" class="nav-tab" data-tab="llms">llms.txt</a>
				<a href="#tab-skills" class="nav-tab" data-tab="skills">skills.md</a>
				<a href="#tab-index" class="nav-tab" data-tab="index">index.json</a>
			</h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="adm-form">
				<input type="hidden" name="action" value="adm_save" />
				<input type="hidden" name="adm_active_tab" id="adm_active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
				<?php wp_nonce_field( 'adm_save_settings', 'adm_nonce' ); ?>

				<div class="adm-panel" id="tab-llms">
					<?php $this->render_llms_tab( $data['llms'] ); ?>
				</div>

				<div class="adm-panel" id="tab-skills">
					<?php $this->render_skills_tab( $data['skills'] ); ?>
				</div>

				<div class="adm-panel" id="tab-index">
					<?php $this->render_index_tab( $data['index'] ); ?>
				</div>

				<p class="adm-actions">
					<button type="submit" class="button button-primary button-hero">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Salva e rigenera i file', 'ai-discovery-manager' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Mostra il box di stato con gli URL pubblici.
	 *
	 * @param array $urls     URL pubblici.
	 * @param bool  $writable Se la root è scrivibile.
	 */
	private function render_status_box( $urls, $writable ) {
		?>
		<div class="adm-status">
			<h3><span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'File pubblici', 'ai-discovery-manager' ); ?></h3>
			<ul>
				<?php foreach ( $urls as $label => $url ) : ?>
					<li>
						<code><?php echo esc_html( $label ); ?></code>
						<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $url ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( ! $writable ) : ?>
				<p class="adm-warning">
					<span class="dashicons dashicons-warning"></span>
					<?php esc_html_e( 'La cartella principale del sito non risulta scrivibile: i file fisici potrebbero non essere creati. Non preoccuparti: il plugin serve comunque i file dinamicamente via URL grazie al sistema di rewrite. Se gli URL restituiscono 404, vai in Impostazioni → Permalink e clicca "Salva".', 'ai-discovery-manager' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Mostra eventuali notice memorizzati in transient.
	 */
	private function render_notice() {
		$notice = get_transient( 'adm_notice' );
		if ( ! $notice || empty( $notice['messages'] ) ) {
			return;
		}
		delete_transient( 'adm_notice' );

		$class = 'notice-info';
		if ( 'success' === $notice['type'] ) {
			$class = 'notice-success';
		} elseif ( 'error' === $notice['type'] ) {
			$class = 'notice-error';
		} elseif ( 'warning' === $notice['type'] ) {
			$class = 'notice-warning';
		}
		?>
		<div class="notice <?php echo esc_attr( $class ); ?> is-dismissible">
			<?php foreach ( $notice['messages'] as $msg ) : ?>
				<p><?php echo esc_html( $msg ); ?></p>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/* =====================================================================
	 * TAB: llms.txt
	 * ===================================================================== */

	/**
	 * Renderizza il tab llms.txt.
	 *
	 * @param array $d Dati sezione.
	 */
	private function render_llms_tab( $d ) {
		?>
		<div class="adm-columns">
			<div class="adm-fields">
				<h2>llms.txt</h2>
				<p class="description"><?php esc_html_e( 'File Markdown (standard llmstxt.org) posizionato nella root del sito, pensato per fornire agli LLM un contesto strutturato.', 'ai-discovery-manager' ); ?></p>

				<?php
				$this->text_field( 'llms', 'title', __( 'Titolo (H1)', 'ai-discovery-manager' ), $d['title'], __( 'Nome dell\'organizzazione. Obbligatorio.', 'ai-discovery-manager' ), true );
				$this->textarea_field( 'llms', 'description', __( 'Descrizione breve (blockquote)', 'ai-discovery-manager' ), $d['description'], __( 'Elevator pitch di una o due frasi. Obbligatorio.', 'ai-discovery-manager' ), 3, true );
				?>

				<h3><?php esc_html_e( 'Sezione About', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->textarea_field( 'llms', 'about_intro', __( 'Introduzione About', 'ai-discovery-manager' ), $d['about_intro'], __( 'Descrizione estesa di cosa fa l\'organizzazione.', 'ai-discovery-manager' ), 3 );
				$this->textarea_field( 'llms', 'about_items', __( 'Aree di competenza (una per riga)', 'ai-discovery-manager' ), $d['about_items'], __( 'Ogni riga diventa un punto elenco.', 'ai-discovery-manager' ), 6 );
				?>

				<h3><?php esc_html_e( 'Sezione Main Pages', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->textarea_field( 'llms', 'main_pages', __( 'Pagine principali (formato "Nome: URL", una per riga)', 'ai-discovery-manager' ), $d['main_pages'], __( 'Esempio: Home: https://www.esempio.it/', 'ai-discovery-manager' ), 6 );
				?>

				<h3><?php esc_html_e( 'Sezione Company', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->text_field( 'llms', 'company_founded', __( 'Anno di fondazione', 'ai-discovery-manager' ), $d['company_founded'], '' );
				$this->text_field( 'llms', 'company_address', __( 'Indirizzo (via e civico)', 'ai-discovery-manager' ), $d['company_address'], '' );
				$this->text_field( 'llms', 'company_city', __( 'Città (CAP, città, provincia)', 'ai-discovery-manager' ), $d['company_city'], '' );
				$this->text_field( 'llms', 'company_phone', __( 'Telefono', 'ai-discovery-manager' ), $d['company_phone'], '' );
				$this->text_field( 'llms', 'company_email', __( 'Email', 'ai-discovery-manager' ), $d['company_email'], '' );
				?>

				<h3><?php esc_html_e( 'Sezione Audience', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->textarea_field( 'llms', 'audience', __( 'Pubblico target', 'ai-discovery-manager' ), $d['audience'], __( 'A chi è rivolto il sito.', 'ai-discovery-manager' ), 3 );
				?>

				<h3><?php esc_html_e( 'Sezione Preferred Citation', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->textarea_field( 'llms', 'preferred_citation', __( 'Citazione preferita', 'ai-discovery-manager' ), $d['preferred_citation'], __( 'Come l\'AI dovrebbe citare l\'organizzazione.', 'ai-discovery-manager' ), 3 );
				?>
			</div>
			<?php $this->render_preview_column( 'llms', __( 'Anteprima llms.txt', 'ai-discovery-manager' ) ); ?>
		</div>
		<?php
	}

	/* =====================================================================
	 * TAB: skills.md
	 * ===================================================================== */

	/**
	 * Renderizza il tab skills.md.
	 *
	 * @param array $d Dati sezione.
	 */
	private function render_skills_tab( $d ) {
		?>
		<div class="adm-columns">
			<div class="adm-fields">
				<h2>skills.md</h2>
				<p class="description"><?php esc_html_e( 'File Markdown nella root del sito che comunica agli agenti AI le capacità operative del sito.', 'ai-discovery-manager' ); ?></p>

				<?php
				$this->text_field( 'skills', 'title', __( 'Titolo (H1)', 'ai-discovery-manager' ), $d['title'], __( 'Esempio: "Nome Organizzazione AI Skills". Obbligatorio.', 'ai-discovery-manager' ), true );
				?>

				<h3><?php esc_html_e( 'Sezione Purpose', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->textarea_field( 'skills', 'purpose', __( 'Scopo del documento', 'ai-discovery-manager' ), $d['purpose'], '', 3 );
				?>

				<h3><?php esc_html_e( 'Sezione Capabilities', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->textarea_field( 'skills', 'capabilities_intro', __( 'Introduzione capacità', 'ai-discovery-manager' ), $d['capabilities_intro'], '', 2 );
				$this->textarea_field( 'skills', 'capabilities', __( 'Capacità / servizi (uno per riga)', 'ai-discovery-manager' ), $d['capabilities'], __( 'Ogni riga diventa un punto elenco.', 'ai-discovery-manager' ), 8 );
				?>

				<h3><?php esc_html_e( 'Sezione User Intent Mapping', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->textarea_field( 'skills', 'intent_intro', __( 'Introduzione intent', 'ai-discovery-manager' ), $d['intent_intro'], '', 2 );
				$this->textarea_field( 'skills', 'intent_mapping', __( 'Frasi di esempio (una per riga)', 'ai-discovery-manager' ), $d['intent_mapping'], __( 'Query tipiche che un utente potrebbe usare.', 'ai-discovery-manager' ), 6 );
				$this->textarea_field( 'skills', 'intent_outro', __( 'Conclusione intent', 'ai-discovery-manager' ), $d['intent_outro'], '', 2 );
				?>

				<h3><?php esc_html_e( 'Sezione Contact', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->text_field( 'skills', 'contact_url', __( 'URL pagina contatti', 'ai-discovery-manager' ), $d['contact_url'], '' );
				$this->text_field( 'skills', 'contact_email', __( 'Email di contatto', 'ai-discovery-manager' ), $d['contact_email'], '' );
				?>

				<h3><?php esc_html_e( 'Sezione Limitations', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->textarea_field( 'skills', 'limitations', __( 'Limitazioni (una per riga)', 'ai-discovery-manager' ), $d['limitations'], __( 'Cosa l\'agente AI NON deve aspettarsi.', 'ai-discovery-manager' ), 4 );
				?>
			</div>
			<?php $this->render_preview_column( 'skills', __( 'Anteprima skills.md', 'ai-discovery-manager' ) ); ?>
		</div>
		<?php
	}

	/* =====================================================================
	 * TAB: index.json
	 * ===================================================================== */

	/**
	 * Renderizza il tab index.json.
	 *
	 * @param array $d Dati sezione.
	 */
	private function render_index_tab( $d ) {
		?>
		<div class="adm-columns">
			<div class="adm-fields">
				<h2>index.json</h2>
				<p class="description"><?php esc_html_e( 'Manifesto machine-readable in /.well-known/agent-skills/ che unifica e referenzia gli altri file (llms.txt e skills.md nella root del sito).', 'ai-discovery-manager' ); ?></p>

				<h3><?php esc_html_e( 'Metadata', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->text_field( 'index', 'schema_version', __( 'Versione schema', 'ai-discovery-manager' ), $d['schema_version'], '' );
				$this->text_field( 'index', 'name', __( 'Nome (name)', 'ai-discovery-manager' ), $d['name'], __( 'Obbligatorio.', 'ai-discovery-manager' ), true );
				$this->textarea_field( 'index', 'description', __( 'Descrizione', 'ai-discovery-manager' ), $d['description'], '', 2 );
				$this->text_field( 'index', 'url', __( 'URL principale', 'ai-discovery-manager' ), $d['url'], '' );
				$this->text_field( 'index', 'logo', __( 'URL logo', 'ai-discovery-manager' ), $d['logo'], '' );
				?>

				<h3><?php esc_html_e( 'Contatti', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->text_field( 'index', 'contact_email', __( 'Email', 'ai-discovery-manager' ), $d['contact_email'], '' );
				$this->text_field( 'index', 'contact_phone', __( 'Telefono', 'ai-discovery-manager' ), $d['contact_phone'], '' );
				$this->text_field( 'index', 'contact_url', __( 'URL contatti', 'ai-discovery-manager' ), $d['contact_url'], '' );
				?>

				<h3><?php esc_html_e( 'Sede (location)', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->text_field( 'index', 'loc_address', __( 'Indirizzo', 'ai-discovery-manager' ), $d['loc_address'], '' );
				$this->text_field( 'index', 'loc_city', __( 'Città', 'ai-discovery-manager' ), $d['loc_city'], '' );
				$this->text_field( 'index', 'loc_province', __( 'Provincia', 'ai-discovery-manager' ), $d['loc_province'], '' );
				$this->text_field( 'index', 'loc_postal', __( 'CAP', 'ai-discovery-manager' ), $d['loc_postal'], '' );
				$this->text_field( 'index', 'loc_country', __( 'Paese (codice ISO)', 'ai-discovery-manager' ), $d['loc_country'], '' );
				?>

				<h3><?php esc_html_e( 'Skills', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->textarea_field(
					'index',
					'skills',
					__( 'Skills (una per riga)', 'ai-discovery-manager' ),
					$d['skills'],
					__( 'Formato: id | nome | descrizione | url | keyword1, keyword2 (url e keyword opzionali).', 'ai-discovery-manager' ),
					8
				);
				?>

				<h3><?php esc_html_e( 'Intent examples', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->textarea_field( 'index', 'intent_examples', __( 'Frasi di esempio (una per riga)', 'ai-discovery-manager' ), $d['intent_examples'], '', 5 );
				?>

				<h3><?php esc_html_e( 'Limitazioni', 'ai-discovery-manager' ); ?></h3>
				<?php
				$this->checkbox_field( 'index', 'has_api', __( 'Il sito espone API pubbliche', 'ai-discovery-manager' ), ! empty( $d['has_api'] ) );
				$this->checkbox_field( 'index', 'has_automation', __( 'Il sito offre servizi automatizzati', 'ai-discovery-manager' ), ! empty( $d['has_automation'] ) );
				$this->text_field( 'index', 'contact_method', __( 'Metodo di contatto', 'ai-discovery-manager' ), $d['contact_method'], __( 'Esempio: form or email', 'ai-discovery-manager' ) );
				?>
			</div>
			<?php $this->render_preview_column( 'index', __( 'Anteprima index.json', 'ai-discovery-manager' ) ); ?>
		</div>
		<?php
	}

	/* =====================================================================
	 * HELPER DI RENDERING CAMPI
	 * ===================================================================== */

	/**
	 * Colonna anteprima.
	 *
	 * @param string $key   Chiave sezione.
	 * @param string $title Titolo.
	 */
	private function render_preview_column( $key, $title ) {
		?>
		<div class="adm-preview">
			<div class="adm-preview-head">
				<h3><?php echo esc_html( $title ); ?></h3>
				<span class="adm-preview-status" data-preview-status="<?php echo esc_attr( $key ); ?>"></span>
			</div>
			<pre class="adm-preview-box" data-preview="<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'L\'anteprima verrà generata automaticamente.', 'ai-discovery-manager' ); ?></pre>
		</div>
		<?php
	}

	/**
	 * Campo di testo.
	 *
	 * @param string $section  Sezione.
	 * @param string $name     Nome campo.
	 * @param string $label    Etichetta.
	 * @param string $value    Valore.
	 * @param string $desc     Descrizione.
	 * @param bool   $required Obbligatorio.
	 */
	private function text_field( $section, $name, $label, $value, $desc = '', $required = false ) {
		$id = 'adm-' . $section . '-' . $name;
		?>
		<div class="adm-field">
			<label for="<?php echo esc_attr( $id ); ?>">
				<?php echo esc_html( $label ); ?>
				<?php if ( $required ) : ?><span class="adm-req">*</span><?php endif; ?>
			</label>
			<input
				type="text"
				id="<?php echo esc_attr( $id ); ?>"
				name="adm[<?php echo esc_attr( $section ); ?>][<?php echo esc_attr( $name ); ?>]"
				value="<?php echo esc_attr( $value ); ?>"
				class="regular-text adm-input"
				<?php echo $required ? 'required' : ''; ?>
			/>
			<?php if ( $desc ) : ?><p class="description"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Campo textarea.
	 *
	 * @param string $section  Sezione.
	 * @param string $name     Nome campo.
	 * @param string $label    Etichetta.
	 * @param string $value    Valore.
	 * @param string $desc     Descrizione.
	 * @param int    $rows     Numero righe.
	 * @param bool   $required Obbligatorio.
	 */
	private function textarea_field( $section, $name, $label, $value, $desc = '', $rows = 4, $required = false ) {
		$id = 'adm-' . $section . '-' . $name;
		?>
		<div class="adm-field">
			<label for="<?php echo esc_attr( $id ); ?>">
				<?php echo esc_html( $label ); ?>
				<?php if ( $required ) : ?><span class="adm-req">*</span><?php endif; ?>
			</label>
			<textarea
				id="<?php echo esc_attr( $id ); ?>"
				name="adm[<?php echo esc_attr( $section ); ?>][<?php echo esc_attr( $name ); ?>]"
				rows="<?php echo esc_attr( $rows ); ?>"
				class="large-text adm-input"
				<?php echo $required ? 'required' : ''; ?>
			><?php echo esc_textarea( $value ); ?></textarea>
			<?php if ( $desc ) : ?><p class="description"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Campo checkbox.
	 *
	 * @param string $section Sezione.
	 * @param string $name    Nome campo.
	 * @param string $label   Etichetta.
	 * @param bool   $checked Selezionato.
	 */
	private function checkbox_field( $section, $name, $label, $checked ) {
		$id = 'adm-' . $section . '-' . $name;
		?>
		<div class="adm-field adm-field-checkbox">
			<label for="<?php echo esc_attr( $id ); ?>">
				<input
					type="checkbox"
					id="<?php echo esc_attr( $id ); ?>"
					name="adm[<?php echo esc_attr( $section ); ?>][<?php echo esc_attr( $name ); ?>]"
					value="1"
					class="adm-input"
					<?php checked( $checked ); ?>
				/>
				<?php echo esc_html( $label ); ?>
			</label>
		</div>
		<?php
	}
}
