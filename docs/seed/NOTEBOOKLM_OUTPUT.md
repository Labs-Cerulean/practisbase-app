# NotebookLM → PractisBase seed output

Dev-side only. NotebookLM (or any offline LLM batch) reads your legacy BOQs / specs / cert blanks / reports and emits **JSON**. Curated files are committed under `database/seed/` and loaded into PostgreSQL before soft launch. No end-user AI.

## Scrub first

Before uploading sources to NotebookLM:

- Remove or replace client names, personal emails/phones, exact site addresses
- Strip job/PA numbers if you do not want them in a cloud notebook
- Rates: either omit, or replace with typical band notes — never leave identifiable tender pricing you would not share

Batch by **trade** (one notebook per trade is easiest).

---

## Prompt to paste into NotebookLM

```text
You are extracting a clean master library for a construction software seed database.
Return ONLY valid JSON matching the schema I specify. No markdown, no commentary.

Rules:
- Anonymise: no client names, addresses, or job references in any string.
- Normalise units to: Nr, m, m2, m3, kg, Sum, Item, ls, set (pick the closest).
- Invent stable codes: item_code like EL-LV-0010, block_code like SPEC-EL-LV-DB-01.
- Every BOQ item MUST include spec_block_code. Every specification_blocks entry MUST list related_item_codes.
- Merge duplicates: same work description → one item. Prefer clear technical wording.
- typical_rate_eur may be null. Do not copy sensitive tender rates.
- keywords: 3–8 short search terms per item.
- Cover the trade thoroughly from the sources, but quality over quantity.

Trade for this notebook: {{TRADE_NAME}}
Package: {{engineering|architecture}}

Emit exactly this shape:
{
  "schema_version": 1,
  "trade": "{{slug}}",
  "package": "{{engineering|architecture}}",
  "source_batch": "{{your-label}}",
  "categories": [
    {
      "code": "string",
      "name": "string",
      "items": [
        {
          "item_code": "string",
          "description": "string",
          "unit": "string",
          "typical_rate_eur": null,
          "rate_notes": null,
          "keywords": ["string"],
          "spec_block_code": "string"
        }
      ]
    }
  ],
  "specification_blocks": [
    {
      "block_code": "string",
      "title": "string",
      "body": "string",
      "related_item_codes": ["string"]
    }
  ]
}
```

Save the model output as `database/seed/boq/{{trade}}.json` after you validate it (JSON parse + spot-check Twin Block links).

### Follow-up prompts (same notebook)

1. “List item_codes that have no matching specification_blocks.block_code — then fix the JSON.”
2. “List block_codes with empty related_item_codes — then fix.”
3. “Deduplicate items whose descriptions differ only by punctuation; keep one item_code.”

---

## Certificate templates (separate small notebook or direct)

Prefer **hand-building** 5–15 templates from your best blanks. If using NotebookLM:

```text
From these certificate examples, output ONLY JSON:
{
  "schema_version": 1,
  "templates": [
    {
      "kind": "equipment|installation|commissioning|inspection",
      "title_template": "string with {{placeholders}}",
      "fields": [
        {"key": "snake_case", "label": "string", "type": "text|textarea|date|select|number", "required": true, "options": []}
      ],
      "default_notes": null,
      "example_filled": {}
    }
  ]
}
Anonymise examples. Prefer recurring fields across certificates over one-off prose.
```

Save as `database/seed/templates/certificates.json`.

---

## Report templates (fire / noise / ventilation / lighting)

```text
From these engineering reports, invent a reusable FORM schema (not a full report dump).
Output ONLY JSON:
{
  "schema_version": 1,
  "templates": [
    {
      "report_type": "fire|noise|ventilation|lighting",
      "title": "string",
      "sections": [
        {
          "key": "snake_case",
          "label": "string",
          "fields": [
            {"key": "snake_case", "label": "string", "type": "text|textarea|date|select|number", "required": true, "options": []}
          ]
        }
      ],
      "example_narrative_snippets": ["short anonymised help phrases only"]
    }
  ]
}
Do not paste entire confidential report bodies. Extract section/field structure only.
```

Save as `database/seed/templates/reports.json`.

---

## What to send into Cursor / this repo

| Bring here | Keep out of git |
|---|---|
| Validated `*.json` seed files | Original client PDFs/Word with PII |
| Short notes on trade coverage gaps | NotebookLM chat transcripts |
| 3–5 anonymised PDF exemplars **only if** needed for UI PDF styling | Full historical tender archive |

Once JSON is in `database/seed/`, implementation work is: SQL tables, loader, Twin Block builder UI, Spec export — no need to re-read the tonnes of source docs in-agent.
