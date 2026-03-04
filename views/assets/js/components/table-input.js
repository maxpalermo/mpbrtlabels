if (!customElements.get("table-input")) {
    class TableInput extends HTMLElement {
        static observedAttributes = ["type", "data-type", "value", "disabled", "readonly", "placeholder", "data-category", "data-endpoint", "data-action", "data-name", "data-maxlength", "data-text-align", "data-suffix", "data-decimals", "data-currency", "data-formatdate", "data-rows", "data-cols", "data-value-on", "data-value-off", "data-label-on", "data-label-off", "data-icon-on", "data-icon-off", "data-currency-group", "data-disabled"];

        constructor() {
            super();
            this.attachShadow({ mode: "open" });

            this._input = null;
            this._textArea = null;
            this._switchWrap = null;
            this._switchOn = null;
            this._switchOff = null;
            this._params = [];
            this._endpoint = "";
            this._action = "";
            this._category = "";
            this._name = "";
            this._maxLength = null;
            this._textAlign = "";
            this._suffix = "";
            this._decimals = null;
            this._currency = "";
            this._currencyGroup = "dot";
            this._formatDate = "";
            this._rows = null;
            this._cols = null;
            this._valueOn = "1";
            this._valueOff = "0";
            this._labelOn = "";
            this._labelOff = "";
            this._iconOn = "";
            this._iconOff = "";
            this._isFocused = false;
            this._suppressRenderWhileTyping = false;
            this._softDisabled = false;

            this._onFocus = this._onFocus.bind(this);
            this._onBlur = this._onBlur.bind(this);
            this._onKeyDown = this._onKeyDown.bind(this);
            this._onInput = this._onInput.bind(this);
            this._onSwitchChange = this._onSwitchChange.bind(this);
        }

        connectedCallback() {
            this._render();
            this._syncFromAttributes();
            this._renderValue();
            this._renderState();
            this._bind();
        }

        disconnectedCallback() {
            this._unbind();
        }

        attributeChangedCallback() {
            const prevType = (this._currentType || "").toLowerCase();
            const nextType = (this.getAttribute("type") || this.dataset.type || "text").toLowerCase();
            const typeChanged = prevType && prevType !== nextType;

            if (typeChanged) {
                this._unbind();
                this._render();
            }
            this._syncFromAttributes();
            if (!(this._isFocused && this._suppressRenderWhileTyping)) {
                this._renderValue();
            }
            this._renderState();

            if (typeChanged) {
                this._bind();
            }
        }

        get value() {
            const type = (this.getAttribute("type") || this.dataset.type || "text").toLowerCase();
            if (type === "switch") {
                return this.rawValue;
            }
            return this._input ? this._input.value : "";
        }

        get rawValue() {
            const type = (this.getAttribute("type") || this.dataset.type || "text").toLowerCase();
            if (type === "switch") {
                if (this._switchOn?.checked) return String(this._switchOn.value);
                if (this._switchOff?.checked) return String(this._switchOff.value);
            }
            const v = this.getAttribute("value");
            return v == null ? "" : String(v);
        }

        set value(v) {
            const nextRaw = this._applyMaxLength(v == null ? "" : String(v));
            if (this.getAttribute("value") !== nextRaw) {
                this.setAttribute("value", nextRaw);
            }
            const type = (this.getAttribute("type") || this.dataset.type || "text").toLowerCase();
            if (type === "switch") {
                this._setSwitchByValue(nextRaw);
                return;
            }
            if (this._input) {
                const nextDisplay = this._isFocused ? nextRaw : this._formatDisplayValue(nextRaw);
                this._input.value = this._applyMaxLength(nextDisplay);
            }
        }

        set rawValue(v) {
            this.value = v;
        }

        get category() {
            return this._category;
        }

        get endpoint() {
            return this._endpoint;
        }

        get action() {
            return this._action;
        }

        get name() {
            return this._name;
        }

        get params() {
            return Array.isArray(this._params) ? this._params.slice() : [];
        }

        clear() {
            this.value = "";
            this.dispatchEvent(
                new CustomEvent("table-input:clear", {
                    bubbles: true,
                    composed: true,
                    detail: { category: this._category, name: this._name },
                }),
            );
        }

        async read(extra = {}) {
            const res = await this._request("read", extra);

            if (res && typeof res === "object") {
                const nextValue = res.value ?? res.data?.value ?? res.data;
                if (nextValue !== undefined) {
                    this.value = nextValue;
                }
            }

            this.dispatchEvent(
                new CustomEvent("table-input:read", {
                    bubbles: true,
                    composed: true,
                    detail: { response: res, category: this._category, name: this._name },
                }),
            );

            return res;
        }

        async write(extra = {}) {
            const res = await this._request("write", { value: this.rawValue, ...extra });

            this.dispatchEvent(
                new CustomEvent("table-input:write", {
                    bubbles: true,
                    composed: true,
                    detail: { response: res, category: this._category, name: this._name, value: this.value },
                }),
            );

            return res;
        }

        focus() {
            this._input?.focus();
        }

        selectAll() {
            if (!this._input) return;
            try {
                this._input.select();
            } catch {
                const v = this._input.value;
                this._input.value = "";
                this._input.value = v;
            }
        }

        _render() {
            if (!this.shadowRoot) return;

            const type = (this.getAttribute("type") || this.dataset.type || "text").toLowerCase();
            this._currentType = type;

            let controlHtml = "<input />";
            if (type === "textarea") {
                controlHtml = "<textarea></textarea>";
            } else if (type === "switch") {
                controlHtml = `
                <div class="ps-switch ps-switch-lg ps-togglable-row">
                    <input type="radio" />
                    <label></label>
                    <input type="radio" />
                    <label></label>
                    <span class="slide-button"></span>
                </div>
            `;
            }

            this.shadowRoot.innerHTML = `
            <style>
                :host{display:block;}
                .wrap{display:block;width:100%;}
                .field{display:flex;align-items:center;gap:8px;}
                .field.has-suffix{position:relative;}
                .field.has-suffix::after{
                    display:inline-block;
                    font: inherit;
                    color: rgba(0,0,0,0.55);
                    white-space: nowrap;
                    padding-left: 8px;
                    content: "";
                }
                .field.has-suffix.suffix-eur::after{content:" EUR";}
                .field.has-suffix.suffix-cm::after{content:" cm";}
                .field.has-suffix.suffix-kg::after{content:" Kg";}
                .field.has-suffix.suffix-m3::after{content:" m3";}
                input, textarea{
                    width:100%;
                    border:none;
                    border-bottom:1px solid rgba(0,0,0,0.25);
                    background:transparent;
                    outline:none;
                    padding:6px 4px;
                    font: inherit;
                    color: inherit;
                }
                textarea{resize:vertical;}
                input:focus, textarea:focus{
                    border-bottom-color:#25b9d7;
                    box-shadow: 0 1px 0 0 #25b9d7;
                }
                input:disabled, textarea:disabled{
                    opacity:.6;
                    cursor:not-allowed;
                }
                .switch .material-icons{font-size:18px;vertical-align:middle;}
                .ps-switch{
                    position:relative;
                    display:inline-flex;
                    align-items:stretch;
                    border:1px solid rgba(0,0,0,0.15);
                    background:#f8f9fa;
                    border-radius:999px;
                    overflow:hidden;
                    user-select:none;
                    height:32px;
                    min-width:120px;
                }
                .ps-switch.ps-switch-lg{height:34px;}
                .ps-switch.ps-togglable-row{width:fit-content;}
                .ps-switch input[type=radio]{
                    position:absolute;
                    opacity:0;
                    pointer-events:none;
                }
                .ps-switch label{
                    position:relative;
                    z-index:2;
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    padding:0 12px;
                    font: inherit;
                    font-weight:600;
                    font-size:12px;
                    line-height:1;
                    color: rgba(0,0,0,0.55);
                    cursor:pointer;
                    min-width:56px;
                }
                .ps-switch .slide-button{
                    position:absolute;
                    top:2px;
                    left:2px;
                    bottom:2px;
                    width:calc(50% - 2px);
                    background:#25b9d7;
                    border-radius:999px;
                    z-index:1;
                    transition:transform 150ms ease;
                }
                .ps-switch input[type=radio]:first-of-type:checked ~ .slide-button{transform:translateX(0%);}
                .ps-switch input[type=radio]:nth-of-type(2):checked ~ .slide-button{transform:translateX(100%);}
                .ps-switch input[type=radio]:first-of-type:checked + label{color:#fff;}
                .ps-switch input[type=radio]:nth-of-type(2):checked + label{color:#fff;}
                .ps-switch input[type=radio]:disabled + label{opacity:.6;cursor:not-allowed;}
                .ps-switch input[type=radio]:disabled ~ .slide-button{opacity:.6;}
            </style>
            <span class="wrap">
                <span class="field">
                    ${controlHtml}
                </span>
            </span>
        `;

            this._input = null;
            this._textArea = null;
            this._switchWrap = null;
            this._switchOff = null;
            this._switchOn = null;
            this._switchLabelOff = null;
            this._switchLabelOn = null;

            if (type === "textarea") {
                this._textArea = this.shadowRoot.querySelector("textarea");
                this._input = this._textArea;
            } else if (type === "switch") {
                this._switchWrap = this.shadowRoot.querySelector(".ps-switch");
                const radios = this._switchWrap ? Array.from(this._switchWrap.querySelectorAll('input[type="radio"]')) : [];
                const labels = this._switchWrap ? Array.from(this._switchWrap.querySelectorAll("label")) : [];
                this._switchOff = radios[0] || null;
                this._switchOn = radios[1] || null;
                this._switchLabelOff = labels[0] || null;
                this._switchLabelOn = labels[1] || null;
            } else {
                this._input = this.shadowRoot.querySelector("input");
            }
        }

        _renderValue() {
            const type = (this.getAttribute("type") || this.dataset.type || "text").toLowerCase();
            if (type === "switch") {
                const raw = this.getAttribute("value");
                this._setSwitchByValue(raw == null ? "" : String(raw));
                return;
            }
            if (!this._input) return;
            const attrValue = this.getAttribute("value");
            if (attrValue != null) {
                const raw = this._applyMaxLength(attrValue);
                const display = this._isFocused ? raw : this._formatDisplayValue(raw);
                const next = this._applyMaxLength(display);
                if (this._input.value !== next) {
                    this._input.value = next;
                }
            }
        }

        _renderState() {
            const type = this.getAttribute("type") || this.dataset.type || "text";
            const isTextArea = String(type).toLowerCase() === "textarea";
            const isSwitch = String(type).toLowerCase() === "switch";

            const placeholder = this.getAttribute("placeholder");
            if (placeholder != null && this._input && !isSwitch) {
                this._input.placeholder = placeholder;
            }

            if (isTextArea && this._textArea) {
                if (this._rows != null) this._textArea.rows = this._rows;
                if (this._cols != null) this._textArea.cols = this._cols;
            }

            if (this._maxLength != null && this._input && !isSwitch) {
                this._input.maxLength = this._maxLength;
                const next = this._applyMaxLength(this._input.value);
                if (this._input.value !== next) {
                    this._input.value = next;
                }
            }

            if (this._textAlign && this._input && !isSwitch) {
                this._input.style.textAlign = this._textAlign;
            } else if (this._input && !isSwitch) {
                this._input.style.removeProperty("text-align");
            }

            const field = this.shadowRoot?.querySelector(".field");
            if (field) {
                field.classList.remove("has-suffix", "suffix-eur", "suffix-cm", "suffix-kg", "suffix-m3");
                const suffixClass = this._getSuffixClass(this._suffix);
                if (suffixClass) {
                    field.classList.add("has-suffix", suffixClass);
                }
            }

            const disabled = this.hasAttribute("disabled") || this.hasAttribute("readonly");
            if (isSwitch) {
                if (this._switchOn) this._switchOn.disabled = disabled;
                if (this._switchOff) this._switchOff.disabled = disabled;
            } else if (this._input) {
                // "soft disabled": block edits without changing visual style (do not set disabled)
                this._input.disabled = this.hasAttribute("disabled");
                this._input.readOnly = this._softDisabled ? true : this.hasAttribute("readonly");
            }

            if (isSwitch) {
                this._renderSwitchMarkup();
            }
        }

        _syncFromAttributes() {
            this._endpoint = this.dataset.endpoint || "";
            this._action = this.dataset.action || "";
            this._category = this.dataset.category || "";
            this._name = this.dataset.name || this.getAttribute("name") || "";
            this._softDisabled = this._parseBoolean(this.dataset.disabled);
            this._maxLength = this._parseMaxLength();
            this._textAlign = (this.dataset.textAlign || "").trim();
            this._suffix = (this.dataset.suffix || "").trim();
            this._decimals = this._parseDecimals();
            this._currency = (this.dataset.currency || "").trim();
            this._currencyGroup = (this.dataset.currencyGroup || "dot").trim().toLowerCase();
            this._formatDate = (this.dataset.formatdate || "").trim();
            this._rows = this._parseIntNullable(this.dataset.rows);
            this._cols = this._parseIntNullable(this.dataset.cols);
            this._valueOn = this.dataset.valueOn != null ? String(this.dataset.valueOn) : "1";
            this._valueOff = this.dataset.valueOff != null ? String(this.dataset.valueOff) : "0";
            this._iconOn = (this.dataset.iconOn || "").trim();
            this._iconOff = (this.dataset.iconOff || "").trim();
            const hasLabelOn = this.dataset.labelOn != null;
            const hasLabelOff = this.dataset.labelOff != null;
            this._labelOn = hasLabelOn ? String(this.dataset.labelOn) : this._iconOn ? "" : "SI";
            this._labelOff = hasLabelOff ? String(this.dataset.labelOff) : this._iconOff ? "" : "NO";
            this._params = this._collectParams();
        }

        _renderSwitchMarkup() {
            if (!this._switchWrap || !this._switchOn || !this._switchOff) return;

            const name = this._name || this.getAttribute("name") || "switch";

            this._switchOff.name = name;
            this._switchOn.name = name;

            this._switchOff.id = `${name}_off`;
            this._switchOn.id = `${name}_on`;

            this._switchOff.value = this._valueOff;
            this._switchOn.value = this._valueOn;

            if (this._switchLabelOff) {
                this._switchLabelOff.setAttribute("for", this._switchOff.id);
                this._switchLabelOff.innerHTML = this._labelOff ? this._escapeHtml(this._labelOff) : this._iconOff ? `<span class="material-icons">${this._escapeHtml(this._iconOff)}</span>` : "";
            }

            if (this._switchLabelOn) {
                this._switchLabelOn.setAttribute("for", this._switchOn.id);
                this._switchLabelOn.innerHTML = this._labelOn ? this._escapeHtml(this._labelOn) : this._iconOn ? `<span class="material-icons">${this._escapeHtml(this._iconOn)}</span>` : "";
            }

            const raw = this.getAttribute("value");
            this._setSwitchByValue(raw == null ? "" : String(raw));
        }

        _setSwitchByValue(value) {
            if (!this._switchOn || !this._switchOff) return;
            const v = value == null ? "" : String(value);

            if (v === String(this._switchOn.value) || v.toLowerCase() === "on" || v === "1") {
                this._switchOn.checked = true;
            } else if (v === String(this._switchOff.value) || v.toLowerCase() === "off" || v === "0") {
                this._switchOff.checked = true;
            }
        }

        _escapeHtml(s) {
            return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;").replace(/'/g, "&#039;");
        }

        _parseIntNullable(raw) {
            if (raw == null || raw === "") return null;
            const n = parseInt(String(raw), 10);
            if (!Number.isFinite(n) || n <= 0) return null;
            return n;
        }

        _parseBoolean(raw) {
            if (raw == null) return false;
            const s = String(raw).trim().toLowerCase();
            if (s === "" || s === "0" || s === "false" || s === "no" || s === "off" || s === "null" || s === "undefined") return false;
            return true;
        }

        _parseDecimals() {
            const raw = this.dataset.decimals;
            if (raw == null || raw === "") {
                return null;
            }
            const n = parseInt(String(raw), 10);
            if (!Number.isFinite(n) || n < 0) {
                return null;
            }
            return n;
        }

        _formatDisplayValue(raw) {
            const v = raw == null ? "" : String(raw);
            if (!v) return v;

            if (this._formatDate) {
                const formattedDate = this._formatDateValue(v, this._formatDate);
                if (formattedDate != null) {
                    return formattedDate;
                }
            }

            if (this._currency) {
                const formattedCurrency = this._formatCurrencyValue(v, this._currency, this._decimals);
                if (formattedCurrency != null) {
                    return formattedCurrency;
                }
            }

            if (this._decimals != null) {
                const formattedNumber = this._formatDecimalsValue(v, this._decimals);
                if (formattedNumber != null) {
                    return formattedNumber;
                }
            }

            return v;
        }

        _parseNumber(value) {
            if (value == null) return null;
            const s = String(value).trim();
            if (!s) return null;
            const normalized = s.replace(/\s+/g, "").replace(/,/g, ".");
            const n = Number.parseFloat(normalized);
            return Number.isFinite(n) ? n : null;
        }

        _formatDecimalsValue(value, decimals) {
            const n = this._parseNumber(value);
            if (n == null) return null;
            try {
                return n.toFixed(decimals);
            } catch {
                return null;
            }
        }

        _normalizeRawValue(value) {
            const v = value == null ? "" : String(value);
            if (!v) return "";

            // If a date formatter is configured, keep raw as-is (server expects ISO-ish)
            if (this._formatDate) {
                return v.trim();
            }

            // If currency/decimals are configured, normalize numeric input
            if (this._currency || this._decimals != null) {
                // Remove currency symbols/letters and keep digits, sign, separators
                // Accept inputs like: "9.348,55 €" or "€ 9 348,55"
                let s = v.replace(/\s+/g, "").replace(/[^0-9,\.\-+]/g, "");

                if (!s) return "";

                // If both '.' and ',' exist, assume '.' are thousands and ',' is decimal
                if (s.includes(".") && s.includes(",")) {
                    s = s.replace(/\./g, "");
                    s = s.replace(/,/g, ".");
                } else {
                    // Only one of them exists: treat ',' as decimal separator
                    s = s.replace(/,/g, ".");
                }

                // Keep only the first sign
                s = s.replace(/(?!^)[+-]/g, "");

                const n = Number.parseFloat(s);
                if (!Number.isFinite(n)) return "";

                if (this._decimals != null) {
                    return n.toFixed(this._decimals);
                }

                return String(n);
            }

            return v;
        }

        _formatCurrencyValue(value, currency, decimals) {
            const n = this._parseNumber(value);
            if (n == null) return null;
            const cur = String(currency || "")
                .trim()
                .toUpperCase();
            const code = cur === "EUR" || cur === "EURO" ? "EUR" : cur;
            if (!code) return null;

            try {
                const fractionDigits = decimals != null ? decimals : undefined;

                // For EUR we prefer suffix format: 9.348,55 € (instead of € 9.348,55)
                if (code === "EUR") {
                    const opts = {
                        style: "decimal",
                        useGrouping: true,
                    };
                    if (fractionDigits != null) {
                        opts.minimumFractionDigits = fractionDigits;
                        opts.maximumFractionDigits = fractionDigits;
                    } else {
                        opts.minimumFractionDigits = 2;
                        opts.maximumFractionDigits = 2;
                    }

                    let out = new Intl.NumberFormat("it-IT", opts).format(n);
                    if (this._currencyGroup === "space") {
                        // it-IT uses '.' for thousands grouping
                        out = out.replace(/\./g, " ");
                    }
                    return `${out} €`;
                }

                const opts = {
                    style: "currency",
                    currency: code,
                    useGrouping: true,
                };
                if (fractionDigits != null) {
                    opts.minimumFractionDigits = fractionDigits;
                    opts.maximumFractionDigits = fractionDigits;
                }
                return new Intl.NumberFormat("it-IT", opts).format(n);
            } catch {
                return null;
            }
        }

        _formatDateValue(value, format) {
            const s = String(value || "").trim();
            const m = s.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/);
            if (!m) return null;

            const year = Number(m[1]);
            const month = Number(m[2]);
            const day = Number(m[3]);
            const hour = Number(m[4] || 0);
            const minute = Number(m[5] || 0);
            const second = Number(m[6] || 0);

            if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) return null;

            const pad2 = (n) => String(n).padStart(2, "0");
            const map = {
                d: pad2(day),
                m: pad2(month),
                Y: String(year),
                H: pad2(hour),
                i: pad2(minute),
                s: pad2(second),
            };

            let out = String(format || "");
            out = out.replace(/Y/g, map.Y);
            out = out.replace(/m/g, map.m);
            out = out.replace(/d/g, map.d);
            out = out.replace(/H/g, map.H);
            out = out.replace(/i/g, map.i);
            out = out.replace(/s/g, map.s);
            return out;
        }

        _getSuffixClass(value) {
            const v = String(value || "")
                .trim()
                .toLowerCase();
            if (!v) return "";
            if (v === "eur" || v === "suffix-eur") return "suffix-eur";
            if (v === "cm" || v === "suffix-cm") return "suffix-cm";
            if (v === "kg" || v === "suffix-kg") return "suffix-kg";
            if (v === "m3" || v === "suffix-m3") return "suffix-m3";
            return "";
        }

        _parseMaxLength() {
            const raw = this.dataset.maxlength;
            if (raw == null || raw === "") {
                return null;
            }
            const n = parseInt(String(raw), 10);
            if (!Number.isFinite(n) || n <= 0) {
                return null;
            }
            return n;
        }

        _applyMaxLength(value) {
            if (this._maxLength == null) {
                return value;
            }
            const v = value == null ? "" : String(value);
            if (v.length <= this._maxLength) {
                return v;
            }
            return v.slice(0, this._maxLength);
        }

        _collectParams() {
            const params = [];
            for (const attr of Array.from(this.attributes || [])) {
                if (!attr || !attr.name) continue;
                if (!attr.name.startsWith("data-param-")) continue;
                const key = attr.name.slice("data-param-".length);
                params.push({ key, value: attr.value });
            }
            return params;
        }

        _bind() {
            const type = (this.getAttribute("type") || this.dataset.type || "text").toLowerCase();
            if (type === "switch") {
                if (this._switchOn) {
                    this._switchOn.removeEventListener("change", this._onSwitchChange);
                    this._switchOn.addEventListener("change", this._onSwitchChange);
                }
                if (this._switchOff) {
                    this._switchOff.removeEventListener("change", this._onSwitchChange);
                    this._switchOff.addEventListener("change", this._onSwitchChange);
                }
                return;
            }

            if (!this._input) return;
            this._input.removeEventListener("focus", this._onFocus);
            this._input.removeEventListener("blur", this._onBlur);
            this._input.removeEventListener("keydown", this._onKeyDown);
            this._input.removeEventListener("input", this._onInput);

            this._input.addEventListener("focus", this._onFocus);
            this._input.addEventListener("blur", this._onBlur);
            this._input.addEventListener("keydown", this._onKeyDown);
            this._input.addEventListener("input", this._onInput);
        }

        _unbind() {
            if (this._switchOn) this._switchOn.removeEventListener("change", this._onSwitchChange);
            if (this._switchOff) this._switchOff.removeEventListener("change", this._onSwitchChange);

            if (!this._input) return;
            this._input.removeEventListener("focus", this._onFocus);
            this._input.removeEventListener("blur", this._onBlur);
            this._input.removeEventListener("keydown", this._onKeyDown);
            this._input.removeEventListener("input", this._onInput);
        }

        _onSwitchChange() {
            if (this._softDisabled) {
                // revert UI selection back to current attribute value
                const raw = this.getAttribute("value");
                this._setSwitchByValue(raw == null ? "" : String(raw));
                return;
            }
            const nextRaw = this.rawValue;
            if (this.getAttribute("value") !== nextRaw) {
                this.setAttribute("value", nextRaw);
            }
            this.dispatchEvent(
                new CustomEvent("table-input:input", {
                    bubbles: true,
                    composed: true,
                    detail: { category: this._category, name: this._name, value: nextRaw },
                }),
            );
        }

        _onFocus() {
            this._isFocused = true;
            this._renderValue();
            this.selectAll();
            this.dispatchEvent(
                new CustomEvent("table-input:focus", {
                    bubbles: true,
                    composed: true,
                    detail: { category: this._category, name: this._name },
                }),
            );
        }

        _onBlur() {
            this._isFocused = false;
            this._renderValue();
            this.dispatchEvent(
                new CustomEvent("table-input:blur", {
                    bubbles: true,
                    composed: true,
                    detail: { category: this._category, name: this._name, value: this.value },
                }),
            );
        }

        _onInput() {
            if (this._input) {
                if (this._softDisabled) {
                    this._renderValue();
                    return;
                }
                const nextRaw = this._applyMaxLength(this._normalizeRawValue(this._input.value));
                if (this.getAttribute("value") !== nextRaw) {
                    this._suppressRenderWhileTyping = true;
                    this.setAttribute("value", nextRaw);
                    this._suppressRenderWhileTyping = false;
                }
            }
            this.dispatchEvent(
                new CustomEvent("table-input:input", {
                    bubbles: true,
                    composed: true,
                    detail: { category: this._category, name: this._name, value: this.value },
                }),
            );
        }

        _onKeyDown(e) {
            if (this._softDisabled) {
                // allow navigation/selection shortcuts, block edits
                const allowed = new Set(["Tab", "ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown", "Shift", "Control", "Alt", "Meta", "Escape"]);
                if (allowed.has(e.key)) return;
                if (e.ctrlKey || e.metaKey) return;
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            if (e.key === "Enter") {
                e.preventDefault();
                e.stopPropagation();

                this.dispatchEvent(
                    new CustomEvent("table-input:enter", {
                        bubbles: true,
                        composed: true,
                        detail: { category: this._category, name: this._name, value: this.value },
                    }),
                );
                return;
            }

            if (e.key === "ArrowDown" || e.key === "ArrowUp") {
                e.preventDefault();
                e.stopPropagation();
                this._focusSibling(e.key === "ArrowDown" ? 1 : -1);
            }
        }

        _focusSibling(direction) {
            const category = this._category;
            if (!category) return;

            const all = Array.from(document.querySelectorAll(`table-input[data-category="${CSS.escape(category)}"]`));
            const idx = all.indexOf(this);
            if (idx === -1) return;

            const next = all[idx + direction];
            if (next && typeof next.focus === "function") {
                next.focus();
                if (typeof next.selectAll === "function") {
                    next.selectAll();
                }
            }
        }

        async _request(mode, extra = {}) {
            if (!this._endpoint || !this._action) {
                return null;
            }

            const formData = new FormData();
            formData.append("ajax", 1);
            formData.append("action", this._action);
            formData.append("mode", mode);

            if (this._category) formData.append("category", this._category);
            if (this._name) formData.append("name", this._name);

            for (const p of this._params) {
                if (!p || !p.key) continue;
                formData.append(p.key, p.value);
            }

            Object.entries(extra || {}).forEach(([k, v]) => {
                if (v === undefined || v === null) return;
                formData.append(k, v);
            });

            const resp = await fetch(this._endpoint, {
                method: "POST",
                body: formData,
            });

            if (!resp.ok) {
                throw new Error("table-input: Network response was not ok");
            }

            const ct = resp.headers.get("content-type") || "";
            if (ct.includes("application/json")) {
                return resp.json();
            }
            const text = await resp.text();
            try {
                return JSON.parse(text);
            } catch {
                return { success: true, value: text };
            }
        }
    }

    customElements.define("table-input", TableInput);
}
