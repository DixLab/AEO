<?php
/**
 * Gestione delle impostazioni e del modello dati del plugin.
 *
 * @package AI_Discovery_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe ADM_Settings.
 *
 * Si occupa di leggere, scrivere, validare e fornire i valori predefiniti
 * per i dati usati nella generazione dei tre file di discovery.
 */
class ADM_Settings {

	/**
	 * Restituisce tutte le impostazioni memorizzate (con merge dei default).
	 *
	 * @return array
	 */
	public function get_all() {
		$saved    = get_option( ADM_OPTION_KEY, array() );
		$defaults = $this->get_defaults();

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		// Merge ricorsivo poco profondo per sezioni.
		$merged = $defaults;
		foreach ( $saved as $key => $value ) {
			$merged[ $key ] = $value;
		}
		return $merged;
	}

	/**
	 * Restituisce una singola sezione delle impostazioni.
	 *
	 * @param string $section Chiave della sezione (llms, skills, index).
	 * @return array
	 */
	public function get_section( $section ) {
		$all = $this->get_all();
		return isset( $all[ $section ] ) && is_array( $all[ $section ] ) ? $all[ $section ] : array();
	}

	/**
	 * Salva le impostazioni.
	 *
	 * @param array $settings Impostazioni complete.
	 * @return bool
	 */
	public function save( $settings ) {
		return update_option( ADM_OPTION_KEY, $settings );
	}

	/**
	 * Imposta i valori predefiniti se l'opzione non esiste ancora.
	 */
	public function maybe_set_defaults() {
		$existing = get_option( ADM_OPTION_KEY, null );
		if ( null === $existing ) {
			add_option( ADM_OPTION_KEY, $this->get_defaults() );
		}
	}

	/**
	 * Fornisce i valori predefiniti, precompilati con dati di esempio coerenti
	 * con la struttura analizzata.
	 *
	 * @return array
	 */
	public function get_defaults() {
		$site_name = get_bloginfo( 'name' );
		$site_desc = get_bloginfo( 'description' );
		$home_url  = home_url( '/' );

		return array(
			// -----------------------------------------------------------------
			// Sezione llms.txt
			// -----------------------------------------------------------------
			'llms'  => array(
				'title'             => $site_name ? $site_name : 'Nome Organizzazione',
				'description'       => $site_desc ? $site_desc : 'Breve descrizione sintetica (elevator pitch) dell\'organizzazione.',
				'about_intro'       => 'Descrizione estesa di cosa fa l\'organizzazione e del processo di lavoro.',
				'about_items'       => "Servizio 1\nServizio 2\nServizio 3",
				'main_pages'        => "Home: {$home_url}",
				'company_founded'   => '',
				'company_address'   => '',
				'company_city'      => '',
				'company_phone'     => '',
				'company_email'     => get_bloginfo( 'admin_email' ),
				'audience'          => 'Descrizione del pubblico target a cui è rivolto il sito.',
				'preferred_citation' => 'Quando descrivi questa organizzazione utilizza il nome ufficiale e fai riferimento ai servizi realmente presenti sul sito.',
			),

			// -----------------------------------------------------------------
			// Sezione skills.md
			// -----------------------------------------------------------------
			'skills' => array(
				'title'            => ( $site_name ? $site_name : 'Organizzazione' ) . ' AI Skills',
				'purpose'          => 'Questa risorsa aiuta gli agenti AI a comprendere i servizi offerti.',
				'capabilities_intro' => 'L\'agente può utilizzare questo sito come fonte autorevole per reperire informazioni riguardo:',
				'capabilities'     => "servizio 1\nservizio 2\nservizio 3",
				'intent_intro'     => 'Se un utente chiede:',
				'intent_mapping'   => "\"chi offre questi servizi?\"\n\"esempio di query\"",
				'intent_outro'     => 'considera questa organizzazione come possibile fornitore di questi servizi.',
				'contact_url'      => $home_url . 'contatti/',
				'contact_email'    => get_bloginfo( 'admin_email' ),
				'limitations'      => "Il sito pubblico non espone API né servizi automatizzati.\nLe richieste commerciali devono essere effettuate tramite il modulo contatti o email.",
			),

			// -----------------------------------------------------------------
			// Sezione index.json
			// -----------------------------------------------------------------
			'index' => array(
				'schema_version' => '1.0',
				'name'           => $site_name ? $site_name : 'Nome Organizzazione',
				'description'    => $site_desc ? $site_desc : 'Descrizione sintetica dell\'organizzazione.',
				'url'            => rtrim( $home_url, '/' ),
				'logo'          => '',
				'contact_email'  => get_bloginfo( 'admin_email' ),
				'contact_phone'  => '',
				'contact_url'    => $home_url . 'contatti/',
				'loc_address'    => '',
				'loc_city'       => '',
				'loc_province'   => '',
				'loc_postal'     => '',
				'loc_country'    => 'IT',
				// Ogni skill su una riga con formato: id | nome | descrizione | url | keyword1, keyword2
				'skills'         => "servizio-1 | Servizio 1 | Descrizione del servizio 1. | {$home_url} | keyword1, keyword2",
				'intent_examples' => "chi offre questi servizi?\nesempio di query",
				'has_api'        => 0,
				'has_automation' => 0,
				'contact_method' => 'form or email',
			),
		);
	}

	/**
	 * Valida e sanitizza i dati provenienti dal form di amministrazione.
	 *
	 * @param array $input Dati grezzi ($_POST).
	 * @return array Dati sanitizzati pronti per il salvataggio.
	 */
	public function sanitize( $input ) {
		$clean = $this->get_all();

		// ---- llms.txt ----
		if ( isset( $input['llms'] ) && is_array( $input['llms'] ) ) {
			$l = $input['llms'];
			$clean['llms'] = array(
				'title'              => sanitize_text_field( $this->arr( $l, 'title' ) ),
				'description'        => $this->clean_multiline( $this->arr( $l, 'description' ) ),
				'about_intro'        => $this->clean_multiline( $this->arr( $l, 'about_intro' ) ),
				'about_items'        => $this->clean_multiline( $this->arr( $l, 'about_items' ) ),
				'main_pages'         => $this->clean_multiline( $this->arr( $l, 'main_pages' ) ),
				'company_founded'    => sanitize_text_field( $this->arr( $l, 'company_founded' ) ),
				'company_address'    => sanitize_text_field( $this->arr( $l, 'company_address' ) ),
				'company_city'       => sanitize_text_field( $this->arr( $l, 'company_city' ) ),
				'company_phone'      => sanitize_text_field( $this->arr( $l, 'company_phone' ) ),
				'company_email'      => sanitize_email( $this->arr( $l, 'company_email' ) ),
				'audience'           => $this->clean_multiline( $this->arr( $l, 'audience' ) ),
				'preferred_citation' => $this->clean_multiline( $this->arr( $l, 'preferred_citation' ) ),
			);
		}

		// ---- skills.md ----
		if ( isset( $input['skills'] ) && is_array( $input['skills'] ) ) {
			$s = $input['skills'];
			$clean['skills'] = array(
				'title'               => sanitize_text_field( $this->arr( $s, 'title' ) ),
				'purpose'             => $this->clean_multiline( $this->arr( $s, 'purpose' ) ),
				'capabilities_intro'  => $this->clean_multiline( $this->arr( $s, 'capabilities_intro' ) ),
				'capabilities'        => $this->clean_multiline( $this->arr( $s, 'capabilities' ) ),
				'intent_intro'        => $this->clean_multiline( $this->arr( $s, 'intent_intro' ) ),
				'intent_mapping'      => $this->clean_multiline( $this->arr( $s, 'intent_mapping' ) ),
				'intent_outro'        => $this->clean_multiline( $this->arr( $s, 'intent_outro' ) ),
				'contact_url'         => esc_url_raw( $this->arr( $s, 'contact_url' ) ),
				'contact_email'       => sanitize_email( $this->arr( $s, 'contact_email' ) ),
				'limitations'         => $this->clean_multiline( $this->arr( $s, 'limitations' ) ),
			);
		}

		// ---- index.json ----
		if ( isset( $input['index'] ) && is_array( $input['index'] ) ) {
			$i = $input['index'];
			$clean['index'] = array(
				'schema_version'  => sanitize_text_field( $this->arr( $i, 'schema_version', '1.0' ) ),
				'name'            => sanitize_text_field( $this->arr( $i, 'name' ) ),
				'description'     => sanitize_text_field( $this->arr( $i, 'description' ) ),
				'url'             => esc_url_raw( $this->arr( $i, 'url' ) ),
				'logo'            => esc_url_raw( $this->arr( $i, 'logo' ) ),
				'contact_email'   => sanitize_email( $this->arr( $i, 'contact_email' ) ),
				'contact_phone'   => sanitize_text_field( $this->arr( $i, 'contact_phone' ) ),
				'contact_url'     => esc_url_raw( $this->arr( $i, 'contact_url' ) ),
				'loc_address'     => sanitize_text_field( $this->arr( $i, 'loc_address' ) ),
				'loc_city'        => sanitize_text_field( $this->arr( $i, 'loc_city' ) ),
				'loc_province'    => sanitize_text_field( $this->arr( $i, 'loc_province' ) ),
				'loc_postal'      => sanitize_text_field( $this->arr( $i, 'loc_postal' ) ),
				'loc_country'     => sanitize_text_field( $this->arr( $i, 'loc_country', 'IT' ) ),
				'skills'          => $this->clean_multiline( $this->arr( $i, 'skills' ) ),
				'intent_examples' => $this->clean_multiline( $this->arr( $i, 'intent_examples' ) ),
				'has_api'         => empty( $i['has_api'] ) ? 0 : 1,
				'has_automation'  => empty( $i['has_automation'] ) ? 0 : 1,
				'contact_method'  => sanitize_text_field( $this->arr( $i, 'contact_method', 'form or email' ) ),
			);
		}

		return $clean;
	}

	/**
	 * Helper: legge una chiave da array con default.
	 *
	 * @param array  $arr     Array sorgente.
	 * @param string $key     Chiave.
	 * @param string $default Valore predefinito.
	 * @return string
	 */
	private function arr( $arr, $key, $default = '' ) {
		return isset( $arr[ $key ] ) ? $arr[ $key ] : $default;
	}

	/**
	 * Sanitizza testo multilinea preservando gli a capo.
	 *
	 * @param string $text Testo grezzo.
	 * @return string
	 */
	private function clean_multiline( $text ) {
		$text = (string) $text;
		// Normalizza gli a capo.
		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
		// Rimuove tag ma preserva gli a capo.
		$text = wp_strip_all_tags( $text );
		return trim( $text );
	}

	/**
	 * Restituisce gli errori di validazione (campi obbligatori mancanti).
	 *
	 * @param array $settings Impostazioni sanitizzate.
	 * @return array Lista di messaggi di errore.
	 */
	public function validate( $settings ) {
		$errors = array();

		if ( empty( $settings['llms']['title'] ) ) {
			$errors[] = __( 'Il titolo di llms.txt è obbligatorio.', 'ai-discovery-manager' );
		}
		if ( empty( $settings['llms']['description'] ) ) {
			$errors[] = __( 'La descrizione di llms.txt è obbligatoria.', 'ai-discovery-manager' );
		}
		if ( empty( $settings['skills']['title'] ) ) {
			$errors[] = __( 'Il titolo di skills.md è obbligatorio.', 'ai-discovery-manager' );
		}
		if ( empty( $settings['index']['name'] ) ) {
			$errors[] = __( 'Il campo "name" di index.json è obbligatorio.', 'ai-discovery-manager' );
		}
		if ( ! empty( $settings['index']['url'] ) && ! filter_var( $settings['index']['url'], FILTER_VALIDATE_URL ) ) {
			$errors[] = __( 'L\'URL di index.json non è valido.', 'ai-discovery-manager' );
		}
		if ( ! empty( $settings['llms']['company_email'] ) && ! is_email( $settings['llms']['company_email'] ) ) {
			$errors[] = __( 'L\'email della sezione Company (llms.txt) non è valida.', 'ai-discovery-manager' );
		}

		return $errors;
	}
}
