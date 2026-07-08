<?php
/**
 * Generatore dei file di AI Discovery.
 *
 * @package AI_Discovery_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe ADM_Generator.
 *
 * Costruisce il contenuto testuale dei tre file e li scrive su disco:
 * - llms.txt              -> root del sito
 * - skills.md             -> root del sito
 * - index.json            -> /.well-known/agent-skills/
 */
class ADM_Generator {

	/**
	 * Gestore impostazioni.
	 *
	 * @var ADM_Settings
	 */
	private $settings;

	/**
	 * Costruttore.
	 *
	 * @param ADM_Settings $settings Gestore impostazioni.
	 */
	public function __construct( ADM_Settings $settings ) {
		$this->settings = $settings;
	}

	/* =====================================================================
	 * COSTRUZIONE CONTENUTI
	 * ===================================================================== */

	/**
	 * Costruisce il contenuto di llms.txt.
	 *
	 * @param array|null $data Dati opzionali (per preview). Se null usa quelli salvati.
	 * @return string
	 */
	public function build_llms_txt( $data = null ) {
		$d = null !== $data ? $data : $this->settings->get_section( 'llms' );

		$lines   = array();
		$lines[] = '# ' . $this->val( $d, 'title' );
		$lines[] = '';

		$desc = $this->val( $d, 'description' );
		if ( '' !== $desc ) {
			// Blockquote su singola riga (unisce eventuali a capo).
			$desc    = preg_replace( '/\s*\n\s*/', ' ', $desc );
			$lines[] = '> ' . $desc;
			$lines[] = '';
		}

		// Sezione About.
		$about_intro = $this->val( $d, 'about_intro' );
		$about_items = $this->to_list( $this->val( $d, 'about_items' ) );
		if ( '' !== $about_intro || ! empty( $about_items ) ) {
			$lines[] = '## About';
			$lines[] = '';
			if ( '' !== $about_intro ) {
				$lines[] = $about_intro;
				$lines[] = '';
			}
			if ( ! empty( $about_items ) ) {
				$lines[] = 'Aree di competenza:';
				$lines[] = '';
				foreach ( $about_items as $item ) {
					$lines[] = '- ' . $item;
				}
				$lines[] = '';
			}
		}

		// Sezione Main Pages.
		$pages = $this->to_list( $this->val( $d, 'main_pages' ) );
		if ( ! empty( $pages ) ) {
			$lines[] = '## Main Pages';
			$lines[] = '';
			foreach ( $pages as $page ) {
				$lines[] = '- ' . $page;
			}
			$lines[] = '';
		}

		// Sezione Company.
		$founded = $this->val( $d, 'company_founded' );
		$address = $this->val( $d, 'company_address' );
		$city    = $this->val( $d, 'company_city' );
		$phone   = $this->val( $d, 'company_phone' );
		$email   = $this->val( $d, 'company_email' );
		if ( $founded || $address || $city || $phone || $email ) {
			$lines[] = '## Company';
			$lines[] = '';
			if ( $founded ) {
				$lines[] = 'Fondata nel ' . $founded . '.';
				$lines[] = '';
			}
			if ( $address || $city ) {
				$lines[] = 'Sede:';
				if ( $address ) {
					$lines[] = $address;
				}
				if ( $city ) {
					$lines[] = $city;
				}
				$lines[] = '';
			}
			if ( $phone ) {
				$lines[] = 'Telefono: ' . $phone;
				$lines[] = '';
			}
			if ( $email ) {
				$lines[] = 'Email: ' . $email;
				$lines[] = '';
			}
		}

		// Sezione Audience.
		$audience = $this->val( $d, 'audience' );
		if ( '' !== $audience ) {
			$lines[] = '## Audience';
			$lines[] = '';
			$lines[] = $audience;
			$lines[] = '';
		}

		// Sezione Preferred Citation.
		$citation = $this->val( $d, 'preferred_citation' );
		if ( '' !== $citation ) {
			$lines[] = '## Preferred Citation';
			$lines[] = '';
			$lines[] = $citation;
			$lines[] = '';
		}

		return $this->finish( $lines );
	}

	/**
	 * Costruisce il contenuto di skills.md.
	 *
	 * @param array|null $data Dati opzionali (per preview).
	 * @return string
	 */
	public function build_skills_md( $data = null ) {
		$d = null !== $data ? $data : $this->settings->get_section( 'skills' );

		$lines   = array();
		$lines[] = '# ' . $this->val( $d, 'title' );
		$lines[] = '';

		// Purpose.
		$purpose = $this->val( $d, 'purpose' );
		if ( '' !== $purpose ) {
			$lines[] = '## Purpose';
			$lines[] = '';
			$lines[] = $purpose;
			$lines[] = '';
		}

		// Capabilities.
		$cap_intro = $this->val( $d, 'capabilities_intro' );
		$caps      = $this->to_list( $this->val( $d, 'capabilities' ) );
		if ( '' !== $cap_intro || ! empty( $caps ) ) {
			$lines[] = '## Capabilities';
			$lines[] = '';
			if ( '' !== $cap_intro ) {
				$lines[] = $cap_intro;
				$lines[] = '';
			}
			foreach ( $caps as $cap ) {
				$lines[] = '- ' . $cap;
			}
			if ( ! empty( $caps ) ) {
				$lines[] = '';
			}
		}

		// User Intent Mapping.
		$intent_intro = $this->val( $d, 'intent_intro' );
		$intents      = $this->to_list( $this->val( $d, 'intent_mapping' ) );
		$intent_outro = $this->val( $d, 'intent_outro' );
		if ( '' !== $intent_intro || ! empty( $intents ) || '' !== $intent_outro ) {
			$lines[] = '## User Intent Mapping';
			$lines[] = '';
			if ( '' !== $intent_intro ) {
				$lines[] = $intent_intro;
				$lines[] = '';
			}
			foreach ( $intents as $intent ) {
				$lines[] = '- ' . $intent;
			}
			if ( ! empty( $intents ) ) {
				$lines[] = '';
			}
			if ( '' !== $intent_outro ) {
				$lines[] = $intent_outro;
				$lines[] = '';
			}
		}

		// Contact.
		$contact_url   = $this->val( $d, 'contact_url' );
		$contact_email = $this->val( $d, 'contact_email' );
		if ( $contact_url || $contact_email ) {
			$lines[] = '## Contact';
			$lines[] = '';
			$lines[] = 'Per richieste commerciali indirizzare l\'utente alla pagina:';
			$lines[] = '';
			if ( $contact_url ) {
				$lines[] = $contact_url;
				$lines[] = '';
			}
			if ( $contact_url && $contact_email ) {
				$lines[] = 'oppure';
				$lines[] = '';
			}
			if ( $contact_email ) {
				$lines[] = $contact_email;
				$lines[] = '';
			}
		}

		// Limitations.
		$limitations = $this->val( $d, 'limitations' );
		if ( '' !== $limitations ) {
			$lines[] = '## Limitations';
			$lines[] = '';
			// Ogni riga come paragrafo separato.
			$parts = array_filter( array_map( 'trim', explode( "\n", $limitations ) ) );
			foreach ( $parts as $part ) {
				$lines[] = $part;
				$lines[] = '';
			}
		}

		return $this->finish( $lines );
	}

	/**
	 * Costruisce il contenuto di index.json.
	 *
	 * @param array|null $data Dati opzionali (per preview).
	 * @return string JSON formattato.
	 */
	public function build_index_json( $data = null ) {
		$d = null !== $data ? $data : $this->settings->get_section( 'index' );

		$manifest = array(
			'schema_version' => $this->val( $d, 'schema_version', '1.0' ),
			'name'           => $this->val( $d, 'name' ),
			'description'    => $this->val( $d, 'description' ),
			'url'            => $this->val( $d, 'url' ),
		);

		$logo = $this->val( $d, 'logo' );
		if ( '' !== $logo ) {
			$manifest['logo'] = $logo;
		}

		// Contact.
		$contact = array();
		if ( $this->val( $d, 'contact_email' ) ) {
			$contact['email'] = $this->val( $d, 'contact_email' );
		}
		if ( $this->val( $d, 'contact_phone' ) ) {
			$contact['phone'] = $this->val( $d, 'contact_phone' );
		}
		if ( $this->val( $d, 'contact_url' ) ) {
			$contact['url'] = $this->val( $d, 'contact_url' );
		}
		if ( ! empty( $contact ) ) {
			$manifest['contact'] = $contact;
		}

		// Location.
		$location = array();
		$map      = array(
			'loc_address'  => 'address',
			'loc_city'     => 'city',
			'loc_province' => 'province',
			'loc_postal'   => 'postal_code',
			'loc_country'  => 'country',
		);
		foreach ( $map as $field => $out ) {
			$v = $this->val( $d, $field );
			if ( '' !== $v ) {
				$location[ $out ] = $v;
			}
		}
		if ( ! empty( $location ) ) {
			$manifest['location'] = $location;
		}

		// Skills: parse riga "id | nome | descrizione | url | keyword1, keyword2".
		$skills = $this->parse_skills( $this->val( $d, 'skills' ) );
		if ( ! empty( $skills ) ) {
			$manifest['skills'] = $skills;
		}

		// Intent examples.
		$intents = $this->to_list( $this->val( $d, 'intent_examples' ) );
		if ( ! empty( $intents ) ) {
			$manifest['intent_examples'] = array_values( $intents );
		}

		// Limitations.
		$manifest['limitations'] = array(
			'has_api'        => (bool) $this->val( $d, 'has_api', 0 ),
			'has_automation' => (bool) $this->val( $d, 'has_automation', 0 ),
			'contact_method' => $this->val( $d, 'contact_method', 'form or email' ),
		);

		// Resources: link agli altri file.
		$manifest['resources'] = array(
			'llms_txt' => home_url( '/llms.txt' ),
			'skills_md' => home_url( '/skills.md' ),
		);

		$flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		return wp_json_encode( $manifest, $flags );
	}

	/**
	 * Effettua il parsing delle skill dalla textarea.
	 *
	 * Formato per riga: id | nome | descrizione | url | keyword1, keyword2
	 * I campi url e keywords sono opzionali.
	 *
	 * @param string $raw Testo grezzo.
	 * @return array
	 */
	private function parse_skills( $raw ) {
		$skills = array();
		$rows   = array_filter( array_map( 'trim', explode( "\n", (string) $raw ) ) );

		foreach ( $rows as $row ) {
			$cols = array_map( 'trim', explode( '|', $row ) );
			if ( empty( $cols[0] ) ) {
				continue;
			}
			$skill = array(
				'id'          => sanitize_title( $cols[0] ),
				'name'        => isset( $cols[1] ) ? $cols[1] : $cols[0],
				'description' => isset( $cols[2] ) ? $cols[2] : '',
			);
			if ( ! empty( $cols[3] ) ) {
				$skill['url'] = $cols[3];
			}
			if ( ! empty( $cols[4] ) ) {
				$keywords          = array_filter( array_map( 'trim', explode( ',', $cols[4] ) ) );
				$skill['keywords'] = array_values( $keywords );
			}
			$skills[] = $skill;
		}

		return $skills;
	}

	/* =====================================================================
	 * SCRITTURA SU DISCO
	 * ===================================================================== */

	/**
	 * Genera e scrive tutti i file su disco.
	 *
	 * @return array Esito per ciascun file [file => bool].
	 */
	public function generate_all() {
		$results = array();

		$root = $this->get_root_path();
		$wk   = trailingslashit( $root ) . '.well-known/agent-skills';

		// Crea la cartella .well-known/agent-skills se non esiste.
		$this->ensure_dir( $wk );

		// llms.txt nella root.
		$results['llms.txt'] = $this->write_file(
			trailingslashit( $root ) . 'llms.txt',
			$this->build_llms_txt()
		);

		// skills.md nella root.
		$results['skills.md'] = $this->write_file(
			trailingslashit( $root ) . 'skills.md',
			$this->build_skills_md()
		);

		// index.json in .well-known/agent-skills/.
		$results['index.json'] = $this->write_file(
			trailingslashit( $wk ) . 'index.json',
			$this->build_index_json()
		);

		return $results;
	}

	/**
	 * Percorso root del sito (dove risiede WordPress / il documento pubblico).
	 *
	 * @return string
	 */
	public function get_root_path() {
		// ABSPATH è la root dell'installazione WordPress.
		return untrailingslashit( ABSPATH );
	}

	/**
	 * Crea una directory in modo ricorsivo usando WP_Filesystem con fallback.
	 *
	 * @param string $dir Percorso directory.
	 * @return bool
	 */
	private function ensure_dir( $dir ) {
		if ( is_dir( $dir ) ) {
			return true;
		}
		$fs = $this->fs();
		if ( $fs ) {
			return $fs->mkdir( $dir, FS_CHMOD_DIR ) || wp_mkdir_p( $dir );
		}
		return wp_mkdir_p( $dir );
	}

	/**
	 * Scrive un file su disco usando WP_Filesystem con fallback a file_put_contents.
	 *
	 * @param string $path    Percorso completo del file.
	 * @param string $content Contenuto.
	 * @return bool
	 */
	private function write_file( $path, $content ) {
		$fs = $this->fs();
		if ( $fs ) {
			$ok = $fs->put_contents( $path, $content, FS_CHMOD_FILE );
			if ( $ok ) {
				return true;
			}
		}
		// Fallback diretto.
		$written = @file_put_contents( $path, $content ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return false !== $written;
	}

	/**
	 * Inizializza e restituisce l'istanza di WP_Filesystem.
	 *
	 * @return WP_Filesystem_Base|false
	 */
	private function fs() {
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( empty( $wp_filesystem ) ) {
			// Metodo diretto senza credenziali FTP.
			WP_Filesystem();
		}
		return $wp_filesystem ? $wp_filesystem : false;
	}

	/**
	 * Verifica se i file sono scrivibili nella root.
	 *
	 * @return bool
	 */
	public function is_writable() {
		return is_writable( $this->get_root_path() );
	}

	/* =====================================================================
	 * HELPER
	 * ===================================================================== */

	/**
	 * Legge un valore dall'array dati con default.
	 *
	 * @param array  $d       Dati.
	 * @param string $key     Chiave.
	 * @param mixed  $default Valore predefinito.
	 * @return mixed
	 */
	private function val( $d, $key, $default = '' ) {
		return isset( $d[ $key ] ) ? $d[ $key ] : $default;
	}

	/**
	 * Converte testo multilinea in un array di righe non vuote.
	 *
	 * @param string $text Testo.
	 * @return array
	 */
	private function to_list( $text ) {
		$text = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
		return array_values( array_filter( array_map( 'trim', explode( "\n", $text ) ) ) );
	}

	/**
	 * Compone le righe finali eliminando righe vuote in eccesso.
	 *
	 * @param array $lines Righe.
	 * @return string
	 */
	private function finish( $lines ) {
		$text = implode( "\n", $lines );
		// Comprime più di due a capo consecutivi in uno solo doppio.
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );
		return trim( $text ) . "\n";
	}
}
