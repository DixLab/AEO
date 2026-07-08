=== AI Discovery Manager ===
Contributors: dixlab
Tags: ai, llms.txt, agent-skills, seo, discovery, llm
Requires at least: 5.5
Tested up to: 6.6
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin sviluppato da Dix Lab. Genera e gestisce automaticamente i file per l'AI Discovery del tuo sito: llms.txt, skills.md e .well-known/agent-skills/index.json.

== Description ==

**AI Discovery Manager** permette di rendere il tuo sito facilmente scopribile e comprensibile da LLM e agenti AI, generando automaticamente tre file standard:

* **llms.txt** — file Markdown (standard llmstxt.org) nella root del sito, con informazioni generali sull'organizzazione.
* **skills.md** — file Markdown nella root del sito che descrive le capacità operative per gli agenti AI.
* **index.json** — manifesto machine-readable in `/.well-known/agent-skills/` che unifica e referenzia gli altri file.

= Caratteristiche principali =

* Interfaccia di amministrazione in italiano con **tab separati** per ogni file.
* **Anteprima in tempo reale** del contenuto generato mentre compili i campi.
* Creazione automatica della cartella `.well-known/agent-skills/`.
* File **accessibili pubblicamente via URL**, sia come file fisici sia (in fallback) serviti dinamicamente tramite le regole di rewrite di WordPress.
* Validazione dei dati inseriti.
* Un solo pulsante per **salvare e rigenerare** tutti i file.

== Installation ==

1. Copia la cartella `ai-discovery-manager` in `wp-content/plugins/`.
2. Attiva il plugin dal menu **Plugin** di WordPress.
3. Vai su **AI Discovery** nel menu di amministrazione.
4. Compila i campi nei tre tab e clicca **Salva e rigenera i file**.
5. Verifica gli URL pubblici mostrati nella pagina.

Se gli URL restituiscono un errore 404, vai in **Impostazioni → Permalink** e clicca **Salva le modifiche** per aggiornare le regole di rewrite.

== Frequently Asked Questions ==

= Dove vengono salvati i file? =

`llms.txt` e `skills.md` vengono salvati nella root del sito. `index.json` in `/.well-known/agent-skills/`. Se le cartelle non sono scrivibili, i file vengono comunque serviti dinamicamente agli stessi URL.

= I file sono accessibili senza fare login? =

Sì, sono pubblici e accessibili da qualsiasi client (browser, LLM, agenti AI).

== Credits ==

Sviluppato e mantenuto da **Dix Lab**.

== Changelog ==

= 1.0.0 =
* Prima versione: generazione di llms.txt, skills.md e index.json, interfaccia a tab, anteprima in tempo reale, serving via rewrite.
