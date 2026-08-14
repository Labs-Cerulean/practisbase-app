/**
 * Client-side gynae/obs Word proforma parser + small-batch vault import.
 * Plaintext never leaves the browser — only ciphertext is POSTed.
 */
(function (global) {
    'use strict';

    var MAX_BATCH = 5;
    var FIELD_ALIASES = [
        { key: 'name', patterns: [/^name\s*:/i] },
        { key: 'id_card', patterns: [/^id(?:\s*card)?\s*(?:no\.?|number)?\s*:/i, /^id\s*:/i] },
        { key: 'tel', patterns: [/^tel(?:ephone)?(?:\s*no\.?)?\s*:/i, /^mobile\s*:/i, /^phone\s*:/i] },
        { key: 'address', patterns: [/^address\s*:/i] },
        { key: 'age', patterns: [/^age\s*:/i] },
        { key: 'consult_date', patterns: [/^date\s+of\s+consult\s*:/i, /^consult(?:ation)?\s*date\s*:/i] },
        { key: 'lmp', patterns: [/^lmp\s*:/i] },
        { key: 'presenting_complaint', patterns: [/^presenting\s+complaint\s*:/i, /^c\/?o\s*:/i, /^complaint\s*:/i] },
        { key: 'gynae_hx', patterns: [/^gynae(?:cology)?\s*hx\s*:/i, /^gyna?e(?:cological)?\s+history\s*:/i] },
        { key: 'obs_hx', patterns: [/^obs(?:tetric)?\s*hx\s*:/i, /^obstetric\s+history\s*:/i] },
        { key: 'pmhx', patterns: [/^pmhx\s*:/i, /^past\s+medical\s+history\s*:/i] },
        { key: 'pshx', patterns: [/^pshx\s*:/i, /^past\s+surgical\s+history\s*:/i] },
        { key: 'dhx', patterns: [/^dhx\s*:/i, /^drug\s+history\s*:/i, /^medications?\s*:/i] },
        { key: 'shx', patterns: [/^shx\s*:/i, /^social\s+history\s*:/i] },
        { key: 'exam', patterns: [/^exam(?:ination)?\s*:/i, /^o\/e\s*:/i] },
        { key: 'us', patterns: [/^us\s*:/i, /^ultrasound\s*:/i, /^u\/s\s*:/i] },
        { key: 'plan', patterns: [/^plan\s*:/i, /^impression\s*\/?\s*plan\s*:/i] }
    ];

    var CLINICAL_KEYS = [
        'lmp', 'presenting_complaint', 'gynae_hx', 'obs_hx',
        'pmhx', 'pshx', 'dhx', 'shx', 'exam', 'us', 'plan'
    ];

    function trim(value) {
        return String(value || '').replace(/\u00a0/g, ' ').trim();
    }

    function matchFieldLabel(line) {
        var text = trim(line);
        for (var i = 0; i < FIELD_ALIASES.length; i++) {
            var alias = FIELD_ALIASES[i];
            for (var j = 0; j < alias.patterns.length; j++) {
                var match = text.match(alias.patterns[j]);
                if (match) {
                    return {
                        key: alias.key,
                        rest: trim(text.slice(match[0].length))
                    };
                }
            }
        }
        return null;
    }

    function splitProformas(rawText) {
        var text = String(rawText || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
        var parts = text.split(/(?=^\s*Name\s*:)/im).map(trim).filter(Boolean);
        if (parts.length === 0 && trim(text)) {
            return [trim(text)];
        }
        return parts;
    }

    function parseMalteseDate(value) {
        var raw = trim(value);
        if (!raw) {
            return null;
        }
        var iso = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (iso) {
            return iso[1] + '-' + iso[2] + '-' + iso[3];
        }
        var m = raw.match(/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{2,4})$/);
        if (!m) {
            return null;
        }
        var day = parseInt(m[1], 10);
        var month = parseInt(m[2], 10);
        var year = parseInt(m[3], 10);
        if (year < 100) {
            year += year >= 70 ? 1900 : 2000;
        }
        if (month < 1 || month > 12 || day < 1 || day > 31) {
            return null;
        }
        return year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
    }

    function isFollowUpLine(line) {
        return /^\d{1,2}[\/.\-]\d{1,2}[\/.\-]\d{2,4}\s*[–—\-:]/.test(trim(line));
    }

    function parseProformaBlock(block) {
        var lines = String(block || '').split('\n');
        var data = {
            name: '',
            id_card: '',
            tel: '',
            address: '',
            age: '',
            consult_date: '',
            fields: {},
            follow_ups: [],
            extra: []
        };
        CLINICAL_KEYS.forEach(function (key) {
            data.fields[key] = '';
        });

        var current = null;
        var afterPlan = false;

        lines.forEach(function (line) {
            var labelled = matchFieldLabel(line);
            if (labelled) {
                current = labelled.key;
                if (current === 'plan') {
                    afterPlan = true;
                }
                if (CLINICAL_KEYS.indexOf(current) !== -1) {
                    data.fields[current] = labelled.rest;
                } else if (current === 'consult_date') {
                    data.consult_date = labelled.rest;
                } else if (Object.prototype.hasOwnProperty.call(data, current)) {
                    data[current] = labelled.rest;
                }
                return;
            }

            var text = trim(line);
            if (!text) {
                return;
            }

            if (afterPlan && isFollowUpLine(text)) {
                data.follow_ups.push(text);
                current = null;
                return;
            }

            if (current && CLINICAL_KEYS.indexOf(current) !== -1) {
                data.fields[current] = data.fields[current]
                    ? data.fields[current] + '\n' + text
                    : text;
                return;
            }

            if (current && Object.prototype.hasOwnProperty.call(data, current) && current !== 'fields') {
                data[current] = data[current] ? data[current] + '\n' + text : text;
                return;
            }

            data.extra.push(text);
        });

        Object.keys(data.fields).forEach(function (key) {
            data.fields[key] = trim(data.fields[key]);
        });

        return data;
    }

    function composeBody(fields, followUps, extra) {
        var labels = {
            lmp: 'LMP',
            presenting_complaint: 'Presenting complaint',
            gynae_hx: 'Gynae Hx',
            obs_hx: 'Obs Hx',
            pmhx: 'PMHx',
            pshx: 'PSHx',
            dhx: 'DHx',
            shx: 'SHx',
            exam: 'Exam',
            us: 'US',
            plan: 'Plan'
        };
        var chunks = [];
        CLINICAL_KEYS.forEach(function (key) {
            if (fields[key]) {
                chunks.push(labels[key] + ':\n' + fields[key]);
            }
        });
        if (followUps && followUps.length) {
            chunks.push('Follow-up notes:\n' + followUps.join('\n'));
        }
        if (extra && extra.length) {
            chunks.push(extra.join('\n'));
        }
        return chunks.join('\n\n');
    }

    function todayIso() {
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function clampDate(iso) {
        if (!iso) {
            return todayIso();
        }
        return iso > todayIso() ? todayIso() : iso;
    }

    function buildRecords(parsedList) {
        return parsedList.map(function (item, index) {
            var consultDate = clampDate(parseMalteseDate(item.consult_date));
            var title = 'Gynae/Obs consult ' + consultDate;
            var body = composeBody(item.fields, item.follow_ups, item.extra);
            return {
                index: index,
                selected: index < MAX_BATCH,
                patient: {
                    display_name: item.name || ('Imported patient ' + (index + 1)),
                    id_card: item.id_card || '',
                    tel: item.tel || '',
                    address: item.address || '',
                    age: item.age || '',
                    date_of_birth: null,
                    notes: ''
                },
                entry: {
                    entry_type: 'journal',
                    entry_date: consultDate,
                    title: title,
                    body: body,
                    template: 'gynae_obs',
                    fields: item.fields
                },
                follow_ups: item.follow_ups || [],
                raw_preview: body.slice(0, 280)
            };
        });
    }

    async function ensureDek() {
        var cryptoApi = global.PractisBaseVaultCrypto;
        if (!cryptoApi) {
            throw new Error('Vault crypto helper missing.');
        }
        var key = cryptoApi.loadDek();
        if (key) {
            return key;
        }
        var response = await fetch('/pro/medical/vault/client-dek', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });
        if (!response.ok) {
            throw new Error('Vault key not available in this browser session. Unlock the vault again, then retry import.');
        }
        var data = await response.json();
        if (!data.dek_b64) {
            throw new Error('Vault key missing.');
        }
        var bytes = cryptoApi.base64ToBytes(data.dek_b64);
        cryptoApi.storeDek(bytes);
        return bytes;
    }

    async function encryptRecords(records) {
        var cryptoApi = global.PractisBaseVaultCrypto;
        if (!cryptoApi) {
            throw new Error('Vault crypto helper missing.');
        }
        var key = await ensureDek();

        var selected = records.filter(function (r) { return r.selected; }).slice(0, MAX_BATCH);
        if (!selected.length) {
            throw new Error('Select at least one parsed proforma to import.');
        }

        return selected.map(function (record) {
            var patientPayload = {
                display_name: record.patient.display_name,
                date_of_birth: record.patient.date_of_birth,
                age: record.patient.age || null,
                id_card: record.patient.id_card || null,
                tel: record.patient.tel || null,
                address: record.patient.address || null,
                notes: record.patient.notes || null
            };
            var entryPayload = {
                title: record.entry.title,
                body: record.entry.body,
                template: 'gynae_obs',
                fields: record.entry.fields,
                extra: ''
            };
            var patientEnc = cryptoApi.encryptPayload(patientPayload, key);
            var entryEnc = cryptoApi.encryptPayload(entryPayload, key);
            return {
                patient: {
                    payload_ciphertext: patientEnc.ciphertext,
                    payload_nonce: patientEnc.nonce
                },
                entries: [
                    {
                        entry_type: record.entry.entry_type,
                        entry_date: record.entry.entry_date,
                        payload_ciphertext: entryEnc.ciphertext,
                        payload_nonce: entryEnc.nonce
                    }
                ]
            };
        });
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    async function commitBatch(encryptedRecords) {
        var response = await fetch('/pro/medical/import/commit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ records: encryptedRecords })
        });
        var data = await response.json().catch(function () { return {}; });
        if (!response.ok) {
            throw new Error(data.message || data.error || 'Import failed.');
        }
        return data;
    }

    function renderPreview(container, records, state) {
        if (!records.length) {
            container.innerHTML = '<p style="color: var(--text-muted);">No proformas detected. Expect blocks starting with <code>Name:</code>.</p>';
            return;
        }

        var html = '<p style="color: var(--text-muted); font-size: 0.9rem; margin: 0 0 1rem;">Detected <strong>' + records.length + '</strong> proforma(s). Small-batch import is capped at <strong>' + MAX_BATCH + '</strong> selected patients per run.</p>';
        html += '<div style="display: grid; gap: 0.75rem;">';
        records.forEach(function (record, idx) {
            var disabled = !record.selected && state.selectedCount() >= MAX_BATCH;
            html += '<label style="display: block; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; box-shadow: var(--shadow-sm); cursor: pointer;">';
            html += '<div style="display: flex; gap: 0.75rem; align-items: flex-start;">';
            html += '<input type="checkbox" data-import-index="' + idx + '"' + (record.selected ? ' checked' : '') + (disabled ? ' disabled' : '') + ' style="margin-top: 0.25rem;">';
            html += '<div style="flex: 1; min-width: 0;">';
            html += '<div style="font-weight: 700; color: var(--primary-navy);">' + escapeHtml(record.patient.display_name) + '</div>';
            html += '<div style="font-size: 0.8rem; color: var(--text-muted); margin: 0.2rem 0 0.55rem;">Consult ' + escapeHtml(record.entry.entry_date);
            if (record.patient.id_card) {
                html += ' · ID ' + escapeHtml(record.patient.id_card);
            }
            if (record.patient.tel) {
                html += ' · ' + escapeHtml(record.patient.tel);
            }
            html += '</div>';
            html += '<div style="font-size: 0.85rem; color: var(--text-main); white-space: pre-wrap; max-height: 7rem; overflow: auto; background: #f8fafc; padding: 0.65rem; border-radius: var(--radius-md);">' + escapeHtml(record.raw_preview || '') + (record.entry.body.length > 280 ? '…' : '') + '</div>';
            html += '</div></div></label>';
        });
        html += '</div>';
        container.innerHTML = html;

        container.querySelectorAll('[data-import-index]').forEach(function (box) {
            box.addEventListener('change', function () {
                var index = parseInt(box.getAttribute('data-import-index'), 10);
                if (box.checked && state.selectedCount() >= MAX_BATCH && !records[index].selected) {
                    box.checked = false;
                    state.setStatus('Batch limit is ' + MAX_BATCH + ' patients. Deselect one first.', true);
                    return;
                }
                records[index].selected = box.checked;
                state.refreshCounts();
                renderPreview(container, records, state);
            });
        });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function boot(root) {
        if (!root) {
            return;
        }

        var fileInput = root.querySelector('[data-import-file]');
        var parseBtn = root.querySelector('[data-import-parse]');
        var commitBtn = root.querySelector('[data-import-commit]');
        var preview = root.querySelector('[data-import-preview]');
        var status = root.querySelector('[data-import-status]');
        var countEl = root.querySelector('[data-import-selected-count]');
        var records = [];

        var state = {
            selectedCount: function () {
                return records.filter(function (r) { return r.selected; }).length;
            },
            refreshCounts: function () {
                if (countEl) {
                    countEl.textContent = String(this.selectedCount());
                }
                if (commitBtn) {
                    commitBtn.disabled = this.selectedCount() === 0;
                }
            },
            setStatus: function (message, isError) {
                if (!status) {
                    return;
                }
                status.style.display = message ? 'block' : 'none';
                status.style.background = isError ? '#fef2f2' : '#ecfdf5';
                status.style.color = isError ? '#991b1b' : '#065f46';
                status.textContent = message || '';
            }
        };

        if (parseBtn) {
            parseBtn.addEventListener('click', function () {
                state.setStatus('');
                if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                    state.setStatus('Choose a .docx file first.', true);
                    return;
                }
                if (!global.mammoth) {
                    state.setStatus('Word parser failed to load.', true);
                    return;
                }
                var file = fileInput.files[0];
                parseBtn.disabled = true;
                parseBtn.textContent = 'Parsing on this device…';
                file.arrayBuffer()
                    .then(function (buffer) {
                        return global.mammoth.extractRawText({ arrayBuffer: buffer });
                    })
                    .then(function (result) {
                        var blocks = splitProformas(result.value || '');
                        records = buildRecords(blocks.map(parseProformaBlock));
                        renderPreview(preview, records, state);
                        state.refreshCounts();
                        state.setStatus('Parsed ' + records.length + ' proforma(s) locally. Review, then import the selected batch.');
                    })
                    .catch(function (err) {
                        state.setStatus(err.message || 'Could not parse that Word file.', true);
                    })
                    .finally(function () {
                        parseBtn.disabled = false;
                        parseBtn.textContent = 'Parse on this device';
                    });
            });
        }

        if (commitBtn) {
            commitBtn.addEventListener('click', function () {
                state.setStatus('');
                commitBtn.disabled = true;
                commitBtn.textContent = 'Encrypting & uploading ciphertext…';
                encryptRecords(records)
                    .then(function (encrypted) {
                        return commitBatch(encrypted);
                    })
                    .then(function (data) {
                        state.setStatus('Imported ' + (data.imported || 0) + ' patient(s) as ciphertext only. Reloading…');
                        setTimeout(function () {
                            window.location.href = '/pro/medical/patients';
                        }, 700);
                    })
                    .catch(function (err) {
                        state.setStatus(err.message || 'Import failed.', true);
                        commitBtn.disabled = false;
                        commitBtn.textContent = 'Encrypt & import selected';
                        state.refreshCounts();
                    });
            });
        }

        state.refreshCounts();
    }

    global.PractisBaseGynaeImport = {
        splitProformas: splitProformas,
        parseProformaBlock: parseProformaBlock,
        buildRecords: buildRecords,
        boot: boot,
        MAX_BATCH: MAX_BATCH
    };

    document.addEventListener('DOMContentLoaded', function () {
        boot(document.querySelector('[data-gynae-import]'));
    });
})(window);
