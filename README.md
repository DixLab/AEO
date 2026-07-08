# AI Discovery Manager

**AI Discovery Manager** è un plugin per WordPress sviluppato da **Dix Lab** che rende il tuo sito facilmente scopribile e comprensibile da LLM e agenti AI, generando e gestendo automaticamente tre file standard:

| File | Posizione | Descrizione |
|------|-----------|-------------|
| `llms.txt` | root del sito | Standard [llmstxt.org](https://llmstxt.org): informazioni generali sull'organizzazione, pagine principali, contatti. |
| `skills.md` | root del sito | File Markdown che descrive le capacità operative del sito agli agenti AI. |
| `index.json` | `/.well-known/agent-skills/` | Manifesto machine-readable che unifica e referenzia gli altri due file. |

## Caratteristiche principali

- 🇮🇹 Interfaccia di amministrazione **in italiano**, con un tab dedicato per ciascun file.
- 👀 **Anteprima in tempo reale** del contenuto generato mentre compili i campi.
- 📁 Creazione automatica della cartella `.well-known/agent-skills/` (per `index.json`).
- 🌐 File **accessibili pubblicamente via URL**, sia come file fisici sia — in fallback — serviti dinamicamente tramite le regole di rewrite di WordPress se la root non è scrivibile.
- ✅ Validazione dei dati inseriti nei form.
- 🔄 Un solo pulsante per **salvare e rigenerare** tutti i file in un colpo solo.

## Requisiti

- WordPress 5.5 o superiore
- PHP 7.2 o superiore

## Installazione

1. Scarica o clona questa repository.
2. Copia la cartella `ai-discovery-manager` in `wp-content/plugins/`.
3. Attiva il plugin dal menu **Plugin** di WordPress.
4. Vai su **AI Discovery** nel menu di amministrazione.
5. Compila i campi nei tre tab (`llms.txt`, `skills.md`, `index.json`) e clicca **Salva e rigenera i file**.
6. Verifica gli URL pubblici mostrati nella pagina di amministrazione.

> ⚠️ Se gli URL restituiscono un errore 404, vai in **Impostazioni → Permalink** e clicca **Salva le modifiche** per aggiornare le regole di rewrite di WordPress.

## Dove vengono salvati i file

- `llms.txt` e `skills.md` vengono salvati nella **root del sito**.
- `index.json` viene salvato in `/.well-known/agent-skills/`.

Se le cartelle non sono scrivibili, i file vengono comunque serviti dinamicamente agli stessi URL pubblici, senza bisogno di scrivere fisicamente su disco.

## FAQ

**I file sono accessibili senza fare login?**
Sì, sono pubblici e accessibili da qualsiasi client: browser, LLM, agenti AI.

**Cosa succede se disinstallo il plugin?**
Le opzioni salvate e i file fisici generati (`llms.txt`, `skills.md`, `index.json`) vengono rimossi automaticamente.

## Changelog

### 1.0.0
Prima versione: generazione di `llms.txt`, `skills.md` e `index.json`, interfaccia a tab, anteprima in tempo reale, serving via rewrite.

## Licenza

Distribuito sotto licenza [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).

## Autore

Sviluppato e mantenuto da **Dix Lab**.
