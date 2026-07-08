<?php
/**
 * Plugin Name:       AI Discovery Manager
 * Plugin URI:        https://example.com/ai-discovery-manager
 * Description:        Genera e gestisce automaticamente i file per l'AI Discovery del tuo sito: llms.txt, skills.md (nella root) e .well-known/agent-skills/index.json. Interfaccia in italiano con anteprima in tempo reale.
 * Version:           1.0.0
 * Requires at least: 5.5
 * Requires PHP:      7.2
 * Author:            Dix Lab
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-discovery-manager
 *
 * @package AI_Discovery_Manager
 */

// Impedisce l'accesso diretto al file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Costanti del plugin.
define( 'ADM_VERSION', '1.0.0' );
define( 'ADM_PLUGIN_FILE', __FILE__ );
define( 'ADM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ADM_OPTION_KEY', 'adm_settings' );

// Inclusione delle classi principali.
require_once ADM_PLUGIN_DIR . 'includes/class-adm-settings.php';
require_once ADM_PLUGIN_DIR . 'includes/class-adm-generator.php';
require_once ADM_PLUGIN_DIR . 'includes/class-adm-server.php';
require_once ADM_PLUGIN_DIR . 'includes/class-adm-admin.php';

/**
 * Classe principale del plugin: orchestratore dei vari componenti.
 */
final class AI_Discovery_Manager {

	/**
	 * Istanza singleton.
	 *
	 * @var AI_Discovery_Manager|null
	 */
	private static $instance = null;

	/**
	 * Gestore impostazioni.
	 *
	 * @var ADM_Settings
	 */
	public $settings;

	/**
	 * Generatore file.
	 *
	 * @var ADM_Generator
	 */
	public $generator;

	/**
	 * Server dei file (rewrite/serving).
	 *
	 * @var ADM_Server
	 */
	public $server;

	/**
	 * Interfaccia di amministrazione.
	 *
	 * @var ADM_Admin
	 */
	public $admin;

	/**
	 * Restituisce l'istanza singleton.
	 *
	 * @return AI_Discovery_Manager
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Costruttore: inizializza i componenti e registra gli hook.
	 */
	private function __construct() {
		$this->settings  = new ADM_Settings();
		$this->generator = new ADM_Generator( $this->settings );
		$this->server    = new ADM_Server( $this->settings, $this->generator );
		$this->admin     = new ADM_Admin( $this->settings, $this->generator, $this->server );

		// Registra i componenti.
		$this->server->register();

		if ( is_admin() ) {
			$this->admin->register();
		}
	}

	/**
	 * Routine di attivazione del plugin.
	 */
	public static function activate() {
		$settings  = new ADM_Settings();
		$generator = new ADM_Generator( $settings );
		$server    = new ADM_Server( $settings, $generator );

		// Imposta i valori predefiniti se non esistono.
		$settings->maybe_set_defaults();

		// Genera i file al primo avvio.
		$generator->generate_all();

		// Registra le regole di rewrite e le rende effettive.
		$server->add_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Routine di disattivazione del plugin.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}

// Hook di attivazione / disattivazione.
register_activation_hook( __FILE__, array( 'AI_Discovery_Manager', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AI_Discovery_Manager', 'deactivate' ) );

/**
 * Avvia il plugin dopo il caricamento di tutti i plugin.
 */
function adm_bootstrap() {
	return AI_Discovery_Manager::instance();
}
add_action( 'plugins_loaded', 'adm_bootstrap' );
