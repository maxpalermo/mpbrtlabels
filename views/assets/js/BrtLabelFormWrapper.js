class BrtLabelFormWrapper {
    constructor(endpoint, orderId, action, form, isAdminOrdersPage = false, autosave = false) {
        this.endpoint = endpoint || "";
        this.orderId = orderId || "";
        this.action = action || "";
        this.form = form;
        this.isAdminOrdersPage = isAdminOrdersPage;
        this.autosave = autosave;
        this._bound = false;
        this._initialValues = null;
        this._btnPrintParcel = null;
        this._btnSendRequest = null;
        this._btnLastError = null;
        this._btnClose = null;
        this._btnShowRequest = null;
        this._dialog = null;
        this._uiBound = false;
        this._onBtnSendRequestClick = null;
        this._onBtnLastErrorClick = null;
        this._onBtnPrintParcelClick = null;
        this._onBtnCloseClick = null;
        this._onBtnShowRequestClick = null;

        this.createDataElements = [];
        this.createDataByName = new Map();

        this._codSwitch = null;
        this._codAmount = null;
        this._codPaymentType = null;
        this._onCodToggle = null;

        this._changeOrderState = null;
        this._onChangeOrderStateInput = null;

        if (!this.form) {
            throw new Error("BrtLabelFormWrapper: form is required");
        }

        this._index();
        this._bindUiButtons();
        this._bindCodToggle();
        this._bindChangeOrderStateSync();
        this._bind();
        this.gerRequestParameters();

        if (!this.isAdminOrdersPage) {
            this.btnDeleteLabel = document.getElementById("btnDeleteLabel");
            this.bindOnClick(this.btnDeleteLabel, this.onDeleteLabel);
        }
    }

    addImportOrderButton() {
        if (document.querySelector(".import-div")) {
            return;
        }

        const div = document.getElementById("dialogBrtLabel").querySelector(".import-order");

        const importDiv = `
            <div class="import-div form-group d-flex justify-content-start align-items-center gap-2" style="border: 1px solid #dcdcdc; border-radius: 5px; padding: 16px;">
                <label>Importa dall'ordine</label>
                <input type="text" class="form-control" name="brtLabelOrderId" id="brtLabelOrderId" value="">
                <button type="button" class="btn btn-primary" id="btnReadOrder">
                    <span class="material-icons mr-2">import_contacts</span>
                    <span>Importa</span>
                </button>
            </div>
        `;

        const template = document.createElement("template");
        template.innerHTML = importDiv;
        const el = template.content.cloneNode(true).querySelector(".import-div");

        div.appendChild(el);

        const btn = document.getElementById("btnReadOrder");
        btn.addEventListener("click", () => {
            this.readOrder();
        });
    }

    async gerRequestParameters() {
        return await this.readFromServer({ orderId: this.orderId }, "readOrderRequestParameters");
    }

    async readRemote(actionOverride = "readOrderRequestParameters") {
        return this.readFromServer({ orderId: this.orderId }, actionOverride);
    }

    async writeRemote(values, actionOverride = null, extra = {}) {
        this.write(values, { remote: false });
        const payload = this.read();
        return this._request("write", { values: JSON.stringify(payload), ...extra }, actionOverride);
    }

    _index() {
        this.createDataElements = Array.from(this.form.querySelectorAll('[data-category="createData"]'));
        this.tableInputs = Array.from(this.form.querySelectorAll("table-input"));
        this.selects = Array.from(this.form.querySelectorAll("select[name]"));
        this.buttons = Array.from(this.form.querySelectorAll("button[name], button[id]"));

        this.createDataByName = new Map();
        this.createDataElements.forEach((el) => {
            const name = el?.dataset?.name || el?.getAttribute?.("name") || el?.name;
            if (!name) return;
            if (!this.createDataByName.has(name)) this.createDataByName.set(name, []);
            this.createDataByName.get(name).push(el);
        });

        this.byName = new Map();
        const add = (name, el) => {
            if (!name) return;
            if (!this.byName.has(name)) this.byName.set(name, []);
            this.byName.get(name).push(el);
        };

        this.tableInputs.forEach((el) => add(el.dataset.name || el.getAttribute("name") || "", el));
        this.selects.forEach((el) => add(el.name || "", el));
        this.buttons.forEach((el) => add(el.name || el.id || "", el));

        this._btnShowRequest = document.getElementById("btnShowRequest");
        this._btnSendRequest = document.getElementById("btnSendRequest");
        this._btnLastError = document.getElementById("btnShowLastError");
        this._btnClose = document.getElementById("btnCloseDialog");
        this._btnPrintParcel = document.getElementById("btnPrintParcel");
        this._dialog = document.getElementById("dialogBrtLabel");

        this._bindUiButtons();

        this._codSwitch = this.createDataByName.get("isCODMandatory")?.[0] || null;
        this._codAmount = this.createDataByName.get("cashOnDelivery")?.[0] || null;
        this._codPaymentType = this.createDataByName.get("codPaymentType")?.[0] || document.getElementById("codPaymentType");
        this._bindCodToggle();

        this._changeOrderState = this.createDataByName.get("changeOrderState")?.[0] || null;
        this._bindChangeOrderStateSync();
    }

    _isAdminOrders() {
        const v = this.isAdminOrdersPage;
        if (v === true) return true;
        if (v === false) return false;
        const s = String(v ?? "")
            .trim()
            .toLowerCase();
        if (!s) return false;
        if (s === "1" || s === "true" || s === "on" || s === "yes") return true;
        return false;
    }

    _syncChangeOrderState() {
        if (!this._isAdminOrders()) return;
        const el = this._changeOrderState;
        if (!el) return;
        const valueOn = el.dataset?.valueOn != null ? String(el.dataset.valueOn) : "1";
        this._setTableInputValue(el, valueOn);
    }

    _bindChangeOrderStateSync() {
        const el = this._changeOrderState;

        if (el && this._onChangeOrderStateInput) {
            el.removeEventListener("table-input:input", this._onChangeOrderStateInput);
        }

        this._onChangeOrderStateInput = () => {
            this._syncChangeOrderState();
        };

        if (el && this._isAdminOrders()) {
            el.addEventListener("table-input:input", this._onChangeOrderStateInput);
        }

        this._syncChangeOrderState();
    }

    _bindCodToggle() {
        if (this._codSwitch && this._onCodToggle) {
            this._codSwitch.removeEventListener("table-input:input", this._onCodToggle);
        }

        this._onCodToggle = () => {
            this._updateCodVisibility();
        };

        if (this._codSwitch) {
            this._codSwitch.addEventListener("table-input:input", this._onCodToggle);
        }

        this._updateCodVisibility();
    }

    _isCodEnabled() {
        const el = this._codSwitch;
        if (!el) return false;
        const v = String(el.value ?? "").trim();
        const valueOn = String(el.dataset?.valueOn ?? "1");
        if (v === valueOn) return true;
        if (v === "1" || v.toLowerCase() === "on" || v.toLowerCase() === "true") return true;
        return false;
    }

    _setRowVisible(el, visible) {
        if (!el) return;
        const tr = el.closest?.("tr");
        if (!tr) return;
        tr.style.display = visible ? "" : "none";
    }

    _updateCodVisibility() {
        const visible = this._isCodEnabled();
        this._setRowVisible(this._codAmount, visible);
        this._setRowVisible(this._codPaymentType, visible);
    }

    readCreateData() {
        const out = {};
        this.createDataByName.forEach((els, name) => {
            const el = els?.[0];
            if (!el) return;
            if ("rawValue" in el) {
                out[name] = el.rawValue;
                return;
            }
            if ("value" in el) {
                out[name] = el.value;
                return;
            }
            out[name] = el.textContent;
        });
        return out;
    }

    writeCreateData(values = {}) {
        if (!values || typeof values !== "object") return;
        Object.entries(values).forEach(([name, value]) => {
            const els = this.createDataByName.get(name);
            if (!els || !els.length) return;
            els.forEach((el) => {
                const v = value == null ? "" : value;
                if ("rawValue" in el) {
                    el.rawValue = String(v);
                    return;
                }
                if (el instanceof HTMLSelectElement || el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement) {
                    el.value = String(v);
                    return;
                }
                el.textContent = String(v);
            });
        });
    }

    async refresh() {
        this._index();
        this._bindUiButtons();
        this._bindCodToggle();
        this._bindChangeOrderStateSync();
        this._bind();
        return await this.gerRequestParameters();
    }

    _bindUiButtons() {
        if (this._uiBound) {
            if (this._btnShowRequest && this._onBtnShowRequestClick) {
                this._btnShowRequest.removeEventListener("click", this._onBtnShowRequestClick);
            }
            if (this._btnSendRequest && this._onBtnSendRequestClick) {
                this._btnSendRequest.removeEventListener("click", this._onBtnSendRequestClick);
            }
            if (this._btnLastError && this._onBtnLastErrorClick) {
                this._btnLastError.removeEventListener("click", this._onBtnLastErrorClick);
            }
            if (this._btnPrintParcel && this._onBtnPrintParcelClick) {
                this._btnPrintParcel.removeEventListener("click", this._onBtnPrintParcelClick);
            }
            if (this._btnClose && this._onBtnCloseClick) {
                this._btnClose.removeEventListener("click", this._onBtnCloseClick);
            }

            this._uiBound = false;
        }

        this._onBtnShowRequestClick = async (e) => {
            e.preventDefault();
            e.stopPropagation();
            await this.createRequestParcel(1);
        };
        this._onBtnSendRequestClick = async (e) => {
            e.preventDefault();
            e.stopPropagation();
            await this.createRequestParcel();
        };
        this._onBtnLastErrorClick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.onBtnLastError();
        };
        this._onBtnPrintParcelClick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.onBtnPrintParcel();
        };
        this._onBtnCloseClick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.onBtnClose();
        };

        if (this._btnShowRequest) this._btnShowRequest.addEventListener("click", this._onBtnShowRequestClick);
        if (this._btnSendRequest) this._btnSendRequest.addEventListener("click", this._onBtnSendRequestClick);
        if (this._btnLastError) this._btnLastError.addEventListener("click", this._onBtnLastErrorClick);
        if (this._btnPrintParcel) this._btnPrintParcel.addEventListener("click", this._onBtnPrintParcelClick);
        if (this._btnClose) this._btnClose.addEventListener("click", this._onBtnCloseClick);

        this._uiBound = true;
    }

    reset() {
        const initial = this._initialValues && typeof this._initialValues === "object" ? this._initialValues : {};

        this.tableInputs.forEach((el) => {
            const name = el.dataset.name || el.getAttribute("name") || "";
            const type = this._getTableInputType(el);
            if (type === "switch") {
                const next = Object.prototype.hasOwnProperty.call(initial, name) ? initial[name] : el.rawValue;
                this._setTableInputValue(el, next);
            } else {
                this._setTableInputValue(el, "");
            }
        });

        this.selects.forEach((sel) => {
            if (!sel || !sel.name) return;
            sel.value = "";
        });

        this._updateCodVisibility();
        this._syncChangeOrderState();
        this.calcTotals();
    }

    _bind() {
        if (this._bound) {
            this._unbind();
        }

        if (!this.autosave) {
            return;
        }

        this.selects.forEach((sel) => {
            if (!sel || !sel.name) return;
            sel._onBrtLabelChange = async () => {
                try {
                    await this._request(
                        "write",
                        {
                            orderId: this.orderId,
                            name: sel.name,
                            value: sel.value,
                        },
                        this.action,
                    );
                } catch (e) {
                    console.warn("Select save failed", sel.name, e);
                }
            };
            sel.removeEventListener("change", sel._onBrtLabelChange);
            sel.addEventListener("change", sel._onBrtLabelChange);
        });

        this._bound = true;
    }

    _unbind() {
        this.selects?.forEach((sel) => {
            if (!sel?._onBrtLabelChange) return;
            sel.removeEventListener("change", sel._onBrtLabelChange);
        });
        this._bound = false;
    }

    async readOrder() {
        const raw = document.getElementById("brtLabelOrderId")?.value;
        const orderId = Number(String(raw ?? "").trim());

        if (!Number.isFinite(orderId) || orderId <= 0) {
            showErrorMessage("Inserisci un ID ordine valido");
            return;
        }

        let res;
        try {
            res = await this._request("read", { orderId: String(orderId) }, "readOrderRequestParameters");
        } catch (e) {
            console.warn("readOrder failed", e);
            showErrorMessage("Errore nel caricamento dell'ordine");
            return;
        }

        const data = res?.data ?? res;
        const params = data?.params ?? data;
        if (!params || typeof params !== "object") {
            showErrorMessage("Risposta non valida dal server");
            return;
        }

        this.writeCreateData(params);

        this._updateCodVisibility();
        this._syncChangeOrderState();
        this.calcTotals();
        showNoticeMessage("Dati importati dall'ordine " + orderId);
    }

    read() {
        const out = {};

        for (const [name, els] of this.byName.entries()) {
            const first = els[0];
            if (!first) continue;

            if (first.tagName && first.tagName.toLowerCase() === "table-input") {
                out[name] = "rawValue" in first ? first.rawValue : first.value;
                continue;
            }

            if (first instanceof HTMLSelectElement) {
                out[name] = first.value;
                continue;
            }

            // buttons are not part of form payload
        }

        return out;
    }

    async readFromServer(extra = {}, actionOverride = null) {
        const res = await this._request("read", { ...extra }, actionOverride);
        const data = res?.data ?? res;
        const params = data?.params ?? data;
        const parcelsHtml = data?.parcelsHtml ?? params?.parcelsHtml;
        if (typeof parcelsHtml === "string" && parcelsHtml !== "") {
            this._updateParcelsTable(parcelsHtml);
        }
        if (params && typeof params === "object") {
            if (!this._initialValues) {
                try {
                    this._initialValues = JSON.parse(JSON.stringify(params));
                } catch {
                    this._initialValues = { ...params };
                }
            }
            this.write(params, { remote: false });
            this._updateCodVisibility();
            this._syncChangeOrderState();
        }
        return res;
    }

    _updateParcelsTable(parcelsHtml) {
        const table = this.form?.querySelector?.("#mpbrtlabel-table-parcels") || document.getElementById("mpbrtlabel-table-parcels");
        const tbody = table?.querySelector?.("tbody");
        if (!tbody) return;
        tbody.innerHTML = parcelsHtml;
        this.calcTotals();
    }

    write(payload, options = {}) {
        const { remote = false, extra = {} } = options || {};

        if (payload === "on" || payload === "off") {
            this._setAllSwitches(payload);
        } else if (payload && typeof payload === "object") {
            Object.entries(payload).forEach(([name, value]) => {
                this._setValue(name, value);
            });
        }

        if (remote) {
            const values = this.read();
            return this._request("write", {
                values: JSON.stringify(values),
                ...extra,
            });
        }

        return Promise.resolve(null);
    }

    _setValue(name, value) {
        const els = this.byName.get(name);
        if (!els || !els.length) return;

        const first = els[0];
        const v = value == null ? "" : value;

        if (first.tagName && first.tagName.toLowerCase() === "table-input") {
            this._setTableInputValue(first, v);
            return;
        }

        if (first instanceof HTMLSelectElement) {
            first.value = String(v);
            first.dispatchEvent(new Event("change", { bubbles: true }));
            return;
        }

        if (first instanceof HTMLButtonElement) {
            // allow payload like { someButton: { disabled: true } } or { someButton: true }
            if (typeof v === "object" && v) {
                if ("disabled" in v) first.disabled = !!v.disabled;
            } else if (typeof v === "boolean") {
                first.disabled = v;
            }
        }
    }

    _getTableInputType(el) {
        const type = (el.getAttribute("type") || el.dataset.type || "text").toLowerCase();
        return type;
    }

    _setTableInputValue(el, value) {
        const type = this._getTableInputType(el);
        if (type === "switch") {
            // switch uses rawValue
            if ("rawValue" in el) {
                el.rawValue = String(value);
            } else {
                el.value = String(value);
            }
            return;
        }

        // textarea/default
        if ("rawValue" in el) {
            el.rawValue = value == null ? "" : String(value);
        } else {
            el.value = value == null ? "" : String(value);
        }
    }

    _setAllSwitches(state) {
        const wantOn = state === "on";
        const switches = this.tableInputs.filter((el) => this._getTableInputType(el) === "switch");
        switches.forEach((el) => {
            const valueOn = el.dataset.valueOn != null ? String(el.dataset.valueOn) : "1";
            const valueOff = el.dataset.valueOff != null ? String(el.dataset.valueOff) : "0";
            const next = wantOn ? valueOn : valueOff;
            this._setTableInputValue(el, next);
        });
    }

    async _request(mode, extra = {}, actionOverride = null) {
        if (!this.endpoint) {
            throw new Error("BrtLabelFormWrapper: endpoint is required");
        }
        const action = actionOverride || this.action;
        if (!action) {
            throw new Error("BrtLabelFormWrapper: action is required");
        }

        const formData = new FormData();
        formData.append("ajax", 1);
        formData.append("action", action);
        formData.append("mode", mode);

        Object.entries(extra || {}).forEach(([k, v]) => {
            if (v === undefined || v === null) return;
            formData.append(k, v);
        });

        const resp = await fetch(this.endpoint, {
            method: "POST",
            body: formData,
        });

        if (!resp.ok) {
            throw new Error("BrtLabelFormWrapper: Network response was not ok");
        }

        const ct = resp.headers.get("content-type") || "";
        if (ct.includes("application/json")) {
            const json = await resp.json();
            return json;
        }

        const text = await resp.text();
        try {
            return JSON.parse(text);
        } catch {
            return { success: true, value: text };
        }
    }

    async addEmptyRow(orderId) {
        const table = document.getElementById("mpbrtlabel-table-parcels");
        if (!table) {
            showErrorMessage("Tabella colli non trovata");
            return false;
        }

        let pecod = "0-0";
        const lastRow = table.querySelector("tbody tr:last-child");
        if (lastRow) {
            pecod = lastRow.querySelector("td:first-child table-input").value;
        }

        const emptyRow = await this._request("addEmptyRow", { orderId: orderId, pecod: pecod }, "addEmptyRow");

        if (emptyRow && emptyRow.success) {
            const el = document.createElement("template");
            el.innerHTML = emptyRow.html;
            const row = el.content.cloneNode(true).querySelector("tr");
            table.querySelector("tbody").appendChild(row);
            row.querySelector("td:nth-child(2) table-input").focus();
        }
    }

    calcVolume(input) {
        console.log(input.value);

        const row = input.closest("tr");
        if (!row) return;

        const xEl = row.querySelector("td.x table-input");
        const yEl = row.querySelector("td.y table-input");
        const zEl = row.querySelector("td.z table-input");
        const vEl = row.querySelector("td.volume table-input");
        if (!xEl || !yEl || !zEl || !vEl) return;

        const x = Number(xEl.rawValue ?? xEl.value ?? 0);
        const y = Number(yEl.rawValue ?? yEl.value ?? 0);
        const z = Number(zEl.rawValue ?? zEl.value ?? 0);

        const volume = (x * y * z) / 1000000;
        vEl.value = Number.isFinite(volume) ? String(volume) : "0";

        this.calcTotals();
    }

    calcTotals() {
        const table = document.getElementById("mpbrtlabel-table-parcels");
        if (!table) {
            showErrorMessage("Tabella colli non trovata");
            return false;
        }

        const rows = table.querySelectorAll("tbody tr");
        let totColli = 0;
        let totPeso = 0;
        let totVolume = 0;
        rows.forEach((item) => {
            const peso = item.querySelector("td.weight table-input").value;
            const volume = item.querySelector("td.volume table-input").value;
            totColli++;
            totPeso += Number(peso);
            totVolume += Number(volume);
        });

        document.getElementById("totColli").textContent = totColli;
        document.getElementById("totPeso").textContent = totPeso.toFixed(1);
        document.getElementById("totVolume").textContent = totVolume.toFixed(3);
    }

    async saveParcel(btn) {
        if (!confirm("Salvare i dati del collo?")) {
            return false;
        }

        const tr = btn.closest("tr");

        const parcel = {
            id: tr.dataset.id,
            PECOD: tr.querySelector("td.pecod table-input").value,
            X: tr.querySelector("td.x table-input").value * 10,
            Y: tr.querySelector("td.y table-input").value * 10,
            Z: tr.querySelector("td.z table-input").value * 10,
            PPESO: tr.querySelector("td.weight table-input").value,
            PVOLU: tr.querySelector("td.volume table-input").value,
        };

        const formData = new FormData();
        formData.append("ajax", 1);
        formData.append("action", "saveParcel");
        formData.append("parcel", JSON.stringify(parcel));

        const response = await fetch(MPBRTLABELS_ENDPOINT, {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            showErrorMessage("Errore nella richiesta API");
        }

        const json = await response.json();
        if (json.success) {
            showNoticeMessage("Dati del collo salvati con successo");
            tr.dataset.id = json.parcelId;
            return;
        }

        showErrorMessage("Errore nel salvataggio dei dati");
    }

    async deleteParcel(numericSenderReference, alphanumericSenderReference, year) {
        if (!confirm("Eliminare l'etichetta con riferimento numerico " + numericSenderReference + " e alfanumerico " + alphanumericSenderReference + "?")) {
            return false;
        }

        const formData = new FormData();
        formData.append("ajax", 1);
        formData.append("action", "deleteRequest");
        formData.append("numericSenderReference", numericSenderReference);
        formData.append("alphanumericSenderReference", alphanumericSenderReference);
        formData.append("year", year);

        const endpoint = window.adminControllerUrl ?? window.MPBRTLABELS_ENDPOINT;

        const response = await fetch(endpoint, {
            method: "POST",
            body: formData,
        });

        const data = await response.json();

        const deleteResponse = data.response.deleteResponse;
        const executionMessage = deleteResponse.executionMessage;

        if (executionMessage.code < 0) {
            showErrorMessage(`
            <h3>Errore ${executionMessage.code}</h3>
            <p>Codice: ${executionMessage.codeDesc}</p>
            <p>Messaggio: ${executionMessage.message}</p>
        `);
        } else if (executionMessage.code > 0) {
            showSuccessMessage(`
            <h3>Attenzione ${executionMessage.code}</h3>
            <p>Etichetta eliminata con avvisi</p>
            <p>Codice: ${executionMessage.codeDesc}</p>
            <p>Messaggio: ${executionMessage.message}</p>
        `);
        } else {
            showSuccessMessage("Etichetta eliminata");
        }
    }

    async createRequestParcel(showRequestData = 0) {
        if (!showRequestData) {
            if (!confirm("Inviare i dati a BRT?")) {
                return false;
            }
        }
        const request = this.readCreateData();

        const formData = new FormData();
        formData.append("ajax", 1);
        formData.append("action", "sendRequest");
        formData.append("orderId", this.orderId);
        formData.append("createData", JSON.stringify(request));
        formData.append("parcel", JSON.stringify([]));
        formData.append("showRequestData", showRequestData);

        const response = await fetch(MPBRTLABELS_ENDPOINT, {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            showErrorMessage("Errore nella richiesta API");
        }

        const json = await response.json();

        if (showRequestData) {
            delete json.showRequestData;

            const pretty = JSON.stringify(json, null, 2);
            const escapeHtml = (s) => String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;").replace(/'/g, "&#039;");
            showNoticeMessage("<pre>" + escapeHtml(pretty) + "</pre>");
            return;
        }

        await this.parseResponse(json);
    }

    async onBtnLastError() {
        //nothing
    }

    async onBtnPrintParcel() {
        //nothing
    }

    onBtnClose() {
        this._dialog?.close?.();
    }

    _base64ToUint8Array(b64) {
        const cleaned = String(b64 ?? "")
            .trim()
            .replace(/^data:application\/pdf;base64,/, "")
            .replace(/\s+/g, "");
        const binary = atob(cleaned);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes;
    }

    _openPdfBytes(pdfBytes, filename = "brt-label.pdf") {
        const blob = new Blob([pdfBytes], { type: "application/pdf" });
        const url = URL.createObjectURL(blob);

        let opened = false;
        try {
            const win = window.open(url, "_blank");
            if (win) {
                try {
                    win.opener = null;
                } catch {
                    // ignore
                }
                opened = true;
            }
        } catch {
            // ignore
        }

        if (!opened) {
            try {
                const a = document.createElement("a");
                a.href = url;
                a.target = "_blank";
                a.rel = "noopener";
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                opened = true;
            } catch {
                // ignore
            }
        }

        if (!opened) {
            showErrorMessage("Popup bloccato: impossibile aprire il PDF in una nuova scheda");
        }
        setTimeout(() => {
            URL.revokeObjectURL(url);
        }, 60_000);
    }

    async _mergePdfStreamsAndOpen(streams) {
        if (!streams || !streams.length) return;
        if (!window.PDFLib || !window.PDFLib.PDFDocument) {
            showErrorMessage("PDFLib non disponibile: impossibile unire le etichette");
            return;
        }

        const merged = await window.PDFLib.PDFDocument.create();

        for (const stream of streams) {
            if (!stream) continue;
            const bytes = this._base64ToUint8Array(stream);
            const src = await window.PDFLib.PDFDocument.load(bytes);
            const pages = await merged.copyPages(src, src.getPageIndices());
            pages.forEach((p) => merged.addPage(p));
        }

        const pdfBytes = await merged.save();
        this._openPdfBytes(pdfBytes);
    }

    async parseResponse(data) {
        if (!data || typeof data !== "object") {
            showErrorMessage("Risposta non valida");
            return;
        }

        if (data.executionMessage) {
            const executionMessage = data.executionMessage;
            const code = Number(executionMessage.code);
            const codeDesc = executionMessage.codeDesc ?? "";
            const message = executionMessage.message ?? "";

            if (Number.isFinite(code) && code < 0) {
                showErrorMessage(`
                    <h4>${code}: ${codeDesc}</h4>
                    <p>${message}</p>
                `);
                return;
            }

            showNoticeMessage(`
                <h4>${Number.isFinite(code) ? code : ""}: ${codeDesc}</h4>
                <p>${message}</p>
            `);
        } else if (data.success === true) {
            showNoticeMessage("Richiesta inviata con successo");
        } else if (data.success === false) {
            showErrorMessage("Errore nell'invio della richiesta");
            return;
        }

        const labels = Array.isArray(data.labels.label) ? data.labels.label : [];
        const streams = labels.map((l) => l?.stream).filter(Boolean);
        if (streams.length) {
            await this._mergePdfStreamsAndOpen(streams);
        }
    }

    showDialog() {
        this.showBrtLabelDialog();
    }

    showBrtLabelDialog() {
        const dialog = document.getElementById("dialogBrtLabel");
        if (dialog) {
            this.moveGrowlContainer(dialog);
            this.reset();
            this.refresh();
            dialog.showModal();
            this.calcTotals();
        }
        if (!this.isAdminOrdersPage) {
            this.addImportOrderButton();
        }
    }

    hideDialog() {
        this.hideBrtLabelDialog();
    }

    hideBrtLabelDialog() {
        const dialog = document.getElementById("dialogBrtLabel");
        if (dialog) {
            this.moveGrowlContainer();
            dialog.close();
        }
    }

    moveGrowlContainer(parent = null) {
        if (!this.isAdminOrdersPage) {
            this.moveGrowlContainerLegacy(parent);
            return;
        }

        let growlContainer = document.getElementById("growls-default");

        if (growlContainer) {
            growlContainer.remove();
            growlContainer = null;
        }

        if (!growlContainer) {
            growlContainer = document.createElement("div");
            growlContainer.id = "growls-default";
        }

        if (parent) {
            parent.appendChild(growlContainer);
        } else {
            document.body.appendChild(growlContainer);
        }

        return growlContainer;
    }

    moveGrowlContainerLegacy(parent = null) {
        let growlContainer = document.getElementById("growls");

        if (growlContainer) {
            growlContainer.remove();
            growlContainer = null;
        }

        if (!growlContainer) {
            growlContainer = document.createElement("div");
            growlContainer.id = "growls";
        }

        if (parent) {
            parent.appendChild(growlContainer);
        } else {
            document.body.appendChild(growlContainer);
        }

        return growlContainer;
    }

    bindOnClick(button, callback, params = null) {
        // Controlla che esiste il bottone
        if (!button) {
            console.error("Bottone non trovato", button);

            return;
        }

        //controlla che esista il callback
        if (typeof callback !== "function") {
            console.error("Callback non è una funzione", callback);

            return;
        }

        button._onClick ??= callback.call(this, params);
        button.removeEventListener("click", button._onClick);
        button.addEventListener("click", button._onClick);
    }

    onDeleteLabel() {
        const self = this;
        return async (e) => {
            e?.preventDefault?.();

            const nsr = document.querySelector("#deleteNumericSenderReference")?.value || 0;
            const asr = document.querySelector("#deletealphanumericSenderReference")?.value || "";
            const year = document.querySelector("#deleteYear")?.value || new Date().getFullYear();

            if (!nsr) {
                showErrorMessage("Specificare un riferimento numerico");
                return;
            }

            let req = "Eliminare la spedizione ";
            if (nsr) {
                req += `\ncon riferimento numerico ${nsr}`;
            }
            if (asr) {
                req += `\ncon riferimento alfanumerico ${asr}`;
            }
            if (year) {
                req += `\ndell'anno ${year}`;
            }
            req += "?";

            if (!confirm(req)) {
                return false;
            }

            const formData = new FormData();
            formData.append("ajax", 1);
            formData.append("action", "deleteRequest");
            formData.append("numericSenderReference", nsr);
            formData.append("alphanumericSenderReference", asr);
            formData.append("year", year);

            const endpoint = self.endpoint;

            const response = await fetch(endpoint, {
                method: "POST",
                body: formData,
            });

            const data = await response.json();

            const executionMessage = data.executionMessage;

            if (executionMessage.code < 0) {
                showErrorMessage(`
                    <h3>Errore ${executionMessage.code}</h3>
                    <p>Codice: ${executionMessage.codeDesc}</p>
                    <p>Messaggio: ${executionMessage.message}</p>
                `);
            } else if (executionMessage.code > 0) {
                showSuccessMessage(`
                    <h3>Attenzione ${executionMessage.code}</h3>
                    <p>Etichetta eliminata con avvisi</p>
                    <p>Codice: ${executionMessage.codeDesc}</p>
                    <p>Messaggio: ${executionMessage.message}</p>
                `);
            } else {
                showSuccessMessage("Etichetta eliminata");
            }
        };
    }
}

window.BrtLabelFormWrapper = BrtLabelFormWrapper;
