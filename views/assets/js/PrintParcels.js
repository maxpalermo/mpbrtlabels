class PrintParcels {
    constructor(items = [], adminControllerUrl) {
        this.items = Array.isArray(items) ? items : [];
        this.adminControllerUrl = adminControllerUrl;
        this.yearDefault = new Date().getFullYear();
    }

    setItems(items = []) {
        this.items = Array.isArray(items) ? items : [];
    }

    _normalizeItems() {
        return (this.items || [])
            .map((it) => {
                const nsr = it && it.numericSenderReference != null ? Number(it.numericSenderReference) : NaN;
                const year = it && it.year != null && it.year !== "" ? Number(it.year) : this.yearDefault;
                return {
                    numericSenderReference: nsr,
                    year: year,
                };
            })
            .filter((it) => Number.isFinite(it.numericSenderReference) && it.numericSenderReference > 0);
    }

    async fetch() {
        if (!this.adminControllerUrl) {
            throw new Error("adminControllerUrl non impostato");
        }

        const items = this._normalizeItems();
        if (!items.length) {
            throw new Error("Nessun numericSenderReference valido");
        }

        const fd = new FormData();
        fd.append("ajax", "1");
        fd.append("action", "fetchLabelsByRefs");
        fd.append("items", JSON.stringify(items));

        const resp = await fetch(this.adminControllerUrl, {
            method: "POST",
            body: fd,
        });

        if (!resp.ok) {
            throw new Error("Errore nella richiesta al server");
        }

        const data = await resp.json();
        if (!data || !data.success) {
            return [];
        }

        return Array.isArray(data.streams) ? data.streams : [];
    }

    async open() {
        const streams = await this.fetch();
        if (!streams.length) {
            throw new Error("Nessuna etichetta trovata");
        }

        const merger = new PdfMerger(streams);
        return await merger.open();
    }

    async print() {
        const streams = await this.fetch();
        if (!streams.length) {
            throw new Error("Nessuna etichetta trovata");
        }

        const merger = new PdfMerger(streams);
        return await merger.print();
    }
}

window.PrintParcels = PrintParcels;
