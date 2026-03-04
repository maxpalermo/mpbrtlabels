class BindBtnAddParcel {
    endpoint = null;
    table = null;
    tbody = null;
    rows = null;
    btnAddParcel = null;

    constructor(endpoint, button) {
        this.endpoint = endpoint;
        this.btnAddParcel = button;
        this.table = document.getElementById("tableParcels");
        if (!this.table) {
            showErrorMessage("Tabella parcelle non trovata");
            return;
        }

        this.tbody = this.table.querySelector("tbody");
        if (!this.tbody) {
            showErrorMessage("TBODY della tabella parcelle non trovata");
            return;
        }

        this.rows = this.tbody.querySelectorAll("tr");
        if (!this.rows) {
            showErrorMessage("Nessuna riga di tabella parcelle trovata");
            return;
        }
    }

    refreshRows() {
        this.rows = this.tbody.querySelectorAll("tr");
        if (!this.rows) {
            showErrorMessage("Nessuna riga di tabella parcelle trovata");
            return;
        }
    }

    onAddParcel() {
        const self = this;
        return (e) => {
            e.preventDefault();

            const endpoint = self.endpoint;
            const table = self.table;
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

    onInputActions() {
        const self = this;

        self.rows.forEach((row) => {
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
}
