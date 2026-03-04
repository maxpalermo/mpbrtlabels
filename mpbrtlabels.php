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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/vendor/autoload.php';

use MpSoft\MpBrtLabels\Api\Request\CodPaymentType;
use MpSoft\MpBrtLabels\Helpers\GetTwigEnvironment;
use MpSoft\MpBrtLabels\Models\ModelBrtLabelsParcel;
use MpSoft\MpBrtLabels\Models\ModelBrtLabelsRequest;
use MpSoft\MpBrtLabels\Models\ModelBrtShipmentResponseLabel;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Grid\Action\Type\SimpleGridAction;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class MpBrtLabels extends Module implements WidgetInterface
{
    protected static $adminControllerName = 'AdminMpBrtLabels';
    protected static $frontControllerName = 'Api';
    protected static $adminControllerUrl;
    protected static $frontControllerUrl;
    protected static $APIAutoWeightUrl;

    public function __construct()
    {
        $this->name = 'mpbrtlabels';
        $this->tab = 'shipping_logistics';
        $this->version = '0.0.2';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('BRT Stampa segnacolli e Borderò');
        $this->description = $this->l('Invio spedizioni tramite API BRT.');
        self::$adminControllerUrl = self::getAdminControllerUrl();
        self::$frontControllerUrl = self::getFrontControllerUrl();
        self::$APIAutoWeightUrl = self::getApiAutoWeightUrl();
    }

    public function getWidgetVariables($hookName, array $configuration) {}

    public function renderWidget($hookName, array $configuration)
    {
        switch ($hookName) {
            case 'displayDashboardToolbarTopMenu':
                return $this->hookDisplayDashboardToolbarTopMenu($configuration);
        }

        return '';
    }

    public function install()
    {
        $parentInstall = parent::install();

        $hooks = [
            'actionOrderGridDefinitionModifier',
            'actionOrderGridQueryBuilderModifier',
            'actionOrderGridDataModifier',
            'actionAdminControllerSetMedia',
            'displayAdminOrderTop',
            'displayAdminEndContent',
            'displayDashboardToolbarTopMenu',
        ];

        return $parentInstall &&
            $this->registerHook($hooks) &&
            $this->installMenu() &&
            ModelBrtLabelsRequest::install() &&
            ModelBrtLabelsParcel::install();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallMenu();
    }

    public function installMenu()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = self::$adminControllerName;
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'MP Borderò BRT';
        }

        $tabRes = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
        $tab_id = $tabRes->findOneIdByClassName('AdminParentShipping');

        $tab->id_parent = $tab_id;
        $tab->module = $this->name;
        $tab->icon = 'local_shipping';
        $tab->position = 1;
        $tab->enabled = 1;
        $tab->active = 1;

        return $tab->add();
    }

    public function uninstallMenu()
    {
        $tabRes = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
        $tab_id = $tabRes->findOneIdByClassName(self::$adminControllerName);

        $tab = new Tab($tab_id);
        if (\Validate::isLoadedObject($tab)) {
            $tab->delete();
        }

        return true;
    }

    public static function getFrontControllerUrl()
    {
        $module_name = 'mpbrtlabels';
        $context = Context::getContext();
        return $context->link->getModuleLink($module_name, 'Api');
    }

    public static function getAdminControllerUrl()
    {
        $context = Context::getContext();
        return $context->link->getAdminLink(self::$adminControllerName);
    }

    public static function getApiAutoWeightUrl()
    {
        $module_name = 'mpbrtlabels';
        $context = Context::getContext();
        return $context->link->getModuleLink($module_name, self::$frontControllerName);
    }

    public static function getApiAutoWeightUrlWithParams($pecod = null, $ppeso = null, $pvolu = null, $x = null, $y = null, $z = null, $id_fiscale = null, $pflag = null, $envelope = null, $ptimp = null)
    {
        $module_name = 'mpbrtlabels';
        $context = Context::getContext();

        if (!$pecod || !$ppeso || !$pvolu || !$x || !$y || !$z) {
            return null;
        }

        if (!$id_fiscale) {
            $id_fiscale = '';
        }

        if (!$pflag) {
            $pflag = 0;
        }

        if (!$envelope) {
            $envelope = 0;
        }

        if (!$ptimp) {
            $ptimp = date('Y-m-d H:i:s');
        }

        if (!Validate::isDate($ptimp)) {
            $ptimp = date('Y-m-d H:i:s');
        }

        return $context->link->getModuleLink(
            $module_name,
            self::$frontControllerName,
            [
                'ajax' => 1,
                'action' => 'insert',
                'PECOD' => pSQL($pecod),
                'PPESO' => number_format((float) $ppeso, 1),
                'PVOLU' => number_format((float) $pvolu, 3),
                'X' => number_format((float) $x, 3),
                'Y' => number_format((float) $y, 3),
                'Z' => number_format((float) $z, 3),
                'ID_FISCALE' => pSQL($id_fiscale),
                'PFLAG' => (int) $pflag,
                'ENVELOPE' => (int) $envelope,
                'PTIMP' => $ptimp,
            ]
        );
    }

    /**
     * Carica CSS/JS custom nell'admin quando necessario.
     *
     * @param array $params
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        $controller = trim(Tools::getValue('controller'));
        $id_order = (int) Tools::getvalue('id_order');

        if (preg_match('/^(AdminOrders|AdminModules)$/i', $controller)) {
            $this->context->controller->addCSS([
                $this->getLocalPath() . 'views/assets/css/fonts.css',
                $this->getLocalPath() . 'views/assets/css/font-awesome-6.css',
            ]);
            $this->context->controller->addJS([
                $this->getLocalPath() . 'views/assets/PdfLib/pdf-lib.min.js',
            ]);
        }

        // Siamo nella pagina dell'ordine
        if (preg_match('/^AdminOrders$/i', $controller) && $id_order) {
            $this->context->controller->addJs([]);
        }
    }

    /**
     * Mostra contenuto custom sopra la pagina ordine in BO.
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayAdminOrderTop($params)
    {
        return '';
    }

    public function hookDisplayDashboardToolbarTopMenu($params)
    {
        if (!$this->isAdminOrderPageController()) {
            return;
        }

        $order = new Order(Tools::getValue('id_order', 0));
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        $currentState = $order->getCurrentState();
        $showButtonOnOrderStates = json_decode(Configuration::get('MPBRTLABELS_ORDERSTATES_DISPLAY'));
        $isAdminEmployee = $this->context->employee->isSuperAdmin();

        if (!$isAdminEmployee && !in_array($currentState, $showButtonOnOrderStates)) {
            return '';
        }

        if ($isAdminEmployee || in_array($currentState, $showButtonOnOrderStates)) {
            return '
                <button type="button" class="btn btn-danger mr-3 ml-3" id="btnShowBrtDialogNew" onclick="showBrtLabelDialog()">
                    <span class="material-icons">label</span>
                    <span>Etichetta Bartolini</span>
                </button>
            ';
        }

        return '';
    }

    protected function isAdminOrdersListController()
    {
        $controller = Tools::getValue('controller');
        if (!preg_match('/AdminOrders/i', $controller)) {
            return false;
        }
        if (Tools::getValue('id_order')) {
            return false;
        }

        return true;
    }

    protected function isAdminOrderPageController()
    {
        $controller = Tools::getValue('controller');
        if (!preg_match('/AdminOrders/i', $controller)) {
            return false;
        }
        if (!Tools::getValue('id_order')) {
            return false;
        }

        return true;
    }

    /**
     * Modifica la definizione della grid ordini (aggiunta colonne, bulk actions).
     */
    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        $definition = $params['definition'];

        // Add a button in the grid actions bar (top-right, near Export)
        $gridActions = $definition->getGridActions();
        $gridActions->add(
            (new SimpleGridAction('mpbrtlabels_open'))
                ->setName('BRTLABELS_BTN_OPEN')
                ->setIcon('local_shipping')
        );
    }

    /**
     * Modifica la query della grid ordini (filtri custom, join, etc).
     *
     * @param array $params
     */
    public function hookActionOrderGridQueryBuilderModifier($params)
    {
        return;
        $searchQueryBuilder = $params['search_query_builder'];

        // Aggiungi campo alla SELECT
        $searchQueryBuilder->addSelect('o.id_order as numericSenderReference');
    }

    /**
     * Modifica i dati della grid ordini (customizzazione dati, etc).
     *
     * @param array $params
     */
    public function hookActionOrderGridDataModifier($params)
    {
        return;
        /** @var PrestaShop\PrestaShop\Core\Grid\Data\GridData $gridData */
        $gridData = $params['data'];
        $records = $gridData->getRecords()->all();

        $modifiedRecords = [];

        foreach ($records as $record) {
            $html = '--';
            $numericSenderReference = $record['numericSenderReference'];
            $labels = ModelBrtShipmentResponseLabel::getByNumericSenderReference($numericSenderReference);

            if ($labels) {
                $parcelIds = array_column($labels, 'parcel_id');
                $labelContent = array_column($labels, 'stream');

                // se parcelIds ha null non c'è l'etichetta
                if (in_array(null, $parcelIds)) {
                    $html = '--';
                } else {
                    $html = sprintf(
                        '<button href="javascript:void(0)" class="btn btn-no-border brt-label-pdf" data-numeric_sender_reference="%s" data-parcel_id="%s" data-label="%s" title="Totale etichette: %s">
                            <span class="fas fa-barcode"></span>
                            <span class="badge badge-info">%s</span>
                        </button>',
                        $numericSenderReference,
                        implode(',', $parcelIds),
                        implode('~|~', $labelContent),
                        count($labels),
                        count($labels)
                    );
                }
            }

            $record['numericSenderReference'] = $html;
            $modifiedRecords[] = $record;
        }

        // Aggiorna usando Reflection
        $recordsCollection = new RecordCollection($modifiedRecords);
        $reflection = new \ReflectionClass($gridData);
        $property = $reflection->getProperty('records');
        $property->setAccessible(true);
        $property->setValue($gridData, $recordsCollection);
    }

    /**
     * Mostra contenuto custom in fondo alla pagina ordine in BO.
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayAdminEndContent($params)
    {
        if (!$this->isAdminOrderPageController()) {
            return '';
        }

        $controller = Tools::getValue('controller');
        $endpoint = $this->context->link->getAdminLink('AdminMpBrtLabels');
        $id_order = (int) Tools::getValue('id_order');

        $params = [
            'isAdminEmployee' => $this->context->employee->isSuperAdmin(),
            'endpoint' => $endpoint,
            'showImportOrder' => true,
            'network' => Configuration::get('MPBRTLABELS_NETWORK'),
            'deliveryFreightTypeCode' => (int) Configuration::get('MPBRTLABELS_DELIVERY_FREIGHT_TYPE_CODE'),
            'serviceType' => (int) Configuration::get('MPBRTLABELS_SERVICE_TYPE'),
            'orderStateChange' => $this->getOrderStateChange(),
            'codPaymentTypeOptions' => (new CodPaymentType(null))->getCodPaymentAssociativeArray(),
            'sandbox_enabled' => Configuration::get('MPBRTLABELS_SANDBOX_ENABLED'),
            'id_order' => $id_order,
            'isAdminOrdersPage' => preg_match('/AdminOrders/i', $controller),
        ];
        $twig = new GetTwigEnvironment($this->name);
        $twig->load('@ModuleTwig/Controllers/Admin/AdminBrtLabel');
        $html = $twig->render($params);

        return $html;
    }

    public function getBrtLabelFormData($orderId)
    {
        if (!$orderId) {
            return '';
        }

        $id_lang = (int) $this->context->getContext()->language->id;
        $configValues = (new BrtConfiguration())->getConfigValues();
        $formLabel = FormLabel::getFormData($orderId);

        $configValues['showOrderIdSearch'] = false;
        $configValues['cod_currency'] = $this->context->getContext()->currency->iso_code;
        $configValues['orderStates'] = OrderState::getOrderStates($id_lang);
        $configValues['formData'] = $formLabel;

        return $configValues;
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
}
