<div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.9rem;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">Commercial picture</div>
            <div id="deskRangeLabel" style="font-size: 1.05rem; font-weight: 700; color: var(--primary-navy); margin-top: 0.15rem;">From the beginning</div>
        </div>
        <div role="group" aria-label="Time range" style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
            <button type="button" data-desk-range="all" aria-pressed="true" style="background: var(--primary-navy); color: white; border: 1px solid var(--primary-navy); border-radius: var(--radius-md); padding: 0.4rem 0.7rem; font-size: 0.8rem; font-weight: 600; cursor: pointer;">From the beginning</button>
            <button type="button" data-desk-range="ytd" aria-pressed="false" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.4rem 0.7rem; font-size: 0.8rem; font-weight: 600; cursor: pointer;">Year to date</button>
            <button type="button" data-desk-range="6m" aria-pressed="false" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.4rem 0.7rem; font-size: 0.8rem; font-weight: 600; cursor: pointer;">6 months</button>
            <button type="button" data-desk-range="1m" aria-pressed="false" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.4rem 0.7rem; font-size: 0.8rem; font-weight: 600; cursor: pointer;">1 month</button>
        </div>
    </div>

    <div role="group" aria-label="Chart series" style="display: flex; flex-wrap: wrap; gap: 0.85rem; margin-bottom: 0.85rem;">
        <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; font-weight: 600; color: var(--primary-navy); cursor: pointer;">
            <input type="checkbox" data-desk-series="invoiced" checked>
            <span style="width: 0.7rem; height: 0.7rem; border-radius: 2px; background: #0369a1; display: inline-block;"></span>
            Invoiced + RFPs
        </label>
        <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; font-weight: 600; color: var(--primary-navy); cursor: pointer;">
            <input type="checkbox" data-desk-series="paid" checked>
            <span style="width: 0.7rem; height: 0.7rem; border-radius: 2px; background: #059669; display: inline-block;"></span>
            Paid
        </label>
        <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; font-weight: 600; color: var(--primary-navy); cursor: pointer;">
            <input type="checkbox" data-desk-series="costs" checked>
            <span style="width: 0.7rem; height: 0.7rem; border-radius: 2px; background: #b45309; display: inline-block;"></span>
            Costs
        </label>
    </div>

    <div id="deskChartScroll" style="overflow-x: auto; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #f8fafc;">
        <svg id="deskChart" role="img" aria-label="Invoiced, paid, and costs by month" style="display: block; height: 240px;"></svg>
    </div>
    <p id="deskChartEmpty" hidden style="margin: 0.75rem 0 0; font-size: 0.85rem; color: var(--text-muted);"></p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin-top: 1rem;">
        <div data-desk-total="invoiced">
            <div style="font-size: 0.75rem; color: var(--text-muted);">Invoiced + RFPs</div>
            <div id="deskInvoiced" style="font-size: 1.35rem; font-weight: 700; color: #0369a1;">€0.00</div>
        </div>
        <div data-desk-total="paid">
            <div style="font-size: 0.75rem; color: var(--text-muted);">Paid</div>
            <div id="deskPaid" style="font-size: 1.35rem; font-weight: 700; color: #059669;">€0.00</div>
        </div>
        <div data-desk-total="costs">
            <div style="font-size: 0.75rem; color: var(--text-muted);">Costs ex-VAT</div>
            <div id="deskCosts" style="font-size: 1.35rem; font-weight: 700; color: #b45309;">€0.00</div>
        </div>
    </div>
    <p id="deskOpenRfpNote" style="margin: 0.75rem 0 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;"></p>
    <p style="margin: 0.35rem 0 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.45;">
        Paid is cash received, and skips transfers between your own accounts. Costs match the glance: expenses ex-VAT, with computer equipment left in wear and tear. Invoiced here adds open proformas to tax invoices and credit notes. A converted proforma is counted once, on the tax invoice. The fiscal glance above still treats open proformas as €0.
    </p>
    <noscript>
        <p style="margin: 0.75rem 0 0; font-size: 0.85rem; color: var(--primary-navy);">
            From the beginning: invoiced €{{ number_format($deskReport['ranges']['all']['totals']['invoiced'], 2) }},
            paid €{{ number_format($deskReport['ranges']['all']['totals']['paid'], 2) }},
            costs €{{ number_format($deskReport['ranges']['all']['totals']['costs'], 2) }}.
        </p>
    </noscript>
</div>

<div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.9rem;">Clients</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
        <div>
            <div style="font-weight: 700; color: var(--primary-navy);">Owes most</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.35rem;">Open now, every document</div>
            <div id="deskOwes"></div>
        </div>
        <div>
            <div style="font-weight: 700; color: var(--primary-navy);">Most revenue</div>
            <div id="deskRevenuePeriod" style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.35rem;">From the beginning</div>
            <div id="deskRevenue"></div>
        </div>
        <div>
            <div style="font-weight: 700; color: var(--primary-navy);">Pays quickest</div>
            <div id="deskSpeedPeriod" style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.35rem;">From the beginning</div>
            <div id="deskSpeed"></div>
        </div>
    </div>
</div>

<script type="application/json" id="deskReportData">@json($deskReport)</script>
<script>
(function () {
    var dataEl = document.getElementById('deskReportData');
    if (!dataEl) return;
    var data;
    try {
        data = JSON.parse(dataEl.textContent || '');
    } catch (e) {
        return;
    }
    if (!data || !data.ranges) return;

    var rangeKey = 'all';
    var seriesOn = { invoiced: true, paid: true, costs: true };
    var colors = { invoiced: '#0369a1', paid: '#059669', costs: '#b45309' };
    var names = { invoiced: 'Invoiced + RFPs', paid: 'Paid', costs: 'Costs ex-VAT' };

    function money(n) {
        var v = Math.round((Number(n) || 0) * 100) / 100;
        var neg = v < 0;
        var parts = Math.abs(v).toFixed(2).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return '€' + (neg ? '-' : '') + parts[0] + '.' + parts[1];
    }

    function compact(n) {
        var v = Number(n) || 0;
        var sign = v < 0 ? '-' : '';
        var a = Math.abs(v);
        if (a >= 1000) {
            return sign + '€' + (a / 1000).toFixed(a >= 10000 ? 0 : 1) + 'k';
        }
        return sign + '€' + Math.round(a);
    }

    function daysLabel(days) {
        days = Number(days) || 0;
        if (days === 0) return 'Same day';
        if (days === 1) return '1 day';
        return days + ' days';
    }

    function receiptLabel(n) {
        n = Number(n) || 0;
        return n === 1 ? '1 receipt' : n + ' receipts';
    }

    function clientLink(row) {
        var id = Number(row.id);
        var node = (id > 0) ? document.createElement('a') : document.createElement('span');
        if (id > 0) {
            node.href = '/company/clients/' + id;
            node.style.color = 'var(--primary-cerulean)';
            node.style.borderBottom = '1px dotted var(--primary-cerulean)';
            node.style.textDecoration = 'none';
        } else {
            node.style.color = 'var(--primary-navy)';
        }
        node.style.fontWeight = '600';
        node.textContent = row.name || 'No client';
        return node;
    }

    function emptyLine(text) {
        var p = document.createElement('p');
        p.style.margin = '0.35rem 0 0';
        p.style.fontSize = '0.85rem';
        p.style.color = 'var(--text-muted)';
        p.textContent = text;
        return p;
    }

    function fillAmounts(el, rows, emptyText) {
        el.replaceChildren();
        if (!rows || !rows.length) {
            el.appendChild(emptyLine(emptyText));
            return;
        }
        rows.forEach(function (row) {
            var item = document.createElement('div');
            item.style.cssText = 'display:flex; justify-content:space-between; align-items:baseline; gap:0.75rem; padding:0.45rem 0; border-bottom:1px solid var(--border-light);';
            item.appendChild(clientLink(row));
            var amt = document.createElement('div');
            amt.style.cssText = 'font-weight:700; font-variant-numeric:tabular-nums; color:var(--primary-navy); white-space:nowrap;';
            amt.textContent = money(row.amount);
            item.appendChild(amt);
            el.appendChild(item);
        });
    }

    function speedRow(row) {
        var item = document.createElement('div');
        item.style.cssText = 'padding:0.45rem 0; border-bottom:1px solid var(--border-light);';
        var top = document.createElement('div');
        top.style.cssText = 'display:flex; justify-content:space-between; align-items:baseline; gap:0.75rem;';
        top.appendChild(clientLink(row));
        var days = document.createElement('div');
        days.style.cssText = 'font-weight:700; color:var(--primary-navy); white-space:nowrap;';
        days.textContent = daysLabel(row.days);
        top.appendChild(days);
        item.appendChild(top);
        var meta = document.createElement('div');
        meta.style.cssText = 'font-size:0.75rem; color:var(--text-muted); margin-top:0.15rem;';
        meta.textContent = money(row.paid) + ' · ' + receiptLabel(row.payments);
        item.appendChild(meta);
        return item;
    }

    function fillSpeed(el, fastest, slowest) {
        el.replaceChildren();
        if (!fastest || !fastest.length) {
            el.appendChild(emptyLine('No payments in this period.'));
            return;
        }
        fastest.forEach(function (row) { el.appendChild(speedRow(row)); });
        var seen = {};
        fastest.forEach(function (row) { seen[String(row.id) + '|' + row.name] = true; });
        var extra = (slowest || []).filter(function (row) {
            return !seen[String(row.id) + '|' + row.name];
        });
        if (!extra.length) return;
        var head = document.createElement('div');
        head.style.cssText = 'font-size:0.7rem; font-weight:700; letter-spacing:0.04em; text-transform:uppercase; color:var(--text-muted); margin:0.8rem 0 0.15rem;';
        head.textContent = 'Takes longest';
        el.appendChild(head);
        extra.forEach(function (row) { el.appendChild(speedRow(row)); });
    }

    function svgEl(name, attrs) {
        var node = document.createElementNS('http://www.w3.org/2000/svg', name);
        Object.keys(attrs).forEach(function (key) { node.setAttribute(key, attrs[key]); });
        return node;
    }

    function draw(months) {
        var svg = document.getElementById('deskChart');
        var scroll = document.getElementById('deskChartScroll');
        var empty = document.getElementById('deskChartEmpty');
        svg.replaceChildren();
        var keys = ['invoiced', 'paid', 'costs'].filter(function (key) { return seriesOn[key]; });
        if (!months.length || !keys.length) {
            scroll.hidden = true;
            empty.hidden = false;
            empty.textContent = keys.length ? 'Nothing in this period yet.' : 'Turn a series back on to draw the chart.';
            return;
        }
        var any = false;
        var min = 0;
        var max = 0;
        months.forEach(function (month) {
            keys.forEach(function (key) {
                var value = Number(month[key]) || 0;
                if (value !== 0) any = true;
                if (value > max) max = value;
                if (value < min) min = value;
            });
        });
        if (!any) {
            scroll.hidden = true;
            empty.hidden = false;
            empty.textContent = 'Nothing in this period yet.';
            return;
        }
        empty.hidden = true;
        scroll.hidden = false;

        var padL = 56, padR = 12, padT = 16, padB = 42, height = 240;
        var groupW = Math.max(42, keys.length * 14 + 16);
        var width = padL + padR + months.length * groupW;
        if (width < 680) {
            groupW = Math.floor((680 - padL - padR) / months.length);
            width = padL + padR + months.length * groupW;
        }
        var plotH = height - padT - padB;
        var span = max - min;
        function y(value) {
            if (span === 0) return padT + plotH;
            return padT + ((max - value) / span) * plotH;
        }

        svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
        svg.setAttribute('width', String(width));
        svg.setAttribute('height', String(height));

        var ticks = min < 0 ? [min, 0, max] : [0, max / 2, max];
        ticks.forEach(function (tick) {
            var yy = y(tick);
            svg.appendChild(svgEl('line', { x1: padL, x2: width - padR, y1: yy, y2: yy, stroke: '#e2e8f0', 'stroke-width': '1' }));
            var label = svgEl('text', { x: padL - 8, y: yy + 4, 'text-anchor': 'end', fill: '#64748b', 'font-size': '11' });
            label.textContent = compact(tick);
            svg.appendChild(label);
        });

        var barW = 10;
        var inner = keys.length * barW + (keys.length - 1) * 3;
        var labelEvery = months.length > 16 ? Math.ceil(months.length / 12) : 1;
        months.forEach(function (month, index) {
            var gx = padL + index * groupW + (groupW - inner) / 2;
            keys.forEach(function (key, keyIndex) {
                var value = Number(month[key]) || 0;
                if (value === 0) return;
                var y0 = y(0);
                var y1 = y(value);
                var rect = svgEl('rect', {
                    x: gx + keyIndex * (barW + 3),
                    y: Math.min(y0, y1),
                    width: barW,
                    height: Math.max(2, Math.abs(y1 - y0)),
                    rx: '2',
                    fill: colors[key]
                });
                var title = svgEl('title', {});
                title.textContent = month.label + ' · ' + names[key] + ' ' + money(value);
                rect.appendChild(title);
                svg.appendChild(rect);
            });
            if (index % labelEvery === 0) {
                var text = svgEl('text', {
                    x: padL + index * groupW + groupW / 2,
                    y: height - 16,
                    'text-anchor': 'middle',
                    fill: '#64748b',
                    'font-size': '11'
                });
                text.textContent = month.short || month.label;
                svg.appendChild(text);
            }
        });
    }

    function paintRange() {
        document.querySelectorAll('[data-desk-range]').forEach(function (btn) {
            var on = btn.getAttribute('data-desk-range') === rangeKey;
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.style.background = on ? 'var(--primary-navy)' : 'white';
            btn.style.color = on ? 'white' : 'var(--primary-navy)';
            btn.style.borderColor = on ? 'var(--primary-navy)' : 'var(--border-light)';
        });
    }

    function render() {
        var range = data.ranges[rangeKey] || data.ranges.all;
        paintRange();
        document.getElementById('deskRangeLabel').textContent = range.label;
        document.getElementById('deskRevenuePeriod').textContent = range.label + ' · RFP + invoiced';
        document.getElementById('deskSpeedPeriod').textContent = range.label + ' · days from the ask to the receipt';
        document.getElementById('deskInvoiced').textContent = money(range.totals.invoiced);
        document.getElementById('deskPaid').textContent = money(range.totals.paid);
        document.getElementById('deskCosts').textContent = money(range.totals.costs);
        document.querySelectorAll('[data-desk-total]').forEach(function (node) {
            node.hidden = !seriesOn[node.getAttribute('data-desk-total')];
        });
        var open = Number(range.totals.open_rfps) || 0;
        var note = document.getElementById('deskOpenRfpNote');
        if (open > 0.009) {
            note.textContent = 'Includes open proformas ' + money(open) + '. Open proformas hold €0 fiscal weight until they become tax invoices.';
        } else {
            note.textContent = 'Open proformas in this period: €0.00.';
        }
        var chart = document.getElementById('deskChart');
        chart.setAttribute('aria-label', range.label + '. Invoiced ' + money(range.totals.invoiced) + ', paid ' + money(range.totals.paid) + ', costs ' + money(range.totals.costs) + '.');
        draw(range.months || []);
        fillAmounts(document.getElementById('deskOwes'), data.owes || [], 'Nobody has an open balance.');
        fillAmounts(document.getElementById('deskRevenue'), range.revenue || [], 'No invoices or open proformas in this period.');
        fillSpeed(document.getElementById('deskSpeed'), range.fastest || [], range.slowest || []);
    }

    document.querySelectorAll('[data-desk-range]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            rangeKey = btn.getAttribute('data-desk-range') || 'all';
            render();
        });
    });
    document.querySelectorAll('[data-desk-series]').forEach(function (box) {
        box.addEventListener('change', function () {
            seriesOn[box.getAttribute('data-desk-series')] = box.checked;
            render();
        });
    });

    render();
})();
</script>
