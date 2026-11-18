import Alpine from 'alpinejs';

Alpine.data('liveTable', ({
    url, search = '', perPage = 10, filters = {},
    regions = ['table-area'], refresh = 0,
}) => ({
    search,
    perPage,
    filters,
    regions,
    page: 1,
    loading: false,
    controller: null,
    timer: null,
    updated: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    exporting: false,

    init() {
        if (refresh > 0) {
            this.timer = setInterval(() => {
                if (!document.hidden) this.load(this.page, true);   // refresh diam-diam
            }, refresh);
        }
    },

    destroy() {
        clearInterval(this.timer);
    },

    async load(page = null, silent = false) {
        this.page = page && Number(page) > 1 ? Number(page) : 1;

        const params = new URLSearchParams();
        if (this.search.trim() !== '') params.set('q', this.search.trim());

        Object.entries(this.filters).forEach(([key, value]) => {
            if (value !== '' && value !== null) params.set(key, value);
        });

        if (this.perPage) params.set('per_page', this.perPage);
        if (this.page > 1) params.set('page', this.page);

        const target = url + (params.toString() ? '?' + params : '');

        this.controller?.abort();
        this.controller = new AbortController();
        if (!silent) this.loading = true;

        try {
            const res = await fetch(target, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: this.controller.signal,
            });
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');

            for (const id of this.regions) {
                const current = document.getElementById(id);
                const fresh = doc.getElementById(id);
                if (current && fresh) current.innerHTML = fresh.innerHTML;
            }

            history.replaceState(null, '', target);
            this.updated = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        } finally {
            this.loading = false;
        }
    },

    exportUrl(base) {
        const params = new URLSearchParams();
        if (this.search.trim() !== '') params.set('q', this.search.trim());

        Object.entries(this.filters).forEach(([key, value]) => {
            if (value !== '' && value !== null) params.set(key, value);
        });

        return base + (params.toString() ? '?' + params : '');
    },

    async exportFile(base) {
        if (this.exporting) return;

        const total = Number(document.querySelector('[data-total]')?.dataset.total ?? 0);

        if (total === 0) {
            window.toast({
                type: 'warning',
                title: 'Tidak ada data',
                message: 'Tidak ada baris yang cocok dengan pencarian atau filter ini, jadi tidak ada yang diekspor.',
            });
            return;
        }

        this.exporting = true;

        const id = window.toast({
            type: 'loading',
            title: 'Menyiapkan ekspor...',
            message: `${total.toLocaleString('id-ID')} data sedang dikemas ke Excel.`,
            timeout: 0,
        });

        try {
            const res = await fetch(this.exportUrl(base), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            // sesi habis: server mengalihkan ke halaman login, bukan mengirim file
            if (!res.ok || res.redirected || (res.headers.get('Content-Type') ?? '').includes('text/html')) {
                throw new Error('Respons bukan file Excel');
            }

            const count = Number(res.headers.get('X-Export-Count') ?? total);
            const disposition = res.headers.get('Content-Disposition') ?? '';
            const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
            const filename = match ? decodeURIComponent(match[1]) : 'member.xlsx';

            const blob = await res.blob();
            const href = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = href;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(href), 10000);

            window.toastUpdate(id, {
                type: 'success',
                title: 'Ekspor berhasil',
                message: `${count.toLocaleString('id-ID')} data tersimpan di ${filename}.`,
            });
        } catch (e) {
            console.error(e);
            window.toastUpdate(id, {
                type: 'error',
                title: 'Ekspor gagal',
                message: 'Terjadi masalah saat membuat file. Muat ulang halaman lalu coba lagi.',
            });
        } finally {
            this.exporting = false;
        }
    },

    onPageClick(event) {
        const link = event.target.closest('a.pg');
        if (!link) return;

        event.preventDefault();
        this.load(new URL(link.href).searchParams.get('page'));
    },
}));

Alpine.data('autoRefresh', ({ url, interval = 60000 }) => ({
    updated: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    timer: null,

    init() {
        this.timer = setInterval(() => this.refresh(), interval);
    },

    destroy() {
        clearInterval(this.timer);
    },

    async refresh() {
        if (document.hidden) return;   // tab tidak sedang dilihat, tidak perlu

        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const fresh = doc.getElementById('today-area');

            if (fresh) {
                document.getElementById('today-area').innerHTML = fresh.innerHTML;
                this.updated = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            }
        } catch (e) {
            console.error(e);
        }
    },
}));

Alpine.store('toasts', {
    items: [],

    push({ type = 'success', title = '', message = '', timeout = 6000 }) {
        const id = Date.now() + Math.random();
        this.items.push({ id, type, title, message });
        if (timeout > 0) setTimeout(() => this.remove(id), timeout);
        return id;
    },

    update(id, patch, timeout = 6000) {
        const item = this.items.find((t) => t.id === id);

        if (!item) {
            this.push({ ...patch, timeout });
            return;
        }

        Object.assign(item, patch);
        if (timeout > 0) setTimeout(() => this.remove(id), timeout);
    },

    remove(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});

window.toast = (options) => Alpine.store('toasts').push(options);
window.toastUpdate = (id, patch, timeout) => Alpine.store('toasts').update(id, patch, timeout);

window.Alpine = Alpine;
Alpine.start();