class BrtLabelDialogManager {
    dialog = null;
    btnShowLastError = null;
    btnPrintLabel = null;
    btnSendRequest = null;
    btnShowDialog = null;
    btnCloseDialog = null;
    btnShowRequestData = null;
    btnAddParcel = null;
    btnReadOrder = null;
    btnDeleteLabel = null;

    lastBrtError = {
        code: 0,
        codeDesc: "",
        message: "",
    };

    tableParcels = null;
    rows = null;
    mergePdf = null;
    form = null;

    endpoint = null;
    orderId = null;

    requestData = null;

    constructor(endpoint, orderId) {
        this.dialog = document.getElementById("dlg-brt-request");
        this.endpoint = endpoint;
        this.orderId = orderId;

        this.btnShowLastError = document.getElementById("btnShowLastError");
        this.btnPrintLabel = document.getElementById("btnPrintLabel");
        this.btnSendRequest = document.getElementById("btnSendRequest");
        this.btnShowDialog = document.getElementById("btnShowBrtDialog");
        this.btnCloseDialog = document.getElementById("btnCloseDialog");
        this.btnShowRequestData = document.getElementById("btnShowRequestData");
        this.btnAddParcel = document.getElementById("btnAddParcel");
        this.btnReadOrder = document.getElementById("btnReadOrder");
        this.btnDeleteLabel = document.getElementById("btnDeleteLabel");

        this.tableParcels = document.getElementById("tableParcels");
        this.form = document.getElementById("form-brt-request");

        this.bindOnClick(this.btnShowLastError, this.onShowLastError);
        this.bindOnClick(this.btnPrintLabel, this.onPrintLabel);
        this.bindOnClick(this.btnSendRequest, this.onSendRequest);
        this.bindOnClick(this.btnShowDialog, this.show);
        this.bindOnClick(this.btnCloseDialog, this.hide);
        this.bindOnClick(this.btnShowRequestData, this.onShowRequestData);
        this.bindOnClick(this.btnAddParcel, this.onAddParcel);
        this.bindOnClick(this.btnReadOrder, this.onReadOrder);
        this.bindOnClick(this.btnDeleteLabel, this.onDeleteLabel);
    }

    moveGrowlContainer(parent = null) {
        const self = this;
        let growlId = "growls-default";

        if (typeof MPBRTLABELS_PAGE !== "undefined") {
            growlId = "growls";
        }

        let growlContainer = document.getElementById(growlId);

        if (growlContainer) {
            growlContainer.remove();
            growlContainer = null;
        }

        if (!growlContainer) {
            growlContainer = document.createElement("div");
            growlContainer.id = growlId;
        }

        if (parent) {
            parent.appendChild(growlContainer);
        } else {
            document.body.appendChild(growlContainer);
        }

        return growlContainer;
    }

    refreshRows() {
        const self = this;
        const rows = this.tableParcels.querySelectorAll("tbody tr");
        if (rows) {
            self.rows = rows;
        }
    }

    getDialog() {
        if (!this.dialog) {
            this.dialog = document.getElementById("dlg-brt-request");
        }

        return this.dialog;
    }

    getEndpoint() {
        return this.endpoint;
    }

    getOrderId() {
        return this.orderId;
    }

    showHideButtons(showBtn, hideBtn) {
        if (!showBtn || !hideBtn) {
            showErrorMessage("Pulsanti non trovati");
            return;
        }
        $(showBtn).show();
        $(hideBtn).hide();
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

    show() {
        const self = this;
        return async () => {
            const dialog = self.dialog;
            if (!dialog || typeof dialog.showModal !== "function") {
                console.error("Dialog non trovato o showModal non disponibile", dialog);
                return;
            }

            self.moveGrowlContainer(self.dialog);

            if (typeof MPBRTLABELS_PAGE === "undefined") {
                let orderIdEl = self.dialog.querySelector("#order_id");

                if (!orderIdEl) {
                    orderIdEl = self.dialog.querySelector("#numericSenderReference");
                }

                if (!orderIdEl) {
                    showErrorMessage("ID ordine non trovato");
                    return;
                }

                self.orderId = Number(orderIdEl.value);

                //Ripristino il bottone INVIO
                self.showHideButtons(self.btnPrintLabel, self.btnSendRequest);
                //Cancello la tabella dei colli
                self.clear();

                await self.readOrder();
                this.bindOnClick(this.btnPrintLabel, this.onPrintLabel);
            }

            dialog.showModal();
        };
    }

    hide() {
        const self = this;
        return () => {
            const dialog = self.dialog;
            if (!dialog || typeof dialog.close !== "function") {
                console.error("Dialog non trovato o close non disponibile", dialog);
                return;
            }

            self.moveGrowlContainer();

            dialog.close();
        };
    }

    clear() {
        const self = this;
        if (self.form) {
            self.form.reset();
        }

        if (self.tableParcels) {
            self.tableParcels.querySelector("tbody").innerHTML = "";
        }
    }

    onPrintLabel() {
        const self = this;

        return async (e) => {
            e.preventDefault();
            e.stopPropagation();

            if (self.mergePdf) {
                showSuccessMessage("Apertura PDF in corso...");
                await self.mergePdf.open();
            } else {
                showErrorMessage("Errore durante l'apertura del PDF");
            }
        };
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
        };
    }

    onSendRequest(showRequestData = false) {
        const self = this;
        return async (e) => {
            e?.preventDefault?.();
            const tableParcels = document.getElementById("tableParcels");

            if (!tableParcels) {
                showErrorMessage("Tabella pacchi non trovata");
                return;
            }

            const totColli = Number(tableParcels.querySelectorAll("tbody tr").length);

            if (totColli == 0) {
                showErrorMessage("Inserisci almeno un collo");
                return;
            }

            const form = document.getElementById("form-brt-request");
            const formData = new FormData(form);
            formData.append("ajax", 1);
            formData.append("action", "sendRequest");
            formData.append("showRequest", showRequestData ? 1 : 0);
            formData.append("orderId", self.orderId);

            const endpoint = self.endpoint;

            const response = await fetch(endpoint, {
                method: "POST",
                body: formData,
            });

            if (!response.ok) {
                showErrorMessage("Errore durante l'invio della richiesta");
                return;
            }

            const data = await response.json();

            if ("showRequestData" in data && data.showRequestData == 1) {
                delete data.showRequestData;
                self.requestData = data;

                return;
            }

            if ("status" in data && data.status == true) {
                const numericSenderReference = data.numericSenderReference;
                const responseTime = data.responseTime;
                const responseData = data.responseData;
                const executionMessage = data.executionMessage;
                const labels = data.labels;

                self.lastBrtError.code = executionMessage.code;
                self.lastBrtError.codeDesc = executionMessage.codeDesc;
                self.lastBrtError.message = executionMessage.message;

                if (executionMessage.code == 0) {
                    showSuccessMessage("Richiesta inviata con successo");
                    //Refresh Etichetta
                    await self.onReadOrder();
                    //Stampa etichetta
                    setTimeout(async () => {
                        if (self.mergePdf) {
                            console.log("Stampa etichetta");
                            await self.mergePdf.open();
                        }
                    }, 1000);
                } else if (executionMessage.code > 0) {
                    showSuccessMessage(`
                    <p>Etichetta inviata. Attenzione:</p>
                    <strong>Errore ${executionMessage.code}</strong>
                    <p>${executionMessage.codeDesc}<p>
                    <p>${executionMessage.message}<p>
                `);
                } else {
                    showErrorMessage(`
                    <strong>Errore ${executionMessage.code}</strong>
                    <p>${executionMessage.codeDesc}<p>
                    <p>${executionMessage.message}<p>
                `);
                }
            } else {
                showErrorMessage("Errore sconosciuto");
            }
        };
    }

    async readOrder() {
        const self = this;
        if (!self.orderId) {
            const orderIdEl = self.dialog.querySelector("#order_id");
            if (orderIdEl) {
                self.orderId = orderIdEl.value;
            } else {
                showErrorMessage("ID ordine non trovato");
                return;
            }
        }
        const orderId = self.orderId;
        const formData = new FormData();
        formData.append("action", "readOrderRequestParameters");
        formData.append("ajax", 1);
        formData.append("orderId", orderId);

        const response = await fetch(self.endpoint, {
            method: "POST",
            body: formData,
        });

        const data = await response.json();
        if (data.error) {
            showErrorMessage(data.error);
            return;
        }

        self.requestData = data.params;
        self.fillLabelForm(self.requestData);

        showSuccessMessage("Ordine caricato");
    }

    onAddParcel() {
        const self = this;
        return (e) => {
            e.preventDefault();

            const endpoint = self.endpoint;
            const table = self.tableParcels;
            const parcel = new Parcel(endpoint);
            const numericSenderReference = document.getElementById("numericSenderReference").value;
            const row = table.querySelector("tbody tr:last-child");
            let code = "";
            if (!row) {
                if (!numericSenderReference) {
                    showErrorMessage("Campo <strong>Riferimento numerico</strong> non valido");
                    return;
                }
                code = numericSenderReference + "-0";
            } else {
                code = row.querySelector(".input-row-code").value;
            }
            const rowNumber = Number(code.split("-").pop());

            table.querySelector("tbody").appendChild(parcel.getEmptyRow(numericSenderReference, rowNumber + 1));
            showNoticeMessage("Riga colli aggiunta");

            self.onInputActions();
            self.parcelsRecalc();
        };
    }

    onShowLastError() {
        const self = this;
        return (e) => {
            e.preventDefault();

            if (self.lastBrtError.code !== 0) {
                if (self.lastBrtError.code < 0) {
                    showErrorMessage(`
                            <strong>${self.lastBrtError.code}</strong>
                            <p>${self.lastBrtError.codeDesc}</p>
                            <p>${self.lastBrtError.message}</p>
                        `);
                } else {
                    showNoticeMessage(`
                            <strong>ATTENZIONE</strong>
                            <strong>${self.lastBrtError.code}</strong>
                            <p>${self.lastBrtError.codeDesc}</p>
                            <p>${self.lastBrtError.message}</p>
                        `);
                }

                return;
            }

            showNoticeMessage("Nessun errore da mostrare");
        };
    }

    onShowRequestData() {
        const self = this;
        let parcels = null;

        const css = `
            .growl-extra-large {
                width: 500px !important;
                font-size: 18px !important;
                padding: 25px !important;
            }

            .growl-close {
                top: 16px !important;

            }
        `;

        const cssEl = document.createElement("style");
        cssEl.id = "css-growl";
        cssEl.textContent = css;
        cssEl.setAttribute("type", "text/css");
        cssEl.setAttribute("data-growl", "true");

        const existsEl = document.getElementById("css-growl");
        if (existsEl) {
            existsEl.remove();
        }

        document.head.appendChild(cssEl);

        return async (e) => {
            e?.preventDefault?.();

            if (!confirm("Inviare l'etichetta a Bartolini?")) {
                return false;
            }

            const sendRequestHandler = self.onSendRequest(true);
            await sendRequestHandler(e);

            const requestData = self.requestData;
            if (!requestData) {
                showErrorMessage("Nessun parametro da mostrare");
                console.error(requestData);
                return;
            }

            if ("labels" in requestData) {
                delete requestData.labels;
            }

            if ("labelExists" in requestData) {
                delete requestData.labelExists;
            }

            if ("parcels" in requestData) {
                parcels = requestData.parcels;
                delete requestData.parcels;
            }

            if ("success" in requestData) {
                delete requestData.success;
            }

            let output = "";

            if (requestData) {
                output += `<strong>Parametri</strong><pre style="max-height: 300px; overflow-y: auto; overflow-x: hidden;">${JSON.stringify(requestData, null, 2)}</pre>`;
            }

            if (parcels) {
                output += `<strong>Colli</strong><pre style="max-height: 300px; overflow-y: auto; overflow-x: hidden;">${JSON.stringify(parcels, null, 2)}</pre>`;
            }

            $.growl.notice({
                title: "Dati Richiesta BRT",
                message: output,
                fixed: true,
                duration: 100000,
                size: "extra-large",
            });
        };
    }

    onReadOrder() {
        const self = this;

        return async (e) => {
            console.log("Lettura dati richiesta ordine");

            e.preventDefault();

            let orderIdEl = self.dialog.querySelector("#order_id");

            if (!orderIdEl) {
                orderIdEl = self.dialog.querySelector("#numericSenderReference");
            }

            if (!orderIdEl) {
                showErrorMessage("ID ordine non trovato");
                return;
            }

            self.orderId = Number(orderIdEl.value);

            //Ripristino il bottone INVIO
            self.showHideButtons(self.btnSendRequest, self.btnPrintLabel);
            //Cancello la tabella dei colli
            self.clear();

            await self.readOrder();
        };
    }

    onInputActions() {
        const self = this;
        self.refreshRows();
        const rows = self.rows;

        if (!rows) {
            showErrorMessage("Nessuna riga trovata");
            return;
        }

        rows.forEach((row) => {
            const inputs = row.querySelectorAll("input");
            console.log("INPUTS", inputs);

            if (inputs) {
                inputs.forEach((input) => {
                    input._onFocus ??= self.onRowInputFocus(input);
                    input._onInput ??= self.onRowInputDigit(row);
                    input._onBlur ??= self.onRowInputBlur();

                    input.removeEventListener("focus", input._onFocus);
                    input.addEventListener("focus", input._onFocus);

                    input.removeEventListener("input", input._onInput);
                    input.addEventListener("input", input._onInput);

                    input.removeEventListener("blur", input._onBlur);
                    input.addEventListener("blur", input._onBlur);
                });
            }

            const btnsSaveRow = row.querySelectorAll("button[data-button-type='saveParcel']");
            const btnsRemoveRow = row.querySelectorAll("button[data-button-type='removeParcel']");

            if (btnsSaveRow) {
                btnsSaveRow.forEach(async (btn) => {
                    btn._onSaveRowClick ??= self.onSaveRowClick();
                    btn.removeEventListener("click", await btn._onSaveRowClick);
                    btn.addEventListener("click", await btn._onSaveRowClick);
                });
            }

            if (btnsRemoveRow) {
                btnsRemoveRow.forEach(async (btn) => {
                    btn._onRemoveRowClick ??= self.onRemoveRowClick();
                    btn.removeEventListener("click", await btn._onRemoveRowClick);
                    btn.addEventListener("click", await btn._onRemoveRowClick);
                });
            }
        });
    }

    onRowInputFocus(input) {
        return function () {
            input.select();
        };
    }

    onRowInputDigit(row) {
        const self = this;

        return function () {
            const x = row.querySelector(".input-row-x").value;
            const y = row.querySelector(".input-row-y").value;
            const z = row.querySelector(".input-row-z").value;
            const weightElement = row.querySelector(".input-row-weight");
            const volumeElement = row.querySelector(".input-row-volume");

            const volume = Number((x * y * z) / 1000000).toFixed(3);

            if (isNaN(volume) || volume == 0) {
                volumeElement.value = "0.001";
            } else {
                volumeElement.value = volume;
            }

            if (isNaN(weightElement.value) || weightElement.value == 0) {
                weightElement.value = "0.001";
            } else {
                weightElement.value = weightElement.value;
            }

            self.parcelsRecalc();
        };
    }

    onRowInputBlur() {
        const self = this;

        return function (e) {
            if (e.target.classList.contains("input-row-weight")) {
                if (e.target.value == "" || isNaN(e.target.value)) {
                    e.target.value = "0.0";
                }
            }
            if (e.target.classList.contains("input-row-volume")) {
                if (e.target.value == "" || isNaN(e.target.value)) {
                    e.target.value = "0.000";
                }
            }
        };
    }

    async onSaveRowClick() {
        const self = this;

        return async function (e) {
            if (!confirm("Salvare le misure del collo?")) {
                return false;
            }

            e.preventDefault();
            e.stopPropagation();

            const btn = e.currentTarget;
            const row = btn.closest("tr");
            const id = Number(row.getAttribute("id").replace("parcel-", ""));

            const parcelData = {
                id_brt_labels_parcel: id,
                parcelId: id,
                PECOD: row.querySelector(".input-row-code").value,
                X: Number(row.querySelector(".input-row-x").value) * 10,
                Y: Number(row.querySelector(".input-row-y").value) * 10,
                Z: Number(row.querySelector(".input-row-z").value) * 10,
                PPESO: Number(row.querySelector(".input-row-weight").value),
                PVOLU: Number(row.querySelector(".input-row-volume").value),
            };

            const endpoint = self.endpoint;

            const parcel = new Parcel(endpoint, parcelData);
            await parcel.update();
        };
    }

    async onRemoveRowClick() {
        const self = this;

        return async function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (!confirm("Rimuovere il collo?")) {
                return false;
            }

            const btn = e.currentTarget;
            const parcelId = btn.getAttribute("data-id");
            const endpoint = self.endpoint;
            const parcel = new Parcel(endpoint);
            await parcel.remove(parcelId);

            self.parcelsRecalc();

            showNoticeMessage(`Collo ${parcelId} rimosso`);
        };
    }

    parcelsRecalc() {
        const self = this;
        self.refreshRows();

        let total = 0;
        let weight = 0;
        let volume = 0;

        if (self.rows) {
            self.rows.forEach((row) => {
                console.log(row);
                total++;
                const weightRow = row.querySelector(".input-row-weight");
                const volumeRow = row.querySelector(".input-row-volume");

                if (isNaN(weightRow.value) || weightRow.value == 0) {
                    weightRow.value = "1";
                }

                if (isNaN(volumeRow.value) || volumeRow.value == 0) {
                    volumeRow.value = "0.001";
                }

                weight += Number(weightRow.value);
                volume += Number(volumeRow.value);
            });
        }

        console.log("parcel recalc:", total, weight, volume);

        if (weight == 0) {
            weight = 1.0;
        }

        if (volume == 0) {
            volume = 0.001;
        }

        document.querySelector("#numberOfParcels").value = total;
        document.querySelector("#weightKG").value = Number(weight).toFixed(1);
        document.querySelector("#volumeM3").value = Number(volume).toFixed(3);
    }

    clearParcelsList() {
        const self = this;
        if (!self.tableParcels) {
            showErrorMessage("Tabella dei colli non trovata");
            return;
        }

        self.tableParcels.querySelector("tbody").innerHTML = "";
    }

    fillLabelForm(params) {
        const self = this;
        const elements = document.querySelectorAll("[name^=createData]");
        if (!elements) {
            showErrorMessage("Elementi del form etichetta non trovati.");
            return;
        }

        elements.forEach((element) => {
            const name = element.name.replace("createData[", "").replace("]", "");
            if (name == "isCODMandatory") {
                const value = Number(params[name]) == 1 ? "1" : "0";
                const switchElement = document.getElementById("isCODMandatory_" + value);
                if (switchElement) {
                    switchElement.checked = true;
                }
            } else {
                element.value = params[name];
            }
        });

        if (params != false && "parcels" in params && params.parcels.length > 0) {
            console.table(params.parcels);
            const tableParcels = self.tableParcels;
            const tbody = tableParcels.querySelector("tbody");
            if (tbody) tbody.replaceChildren(); // rimuove tutti i figli

            let weightKG = 0;
            let volumeM3 = 0;

            params.parcels.forEach((parcel) => {
                const pkg = new Parcel(self.endpoint, parcel);
                tbody.appendChild(pkg.compile());

                weightKG += Number(pkg.getWeightKG());
                volumeM3 += Number(pkg.getVolumeM3());
            });

            document.querySelector('[name="createData[numberOfParcels]"]').value = params.parcels.length;
            document.querySelector('[name="createData[weightKG]"]').value = Number(weightKG).toFixed(1);
            document.querySelector('[name="createData[volumeM3]"]').value = Number(volumeM3).toFixed(3);

            if (params.labelExists !== false) {
                self.showHideButtons(self.btnPrintLabel, self.btnSendRequest);

                const labels = [];
                params.labels.forEach((label) => {
                    labels.push(label.stream);
                });

                self.mergePdf = new PdfMerger(labels);
                self.bindOnClick(self.btnPrintLabel, self.onPrintLabel());
            }

            self.onInputActions();
        } else {
            const totColli = document.querySelector('[name="createData[numberOfParcels]"]');
            const weightKG = document.querySelector('[name="createData[weightKG]"]');
            const volumeM3 = document.querySelector('[name="createData[volumeM3]"]');

            if (totColli) {
                totColli.value = "1";
            }
            if (weightKG) {
                weightKG.value = "1";
            }
            if (volumeM3) {
                volumeM3.value = "0.001";
            }
        }
    }
}
