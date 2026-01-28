# mpbrtlabels

Modulo PrestaShop per la **generazione**, **gestione** e **stampa** delle etichette di spedizione BRT (Bartolini) dal Back Office.

## Autore

- **Massimiliano Palermo**
- Email: `maxx.palermo@gmail.com`

## Cosa fa il modulo

- **Creazione richiesta etichetta BRT**
    - Compilazione dati mittente/destinatario/spedizione.
    - Gestione colli (dimensioni/peso/volume) e calcolo totali.
    - Supporto contrassegno (COD) e parametri accessori.

- **Invio richiesta a BRT via REST**
    - Costruzione payload e chiamata endpoint BRT.
    - Parsing risposta, inclusi gli stream delle etichette.
    - Persistenza dati richiesta/risposta in tabelle dedicate.

- **Storico Borderò / spedizioni**
    - Tabella con paginazione server-side e filtri.
    - Indicatori “stampato”, contrassegno, ecc.

- **Stampa etichette**
    - Merge di più PDF in un unico documento tramite `pdf-lib`.
    - Apertura del PDF risultante nel browser.
    - (Opzionale) integrazione QZ Tray per stampa diretta su stampante.

## Requisiti / Dipendenze

- PrestaShop 8.x
- **pdf-lib** lato browser (incluso in `views/assets/js/PdfLib/pdf-lib.min.js`)
- `mergePDF.js` per unire i PDF (classe `PdfMerger`)
- (Opzionale) **QZ Tray**
    - Richiede il file `qz-tray.js` e QZ Tray installato sul client
    - Può richiedere configurazione di certificato/firma (security)

## Componenti principali

- **Controller Admin**: `controllers/admin/AdminMpBrtLabels.php`
    - Endpoint AJAX per BO (caricamento ordini, salvataggio colli, fetch labels, ecc.)

- **Richiesta BRT**: `src/Api/Request/RequestData.php`
- **REST Create**: `src/Api/Rest/Create.php`
- **Models**:
    - `src/Models/ModelBrtLabelsRequest.php`
    - `src/Models/ModelBrtLabelsResponse.php`
    - `src/Models/ModelBrtLabelsParcel.php`

- **JS**:
    - `views/assets/js/mergePDF.js` (classe `PdfMerger`)
    - `views/assets/js/PrintParcels.js` (classe `PrintParcels` per stampare etichette da elenco riferimenti)

## Installazione rapida

### 1) Copia dipendenze JS

- **pdf-lib**
    - Copia `pdf-lib.min.js` in:
        - `views/assets/js/PdfLib/pdf-lib.min.js`
    - In pagina (Twig) deve essere caricato **prima** di `mergePDF.js`.

- **QZ Tray (opzionale)**
    - Scarica `qz-tray.js` dalle release ufficiali:
        - https://github.com/qzind/tray/releases
    - Copia `qz-tray.js` in:
        - `views/assets/js/qz-tray.js`
    - Assicurati che sul PC client sia installato **QZ Tray**.

### 2) Ordine di inclusione consigliato (Twig)

Esempio:

- `qz-tray.js` (solo se usi QZ)
- `PdfLib/pdf-lib.min.js`
- `mergePDF.js`
- `PrintParcels.js`

### 3) Verifica rapida endpoint AJAX

Dal Back Office (console del browser) puoi verificare che gli endpoint rispondano in JSON.

Nota: `adminControllerUrl` deve essere l’URL del controller admin (es. `index.php?controller=AdminMpBrtLabels&token=...`).

- **Fetch labels (stream base64) per stampa multipla**

```js
const fd = new FormData();
fd.append("ajax", "1");
fd.append("action", "fetchLabelsByRefs");
fd.append(
    "items",
    JSON.stringify([
        { numericSenderReference: 12345, year: 2026 },
        { numericSenderReference: 23456 }, // year = anno corrente
    ]),
);

fetch(adminControllerUrl, { method: "POST", body: fd })
    .then((r) => r.json())
    .then(console.log);
```

La risposta attesa è:

```json
{ "success": true, "streams": ["...base64pdf...", "...base64pdf..."] }
```

- **Fetch table borderò (bootstrap-table server-side)**

```js
const fd = new URLSearchParams();
fd.set("ajax", "1");
fd.set("action", "fetchTableBordero");
fd.set("limit", "10");
fd.set("offset", "0");
fd.set("sort", "numericSenderReference");
fd.set("order", "DESC");

fetch(adminControllerUrl, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: fd.toString(),
})
    .then((r) => r.json())
    .then(console.log);
```

La risposta attesa è (struttura bootstrap-table):

```json
{ "rows": [ {"numericSenderReference": 123, "year": 2026, ...} ], "total": 1, "totalNotFiltered": 1 }
```

Se ottieni HTML invece di JSON, in genere significa:

- stai chiamando l’URL sbagliato (non quello admin con token)
- non stai passando `ajax=1` / `action=...`
- non hai permessi BO o il token è scaduto

## Cosa manca / TODO

- **Hardening sicurezza AJAX**
    - Validazione permessi/ACL e protezione CSRF dove necessario.

- **QZ Tray (completo)**
    - Aggiungere e documentare configurazione `qz.security.setCertificatePromise` e `qz.security.setSignaturePromise`.
    - UI per selezione stampante e parametri di stampa.

- **Gestione errori e logging**
    - Consolidare gestione errori BRT lato server e messaggistica BO.
    - Logging strutturato (invece di file di debug locali).

- **Pulizia e uniformità dei nomi campi**
    - Uniformare naming tra DB/JS/PHP (`weightKG` vs `weightKg`, ecc.).

- **Test e regressioni**
    - Test manuali guidati (checklist) e/o test automatici dove possibile.

---
