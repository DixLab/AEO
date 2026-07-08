<?php
/**
 * Gestione del serving pubblico dei file di discovery.
 *
 * @package AI_Discovery_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe ADM_Server.
 *
 * Registra le regole di rewrite e serve dinamicamente i file quando i file
 * fisici non sono disponibili (ad esempio se la root non è scrivibile).
 */
class ADM_Server {

	/**
	 * Gestore impostazioni.
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
	 * Costruttore.
	 *
	 * @param ADM_Settings  $settings  Impostazioni.
	 * @param ADM_Generator $generator Generatore.
	 */
	public function __construct( ADM_Settings $settings, ADM_Generator $generator ) {
		$this->settings  = $settings;
		$this->generator = $generator;
	}

	/**
	 * Registra gli hook.
	 */
	public function register() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'maybe_serve_file' ) );
	}

	/**
	 * Aggiunge le regole di rewrite per i tre file.
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?adm_file=llms', 'top' );
		add_rewrite_rule( '^skills\.md$', 'index.php?adm_file=skills', 'top' );
		add_rewrite_rule( '^\.well-known/agent-skills/index\.json$', 'index.php?adm_file=index', 'top' );
	}

	/**
	 * Registra la query var personalizzata.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'adm_file';
		return $vars;
	}

	/**
	 * Serve dinamicamente il file richiesto se corrisponde a una query var nota.
	 *
	 * Questo funge da fallback quando i file fisici non esistono, garantendo
	 * comunque l'accessibilità pubblica via URL.
	 */
	public function maybe_serve_file() {
		$file = get_query_var( 'adm_file' );
		if ( empty( $file ) ) {
			return;
		}

		switch ( $file ) {
			case 'llms':
				$this->output( $this->generator->build_llms_txt(), 'text/plain; charset=utf-8' );
				break;
			case 'skills':
				$this->output( $this->generator->build_skills_md(), 'text/markdown; charset=utf-8' );
				break;
			case 'index':
				$this->output( $this->generator->build_index_json(), 'application/json; charset=utf-8' );
				break;
			default:
				return;
		}
	}

	/**
	 * Invia il contenuto con l'header corretto e termina l'esecuzione.
	 *
	 * @param string $content Contenuto.
	 * @param string $mime    Tipo MIME.
	 */
	private function output( $content, $mime ) {
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'X-Robots-Tag: all', true );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * URL pubblici dei tre file.
	 *
	 * @return array
	 */
	public function get_public_urls() {
		return array(
			'llms.txt'   => home_url( '/llms.txt' ),
			'skills.md'  => home_url( '/skills.md' ),
			'index.json' => home_url( '/.well-known/agent-skills/index.json' ),
		);
	}
}
