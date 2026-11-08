import Alpine from 'alpinejs';

Alpine.data('liveTable', ({ url, search = '', perPage = 10, filters = {} }) => ({
    search,
    perPage,
    filters,
    loading: false,
    controller: null,

    async load(page = null) {
        const params = new URLSearchParams();
        if (this.search.trim() !== '') params.set('q', this.search.trim());

        Object.entries(this.filters).forEach(([key, value]) => {
            if (value !== '' && value !== null) params.set(key, value);
        });

        if (this.perPage) params.set('per_page', this.perPage);
        if (page && Number(page) > 1) params.set('page', page);

        const target = url + (params.toString() ? '?' + params : '');

        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;

        try {
            const res = await fetch(target, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: this.controller.signal,
            });
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.getElementById('table-area');

            if (fresh) {
                document.getElementById('table-area').innerHTML = fresh.innerHTML;
                history.replaceState(null, '', target);
            }
        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        } finally {
            this.loading = false;
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

window.Alpine = Alpine;
Alpine.start();