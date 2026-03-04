<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

use MpSoft\MpBrtLabels\Api\Request\CodPaymentType;
use MpSoft\MpBrtLabels\Api\Request\RequestData;
use MpSoft\MpBrtLabels\Helpers\Bordero;
use MpSoft\MpBrtLabels\Helpers\GetTwigEnvironment;
use MpSoft\MpBrtLabels\Helpers\Label;
use MpSoft\MpBrtLabels\Models\ModelBrtLabelsParcel;
use MpSoft\MpBrtLabels\Models\ModelBrtLabelsRequest;
use MpSoft\MpBrtLabels\Models\ModelBrtLabelsResponse;
use MpSoft\MpBrtLabels\Models\ModelBrtShipmentBordero;

class AdminMpBrtLabelsController extends ModuleAdminController
{
    protected static $currentPage = 'settings';
    protected static $adminControllerUrl;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = ModelBrtShipmentBordero::$definition['table'];
        $this->identifier = ModelBrtShipmentBordero::$definition['primary'];
        $this->className = ModelBrtShipmentBordero::class;
        $this->lang = false;

        parent::__construct();

        self::$adminControllerUrl = $this->context->link->getAdminLink('AdminMpBrtLabels');

        if (Tools::isSubmit('ajax') && Tools::isSubmit('action')) {
            $action = 'ajaxProcess' . Tools::ucfirst(Tools::getValue('action'));
            if (method_exists($this, $action)) {
                header('Content-Type: application/json');
                http_response_code(200);

                $this->ajaxRender(json_encode($this->$action()));
                exit();
            }
        }
    }

    public function setMedia($isNewTheme = false)
    {
        $this->addCSS($this->module->getLocalPath() . 'views/assets/css/style.css', 'all', 999);
        parent::setMedia($isNewTheme);
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $adminControllerUrl = self::$adminControllerUrl;

        $this->page_header_toolbar_btn['history'] = [
            'href' => self::$adminControllerUrl . '&page=history',
            'desc' => $this->trans('Storico Borderò', [], 'Admin.Actions'),
            'icon' => 'icon-list',
            // 'target' => '_blank',
            'class' => 'history',
        ];

        $this->page_header_toolbar_btn['labels'] = [
            'href' => $adminControllerUrl . '&page=labels',
            'desc' => $this->trans('Etichette', [], 'Admin.Actions'),
            'icon' => 'icon-barcode',
            // 'target' => '_blank',
            'class' => 'barcode',
        ];

        $this->page_header_toolbar_btn['settings'] = [
            'href' => $adminControllerUrl . '&page=settings',
            'desc' => $this->trans('Impostazioni', [], 'Admin.Actions'),
            'icon' => 'icon-cog',
            // 'target' => '_blank',
            'class' => 'settings',
        ];
    }

    public function initContent()
    {
        self::$currentPage = Tools::getValue('page', 'history');
        $currentPage = self::$currentPage;
        $js = <<<JS
                <style>
                    .btn-active {
                        background-color: #cea346 !important;
                        border-color: #cd9c32 !important;
                    }
                </style>
                <script type="text/javascript">
                    const currentPage = "{$currentPage}";
                    document.addEventListener("DOMContentLoaded", (e) => {
                        const toolbarButtons = document.querySelectorAll("ul#toolbar-nav li a");
                        if (toolbarButtons) {
                            const currentId = "page-header-desc-brt_shipment_bordero-{$currentPage}";
                            console.log(toolbarButtons, currentId, currentPage);
                            toolbarButtons.forEach((button) => {
                                const id = button.id;
                                console.log(id);
                                if (id === currentId) {
                                    console.log("FOUND", currentId)
                                    button.classList.add("btn-active");
                                }
                            });
                        }
                    });
                </script>
            JS;

        $this->content = $this->getPage() . $js;

        parent::initContent();
    }

    protected function getPage()
    {
        $adminControllerUrl = $this->context->link->getAdminLink('AdminMpBrtLabels');
        $id_lang = (int) Context::getContext()->language->id;
        $twig = new GetTwigEnvironment($this->module->name);
        $params = [];
        switch (self::$currentPage) {
            case 'settings':
                $twig->load('@ModuleTwig/Controllers/Admin/Settings');
                $params = [
                    'adminControllerUrl' => $adminControllerUrl,
                    'employees' => Employee::getEmployees(),
                    'employees_enabled' => json_decode(Configuration::get('MPBRTLABELS_EMPLOYEES_ENABLED'), true),
                    'orderStates' => OrderState::getOrderStates($id_lang),
                    'orderStates_display' => json_decode(Configuration::get('MPBRTLABELS_ORDERSTATES_DISPLAY'), true),
                    'orderState_change' => json_decode(Configuration::get('MPBRTLABELS_ORDERSTATE_CHANGE'), true),
                    'account_id' => Configuration::get('MPBRTLABELS_ACCOUNT_ID'),
                    'account_pwd' => Configuration::get('MPBRTLABELS_ACCOUNT_PWD'),
                    'account_departure_depot' => Configuration::get('MPBRTLABELS_ACCOUNT_DEPARTURE_DEPOT'),
                    'account_customer_code' => Configuration::get('MPBRTLABELS_ACCOUNT_CUSTOMER_CODE'),
                    'sandbox_id' => Configuration::get('MPBRTLABELS_SANDBOX_ID'),
                    'sandbox_pwd' => Configuration::get('MPBRTLABELS_SANDBOX_PWD'),
                    'sandbox_departure_depot' => Configuration::get('MPBRTLABELS_SANDBOX_DEPARTURE_DEPOT'),
                    'sandbox_customer_code' => Configuration::get('MPBRTLABELS_SANDBOX_CUSTOMER_CODE'),
                    'sandbox_enabled' => Configuration::get('MPBRTLABELS_SANDBOX_ENABLED'),
                    'orderstates_display' => json_decode(Configuration::get('MPBRTLABELS_ORDERSTATES_DISPLAY'), true),
                    'orderstate_change' => (int) Configuration::get('MPBRTLABELS_ORDERSTATE_CHANGE'),
                    'payment_modules' => PaymentModule::getPaymentModules(),
                    'cash_on_delivery_module' => json_decode(Configuration::get('MPBRTLABELS_CASH_ON_DELIVERY_MODULE'), true),
                    'service_type' => Configuration::get('MPBRTLABELS_SERVICE_TYPE'),
                    'sender_parcel_type' => Configuration::get('MPBRTLABELS_SENDER_PARCEL_TYPE'),
                    'label_type' => (int) Configuration::get('MPBRTLABELS_LABEL_TYPE'),
                    'label_border' => (int) Configuration::get('MPBRTLABELS_LABEL_BORDER'),
                    'label_logo' => (int) Configuration::get('MPBRTLABELS_LABEL_LOGO'),
                    'label_barcode' => (int) Configuration::get('MPBRTLABELS_LABEL_BARCODE'),
                    'offset_x' => (int) Configuration::get('MPBRTLABELS_OFFSET_X'),
                    'offset_y' => (int) Configuration::get('MPBRTLABELS_OFFSET_Y'),
                ];
                break;
            case 'history':
                $params = [
                    'adminControllerUrl' => $adminControllerUrl,
                ];
                $twig->load('@ModuleTwig/Controllers/Admin/History');
                break;
            case 'labels':
                $params = [
                    'isAdminEmployee' => $this->context->employee->isSuperAdmin(),
                    'adminControllerUrl' => $adminControllerUrl,
                    'showImportOrder' => true,
                    'network' => Configuration::get('MPBRTLABELS_NETWORK'),
                    'deliveryFreightTypeCode' => (int) Configuration::get('MPBRTLABELS_DELIVERY_FREIGHT_TYPE_CODE'),
                    'serviceType' => (int) Configuration::get('MPBRTLABELS_SERVICE_TYPE'),
                    'orderStateChange' => $this->getOrderStateChange(),
                    'codPaymentTypeOptions' => (new CodPaymentType(null))->getCodPaymentAssociativeArray(),
                    'sandbox_enabled' => Configuration::get('MPBRTLABELS_SANDBOX_ENABLED'),
                ];
                $twig->load('@ModuleTwig/Controllers/Admin/Labels');
                break;
        }

        return $twig->render($params);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitSaveSettings')) {
            $this->saveSettings();
        }

        if (Tools::isSubmit('submitCreateTables')) {
            $this->createTables();
        }
    }

    protected function saveSettings()
    {
        $values = Tools::getAllValues();
        $params = [
            'employees_enabled' => json_encode($values['employees'] ?? []),
            'account_id' => $values['account_id'] ?? '',
            'account_pwd' => $values['account_pwd'] ?? '',
            'account_departure_depot' => $values['account_departure_depot'] ?? '',
            'account_customer_code' => $values['account_customer_code'] ?? '',
            'sandbox_id' => $values['sandbox_id'] ?? '',
            'sandbox_pwd' => $values['sandbox_pwd'] ?? '',
            'sandbox_departure_depot' => $values['sandbox_departure_depot'] ?? '',
            'sandbox_customer_code' => $values['sandbox_customer_code'] ?? '',
            'sandbox_enabled' => (int) ($values['sandbox_enabled'] ?? 0),
            'orderstates_display' => json_encode($values['orderstates_display'] ?? []),
            'orderstate_change' => (int) ($values['orderstate_change'] ?? ''),
            'cash_on_delivery_module' => json_encode($values['cash_on_delivery_module'] ?? []),
            'network' => $values['network'] ?? 'D',
            'delivery_freight_type_code' => $values['deliveryFreightTypeCode'] ?? 'DAP',
            'service_type' => $values['serviceType'] ?? '',
            'sender_parcel_type' => $values['senderParcelType'] ?? 'VARI',
            'label_type' => (int) ($values['label_type'] ?? 0),
            'label_border' => (int) ($values['label_border'] ?? 0),
            'label_logo' => (int) ($values['label_logo'] ?? 0),
            'label_barcode' => (int) ($values['label_barcode'] ?? 0),
            'offset_x' => (int) ($values['offset_x'] ?? 0),
            'offset_y' => (int) ($values['offset_y'] ?? 0),
        ];

        foreach ($params as $key => $value) {
            $key = Tools::strtoupper($key);
            $ConfKey = "MPBRTLABELS_{$key}";
            Configuration::updateValue($ConfKey, $value);
        }

        $this->confirmations = 'Impostazioni salvate';
    }

    protected function createTables()
    {
        $table = ModelBrtLabelsRequest::$definition['table'];
        ModelBrtLabelsRequest::install();
        $this->confirmations[] = "<p>Tabella {$table} creata</p>";

        $table = ModelBrtLabelsParcel::$definition['table'];
        ModelBrtLabelsParcel::install();
        $this->confirmations[] = "<p>Tabella {$table} creata</p>";

        $table = ModelBrtLabelsResponse::$definition['table'];
        ModelBrtLabelsResponse::install();
        $this->confirmations[] = "<p>Tabella {$table} creata</p>";
    }

    protected function getOrderStateChange()
    {
        $id_lang = (int) Context::getContext()->language->id;
        $id_order_state = (int) Configuration::get('MPBRTLABELS_ORDERSTATE_CHANGE');

        $orderState = new OrderState($id_order_state, $id_lang);
        if (\Validate::isLoadedObject($orderState)) {
            return Tools::strtoupper($orderState->name);
        }

        return 'Errore: Non Impostato';
    }

    public function ajaxProcessFetchParcel()
    {
        $parcelId = (int) Tools::getValue('parcelId');

        $parcel = new ModelBrtLabelsParcel($parcelId);

        if (!$parcel->id) {
            $this->ajaxRender(json_encode(['error' => 'Collo non trovato']));
            return;
        }

        return ['parcel' => $parcel->getAjaxParams()];
    }

    public function ajaxProcessFetchParcels()
    {
        $orderId = (int) Tools::getValue('orderId');
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('*')
            ->from(ModelBrtLabelsParcel::$definition['table'])
            ->where('id_order = ' . $orderId)
            ->orderBy(ModelBrtLabelsParcel::$definition['primary'] . ' ASC');

        $parcels = $db->executeS($sql);

        if (empty($parcels)) {
            return ['error' => 'Nessun collo trovato'];
        } else {
            return ['parcels' => $parcels];
        }
    }

    public function ajaxProcessFetchTableParcels()
    {
        $limit = (int) Tools::getValue('limit', 50);
        $offset = (int) Tools::getValue('offset', 0);
        $sort = Tools::getValue('sort', 'PECOD');
        $order = Tools::getValue('order', 'DESC');
        $filter = json_decode(Tools::getValue('filter'));

        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('count(PECOD) as total')->from(ModelBrtLabelsParcel::$definition['table']);
        $totalNotFiltered = (int) $db->getValue($sql);
        $total = $totalNotFiltered;

        $sql = new DbQuery();
        $sql
            ->select('*')
            ->from(ModelBrtLabelsParcel::$definition['table'])
            ->orderBy("{$sort} {$order}")
            ->orderBy(ModelBrtLabelsParcel::$definition['primary'] . ' DESC')
            ->limit($limit, $offset);

        if ($filter) {
            $sqlFiltered = new DbQuery();
            $sqlFiltered
                ->select('count(PECOD) as total')
                ->from(ModelBrtLabelsParcel::$definition['table']);
            foreach ($filter as $field => $value) {
                switch ($field) {
                    case 'id_brt_labels_parcel':
                        $value = (int) $value;
                        $sql->where("{$field} = {$value}");
                        $sqlFiltered->where("{$field} = {$value}");
                        break;
                    case 'PECOD':
                        $value = pSQL($value);
                        $sql->where("{$field} LIKE '{$value}%'");
                        $sqlFiltered->where("{$field} LIKE '{$value}%'");
                        break;
                    case 'PPESO':
                    case 'PVOLU':
                    case 'X':
                    case 'Y':
                    case 'Z':
                        $value = pSQL($value);
                        $sql->where("{$field} {$value}");
                        $sqlFiltered->where("{$field} {$value}");
                        break;
                }
            }

            $total = (int) $db->getValue($sqlFiltered);
        }

        $parcels = $db->executeS($sql);

        if (empty($parcels)) {
            return ['rows' => [], 'total' => 0, 'totalNotFiltered' => 0];
        } else {
            return ['rows' => $parcels, 'total' => $total, 'totalNotFiltered' => $totalNotFiltered];
        }
    }

    public function ajaxProcessFetchTableBordero()
    {
        /*
         * FILTER
         *   {
         *   "id_brt_labels_response": "3",
         *   "numericSenderReference": "254325",
         *   "alphanumericSenderReference": "ABCD-1000",
         *   "consigneeCompanyName": "Giovanni+Vario",
         *   "consigneeAddress": "via+roma",
         *   "consigneeZIPCode": "90100",
         *   "consigneeCity": "palermo",
         *   "consigneeProvinceAbbreviation": "PA",
         *   "consigneeCountryAbbreviationBRT": "IT",
         *   "numberOfParcels": "4",
         *   "weightKG": "10",
         *   "volumeM3": "7",
         *   "year": "2026",
         *   "borderoNumber": "18",
         *   "borderoDate": "2026-01-15",
         *   "printed": "1"
         *   }
         */
        $limit = (int) Tools::getValue('limit', 50);
        $offset = (int) Tools::getValue('offset', 0);
        $sort = Tools::getValue('sort', 'numericSenderReference');
        $order = Tools::getValue('order', 'DESC');
        $filter = json_decode(Tools::getValue('filter'));

        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('count(year) as total')->from(ModelBrtLabelsResponse::$definition['table']);
        $totalNotFiltered = (int) $db->getValue($sql);
        $total = $totalNotFiltered;

        if ($sort) {
            switch ($sort) {
                case 'numericSenderReference':
                    $sort = 'a.numericSenderReference';
                    break;
                case 'alphanumericSenderReference':
                    $sort = 'a.alphanumericSenderReference';
                    break;
                case 'consigneeCompanyName':
                    $sort = 'a.consigneeCompanyName';
                    break;
                case 'consigneeAddress':
                    $sort = 'a.consigneeAddress';
                    break;
                case 'consigneeZIPCode':
                    $sort = 'a.consigneeZIPCode';
                    break;
                case 'consigneeCity':
                    $sort = 'a.consigneeCity';
                    break;
                case 'consigneeProvinceAbbreviation':
                    $sort = 'a.consigneeProvinceAbbreviation';
                    break;
                case 'consigneeCountryAbbreviationBRT':
                    $sort = 'a.consigneeCountryAbbreviationBRT';
                    break;
                case 'numberOfParcels':
                    $sort = 'a.numberOfParcels';
                    break;
                case 'weightKG':
                    $sort = 'a.weightKG';
                    break;
                case 'volumeM3':
                    $sort = 'a.volumeM3';
                    break;
                case 'year':
                    $sort = 'a.year';
                    break;
                case 'borderoNumber':
                    $sort = 'a.borderoNumber';
                    break;
                case 'borderoDate':
                    $sort = 'a.borderoDate';
                    break;
                case 'printed':
                    $sort = 'a.printed';
                    break;
                case 'isCODMandatory':
                    $sort = 'b.isCODMandatory';
                    break;
                default:
                    $sort = 'a.' . $sort;
                    break;
            }
        }

        $sql = new DbQuery();
        $sql
            ->select('a.*')
            ->select('b.isCODMandatory')
            ->select('b.cashOnDelivery')
            ->from(ModelBrtLabelsResponse::$definition['table'], 'a')
            ->innerJoin(ModelBrtLabelsRequest::$definition['table'], 'b', 'a.numericSenderReference = b.numericSenderReference AND a.year=b.year')
            ->orderBy("{$sort} {$order}")
            ->orderBy(ModelBrtLabelsResponse::$definition['primary'] . ' DESC')
            ->limit($limit, $offset);

        if ($filter) {
            $sqlFiltered = new DbQuery();
            $sqlFiltered
                ->select('count(a.year) as total')
                ->from(ModelBrtLabelsResponse::$definition['table'], 'a')
                ->innerJoin(ModelBrtLabelsRequest::$definition['table'], 'b', 'a.numericSenderReference = b.numericSenderReference AND a.year=b.year');
            foreach ($filter as $field => $value) {
                switch ($field) {
                    case 'id_brt_labels_response':
                        $value = (int) $value;
                        $sql->where("{$field} = {$value}");
                        $sqlFiltered->where("{$field} = {$value}");
                        break;
                    case 'numericSenderReference':
                    case 'alphanumericSenderReference':
                    case 'consigneeCompanyName':
                    case 'consigneeAddress':
                    case 'consigneeZIPCode':
                    case 'consigneeCity':
                    case 'consigneeProvinceAbbreviation':
                    case 'consigneeCountryAbbreviationBRT':
                        $value = pSQL($value);
                        $sql->where("a.{$field} LIKE '{$value}%'");
                        $sqlFiltered->where("a.{$field} LIKE '{$value}%'");
                        break;
                    case 'numberOfParcels':
                        $value = (int) $value;
                        $sql->where("a.{$field} = {$value}");
                        $sqlFiltered->where("a.{$field} = {$value}");
                        break;
                    case 'weightKg':
                    case 'volumeM3':
                        if (preg_match('/(>=|<=|>|<)\s*(\d+(?:\.\d+)?)/', $value, $matches)) {
                            $operator = $matches[1];
                            $number = (float) $matches[2];
                        } else {
                            $operator = '>=';
                            $number = (float) $value;
                        }
                        $sql->where("a.{$field} {$operator} {$number}");
                        $sqlFiltered->where("a.{$field} {$operator} {$number}");
                        break;
                    case 'year':
                    case 'borderoNumber':
                        $value = (int) $value;
                        $sql->where("a.{$field} = {$value}");
                        $sqlFiltered->where("a.{$field} = {$value}");
                        break;
                    case 'borderodate':
                        if (Validate::isDateFormat($value)) {
                            $value = pSQL($value);
                            $sql->where("a.{$field} = '{$value}'");
                            $sqlFiltered->where("a.{$field} = '{$value}'");
                        }
                        break;
                    case 'printed':
                        $value = (int) $value;
                        $sql->where("a.{$field} = {$value}");
                        $sqlFiltered->where("a.{$field} = {$value}");
                        break;
                    case 'isCODMandatory':
                        $value = (int) $value;
                        $sql->where("b.{$field} = {$value}");
                        $sqlFiltered->where("b.{$field} = {$value}");
                        break;
                }
            }

            $total = (int) $db->getValue($sqlFiltered);
        }

        $parcels = $db->executeS($sql);

        if (empty($parcels)) {
            return ['rows' => [], 'total' => 0, 'totalNotFiltered' => 0];
        } else {
            return ['rows' => $parcels, 'total' => $total, 'totalNotFiltered' => $totalNotFiltered];
        }
    }

    public function ajaxProcessImportFromV16()
    {
        $offset = (int) Tools::getValue('offset', 0);
        $limit = (int) Tools::getValue('limit', 1000);

        $token = Configuration::get('MP_REQUEST_API_TOKEN');
        $url = Configuration::get('MP_REQUEST_API_URL');

        $query = new DbQuery();
        $query->select('*')->from('mp_brt_label_weight')->orderBy('id_weight ASC')->limit($limit, $offset);
        $query = $query->build();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'token=' . $token . '&query=' . $query);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);

        curl_close($ch);

        if ($response === false) {
            return ['errors' => 'Errore nella risposta', 'end' => false, 'offset' => $offset, 'rows' => 0];
        }

        $this->confirmations[] = $this->module->l('Richiesta effettuata con successo');

        $data = json_decode($response, true);

        if (!$data['success']) {
            return ['errors' => 'Nessun dato restituito', 'end' => true, 'offset' => $offset, 'rows' => 0];
        }

        // Decodifico i dati base64
        $base64 = base64_decode($data['data']);
        // Decodifico i dati json
        $data = json_decode($base64, true);

        if (!$data) {
            return ['end' => true];
        }

        foreach ($data as $row) {
            $model = ModelBrtLabelsParcel::getByParcelId($row['id_weight']);
            $model->PECOD = $row['barcode'];
            $model->PPESO = number_format($row['weight'], 1, '.', '');
            $model->PVOLU = number_format($row['volume'], 3, '.', '');
            $model->X = $row['x'] ?? 0;
            $model->Y = $row['y'] ?? 0;
            $model->Z = $row['z'] ?? 0;
            $model->ID_FISCALE = $row['id_read'] ?? 0;
            $model->PFLAG = $row['pflag'] ?? 0;
            $model->PTIMP = $row['ptimp'] ?? 0;
            $model->date_add = $row['date_add'] ?? date('Y-m-d H:i:s');
            $model->date_upd = $row['date_upd'] ?? date('Y-m-d H:i:s');

            try {
                $model->save();
            } catch (\Exception $e) {
                $this->errors[] = $this->module->l('Errore nel salvataggio: ' . $e->getMessage());
            }
        }

        return ['errors' => $this->errors, 'end' => false, 'offset' => $offset, 'rows' => count($data)];
    }

    public function ajaxProcessReadOrderRequestParameters()
    {
        $orderId = (int) Tools::getValue('orderId');
        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            return ['error' => 'Ordine non trovato'];
        }

        $customer = new Customer($order->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            return ['error' => 'Cliente non trovato'];
        }

        $address = new Address($order->id_address_delivery);
        if (!Validate::isLoadedObject($address)) {
            return ['error' => 'Indirizzo non trovato'];
        }

        $country = new Country($address->id_country);
        if (!Validate::isLoadedObject($country)) {
            $country = new stdClass();
            $country->iso_code = '';
        }

        $state = new State($address->id_state);
        if (!Validate::isLoadedObject($state)) {
            $state = new stdClass();
            $state->iso_code = '';
        }

        $parcels = $this->getParcels($order->id);

        $result = [
            'numericSenderReference' => $order->id,
            'alphanumericSenderReference' => $order->reference,
            'senderParcelType' => Configuration::get('MPBRTLABELS_SENDER_PARCEL_TYPE') ?: 'VARIA',
            'consigneeCompanyName' => $address->company ? Tools::strtoupper($address->company) : Tools::strtoupper($customer->firstname . ' ' . $customer->lastname),
            'consigneeAddress' => Tools::strtoupper($address->address1 . ' ' . $address->address2),
            'consigneeZIPCode' => Tools::strtoupper($address->postcode),
            'consigneeCity' => Tools::strtoupper($address->city),
            'consigneeProvinceAbbreviation' => Tools::strtoupper($state->iso_code),
            'consigneeCountryAbbreviationISOAlpha2' => Tools::strtoupper($country->iso_code),
            'consigneeContactName' => Tools::strtoupper($customer->firstname . ' ' . $customer->lastname),
            'consigneeTelephone' => $address->phone,
            'consigneeMobilePhoneNumber' => $address->phone_mobile,
            'consigneeEMail' => $customer->email,
            'notes' => $address->other,
            'isCODMandatory' => $this->isCodMandatory($order->module),
            'cashOnDelivery' => $order->total_paid_tax_incl,
            'codPaymentType' => '',
            'network' => Configuration::get('MPBRTLABELS_NETWORK'),
            'deliveryFreightTypeCode' => Configuration::get('MPBRTLABELS_DELIVERY_FREIGHT_TYPE_CODE'),
            'serviceType' => Configuration::get('MPBRTLABELS_SERVICE_TYPE'),
            'pudoId' => '',
            'parcels' => $parcels,
            'parcelsHtml' => $this->renderTableRows($parcels),
            'labelExists' => $this->labelExists($order->id),
            'labels' => $this->getLabels($order->id),
        ];

        // Controllo se esiste una request effettuata
        $request = ModelBrtLabelsRequest::getByNumericSenderReference($order->id);
        if (Validate::isLoadedObject($request)) {
            $requestData = $request->createDataJson;
            if ($requestData) {
                /*
                 * "network": "",
                 * "departureDepot": "102",
                 * "senderCustomerCode": "1020111",
                 * "deliveryFreightTypeCode": "DAP",
                 * "consigneeCompanyName": "MASSIMILIANO PALERMO",
                 * "consigneeAddress": "CDA PETRONI 77 ",
                 * "consigneeZIPCode": "87018",
                 * "consigneeCity": "SAN MARCO ARGENTANO",
                 * "consigneeProvinceAbbreviation": "CS",
                 * "consigneeCountryAbbreviationISOAlpha2": "IT",
                 * "consigneeContactName": "MASSIMILIANO PALERMO",
                 * "consigneeTelephone": "3925296839",
                 * "consigneeEMail": "maxx.palermo@gmail.com",
                 * "consigneeMobilePhoneNumber": "3925296839",
                 * "notes": "",
                 * "isAlertRequired": 1,
                 * "pricingConditionCode": "",
                 * "serviceType": "",
                 * "senderParcelType": "ABBIGLIAMENTO",
                 * "numericSenderReference": "138329",
                 * "alphanumericSenderReference": "WW-490017",
                 * "numberOfParcels": "3",
                 * "weightKG": 1,
                 * "volumeM3": "0.273",
                 * "isCODMandatory": "1",
                 * "codPaymentType": "",
                 * "cashOnDelivery": "48.520000",
                 * "codCurrency": "EUR"
                 */

                $parcels = $this->getParcels($order->id);

                $result = [
                    'numericSenderReference' => $requestData['numericSenderReference'],
                    'alphanumericSenderReference' => $requestData['alphanumericSenderReference'],
                    'senderParcelType' => $requestData['senderParcelType'] ?: 'VARIA',
                    'consigneeCompanyName' => $requestData['consigneeCompanyName'],
                    'consigneeAddress' => $requestData['consigneeAddress'],
                    'consigneeZIPCode' => $requestData['consigneeZIPCode'],
                    'consigneeCity' => $requestData['consigneeCity'],
                    'consigneeProvinceAbbreviation' => $requestData['consigneeProvinceAbbreviation'],
                    'consigneeCountryAbbreviationISOAlpha2' => $requestData['consigneeCountryAbbreviationISOAlpha2'],
                    'consigneeContactName' => $requestData['consigneeContactName'],
                    'consigneeTelephone' => $requestData['consigneeTelephone'],
                    'consigneeMobilePhoneNumber' => $requestData['consigneeMobilePhoneNumber'],
                    'consigneeEMail' => $requestData['consigneeEMail'],
                    'notes' => $requestData['notes'],
                    'isCODMandatory' => (int) $requestData['isCODMandatory'],
                    'cashOnDelivery' => (float) $requestData['cashOnDelivery'],
                    'codPaymentType' => $requestData['codPaymentType'],
                    'network' => $requestData['network'],
                    'deliveryFreightTypeCode' => $requestData['deliveryFreightTypeCode'],
                    'serviceType' => $requestData['serviceType'],
                    'pudoId' => isset($requestData['pudoId']) ? $requestData['pudoId'] : '',
                    'parcels' => $parcels,
                    'parcelsHtml' => $this->renderTableRows($parcels),
                    'labelExists' => $this->labelExists($order->id),
                    'labels' => $this->getLabels($order->id),
                ];
            }
        }

        return [
            'success' => true,
            'params' => $result,
        ];
    }

    public function renderTableRows($parcels)
    {
        $twig = new GetTwigEnvironment($this->module->name);
        $template = $twig->load('Controllers/ParcelRows.html.twig');

        return $template->render([
            'parcels' => $parcels,
        ]);
    }

    public function ajaxProcessSaveParcel()
    {
        $parcel = json_decode(Tools::getValue('parcel'), 1);
        if (!is_array($parcel)) {
            return [
                'success' => false,
                'error' => 'Dati parcel non validi'
            ];
        }

        $model = new ModelBrtLabelsParcel($parcel['id']);
        $model->hydrate($parcel);
        if (Validate::isLoadedObject($model)) {
            $res = $model->update();
        } else {
            $res = $model->add();
        }

        return [
            'success' => (bool) $res,
            'parcelId' => $model->id
        ];
    }

    public function ajaxProcessSaveLabelField()
    {
        $mode = (string) Tools::getValue('mode', 'write');
        $orderId = (int) (Tools::getValue('orderId') ?: Tools::getValue('id_order') ?: Tools::getValue('id-order'));
        if (!$orderId) {
            return ['success' => false, 'error' => 'ID ordine mancante'];
        }

        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            return ['success' => false, 'error' => 'Ordine non trovato'];
        }

        $year = (int) date('Y');
        $request = ModelBrtLabelsRequest::getByNumericSenderReference($orderId, $year);
        if (!$request) {
            $request = new ModelBrtLabelsRequest();
            $request->year = $year;
            $request->orderId = (int) $orderId;
            $request->numericSenderReference = (string) $orderId;
            $request->alphanumericSenderReference = (string) $order->reference;
            $request->isLabelRequired = 1;
            $request->accountJson = [];
            $request->labelParametersJson = [];
            $request->parcelsJson = [];
            $request->createDataJson = [];
        }

        if (!is_array($request->createDataJson)) {
            $request->createDataJson = [];
        }

        if ($mode === 'read') {
            $name = (string) Tools::getValue('name');
            $value = $name && isset($request->createDataJson[$name]) ? $request->createDataJson[$name] : '';
            return ['success' => true, 'value' => $value];
        }

        $valuesJson = Tools::getValue('values');
        if ($valuesJson) {
            $decoded = json_decode($valuesJson, true);
            if (is_array($decoded)) {
                foreach ($decoded as $k => $v) {
                    $request->createDataJson[(string) $k] = $v;
                }
            }
        } else {
            $name = (string) Tools::getValue('name');
            $value = Tools::getValue('value');
            if ($name !== '') {
                $request->createDataJson[$name] = $value;
            }
        }

        if (array_key_exists('isCODMandatory', $request->createDataJson)) {
            $request->isCODMandatory = (bool) (int) $request->createDataJson['isCODMandatory'];
        }
        if (array_key_exists('cashOnDelivery', $request->createDataJson)) {
            $request->cashOnDelivery = (float) $request->createDataJson['cashOnDelivery'];
        }

        try {
            if ($request->id) {
                $request->update();
            } else {
                $request->add();
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return [
            'success' => true,
            'value' => Tools::getValue('value', ''),
        ];
    }

    protected function getLabels($numericSenderReference)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('labels')
            ->from(ModelBrtLabelsResponse::$definition['table'])
            ->where("numericSenderReference = {$numericSenderReference}")
            ->where("JSON_LENGTH(labels, '\$.label') > 0");
        $result = $db->getValue($sql);

        if ($result) {
            $result = json_decode($result, true);
            $result = reset($result);
            $output = [];
            foreach ($result as $label) {
                $output[] = [
                    'parcelId' => $label['parcelID'],
                    'trackingByParcelID' => $label['trackingByParcelID'],
                    'stream' => $label['stream'],
                ];
            }

            return $output;
        }

        return false;
    }

    protected function parseLabelsJson($labelsJson)
    {
        if (!$labelsJson) {
            return [];
        }

        $decoded = json_decode($labelsJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        $labels = reset($decoded);
        if (!is_array($labels)) {
            return [];
        }

        $streams = [];
        foreach ($labels as $label) {
            if (isset($label['stream']) && $label['stream']) {
                $streams[] = $label['stream'];
            }
        }

        return $streams;
    }

    public function ajaxProcessFetchLabelsByRefs()
    {
        $itemsJson = Tools::getValue('items');
        $items = json_decode($itemsJson, true);
        if (!is_array($items)) {
            $items = [];
        }

        $yearDefault = (int) date('Y');

        $db = Db::getInstance();
        $allStreams = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $nsr = isset($item['numericSenderReference']) ? (int) $item['numericSenderReference'] : 0;
            $year = isset($item['year']) ? (int) $item['year'] : $yearDefault;
            if ($nsr <= 0) {
                continue;
            }

            $sql = new DbQuery();
            $sql
                ->select('labels')
                ->from(ModelBrtLabelsResponse::$definition['table'])
                ->where('numericSenderReference = ' . (int) $nsr)
                ->where('year = ' . (int) $year)
                ->where("JSON_LENGTH(labels, '\$.label') > 0");

            $labelsJson = $db->getValue($sql);
            $streams = $this->parseLabelsJson($labelsJson);
            foreach ($streams as $s) {
                $allStreams[] = $s;
            }
        }

        return [
            'success' => count($allStreams) > 0,
            'streams' => $allStreams,
        ];
    }

    protected function buildBorderoDataFromResponses(array $responses): array
    {
        $customerCode = (string) Configuration::get('MPBRTLABELS_ACCOUNT_CUSTOMER_CODE');
        $senderCompanyName = '';
        if (!empty($responses) && isset($responses[0]['senderCompanyName'])) {
            $senderCompanyName = (string) $responses[0]['senderCompanyName'];
        }

        $company = trim(sprintf('[%s] %s', $customerCode, $senderCompanyName));
        if ($company === '[]') {
            $company = '[BRT]';
        }

        $rows = [];
        $totSpedizioni = 0;
        $totColli = 0;
        $totContrassegniOrdini = 0;
        $importoContrassegni = 0.0;
        $totPeso = 0.0;
        $totVolume = 0.0;

        foreach ($responses as $r) {
            $totSpedizioni++;
            $colli = (int) ($r['numberOfParcels'] ?? 0);
            $peso = (float) ($r['weightKG'] ?? 0);
            $vol = (float) ($r['volumeM3'] ?? 0);
            $totColli += $colli;
            $totPeso += $peso;
            $totVolume += $vol;

            $cassa = 0.0;
            if ((int) ($r['isCODMandatory'] ?? 0) === 1) {
                $cassa = (float) ($r['cashOnDelivery'] ?? 0);
                if ($cassa > 0) {
                    $totContrassegniOrdini++;
                    $importoContrassegni += $cassa;
                }
            }

            $segnacolli = '';
            if (!empty($r['labels'])) {
                $labels = is_array($r['labels']) ? $r['labels'] : json_decode($r['labels'], true);
                if (is_array($labels)) {
                    $labelArr = reset($labels);
                    if (is_array($labelArr) && !empty($labelArr[0])) {
                        // $segnacolli = (string) ($labelArr[0]['trackingByParcelID'] ?? ($labelArr[0]['parcelID'] ?? ''));
                        $segnacolli = $r['parcelNumberFrom'] . "\n" . $r['parcelNumberTo'];
                    }
                }
            }

            $rows[] = [
                'destinatario' => (string) ($r['consigneeCompanyName'] ?? ''),
                'indirizzo' => trim((string) ($r['consigneeAddress'] ?? '')) . "\n" . trim(sprintf('%s %s - %s', (string) ($r['consigneeZIPCode'] ?? ''), (string) ($r['consigneeCity'] ?? ''), (string) ($r['consigneeProvinceAbbreviation'] ?? ''))),
                'rif_num' => (string) ($r['numericSenderReference'] ?? ''),
                'cod_bolla' => (string) ($r['alphanumericSenderReference'] ?? ''),
                'importo_cassa' => $cassa,
                'colli' => $colli,
                'peso' => $peso,
                'volume' => $vol,
                'segnacolli' => $segnacolli,
            ];
        }

        return [
            'header' => [
                'company' => $company,
                'generatedAt' => date('d/m/Y H:i:s'),
            ],
            'rows' => $rows,
            'summary' => [
                'totale_spedizioni' => $totSpedizioni,
                'totale_colli' => $totColli,
                'totale_contrassegni_ordini' => $totContrassegniOrdini,
                'importo_contrassegni' => $importoContrassegni,
                'totale_peso' => $totPeso,
                'totale_volume' => $totVolume,
            ],
        ];
    }

    protected function fetchResponsesByRefs(array $items): array
    {
        $yearDefault = (int) date('Y');
        $db = Db::getInstance();
        $out = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $nsr = isset($item['numericSenderReference']) ? (int) $item['numericSenderReference'] : 0;
            $year = isset($item['year']) ? (int) $item['year'] : $yearDefault;
            if ($nsr <= 0) {
                continue;
            }

            $sql = new DbQuery();
            $sql
                ->select('a.*')
                ->select('b.isCODMandatory')
                ->select('b.cashOnDelivery')
                ->from(ModelBrtLabelsResponse::$definition['table'], 'a')
                ->leftJoin(ModelBrtLabelsRequest::$definition['table'], 'b', 'a.numericSenderReference = b.numericSenderReference AND a.year=b.year')
                ->where('a.numericSenderReference = ' . (int) $nsr)
                ->where('a.year = ' . (int) $year);

            $row = $db->getRow($sql);
            if ($row) {
                $out[] = $row;
            }
        }

        return $out;
    }

    public function ajaxProcessPrintParcels()
    {
        $itemsJson = Tools::getValue('items');
        $items = json_decode($itemsJson, true);
        if (!is_array($items)) {
            $items = [];
        }

        $responses = $this->fetchResponsesByRefs($items);
        if (empty($responses)) {
            return [
                'success' => false,
                'error' => 'Nessun dato trovato per i riferimenti richiesti',
            ];
        }

        $data = $this->buildBorderoDataFromResponses($responses);
        $pdfBinary = Bordero::render($data);

        return [
            'success' => true,
            'pdfBase64' => base64_encode($pdfBinary),
            'filename' => 'bordero_' . date('YmdHis') . '.pdf',
        ];
    }

    public function ajaxProcessPrintMultipleLabels()
    {
        $labels = json_decode(Tools::getValue('items'), true);
        if (!is_array($labels)) {
            $labels = [];
        }

        $stream = Label::getLabelsStream($labels);

        return [
            'success' => true,
            'stream' => $stream,
            'filename' => 'etichette_' . date('YmdHis') . '.pdf',
        ];
    }

    public function ajaxProcessPrintLastBordero()
    {
        $update = true;
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('borderoNumber')
            ->select('year')
            ->from(ModelBrtLabelsResponse::$definition['table'])
            ->where('borderoNumber IS NOT NULL')
            ->orderBy('borderoDate DESC')
            ->orderBy(ModelBrtLabelsResponse::$definition['primary'] . ' DESC');

        $last = $db->getRow($sql);
        if (!$last || empty($last['borderoNumber'])) {
            $borderoNumber = 1;
        } else {
            $borderoNumber = (int) $last['borderoNumber'] + 1;
        }

        $year = (int) ($last['year'] ?? date('Y'));

        $sql2 = new DbQuery();
        $sql2
            ->select('a.*')
            ->select('b.isCODMandatory')
            ->select('b.cashOnDelivery')
            ->from(ModelBrtLabelsResponse::$definition['table'], 'a')
            ->leftJoin(ModelBrtLabelsRequest::$definition['table'], 'b', 'a.numericSenderReference = b.numericSenderReference AND a.year=b.year')
            ->where('a.printed = 0')
            ->where('a.year = ' . (int) $year)
            ->orderBy('a.numericSenderReference ASC');

        $responses = $db->executeS($sql2);

        if (empty($responses)) {
            // Stampo l'ultimo borderò
            $borderoNumber--;

            $sql3 = new DbQuery();
            $sql3
                ->select('a.*')
                ->select('b.isCODMandatory')
                ->select('b.cashOnDelivery')
                ->from(ModelBrtLabelsResponse::$definition['table'], 'a')
                ->leftJoin(ModelBrtLabelsRequest::$definition['table'], 'b', 'a.numericSenderReference = b.numericSenderReference AND a.year=b.year')
                ->where('a.printed = 1')
                ->where('a.year = ' . (int) $year)
                ->where('a.borderoNumber = ' . (int) $borderoNumber)
                ->orderBy('a.numericSenderReference ASC');

            $responses = $db->executeS($sql3);
            $update = false;
        }

        $data = $this->buildBorderoDataFromResponses($responses);
        $pdfBinary = Bordero::render($data);

        if ($update) {
            $update = $this->updateLastBordero($borderoNumber, $year);
        } else {
            $update = [
                'updated' => 0,
                'total' => 0,
            ];
        }

        return [
            'success' => true,
            'pdfBase64' => base64_encode($pdfBinary),
            'filename' => 'bordero_' . date('YmdHis') . '.pdf',
            'borderoNumber' => $borderoNumber,
            'year' => $year,
            'updated' => $update['updated'],
            'total' => $update['total'],
        ];
    }

    protected function updateLastBordero($borderoNumber, $year)
    {
        $table = ModelBrtLabelsResponse::$definition['table'];
        $primary = ModelBrtLabelsResponse::$definition['primary'];
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select($primary)
            ->from($table)
            ->where('printed = 0')
            ->where('year = ' . (int) $year)
            ->orderBy($primary . ' ASC');

        $response = $db->executeS($sql);
        $total = 0;
        $updated = 0;
        if ($response) {
            $total = count($response);
            foreach ($response as $row) {
                $model = new ModelBrtLabelsResponse((int) $row[$primary]);
                if (!Validate::isLoadedObject($model)) {
                    continue;
                }
                $model->borderoNumber = $borderoNumber;
                $model->borderoDate = date('Y-m-d H:i:s');
                $model->printed = 1;
                $model->update();
                $updated++;
            }
        }

        return [
            'total' => $total,
            'updated' => $updated,
        ];
    }

    protected function labelExists($numericSenderReference)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select("JSON_LENGTH(labels, '\$.label') AS label_count")
            ->from(ModelBrtLabelsResponse::$definition['table'])
            ->where("numericSenderReference = {$numericSenderReference}")
            ->where("JSON_LENGTH(labels, '\$.label') > 0");
        $result = $db->getValue($sql);

        return (int) $result > 0;
    }

    protected function isCodMandatory($payment)
    {
        $idPaymentModules = json_decode(Configuration::get('MPBRTLABELS_CASH_ON_DELIVERY_MODULE'), true);
        $paymentsModulesName = array_map(function ($idPaymentModule) {
            $paymentModule = Module::getInstanceById($idPaymentModule);
            return $paymentModule->name;
        }, $idPaymentModules);

        return in_array($payment, $paymentsModulesName);
    }

    protected function getParcels($id_order)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('*, ' . ModelBrtLabelsParcel::$definition['primary'] . ' as `id`')
            ->from(ModelBrtLabelsParcel::$definition['table'])
            ->where("PECOD LIKE '{$id_order}%'")
            ->orderBy(ModelBrtLabelsParcel::$definition['primary']);

        $result = $db->executeS($sql);

        return $result;
    }

    public function ajaxProcessSendRequest()
    {
        $orderId = (int) Tools::getValue('orderId');
        $createData = json_decode(Tools::getValue('createData'), 1);
        $parcels = json_decode(Tools::getValue('parcels', '[]'), 1);
        $showRequest = (int) Tools::getValue('showRequestData');

        if (isset($createData['changeOrderState'])) {
            $changeOrderState = (int) $createData['changeOrderState'];
            unset($createData['changeOrderState']);
        }

        if ($labels = $this->existsResponse($createData)) {
            return [
                'status' => true,
                'executionMessage' => [
                    'code' => 0,
                    'severity' => 'INFO',
                    'codeDesc' => 'RICHIESTA GIÀ INVIATA',
                    'message' => 'Richiesta già inviata. Anteprima etichette in corso',
                ],
                'labels' => $labels,
                'numericSenderReference' => $createData['numericSenderReference'],
            ];
        }

        $requestData = new RequestData($orderId, $createData, $parcels, (int) date('Y'));

        if ($showRequest) {
            exit(json_encode($requestData->showRequestData()));
        }

        $result = $requestData->send();

        if ($changeOrderState) {
            $id_order_state = (int) Configuration::get('MPBRTLABELS_ORDERSTATE_CHANGE');
            $id_employee = (int) $this->context->employee->id;
            $order = new Order($orderId);
            if ($id_order_state && $id_order_state != $order->current_state) {
                $order->setCurrentState($id_order_state, $id_employee);
            }
        }

        return $result;
    }

    private function existsResponse($data)
    {
        $numericSenderReference = $data['numericSenderReference'] ?? 0;
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('labels')
            ->from('brt_labels_response')
            ->where("numericSenderreference = '" . pSQL($numericSenderReference) . "'")
            ->where('year = ' . (int) date('Y'))
            ->where('executionMessage IS NOT NULL')
            ->where("executionMessage <> ''")
            ->where('JSON_VALID(executionMessage)')
            ->where("CAST(JSON_UNQUOTE(JSON_EXTRACT(executionMessage, '\$.code')) AS SIGNED) >= 0");
        $labels = $db->getValue($sql);
        if ($labels) {
            return json_decode($labels, 1);
        }

        return false;
    }

    public function ajaxProcessAddEmptyRow()
    {
        $pecod = Tools::getValue('pecod');
        $orderId = (int) Tools::getValue('orderId');

        $twig = new GetTwigEnvironment($this->module->name);
        $twig->load('@ModuleTwig/Controllers/ParcelRow');

        $pecodSplit = explode('-', $pecod);
        $count = (int) $pecodSplit[1] + 1;

        $adminControllerUrl = $this->context->link->getAdminLink($this->controller_name);

        $data = [
            // Contesto pagina
            'id_order' => (int) $orderId,  // usato ovunque (parametri ajax, template, ecc.)
            'adminControllerUrl' => (string) $adminControllerUrl,  // finisce in JS: MPBRTLABELS_ENDPOINT
            'endpoint' => (string) $adminControllerUrl,  // usato nei table-input: data-endpoint="{{ endpoint|raw }}"
            // Dati colli (parcels table)
            'parcel' => [
                // lista di colli; ogni elemento viene usato in ParcelRow.html.twig
                'id_brt_labels_parcel' => 0,  // attributo <tr data-id="...">
                'id' => 0,  // usato come data-param-id-parcel="{{ parcel.id }}"
                'PECOD' => (string) ($orderId . '-' . $count),  // value pecod
                'X' => 0,  // nel twig: value = X/10 (cm)
                'Y' => 0,  // nel twig: value = Y/10 (cm)
                'Z' => 0,  // nel twig: value = Z/10 (cm)
                'PPESO' => 0,  // value peso
                'PVOLU' => 0,  // value volume (m3) -> input soft-disabled
            ],
        ];

        $row = $twig->render($data);

        return [
            'success' => true,
            'html' => $row,
        ];
    }

    public function ajaxProcessDeleteRequest()
    {
        $force = true;
        $numericSenderReference = (int) Tools::getValue('numericSenderReference');
        $alphanumericSenderReference = Tools::getValue('alphanumericSenderReference');
        $year = (int) Tools::getValue('year');
        $model = ModelBrtLabelsRequest::getByNumericSenderReference($numericSenderReference, $year);

        if (!\Validate::isLoadedObject($model) && !$force) {
            return [
                'success' => false,
                'error' => 'Etichetta non trovata',
            ];
        }

        if (!$model) {
            $model = new ModelBrtLabelsRequest();
            $model->numericSenderReference = $numericSenderReference;
            $model->alphanumericSenderReference = $alphanumericSenderReference;
            $model->year = $year;
        }

        $response = $model->delete($force);

        return [
            'numericSenderReference' => $numericSenderReference,
            'alphanumericSenderReference' => $alphanumericSenderReference,
            'year' => $year,
            'response' => $response,
        ];
    }

    public function ajaxProcessUpdateParcel()
    {
        /*
         * parcelId;
         * parcelCode;
         * x;
         * y;
         * z;
         * weight;
         * volume;
         */
        $data = Tools::getAllValues();

        $PECOD = $data['parcelCode'];
        $model = ModelBrtLabelsParcel::getByParcelCode($PECOD);

        $model->PECOD = $data['parcelCode'];
        $model->PPESO = $data['weight'];
        $model->PVOLU = $data['volume'];
        $model->X = (int) ($data['x'] * 10);
        $model->Y = (int) ($data['y'] * 10);
        $model->Z = (int) ($data['z'] * 10);
        $result = false;

        try {
            if (Validate::IsLoadedObject($model)) {
                $result = $model->update();
                return [
                    'success' => true,
                    'params' => $data,
                    'result' => $result,
                ];
            }

            if (isset($data['parcelId']) && $data['parcelId']) {
                $model->force_id = $data['parcelId'];
                $model->id = $data['parcelId'];
                $result = $model->add();
            } else {
                $result = $model->add();
            }
        } catch (\Throwable $th) {
            $result = false;
            $this->errors[] = $th->getMessage();
        }

        return [
            'success' => $result,
            'errors' => $this->errors,
            'params' => $data,
            'result' => $result,
        ];
    }

    public function ajaxProcessRemoveParcel()
    {
        $parcelId = (int) Tools::getValue('parcelId');
        return [
            'success' => true,
            'params' => $parcelId,
        ];
    }
}
