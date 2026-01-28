const lastBrtError = {
    code: 0,
    codeDesc: "",
    message: "",
};

function dateToItalian(dateISO) {
    if (!dateISO) return "";

    if (dateISO == "0000-00-00" || dateISO == "0000-00-00 00:00:00") {
        return "--";
    }

    const date = new Date(dateISO);
    if (isNaN(date.getTime())) return dateISO;

    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const year = date.getFullYear();

    return `${day}/${month}/${year}`;
}

function fixBootStrapTableIcons() {
    const bsTableDiv = document.querySelector(".bootstrap-table");
    if (!bsTableDiv) {
        return false;
    }
    const iconRefresh = bsTableDiv.querySelector(".icon-refresh-cw");
    if (iconRefresh) {
        iconRefresh.classList.remove("icon-refresh-cw");
        iconRefresh.classList.add("icon-refresh");
    }

    const btnHideShow = document.getElementsByName("filterControlSwitch");
    if (btnHideShow) {
        btnHideShow.forEach((btn) => {
            btn.innerHTML = "";
            btn.appendChild(document.createElement("i")).classList.add("icon-eye-slash");
        });
    }
}

function fixDropDownMenuPagination() {
    $(".fixed-table-pagination .dropdown-toggle")
        .off("click")
        .on("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            const $btn = $(this);
            const $menu = $btn.closest(".btn-group").find(".dropdown-menu");

            $(".fixed-table-pagination .dropdown-menu").not($menu).removeClass("show");
            $menu.toggleClass("show");
        });

    // Normalizza il markup del dropdown page-size a Bootstrap 3
    $(".fixed-table-pagination .btn-group.dropdown").each(function () {
        var $group = $(this);
        var $menuDiv = $group.find("> .dropdown-menu");

        if ($menuDiv.length) {
            // Se non è già <ul>, converti
            if ($menuDiv.prop("tagName") !== "UL") {
                var $ul = $('<ul class="dropdown-menu" role="menu"></ul>');

                $menuDiv.find("a").each(function () {
                    var $a = $(this);
                    var $li = $("<li></li>");
                    $a.removeClass("dropdown-item"); // classe BS4/5 inutile qui
                    $li.append($a);
                    $ul.append($li);
                });

                $menuDiv.replaceWith($ul);
            }
        }

        // Assicura data-toggle (non data-bs-toggle) e inizializza il plugin
        var $btn = $group.find("> .dropdown-toggle");
        if ($btn.attr("data-bs-toggle") === "dropdown") {
            $btn.removeAttr("data-bs-toggle").attr("data-toggle", "dropdown");
        }
        if (typeof $.fn.dropdown === "function") {
            $btn.dropdown();
        }
    });

    $(document)
        .off("click.bs-table-page-size")
        .on("click.bs-table-page-size", function () {
            $(".fixed-table-pagination .dropdown-menu").removeClass("show");
        });
}

function bindBtnImportOrder() {
    const btn = document.getElementById("btn-import-order");
    if (!btn) {
        showErrorMessage("Pulsante di importazione ordine non trovato");
        return;
    }

    btn.addEventListener("click", async (e) => {
        e.preventDefault();
        const orderId = document.getElementById("order_id").value;
        const formData = new FormData();
        formData.append("action", "importOrderForLabel");
        formData.append("ajax", 1);
        formData.append("orderId", orderId);

        const response = await fetch(adminControllerUrl, {
            method: "POST",
            body: formData,
        });

        const data = await response.json();
        if (data.error) {
            showErrorMessage(data.error);
            return;
        }

        fillLabelForm(data.params);

        showSuccessMessage("Ordine caricato");
    });
}

function fillLabelForm(params) {
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

    if (params.parcels.length > 0) {
        const tableParcels = document.getElementById("tableParcels");
        const tbody = tableParcels.querySelector("tbody");
        if (tbody) tbody.replaceChildren(); // rimuove tutti i figli

        let weightKG = 0;
        let volumeM3 = 0;

        params.parcels.forEach((parcel) => {
            const pkg = new Parcel(adminControllerUrl, parcel);
            tbody.appendChild(pkg.compile());

            weightKG += Number(pkg.getWeightKG());
            volumeM3 += Number(pkg.getVolumeM3());
        });

        document.querySelector('[name="createData[numberOfParcels]"]').value = params.parcels.length;
        document.querySelector('[name="createData[weightKG]"]').value = Number(weightKG).toFixed(1);
        document.querySelector('[name="createData[volumeM3]"]').value = Number(volumeM3).toFixed(3);

        if (params.labelExists !== false) {
            $(document.getElementById("submitCreateRequest")).hide();
            $(document.getElementById("submitPrintLabels")).show();

            const labels = [];
            params.labels.forEach((label) => {
                labels.push(label.stream);
            });

            mergePdf = new PdfMerger(labels);
            bindSubmitPrintLabels();
        }

        bindTableInputRows();
    }
}

function bindBtnAddParcel() {
    const btn = document.getElementById("btn-add-parcel");
    if (btn) {
        btn.addEventListener("click", (e) => {
            e.preventDefault();

            const table = document.getElementById("tableParcels");
            const parcel = new Parcel(adminControllerUrl);
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
            bindTableInputRows();
        });
    }
}

function bindTableInputRows() {
    console.log("bindTableInputRows");

    const table = document.getElementById("tableParcels");
    const tbody = table.querySelector("tbody");
    const rows = tbody.querySelectorAll("tr");

    rows.forEach((row) => {
        const inputs = row.querySelectorAll("input");

        inputs.forEach((input) => {
            input.addEventListener("focus", () => {
                input.select();
            });

            input.addEventListener("input", (e) => {
                const x = row.querySelector(".input-row-x").value;
                const y = row.querySelector(".input-row-y").value;
                const z = row.querySelector(".input-row-z").value;
                const weightElement = row.querySelector(".input-row-weight");
                const volumeElement = row.querySelector(".input-row-volume");

                const volume = Number((x * y * z) / 1000000).toFixed(3);

                if (isNaN(volume)) {
                    volumeElement.value = "0.000";
                } else {
                    volumeElement.value = volume;
                }

                parcels_recalc();
            });

            input.addEventListener("blur", (e) => {
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
            });
        });

        const btnsSaveRow = row.querySelectorAll("button[data-button-type='save-parcel']");
        const btnsRemoveRow = row.querySelectorAll("button[data-button-type='remove-parcel']");

        btnsSaveRow.forEach((btn) => {
            btn.addEventListener("click", async (e) => {
                e.preventDefault();
                e.stopPropagation();

                if (!confirm("Salvare le misure del collo?")) {
                    return false;
                }

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
                console.log(parcelData);

                const parcel = new Parcel(adminControllerUrl, parcelData);
                await parcel.update();
            });
        });

        btnsRemoveRow.forEach((btn) => {
            btn.addEventListener("click", async (e) => {
                e.preventDefault();
                e.stopPropagation();

                if (!confirm("Rimuovere il collo?")) {
                    return false;
                }

                const parcelId = btn.getAttribute("data-id");
                const parcel = new Parcel(adminControllerUrl);
                await parcel.remove(parcelId);

                parcels_recalc();

                showNoticeMessage(`Collo ${parcelId} rimosso`);
            });
        });
    });
}

function parcels_recalc() {
    const rows = document.querySelectorAll("#tableParcels tbody tr");
    let total = 0;
    let weight = 0;
    let volume = 0;

    if (rows) {
        rows.forEach((row) => {
            console.log(row);
            total++;
            const weightRow = row.querySelector(".input-row-weight");
            const volumeRow = row.querySelector(".input-row-volume");
            weight += Number(weightRow.value);
            volume += Number(volumeRow.value);
        });
    }

    console.log(total, weight, volume);

    document.querySelector("#numberOfParcels").value = total;
    document.querySelector("#weightKG").value = Number(weight).toFixed(1);
    document.querySelector("#volumeM3").value = Number(volume).toFixed(3);
}

function bindShowLastError() {
    const btn = document.getElementById("showLastError");
    if (btn) {
        btn.addEventListener("click", (e) => {
            e.preventDefault();

            if (lastBrtError.code !== 0) {
                if (lastBrtError.code < 0) {
                    showErrorMessage(`
                        <strong>${lastBrtError.code}</strong>
                        <p>${lastBrtError.codeDesc}</p>
                        <p>${lastBrtError.message}</p>
                    `);
                } else {
                    showNoticeMessage(`
                        <strong>ATTENZIONE</strong>
                        <strong>${lastBrtError.code}</strong>
                        <p>${lastBrtError.codeDesc}</p>
                        <p>${lastBrtError.message}</p>
                    `);
                }

                return;
            }

            showNoticeMessage("Nessun errore da mostrare");
        });
    }
}

async function bindSubmitCreateRequest() {
    const btnSendRequest = document.getElementById("submitCreateRequest");
    if (!btnSendRequest) {
        showErrorMessage("Pulsante di invio richiesta non trovato");
        return;
    }
    btnSendRequest.addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (!confirm("Inviare l'etichetta a Bartolini?")) {
            return false;
        }

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

        const response = await fetch(adminControllerUrl, {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            showErrorMessage("Errore durante l'invio della richiesta");
            return;
        }

        const data = await response.json();

        if (data.status == true) {
            const numericSenderReference = data.numericSenderReference;
            const responseTime = data.responseTime;
            const responseData = data.responseData;
            const executionMessage = data.executionMessage;
            const labels = data.labels;

            lastBrtError.code = executionMessage.code;
            lastBrtError.codeDesc = executionMessage.codeDesc;
            lastBrtError.message = executionMessage.message;

            if (executionMessage.code == 0) {
                showSuccessMessage("Richiesta inviata con successo");
                //Refresh Etichetta
                await document.getElementById("btn-import-order").click();
                //Stampa etichetta
                console.log("Stampa etichetta");
                setTimeout(() => {
                    document.getElementById("submitPrintLabels").click();
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
            showErrorMessage(data.error);
        }
    });
}

async function bindSubmitPrintLabels() {
    if (mergePdf) {
        const btnPrint = document.getElementById("submitPrintLabels");
        if (btnPrint) {
            btnPrint.addEventListener("click", async (e) => {
                e.preventDefault();

                await mergePdf.open();
            });
        }
    }
}

function bindDialog() {
    const dialog = document.getElementById("dlg-brt-request");
    const btnCloseDialog = document.getElementById("btn-close-brt-dialog");
    const btnOpenDialog = document.getElementById("btn-open-brt-dialog");
    const btnDeleteBrtLabel = document.getElementById("btn-delete-brt-label");

    if (!dialog) {
        showErrorMessage("Dialog non trovato");
        return;
    }

    if (btnCloseDialog) {
        btnCloseDialog.addEventListener("click", () => {
            dialog.close();
        });
    }

    if (btnOpenDialog && dialog && typeof dialog.showModal === "function") {
        btnOpenDialog.addEventListener("click", () => {
            showNoticeMessage("Creazione nuova etichetta.");

            dialog.addEventListener("close", (e) => {
                e.preventDefault();
                const growlElement = document.getElementById("growls");
                if (growlElement) {
                    growlElement.remove();
                }
            });

            dialog.addEventListener("cancel", (e) => {
                e.preventDefault(); // blocca la chiusura via ESC
                return false;
            });

            dialog.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                console.log("dlg clicked", e.target.id === dialog.id);

                if (e.target.id === dialog.id) {
                    return false;
                }
            });

            dialog.showModal();

            const growlElement = document.getElementById("growls");
            if (growlElement) {
                document.getElementById("growls").remove();
                dialog.appendChild(growlElement);
                setTimeout(() => {
                    const growlMessage = growlElement.querySelector(".growl");
                    if (growlMessage) {
                        growlMessage.remove();
                    }
                }, 0);
            }

            clearDialogForm();
            bindBtnAddParcel();
        });
    }

    if (btnDeleteBrtLabel) {
        btnDeleteBrtLabel.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const id = document.getElementById("deleteNumericSenderReference").value;
            const alphanumericId = document.getElementById("deleteAlphanumericSenderReference").value;

            if (!confirm("Eliminare l'etichetta con riferimento numerico " + id + " e alfanumerico " + alphanumericId + "?")) {
                return false;
            }

            const formData = new FormData();
            formData.append("ajax", 1);
            formData.append("action", "deleteRequest");
            formData.append("numericSenderReference", id);
            formData.append("alphanumericSenderReference", alphanumericId);

            const response = await fetch(adminControllerUrl, {
                method: "POST",
                body: formData,
            });

            const data = await response.json();

            if (data.success == true) {
                showSuccessMessage("Etichetta eliminata");
            } else {
                showErrorMessage(data.error);
            }
        });
    }
}

function clearDialogForm() {
    const form = document.getElementById("form-brt-request");
    if (form) {
        form.reset();
    }

    const tableParcels = document.getElementById("tableParcels");
    if (tableParcels) {
        tableParcels.querySelector("tbody").innerHTML = "";
    }
}

// Inizializza quando il documento è pronto
document.addEventListener("DOMContentLoaded", function () {
    bindDialog();
    bindSubmitCreateRequest();
    bindShowLastError();
    bindBtnImportOrder();
});
