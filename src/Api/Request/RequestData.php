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
 *
 * network: {'': Standard, 'D': 'DPD', 'E': 'EuroExpress', 'S': 'FED'}
 * deliveryFreightTypeCode: {'DAP': 'Franco', 'EXW': 'Assegnato'}
 * serviceType: {'': 'Standard', 'E': 'Express', 'H': '10:30'}
 * codPaymentType: {'': 'Contanti', 'BM': 'Assegno Bancario Mittente', 'CM': 'Assegno Circolare Mittente', 'BB': 'Assegno Bancario Corriere Manleva', 'OM': 'Assegno Mittente Originale', 'OC': 'Assegno Circolare Mittente Originale'}
 * weightKG: precision 5, scale 1 - DEFAULT: 1.0
 * volumeM3: precision 5, scale 3 - DEFAUL: 0.000
 * cashOnDelivery: precision 7, scale 2 - DEFAULT: 0.00
 */

namespace MpSoft\MpBrtLabels\Api\Request;

use MpSoft\MpBrtLabels\Api\Rest\Create;
use MpSoft\MpBrtLabels\Models\ModelBrtLabelsRequest;
use MpSoft\MpBrtLabels\Models\ModelBrtLabelsResponse;

class RequestData
{
    protected $request_data;
    protected $context;
    protected $orderId;
    protected $numericSenderReference;
    protected $alphanumericSenderReference;
    protected $sandBox;
    protected $account_id;
    protected $account_pwd;
    protected $departure_depot;
    protected $customer_code;
    protected $network;
    protected $deliveryFreightTypeCode;
    protected $pricing_condition_code;
    protected $serviceType;
    protected $consignee;
    protected $isCODMandatory;
    protected $codPaymentType;
    protected $cashOnDelivery;
    protected $codCurrency;
    protected $senderParcelType;
    protected $currency = 'EUR';
    protected $notes;
    protected $year;
    protected $numberOfParcels;
    protected $weightKg;
    protected $volumeM3;
    protected $parcels;
    protected $pudoId;
    private $error;

    protected $notMandatory = [
        'insuranceAmount' => 0,
        'insuranceAmountCurrency' => 'EUR',
        'quantityToBeInvoiced' => 0.0,
        'consigneeClosingShift1_DayOfTheWeek' => '',
        'consigneeClosingShift1_PeriodOfTheDay' => '',
        'consigneeClosingShift2_DayOfTheWeek' => '',
        'consigneeClosingShift2_PeriodOfTheDay' => '',
        'consigneeVATNumber' => '',
        'consigneeVATNumberCountryISOAlpha2' => '',
        'consigneeItalianFiscalCode' => '',
        'parcelsHandlingCode' => '2',
        'deliveryDateRequired' => '',
        'deliveryType' => '',
        'declaredParcelValue' => 0,
        'declaredParcelValueCurrency' => 'EUR',
        'particularitiesDeliveryManagementCode' => '',
        'particularitiesHoldOnStockManagementCode' => '',
        'variousParticularitiesManagementCode' => 'B',
        'particularDelivery1' => '',
        'particularDelivery2' => '',
        'palletType1' => '',
        'palletType1Number' => 0,
        'palletType2' => '',
        'palletType2Number' => 0,
        'originalSenderCompanyName' => '',
        'originalSenderZIPCode' => '',
        'originalSenderCountryAbbreviationISOAlpha2' => '   ',
        'cmrCode' => '',
        'neighborNameMandatoryAuthorization' => '',
        'pinCodeMandatoryAuthorization' => '',
        'packingListPDFName' => '',
        'packingListPDFFlagPrint' => '',
        'packingListPDFFlagEmail' => '',
    ];

    public function __construct(
        int $orderId,
        array $createData,
        array $parcels,
        int $year,
    ) {
        if (!$year) {
            $year = date('Y');
        }

        $this->orderId = $orderId;
        $this->createData = $createData;
        $this->year = $year;
        $this->numericSenderReference = $createData['numericSenderReference'] ?? null;
        $this->alphanumericSenderReference = $createData['alphanumericSenderReference'] ?? null;
        $this->context = \Context::getContext();
        $this->sandBox = (int) \Configuration::get('MPBRTLABELS_SANDBOX_ENABLED');
        if ($this->sandBox) {
            $this->account_id = \Configuration::get('MPBRTLABELS_SANDBOX_ID');
            $this->account_pwd = \Configuration::get('MPBRTLABELS_SANDBOX_PWD');
            $this->departure_depot = \Configuration::get('MPBRTLABELS_SANDBOX_DEPARTURE_DEPOT');
            $this->customer_code = \Configuration::get('MPBRTLABELS_SANDBOX_CUSTOMER_CODE');
        } else {
            $this->account_id = \Configuration::get('MPBRTLABELS_ACCOUNT_ID');
            $this->account_pwd = \Configuration::get('MPBRTLABELS_ACCOUNT_PWD');
            $this->departure_depot = \Configuration::get('MPBRTLABELS_ACCOUNT_DEPARTURE_DEPOT');
            $this->customer_code = \Configuration::get('MPBRTLABELS_ACCOUNT_CUSTOMER_CODE');
        }
        $this->numericSenderReference = $createData['numericSenderReference'] ?? null;
        $this->alphanumericSenderReference = $createData['alphanumericSenderReference'] ?? null;
        $this->network = $createData['network'] ?? '';
        $this->deliveryFreightTypeCode = $createData['deliveryFreightTypeCode'] ?? 'DAP';
        $this->numberOfParcels = $createData['numberOfParcels'] ?? 1;
        $this->pricing_condition_code = (new PricingConditionCode($this->network, $this->numberOfParcels, $createData['weightKG'], $createData['volumeM3'], $this->sandBox))->getPricingConditionCode();
        $this->serviceType = $createData['serviceType'] ?? '';
        $this->consignee = new Consignee($createData);
        $this->senderParcelType = $createData['senderParcelType'] ?? '';
        $this->notes = $createData['notes'] ?? '';
        $this->isCODMandatory = (int) ($createData['isCODMandatory'] ?? 0);
        $this->codPaymentType = $createData['codPaymentType'] ?? '';
        $this->cashOnDelivery = (float) ($createData['cashOnDelivery'] ?? 0);
        $this->codCurrency = $createData['codCurrency'] ?? 'EUR';
        $this->numberOfParcels = $createData['numberOfParcels'] ?? 1;
        $this->weightKg = $createData['weightKG'] ?? 1;
        $this->volumeM3 = $createData['volumeM3'] ?? 0.01;
        $this->parcels = $this->parseParcels($parcels);
        $this->pudoId = $createData['pudoId'] ?? '';
    }

    protected function parseParcels($parcelsData)
    {
        $parcels = [];
        $id = [];
        foreach ($parcelsData as $key => $parcelData) {
            if ($key == 'id') {
                foreach ($parcelData as $k => $data) {
                    $id[$k] = $data;
                    $parcels[$data]['id'] = $data;
                }
            } else {
                foreach ($parcelData as $kk => $valueData) {
                    $parcels[$id[$kk]][$key] = $valueData;
                }
            }
        }

        return $parcels;
    }

    public function getParcels()
    {
        return $this->parcels;
    }

    public function getRequestData()
    {
        $consigneeData = $this->consignee->toArray();

        $codMandatory = [
            'isCODMandatory' => $this->isCODMandatory,
            'codPaymentType' => $this->codPaymentType,
            'cashOnDelivery' => $this->cashOnDelivery,
            'codCurrency' => $this->codCurrency,
        ];

        $request = [
            'account' => [
                'userID' => $this->account_id,
                'password' => $this->account_pwd
            ],
            'createData' => [
                'network' => $this->network,
                'departureDepot' => $this->departure_depot,
                'senderCustomerCode' => $this->customer_code,
                'deliveryFreightTypeCode' => $this->deliveryFreightTypeCode,
                'consigneeCompanyName' => $consigneeData['consigneeCompanyName'],
                'consigneeAddress' => $consigneeData['consigneeAddress'],
                'consigneeZIPCode' => $consigneeData['consigneeZIPCode'],
                'consigneeCity' => $consigneeData['consigneeCity'],
                'consigneeProvinceAbbreviation' => $consigneeData['consigneeProvinceAbbreviation'],
                'consigneeCountryAbbreviationISOAlpha2' => $consigneeData['consigneeCountryAbbreviationISOAlpha2'],
                'consigneeContactName' => $consigneeData['consigneeContactName'],
                'consigneeTelephone' => $consigneeData['consigneeTelephone'],
                'consigneeEMail' => $consigneeData['consigneeEMail'],
                'consigneeMobilePhoneNumber' => $consigneeData['consigneeMobilePhoneNumber'],
                'notes' => $this->notes,
                'isAlertRequired' => 1,
                'pricingConditionCode' => $this->pricing_condition_code,
                'serviceType' => $this->serviceType,
                'senderParcelType' => $this->senderParcelType,
                'numericSenderReference' => $this->numericSenderReference,
                'alphanumericSenderReference' => $this->alphanumericSenderReference,
                'numberOfParcels' => $this->numberOfParcels,
                'weightKG' => $this->weightKg,
                'volumeM3' => $this->volumeM3,
            ],
            'isLabelRequired' => 1,
            'labelParameters' => [
                'outputType' => \Configuration::get('MPBRTLABELS_TYPE') ?: 'PDF',
                'offsetX' => \Configuration::get('MPBRTLABELS_OFFSET_X') ?: 0,
                'offsetY' => \Configuration::get('MPBRTLABELS_OFFSET_Y') ?: 0,
                'isBorderRequired' => \Configuration::get('MPBRTLABELS_LABEL_BORDER') ?: 0,
                'isLogoRequired' => \Configuration::get('MPBRTLABELS_LABEL_LOGO') ?: 0,
                'isBarcodeControlRowRequired' => \Configuration::get('MPBRTLABELS_LABEL_BARCODE') ?: 0
            ]
        ];

        if ((int) $this->isCODMandatory != 0) {
            $request['createData'] = array_merge($request['createData'], $codMandatory);
        } else {
            $request['createData']['isCODMandatory'] = 0;
            $request['createData']['codPaymentType'] = '';
            $request['createData']['cashOnDelivery'] = 0;
            $request['createData']['codCurrency'] = 'EUR';
        }

        if ($this->pudoId) {
            $request['createData']['pudoId'] = $this->pudoId;
        }

        return $request;
    }

    public function send()
    {
        $requestData = $this->getRequestData();
        $restCreate = new Create($requestData);

        // Controllo che non ci sia un'altra etichetta uguale
        $numericSenderReference = (int) $requestData['createData']['numericSenderReference'];
        $existingRequest = ModelBrtLabelsRequest::getByNumericSenderReference($numericSenderReference, date('Y'));

        if (!\Validate::isLoadedObject($existingRequest)) {
            // Salvo la richiesta
            $numericSenderReference = (int) $requestData['createData']['numericSenderReference'];
            $model = ModelBrtLabelsRequest::getByNumericSenderReference($numericSenderReference);
            if (!$model) {
                $model = new ModelBrtLabelsRequest();
            }
            $model->year = date('Y');
            $model->orderId = $this->orderId;
            $model->numericSenderReference = $numericSenderReference;
            $model->alphanumericSenderReference = $requestData['createData']['alphanumericSenderReference'];
            $model->accountJson = $requestData['account'];
            $model->createDataJson = $requestData['createData'];
            $model->isLabelRequired = (int) ($requestData['isLabelRequired'] ?? 0);
            $model->labelParametersJson = $requestData['labelParameters'];
            $model->parcelsJson = $this->getParcels();
            $model->isCODMandatory = (int) ($requestData['createData']['isCODMandatory'] ?? 0);
            $model->cashOnDelivery = (float) ($requestData['createData']['cashOnDelivery'] ?? 0);
            $model->date_add = date('Y-m-d H:i:s');
            if (\Validate::isLoadedObject($model)) {
                $model->date_upd = date('Y-m-d H:i:s');
            }

            try {
                $model->save();
            } catch (\Exception $e) {
                $this->error = 'Error saving BRT label request: ' . $e->getMessage();
                return false;
            }
        }

        $response = $restCreate->doPostRequest();

        if ($response['success']) {
            $parsedResponse = $this->parse($response['data'], $requestData['createData']['numericSenderReference']);
            // Salvo il risultato
            $responseData = $parsedResponse['responseData'];
            $executionMessage = $parsedResponse['executionMessage'];
            $labels = $parsedResponse['labels'];

            $model = ModelBrtLabelsResponse::getByNumericSenderReference($numericSenderReference, date('Y'));
            $model->arrivalTerminal = $responseData['arrivalTerminal'];
            $model->arrivalDepot = $responseData['arrivalDepot'];
            $model->deliveryZone = $responseData['deliveryZone'];
            $model->parcelNumberFrom = $responseData['parcelNumberFrom'];
            $model->parcelNumberTo = $responseData['parcelNumberTo'];
            $model->departureDepot = $responseData['departureDepot'];
            $model->seriesNumber = $responseData['seriesNumber'];
            $model->serviceType = $responseData['serviceType'];
            $model->consigneeCompanyName = $responseData['consigneeCompanyName'];
            $model->consigneeAddress = $responseData['consigneeAddress'];
            $model->consigneeZIPCode = $responseData['consigneeZIPCode'];
            $model->consigneeCity = $responseData['consigneeCity'];
            $model->consigneeProvinceAbbreviation = $responseData['consigneeProvinceAbbreviation'];
            $model->consigneeCountryAbbreviationBRT = $responseData['consigneeCountryAbbreviationBRT'];
            $model->numberOfParcels = $responseData['numberOfParcels'];
            $model->weightKG = (float) $responseData['weightKG'];
            $model->volumeM3 = (float) $responseData['volumeM3'];
            $model->numericSenderReference = $numericSenderReference;
            $model->alphanumericSenderReference = $responseData['alphanumericSenderReference'];
            $model->senderCompanyName = $responseData['senderCompanyName'];
            $model->senderProvinceAbbreviation = $responseData['senderProvinceAbbreviation'];
            $model->year = date('Y');
            $model->labels = $labels;
            $model->currentTimeUTC = $responseData['currentTimeUTC'];
            $model->executionMessage = $executionMessage;
            $model->disclaimer = $responseData['disclaimer'] ?? '';

            try {
                $action = $model->save();
            } catch (\Throwable $th) {
                $this->error = 'Error saving BRT label response: ' . $th->getMessage();
                $action = false;
                $parsedResponse['error'] = $this->error;
            }
        } else {
            $action = false;
            $parsedResponse['error'] = 'Error sending BRT label request';
        }

        $parsedResponse['status'] = $action;

        return $parsedResponse;
    }

    private function parse($response, $numericSenderReference)
    {
        $result = [];
        $createResponse = $response['createResponse'];

        $result['numericSenderReference'] = $numericSenderReference;
        try {
            $result['responseTime'] = self::parseUTC($createResponse['currentTimeUTC']);
        } catch (\Throwable $th) {
            $result['responseTime'] = $createResponse['currentTimeUTC'];
        }
        $executionMessage = $createResponse['executionMessage'] ?? [];
        $labels = $createResponse['labels'] ?? [];
        unset($createResponse['executionMessage'], $createResponse['labels']);

        $result['responseData'] = $createResponse;
        $result['executionMessage'] = $executionMessage;
        $result['labels'] = $labels;

        return $result;
    }

    public static function parseUTC($utc)
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d-H.i.s.uP', $utc);
        if (!$dt) {
            throw new \RuntimeException('Formato data non valido');
        }

        return $dt->format('Y-m-d H:i:s');
    }

    public function showRequestData()
    {
        $requestData = $this->getRequestData();
        $requestData['showRequestData'] = 1;
        return $requestData;
    }
}
