@extends('layouts.app')
@section('title', 'Blast WhatsApp Penagihan')

@push('styles')
<style>
.placeholder-chip {
    border: 1px dashed var(--nb-primary); color: var(--nb-primary); background: #fff;
    border-radius: 50rem; padding: .15rem .6rem; font-size: .72rem; font-weight: 600; cursor: pointer;
}
.placeholder-chip:hover { background: var(--nb-primary); color: #fff; }
.blast-row { border: 1px solid #e2e8f0; border-radius: .75rem; padding: .65rem .75rem; margin-bottom: .5rem; background: #fff; }
.blast-row.is-sent { border-color: #86efac; background: #f0fdf4; }
.blast-row.is-selected { border-color: var(--nb-primary); box-shadow: 0 0 0 2px rgba(99,102,241,.15); }
.blast-row .form-check-input { width: 1.05rem; height: 1.05rem; }
.blast-queue { position: sticky; bottom: calc(var(--nb-bottom-nav-h) + .5rem); z-index: 1020; }
@media (min-width: 992px) { .blast-queue { bottom: 1rem; } }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h5 class="mb-0"><i class="bi bi-whatsapp text-success"></i> Blast WhatsApp Penagihan</h5>
        <p class="text-muted small mb-0">Kirim pemberitahuan tagihan ke pelanggan satu per satu atau sekaligus.</p>
    </div>
    <a href="{{ route('blast.history') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-clock-history"></i> Riwayat Blast</a>
</div>

<div class="row g-2 mb-3">
    <div class="col-4"><div class="card stat-card h-100"><div class="card-body py-2">
        <div class="text-muted small">Pelanggan</div>
        <div class="stat-value">{{ $summary['customers'] }}</div>
    </div></div></div>
    <div class="col-4"><div class="card stat-card h-100"><div class="card-body py-2">
        <div class="text-muted small">Punya No. WA</div>
        <div class="stat-value text-success">{{ $summary['with_phone'] }}</div>
    </div></div></div>
    <div class="col-4"><div class="card stat-card h-100"><div class="card-body py-2">
        <div class="text-muted small">Total Tunggakan</div>
        <div class="stat-value text-danger fs-6">{{ format_rupiah($summary['total_arrears']) }}</div>
    </div></div></div>
</div>

<div class="card mb-3"><div class="card-body">
<form method="GET" action="{{ route('blast.index') }}">
<div class="row g-2 align-items-end">
    <div class="col-12 col-md-3">
        <label class="form-label small mb-1">Cari pelanggan</label>
        <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Nama / kode / telepon">
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Dusun</label>
        <select name="dusun" class="form-select">
            <option value="">Semua</option>
            @foreach($dusunList as $d)
            <option value="{{ $d }}" @selected($filters['dusun'] === $d)>{{ $d }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-3">
        <input type="hidden" name="only_arrears" value="0">
        <input type="hidden" name="not_contacted" value="0">
        <div class="form-check form-switch mt-3">
            <input class="form-check-input" type="checkbox" role="switch" id="only_arrears" name="only_arrears" value="1" @checked($filters['only_arrears'])>
            <label class="form-check-label small" for="only_arrears">Hanya yang menunggak</label>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="not_contacted" name="not_contacted" value="1" @checked($filters['not_contacted'])>
            <label class="form-check-label small" for="not_contacted">Belum di-blast 7 hari</label>
        </div>
    </div>
    <div class="col-12 col-md-2 d-grid">
        <button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Terapkan</button>
    </div>
</div>

<hr class="my-3">

<label class="form-label small fw-semibold" for="template">Template Pesan</label>
<textarea id="template" name="template" class="form-control" rows="7" placeholder="Tulis template pesan…">{{ $template }}</textarea>
<div class="d-flex flex-wrap gap-1 mt-2">
    @foreach($placeholders as $key => $label)
    <button type="button" class="placeholder-chip" data-key="{{ $key }}" title="{{ $label }}">{{ $key }}</button>
    @endforeach
</div>
<div class="d-flex gap-2 mt-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" id="reset-template"><i class="bi bi-arrow-counterclockwise"></i> Template Default</button>
    <button type="button" class="btn btn-sm btn-outline-primary" id="preview-template"><i class="bi bi-eye"></i> Pratinjau Pesan</button>
</div>
</form>
</div></div>

@if($rows->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
        Tidak ada pelanggan yang sesuai filter.
    </div></div>
@else
<div class="card mb-3"><div class="card-body py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="form-check mb-0">
        <input class="form-check-input" type="checkbox" id="select-all">
        <label class="form-check-label small" for="select-all">Pilih semua yang punya nomor WA (<span id="selectable-count">0</span>)</label>
    </div>
    <div class="small text-muted"><span id="selected-count">0</span> dipilih</div>
</div></div>

<div id="blast-list">
@foreach($rows as $row)
<div class="blast-row" data-row
     data-id="{{ $row['id'] }}"
     data-name="{{ $row['name'] }}"
     data-code="{{ $row['code'] }}"
     data-phone="{{ $row['wa_number'] ?? '' }}"
     data-package="{{ $row['package'] ?? '-' }}"
     data-periods="{{ $row['periods_label'] }}"
     data-due-date="{{ $row['due_date'] }}"
     data-dusun="{{ $row['dusun'] ?? '-' }}"
     data-alamat="{{ $row['alamat'] ?? '-' }}"
     data-collector="{{ auth()->user()->name }}"
     data-total="{{ $row['total_label'] }}">
    <div class="d-flex align-items-start gap-2">
        <input class="form-check-input mt-1" type="checkbox" data-check @disabled(! $row['wa_url'])>
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex justify-content-between gap-2">
                <div class="min-w-0">
                    <div class="fw-semibold text-truncate">{{ $row['name'] }}</div>
                    <div class="small text-muted text-truncate">
                        {{ $row['code'] }}
                        @if($row['phone']) · {{ $row['phone'] }} @else · <span class="text-danger">tanpa nomor</span> @endif
                        @if($row['dusun']) · {{ $row['dusun'] }} @endif
                    </div>
                    <div class="small text-muted text-truncate">
                        {{ $row['package'] ?? '-' }}
                        @if($row['periods_label']) · {{ $row['periods_label'] }} @endif
                        · JT {{ $row['due_date'] }}
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="fw-bold {{ $row['total'] > 0 ? 'text-danger' : 'text-success' }}">{{ $row['total_label'] }}</div>
                    @if($row['last_sent_label'])
                    <div class="small text-muted" data-last-sent>Terakhir: {{ $row['last_sent_label'] }}</div>
                    @else
                    <div class="small text-muted d-none" data-last-sent></div>
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-1 mt-2">
                <button type="button" class="btn btn-sm btn-success" data-send @disabled(! $row['wa_url'])><i class="bi bi-whatsapp"></i> Kirim</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-copy data-url="{{ $row['wa_url'] }}"><i class="bi bi-clipboard"></i> Salin Pesan</button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-view-message><i class="bi bi-eye"></i> Lihat</button>
                <button type="button" class="btn btn-sm btn-outline-success" data-mark><i class="bi bi-check2"></i> Tandai Terkirim</button>
            </div>
        </div>
    </div>
</div>
@endforeach
</div>

<div class="card blast-queue shadow-sm">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div class="small">
                <strong><span id="queue-count">0</span></strong> pelanggan dipilih ·
                <span id="queue-total">Rp0</span>
            </div>
            <div class="small text-muted" id="queue-progress"></div>
        </div>
        <div class="progress mb-2 d-none" style="height:6px" id="queue-bar-wrap">
            <div class="progress-bar bg-success" id="queue-bar" style="width:0%"></div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-touch flex-grow-1" id="start-blast" disabled><i class="bi bi-send"></i> Mulai Blast</button>
            <button type="button" class="btn btn-outline-secondary btn-touch" id="reset-selection"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="blastQueueModal" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
<div class="modal-header">
    <h5 class="modal-title"><i class="bi bi-whatsapp text-success"></i> Blast Berjalan</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" id="queue-close"></button>
</div>
<div class="modal-body">
    <div class="d-flex justify-content-between small text-muted mb-1">
        <span id="queue-step-label">0 / 0</span>
        <span id="queue-step-name"></span>
    </div>
    <div class="progress mb-3" style="height:6px"><div class="progress-bar bg-success" id="queue-step-bar" style="width:0%"></div></div>
    <pre class="bg-light border rounded p-3 small mb-3" style="white-space:pre-wrap; max-height:220px; overflow:auto" id="queue-message"></pre>
    <div class="alert alert-info py-2 small mb-0">
        Tekan <strong>Buka WhatsApp</strong> untuk membuka chat pelanggan ini. Setelah pesan terkirim di WhatsApp, tekan <strong>Lanjut</strong>.
    </div>
</div>
<div class="modal-footer flex-wrap gap-2">
    <button type="button" class="btn btn-outline-secondary" id="queue-skip">Lewati</button>
    <button type="button" class="btn btn-outline-danger" id="queue-stop">Hentikan</button>
    <button type="button" class="btn btn-success" id="queue-open"><i class="bi bi-whatsapp"></i> Buka WhatsApp</button>
    <button type="button" class="btn btn-primary d-none" id="queue-next">Lanjut <i class="bi bi-arrow-right"></i></button>
</div>
</div></div></div>

<div class="modal fade" id="messageModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Pratinjau Pesan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="small text-muted mb-2" id="message-target"></div>
    <pre class="bg-light border rounded p-3 small mb-0" style="white-space:pre-wrap" id="message-body"></pre>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
    <a href="#" target="_blank" rel="noopener" class="btn btn-success" id="message-send"><i class="bi bi-whatsapp"></i> Kirim</a>
</div>
</div></div></div>
@endsection

@push('scripts')
<script>
(function () {
    const defaultTemplate = @json(\App\Services\BillingMessageService::DEFAULT_TEMPLATE);
    const logUrl = @json(route('blast.log'));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const templateInput = document.getElementById('template');
    const rows = Array.from(document.querySelectorAll('[data-row]'));
    const selectAll = document.getElementById('select-all');
    const selectedCount = document.getElementById('selected-count');
    const queueCount = document.getElementById('queue-count');
    const queueTotal = document.getElementById('queue-total');
    const startBtn = document.getElementById('start-blast');
    const resetBtn = document.getElementById('reset-selection');
    const progressText = document.getElementById('queue-progress');
    const barWrap = document.getElementById('queue-bar-wrap');
    const bar = document.getElementById('queue-bar');
    const messageModalEl = document.getElementById('messageModal');
    const messageModal = messageModalEl ? new bootstrap.Modal(messageModalEl) : null;

    let queue = [];
    let running = false;

    function fmt(v) { return 'Rp' + Number(v || 0).toLocaleString('id-ID'); }

    function selectedRows() {
        return rows.filter(r => {
            const c = r.querySelector('[data-check]');
            return c && c.checked && !c.disabled;
        });
    }

    function syncSelection() {
        const selected = selectedRows();
        const selectable = rows.filter(r => {
            const c = r.querySelector('[data-check]');
            return c && !c.disabled;
        });

        selectedCount.textContent = selected.length;
        queueCount.textContent = selected.length;

        const total = selected.reduce((sum, r) => {
            const raw = parseInt(r.dataset.total.replace(/[^0-9]/g, ''), 10);
            return sum + (isNaN(raw) ? 0 : raw);
        }, 0);
        queueTotal.textContent = fmt(total);

        startBtn.disabled = selected.length === 0 || running;

        const el = document.getElementById('selectable-count');
        if (el) el.textContent = selectable.length;

        if (selectAll) {
            selectAll.checked = selectable.length > 0 && selected.length === selectable.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < selectable.length;
        }

        rows.forEach(r => {
            const c = r.querySelector('[data-check]');
            r.classList.toggle('is-selected', !!(c && c.checked));
        });
    }

    function currentTemplate() {
        return templateInput.value.trim() === '' ? defaultTemplate : templateInput.value;
    }

    function buildMessage(row) {
        const template = currentTemplate();
        const periods = row.dataset.periods || '';
        const replacements = {
            '{nama}': row.dataset.name || '',
            '{kode}': row.dataset.code || '',
            '{paket}': row.dataset.package || '-',
            '{periode}': periods || '-',
            '{total}': row.dataset.total || 'Rp0',
            '{jatuh_tempo}': row.dataset.dueDate || '-',
            '{dusun}': row.dataset.dusun || '-',
            '{alamat}': row.dataset.alamat || '-',
            '{penagih}': row.dataset.collector || '',
        };

        return Object.keys(replacements).reduce((msg, key) => msg.split(key).join(replacements[key]), template);
    }

    function whatsappUrl(phone, message) {
        const number = (phone || '').replace(/[^0-9]/g, '');
        if (!number) return null;
        return 'https://wa.me/' + number + '?text=' + encodeURIComponent(message);
    }

    async function logSent(items) {
        if (!items.length) return;
        try {
            await fetch(logUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: items }),
            });
        } catch (err) { /* logging is best-effort */ }
    }

    function markRowSent(row, when) {
        row.classList.add('is-sent');
        const el = row.querySelector('[data-last-sent]');
        if (el) {
            el.classList.remove('d-none');
            el.textContent = 'Terakhir: ' + when;
        }
    }

    rows.forEach(row => {
        const check = row.querySelector('[data-check]');
        if (check) check.addEventListener('change', syncSelection);

        const sendBtn = row.querySelector('[data-send]');
        const markBtn = row.querySelector('[data-mark]');
        const copyBtn = row.querySelector('[data-copy]');
        const viewBtn = row.querySelector('[data-view-message]');

        if (sendBtn) {
            sendBtn.addEventListener('click', function () {
                const message = buildMessage(row);
                const url = whatsappUrl(row.dataset.phone, message);
                if (!url) return;

                window.open(url, '_blank', 'noopener');
                markBtn.classList.remove('d-none');
            });
        }

        if (markBtn) {
            markBtn.addEventListener('click', async function () {
                markBtn.disabled = true;
                const res = await fetch(logUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items: [{ customer_id: Number(row.dataset.id), message: buildMessage(row) }] }),
                }).then(r => r.json()).catch(() => null);

                markRowSent(row, res ? res.sent_at : 'baru saja');
                markBtn.classList.add('d-none');
                markBtn.disabled = false;
            });
        }

        if (copyBtn) {
            copyBtn.addEventListener('click', async function () {
                try {
                    await navigator.clipboard.writeText(buildMessage(row));
                    copyBtn.innerHTML = '<i class="bi bi-check2"></i> Tersalin';
                    setTimeout(() => { copyBtn.innerHTML = '<i class="bi bi-clipboard"></i> Salin Pesan'; }, 1800);
                } catch (err) {
                    const url = whatsappUrl(row.dataset.phone, buildMessage(row));
                    if (url) window.open(url, '_blank', 'noopener');
                }
            });
        }

        if (viewBtn) {
            viewBtn.addEventListener('click', function () {
                document.getElementById('message-target').textContent = row.dataset.name + ' · ' + (row.dataset.phone || 'tanpa nomor');
                document.getElementById('message-body').textContent = buildMessage(row);
                const url = whatsappUrl(row.dataset.phone, buildMessage(row));
                const sendLink = document.getElementById('message-send');
                sendLink.href = url || '#';
                sendLink.classList.toggle('disabled', !url);
                if (messageModal) messageModal.show();
            });
        }
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rows.forEach(r => {
                const c = r.querySelector('[data-check]');
                if (c && !c.disabled) c.checked = selectAll.checked;
            });
            syncSelection();
        });
    }

    document.querySelectorAll('.placeholder-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            const key = chip.dataset.key;
            const start = templateInput.selectionStart || templateInput.value.length;
            const end = templateInput.selectionEnd || templateInput.value.length;
            templateInput.value = templateInput.value.slice(0, start) + key + templateInput.value.slice(end);
            templateInput.focus();
            templateInput.selectionStart = templateInput.selectionEnd = start + key.length;
        });
    });

    document.getElementById('reset-template').addEventListener('click', function () {
        templateInput.value = defaultTemplate;
    });

    document.getElementById('preview-template').addEventListener('click', function () {
        const first = selectedRows()[0] || rows[0];
        if (!first) return;
        document.getElementById('message-target').textContent = first.dataset.name + ' · ' + (first.dataset.phone || 'tanpa nomor');
        document.getElementById('message-body').textContent = buildMessage(first);
        const url = whatsappUrl(first.dataset.phone, buildMessage(first));
        const sendLink = document.getElementById('message-send');
        sendLink.href = url || '#';
        sendLink.classList.toggle('disabled', !url);
        if (messageModal) messageModal.show();
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            rows.forEach(r => {
                const c = r.querySelector('[data-check]');
                if (c) c.checked = false;
            });
            syncSelection();
        });
    }

    const queueModalEl = document.getElementById('blastQueueModal');
    const queueModal = queueModalEl ? new bootstrap.Modal(queueModalEl, { backdrop: 'static', keyboard: false }) : null;
    const queueStepLabel = document.getElementById('queue-step-label');
    const queueStepName = document.getElementById('queue-step-name');
    const queueStepBar = document.getElementById('queue-step-bar');
    const queueMessage = document.getElementById('queue-message');
    const queueOpenBtn = document.getElementById('queue-open');
    const queueNextBtn = document.getElementById('queue-next');
    const queueSkipBtn = document.getElementById('queue-skip');
    const queueStopBtn = document.getElementById('queue-stop');

    let queueItems = [];
    let queueIndex = 0;
    let queueBatch = [];

    function queueStep() {
        const row = queueItems[queueIndex];

        if (!row) {
            finishQueue();
            return;
        }

        const message = buildMessage(row);
        const url = whatsappUrl(row.dataset.phone, message);

        queueStepLabel.textContent = (queueIndex + 1) + ' / ' + queueItems.length;
        queueStepName.textContent = row.dataset.name;
        queueStepBar.style.width = Math.round((queueIndex / queueItems.length) * 100) + '%';
        queueMessage.textContent = message;
        queueOpenBtn.dataset.url = url || '';
        queueOpenBtn.disabled = !url;
        queueNextBtn.classList.add('d-none');
        queueOpenBtn.classList.remove('d-none');
    }

    function finishQueue() {
        queueStepBar.style.width = '100%';

        logSent(queueBatch).then(() => {
            const when = new Date().toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
            queueBatch.forEach(item => {
                const row = queueItems.find(r => Number(r.dataset.id) === item.customer_id);
                if (row) markRowSent(row, when);
            });
        });

        if (queueModal) queueModal.hide();
        progressText.textContent = 'Selesai — ' + queueBatch.length + ' pesan dicatat.';
        barWrap.classList.add('d-none');
        running = false;
        startBtn.disabled = false;
        startBtn.innerHTML = '<i class="bi bi-send"></i> Mulai Blast';
    }

    if (startBtn) {
        startBtn.addEventListener('click', function () {
            if (running) return;

            queueItems = selectedRows().slice();
            if (!queueItems.length) return;

            running = true;
            queueIndex = 0;
            queueBatch = [];
            startBtn.disabled = true;
            startBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Berjalan…';

            if (!queueModal) return;
            queueStep();
            queueModal.show();
        });
    }

    if (queueOpenBtn) {
        queueOpenBtn.addEventListener('click', function () {
            const row = queueItems[queueIndex];
            if (!row) return;

            const url = queueOpenBtn.dataset.url;
            if (url) window.open(url, '_blank', 'noopener');

            queueBatch.push({ customer_id: Number(row.dataset.id), message: buildMessage(row) });

            queueOpenBtn.classList.add('d-none');
            queueNextBtn.classList.remove('d-none');
            queueNextBtn.focus();
        });
    }

    if (queueNextBtn) {
        queueNextBtn.addEventListener('click', function () {
            queueIndex++;
            queueStep();
        });
    }

    if (queueSkipBtn) {
        queueSkipBtn.addEventListener('click', function () {
            queueIndex++;
            queueStep();
        });
    }

    if (queueStopBtn) {
        queueStopBtn.addEventListener('click', function () {
            if (queueModal) queueModal.hide();
            logSent(queueBatch);
            queueBatch = [];
            progressText.textContent = 'Dihentikan.';
            running = false;
            startBtn.disabled = false;
            startBtn.innerHTML = '<i class="bi bi-send"></i> Mulai Blast';
        });
    }

    syncSelection();
})();
</script>
@endpush
