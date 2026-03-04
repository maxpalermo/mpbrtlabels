class PrintBordero {
    constructor(adminControllerUrl) {
        this.adminControllerUrl = adminControllerUrl;
        this.yearDefault = new Date().getFullYear();
    }

    _base64ToBlob(base64, mimeType = "application/pdf") {
        const clean = (base64 || "").replace(/\s/g, "");
        const binary = atob(clean);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return new Blob([bytes], { type: mimeType });
    }

    _openPdfBase64(pdfBase64, filename = "bordero.pdf") {
        const blob = this._base64ToBlob(pdfBase64, "application/pdf");
        const url = URL.createObjectURL(blob);
        const win = window.open(url, "_blank", "noopener,noreferrer");
        if (!win) {
            URL.revokeObjectURL(url);
            throw new Error("Popup bloccato dal browser");
        }
        win.addEventListener("beforeunload", () => {
            try {
                URL.revokeObjectURL(url);
            } catch (e) {}
        });
        return { win, url, filename };
    }

    async printLastBordero() {
        if (!this.adminControllerUrl) {
            throw new Error("adminControllerUrl non impostato");
        }

        const fd = new FormData();
        fd.append("ajax", "1");
        fd.append("action", "printLastBordero");

        const resp = await fetch(this.adminControllerUrl, {
            method: "POST",
            body: fd,
        });

        if (!resp.ok) {
            throw new Error("Errore nella richiesta al server");
        }

        const data = await resp.json();
        if (!data || !data.success) {
            throw new Error((data && data.error) || "Impossibile generare il bordero");
        }

        showSuccessMessage(`
            <strong>Successo!</strong>
            <p>Il bordero è stato generato con successo.</p>
            <p>Aggiornate ${data.updated} su ${data.total} etichette.</p>
        `);

        $("#table-list-bordero").bootstrapTable("refresh");

        return this._openPdfBase64(data.pdfBase64, data.filename);
    }

    async printParcels(items = []) {
        if (!this.adminControllerUrl) {
            throw new Error("adminControllerUrl non impostato");
        }

        const normalized = (Array.isArray(items) ? items : [])
            .map((it) => {
                const nsr = it && it.numericSenderReference != null ? Number(it.numericSenderReference) : NaN;
                const year = it && it.year != null && it.year !== "" ? Number(it.year) : this.yearDefault;
                return { numericSenderReference: nsr, year: year };
            })
            .filter((it) => Number.isFinite(it.numericSenderReference) && it.numericSenderReference > 0);

        if (!normalized.length) {
            throw new Error("Nessun numericSenderReference valido");
        }

        const fd = new FormData();
        fd.append("ajax", "1");
        fd.append("action", "printParcels");
        fd.append("items", JSON.stringify(normalized));

        const resp = await fetch(this.adminControllerUrl, {
            method: "POST",
            body: fd,
        });

        if (!resp.ok) {
            throw new Error("Errore nella richiesta al server");
        }

        const data = await resp.json();
        if (!data || !data.success) {
            throw new Error((data && data.error) || "Impossibile generare il bordero");
        }

        return this._openPdfBase64(data.pdfBase64, data.filename);
    }
}

window.PrintBordero = PrintBordero;
