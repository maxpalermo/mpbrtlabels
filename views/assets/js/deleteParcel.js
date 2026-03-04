async function deleteParcel(numericSenderReference, alphanumericSenderReference, year) {
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
