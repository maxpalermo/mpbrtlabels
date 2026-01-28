class Parcel {
    adminControllerUrl;
    parcelId;
    parcelCode;
    x = 0;
    y = 0;
    z = 0;
    weight = 0;
    volume = 0;

    constructor(adminControllerUrl, parcel = null) {
        this.adminControllerUrl = adminControllerUrl;
        this.init(parcel);
    }

    getEmptyRow(orderId, rowNumber) {
        const html = `
            <tr id="parcel-0">
                <td data-id="parcel-code" class="text-right">
                    <input type="hidden" name="parcel[id][]" value="">
                    <input class="form-control text-right input-row-code" type="text" name="parcel[code][]" value="${orderId}-${rowNumber}" readonly>
                </td>
                <td data-id="length" class="text-center">
                    <div class="input-group" style="max-width: 140px;">
                        <input class="form-control text-right input-row-x" type="number" step="0.1" name="parcel[length][]" value="0">
                        <span class="input-group-addon">cm</span>
                    </div>
                </td>
                <td data-id="height" class="text-center">
                    <div class="input-group" style="max-width: 140px;">
                        <input class="form-control text-right input-row-y" type="number" step="0.1" name="parcel[height][]" value="0">
                        <span class="input-group-addon">cm</span>
                    </div>
                </td>
                <td data-id="width" class="text-center">
                    <div class="input-group" style="max-width: 140px;">
                        <input class="form-control text-right input-row-z" type="number" step="0.1" name="parcel[width][]" value="0">
                        <span class="input-group-addon">cm</span>
                    </div>
                </td>
                <td data-id="weight" class="text-center">
                    <div class="input-group" style="max-width: 140px;">
                        <input class="form-control text-right input-row-weight" type="number" step="0.1" name="parcel[weight][]" value="0">
                        <span class="input-group-addon">kg</span>
                    </div>
                </td>
                <td data-id="volume" class="text-center">
                    <div class="input-group" style="max-width: 140px;">
                        <input class="form-control text-right input-row-volume" type="number" step="0.001" name="parcel[volume][]" value="0" readonly>
                        <span class="input-group-addon">m³</span>
                    </div>
                </td>
                <td class="text-center">
                    <div class="btn-group" style="min-width: 130px;">
                        <button type="button" class="btn btn-default" data-id="0" data-button-type="save-parcel" title="Salva collo">
                            <i class="material-icons text-success">save</i>
                        </button>
                        <button type="button" class="btn btn-default" data-id="0" data-button-type="remove-parcel" title="Rimuovi collo">
                            <i class="material-icons text-danger">delete</i>
                        </button>
                    </div>
                </td>
            </tr>
        `;

        const template = document.createElement("template");
        template.innerHTML = html;

        const tr = template.content.querySelector("tr");
        return tr;
    }

    init(parcel) {
        if (!parcel) {
            return;
        }

        if (parcel.id_brt_labels_parcel) {
            this.parcelId = parcel.id_brt_labels_parcel;
        }
        if (parcel.parcelId) {
            this.parcelId = parcel.parcelId;
        }
        this.parcelCode = parcel.PECOD;
        this.x = parcel.X / 10;
        this.y = parcel.Y / 10;
        this.z = parcel.Z / 10;
        this.weight = parcel.PPESO;
        this.volume = parcel.PVOLU;
    }

    async fetchParcelData(parcelId) {
        const self = this;
        const formData = new FormData();
        formData.append("action", "fetchParcel");
        formData.append("ajax", 1);
        formData.append("parcelId", parcelId);

        try {
            const response = await fetch(`${this.adminControllerUrl}`, {
                method: "POST",
                body: formData,
            });
            if (!response.ok) {
                throw new Error("Errore nella risposta del server");
            }

            const data = await response.json();
            if (data.error) {
                console.error("Errore nel recupero dei dati del collo:", data.error);
                return null;
            }

            self.init(data.parcel);

            return self;
        } catch (error) {
            console.error("Errore nella richiesta dei dati del collo:", error);
            return null;
        }
    }

    compile() {
        const self = this;
        const tr = self.getEmptyRow();

        const parcelId = tr.querySelector("[data-id='parcel-code'] input[type='hidden']");
        const parcelCode = tr.querySelector("[data-id='parcel-code'] input[type='text']");
        const length = tr.querySelector("[data-id='length'] input");
        const height = tr.querySelector("[data-id='height'] input");
        const width = tr.querySelector("[data-id='width'] input");
        const weight = tr.querySelector("[data-id='weight'] input");
        const volume = tr.querySelector("[data-id='volume'] input");
        const btnRemove = tr.querySelector("[data-button-type='remove-parcel']");
        const btnSave = tr.querySelector("[data-button-type='save-parcel']");

        self.calc();

        tr.id = `parcel-${self.parcelId}`;
        parcelId.value = self.parcelId;
        parcelCode.value = self.parcelCode;
        length.value = self.x;
        height.value = self.y;
        width.value = self.z;
        weight.value = self.weight;
        volume.value = self.volume;
        btnRemove.setAttribute("data-id", self.parcelId);
        btnSave.setAttribute("data-id", self.parcelId);

        return tr;
    }

    calc() {
        const self = this;
        self.weight = Number(self.weight).toFixed(1);
        self.volume = Number((self.x * self.y * self.z) / 1000000).toFixed(3);
        parcels_recalc();
    }

    async update() {
        const self = this;
        const formData = new FormData();
        Object.entries(self).forEach(([key, value]) => {
            if (value === undefined || value === null) return;
            formData.append(key, String(value));
        });
        formData.append("action", "updateParcel");
        formData.append("ajax", 1);

        const response = await fetch(self.adminControllerUrl, {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            showErrorMessage("Salvataggio collo fallito");
            return false;
        }

        const data = await response.json();
        if (data.success) {
            showNoticeMessage(`Collo ${self.parcelCode} aggiornato`);
            return true;
        }

        showErrorMessage(data.errors.join("\n"));
        return false;
    }

    async remove(parcelId) {
        const self = this;
        const table = document.getElementById("tableParcels");
        if (!table) {
            showErrorMessage("Tabella colli non trovata");
            return false;
        }

        const tr = document.getElementById(`parcel-${parcelId}`);

        if (!tr) {
            showErrorMessage(`Collo ${parcelId} non trovato`);
            return false;
        }

        const formData = new FormData();
        formData.append("ajax", 1);
        formData.append("action", "removeParcel");
        formData.append("parcelId", parcelId);

        const response = await fetch(self.adminControllerUrl, {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            showErrorMessage("Errore durante la chiamata alla funzione <strong>removeParcel</strong>");
            return false;
        }
        const data = await response.json();
        if (!data.success) {
            showErrorMessage(data.errors.join("\n"));
            return false;
        }
        tr.remove();
        showNoticeMessage(`Collo ${parcelId} rimosso`);
        return true;
    }

    getWeightKG() {
        return Number(this.weight).toFixed(1);
    }

    getVolumeM3() {
        return Number(this.volume).toFixed(3);
    }
}
