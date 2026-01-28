(function (global) {
    class PdfMerger {
        constructor(base64Pdfs = []) {
            this.base64Pdfs = Array.isArray(base64Pdfs) ? base64Pdfs : [];
            this._blobUrl = null;
        }

        setPdfs(base64Pdfs = []) {
            this.base64Pdfs = Array.isArray(base64Pdfs) ? base64Pdfs : [];
            this.revoke();
        }

        addPdf(base64Pdf) {
            if (!base64Pdf) return;
            this.base64Pdfs.push(base64Pdf);
            this.revoke();
        }

        revoke() {
            if (this._blobUrl) {
                URL.revokeObjectURL(this._blobUrl);
                this._blobUrl = null;
            }
        }

        _normalizeBase64(input) {
            if (typeof input !== "string") return "";
            const idx = input.indexOf("base64,");
            return idx >= 0 ? input.slice(idx + 7) : input;
        }

        _base64ToUint8Array(base64) {
            const clean = this._normalizeBase64(base64).replace(/\s/g, "");
            const binary = atob(clean);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            return bytes;
        }

        async merge() {
            if (!global.PDFLib || !global.PDFLib.PDFDocument) {
                throw new Error("PDFLib non disponibile. Assicurati di includere pdf-lib.min.js prima di mergePDF.js");
            }

            const { PDFDocument } = global.PDFLib;
            const mergedPdf = await PDFDocument.create();

            for (const b64 of this.base64Pdfs) {
                if (!b64) continue;
                const bytes = this._base64ToUint8Array(b64);
                const srcPdf = await PDFDocument.load(bytes);
                const copiedPages = await mergedPdf.copyPages(srcPdf, srcPdf.getPageIndices());
                copiedPages.forEach((p) => mergedPdf.addPage(p));
            }

            const mergedBytes = await mergedPdf.save();
            return mergedBytes;
        }

        async getBlobUrl() {
            if (this._blobUrl) return this._blobUrl;
            const bytes = await this.merge();
            const blob = new Blob([bytes], { type: "application/pdf" });
            this._blobUrl = URL.createObjectURL(blob);
            return this._blobUrl;
        }

        async open() {
            const url = await this.getBlobUrl();
            const win = window.open(url, "_blank", "noopener,noreferrer");
            if (!win) {
                throw new Error("Popup bloccato dal browser");
            }
            return win;
        }

        async print() {
            const url = await this.getBlobUrl();
            const win = window.open("", "_blank", "noopener,noreferrer");
            if (!win) {
                throw new Error("Popup bloccato dal browser");
            }

            win.document.open();
            win.document.write(`<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0"><iframe src="${url}" style="border:0;width:100%;height:100vh"></iframe></body></html>`);
            win.document.close();

            const triggerPrint = () => {
                try {
                    win.focus();
                    win.print();
                } catch (e) {}
            };

            win.addEventListener("load", () => {
                setTimeout(triggerPrint, 300);
            });

            return win;
        }
    }

    global.PdfMerger = PdfMerger;
})(window);
