<?php

namespace MpSoft\MpBrtApiShipment\Api;

class ShipmentResponse
{
    public $currentTimeUTC;
    public $executionMessage;
    public $arrivalTerminal;
    public $arrivalDepot;
    public $deliveryZone;
    public $parcelNumberFrom;
    public $parcelNumberTo;
    public $departureDepot;
    public $seriesNumber;
    public $serviceType;
    public $consigneeCompanyName;
    public $consigneeAddress;
    public $consigneeZIPCode;
    public $consigneeCity;
    public $consigneeProvinceAbbreviation;
    public $consigneeCountryAbbreviationBRT;
    public $cashOnDelivery;
    public $numberOfParcels;
    public $weightKG;
    public $volumeM3;
    public $alphanumericSenderReference;
    public $senderCompanyName;
    public $senderProvinceAbbreviation;
    public $labels = [];
    public $disclaimer;

    public function __construct($arr)
    {
        $this->currentTimeUTC = $arr['currentTimeUTC'] ?? '';
        $this->executionMessage = isset($arr['executionMessage']) ? ExecutionMessage::fromArray($arr['executionMessage']) : null;
        $this->arrivalTerminal = $arr['arrivalTerminal'] ?? '';
        $this->arrivalDepot = $arr['arrivalDepot'] ?? '';
        $this->deliveryZone = $arr['deliveryZone'] ?? '';
        $this->parcelNumberFrom = $arr['parcelNumberFrom'] ?? '';
        $this->parcelNumberTo = $arr['parcelNumberTo'] ?? '';
        $this->departureDepot = $arr['departureDepot'] ?? '';
        $this->seriesNumber = $arr['seriesNumber'] ?? '';
        $this->serviceType = $arr['serviceType'] ?? '';
        $this->consigneeCompanyName = $arr['consigneeCompanyName'] ?? '';
        $this->consigneeAddress = $arr['consigneeAddress'] ?? '';
        $this->consigneeZIPCode = $arr['consigneeZIPCode'] ?? '';
        $this->consigneeCity = $arr['consigneeCity'] ?? '';
        $this->consigneeProvinceAbbreviation = $arr['consigneeProvinceAbbreviation'] ?? '';
        $this->consigneeCountryAbbreviationBRT = $arr['consigneeCountryAbbreviationBRT'] ?? '';
        $this->cashOnDelivery = $arr['cashOnDelivery'] ?? 0;
        $this->numberOfParcels = $arr['numberOfParcels'] ?? 0;
        $this->weightKG = $arr['weightKG'] ?? 0;
        $this->volumeM3 = $arr['volumeM3'] ?? 0;
        $this->alphanumericSenderReference = $arr['alphanumericSenderReference'] ?? '';
        $this->senderCompanyName = $arr['senderCompanyName'] ?? '';
        $this->senderProvinceAbbreviation = $arr['senderProvinceAbbreviation'] ?? '';
        $this->disclaimer = $arr['disclaimer'] ?? '';
        if (isset($arr['labels']['label']) && is_array($arr['labels']['label'])) {
            foreach ($arr['labels']['label'] as $lbl) {
                $this->labels[] = Label::fromArray($lbl);
            }
        }
    }

    public function getLabels(): array
    {
        return $this->labels;
    }
}
