<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA.
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

namespace MpSoft\MpBrtLabels\Models;

use MpSoft\MpBrtLabels\Helpers\DeleteByNumericReference;
use MpSoft\MpBrtLabels\Helpers\GetByNumericReference;
use ObjectModel;
use Throwable;

class ModelBrtLabelsResponse extends ObjectModel
{
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
    public $numberOfParcels;
    public $weightKG;
    public $volumeM3;
    public $numericSenderReference;
    public $alphanumericSenderReference;
    public $senderCompanyName;
    public $senderProvinceAbbreviation;
    public $year;
    public $labels;
    public $currentTimeUTC;
    public $executionMessage;
    public $disclaimer;
    public $borderoNumber;
    public $borderoDate;
    public $printed;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'brt_labels_response',
        'primary' => 'id_brt_labels_response',
        'fields' => [
            'arrivalTerminal' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'arrivalDepot' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'deliveryZone' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'parcelNumberFrom' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'parcelNumberTo' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'departureDepot' => ['type' => self::TYPE_STRING, 'validate' => 'isUnsignedInt'],
            'seriesNumber' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'serviceType' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'consigneeCompanyName' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'consigneeAddress' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'consigneeZIPCode' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'consigneeCity' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'consigneeProvinceAbbreviation' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'consigneeCountryAbbreviationBRT' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'numberOfParcels' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'weightKG' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'volumeM3' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'numericSenderReference' => ['type' => self::TYPE_STRING, 'size' => 15, 'validate' => 'isUnsignedInt'],
            'alphanumericSenderReference' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'senderCompanyName' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'senderProvinceAbbreviation' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'year' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'labels' => ['type' => self::TYPE_HTML, 'validate' => 'isJson'],
            'currentTimeUTC' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'executionMessage' => ['type' => self::TYPE_HTML, 'validate' => 'isJson'],
            'disclaimer' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'borderoNumber' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'borderoDate' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'printed' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
        ],
    ];

    public function toArray()
    {
        return [
            'id' => $this->id,
            'arrivalTerminal' => $this->arrivalTerminal,
            'arrivalDepot' => $this->arrivalDepot,
            'deliveryZone' => $this->deliveryZone,
            'parcelNumberFrom' => $this->parcelNumberFrom,
            'parcelNumberTo' => $this->parcelNumberTo,
            'departureDepot' => $this->departureDepot,
            'seriesNumber' => $this->seriesNumber,
            'serviceType' => $this->serviceType,
            'consigneeCompanyName' => $this->consigneeCompanyName,
            'consigneeAddress' => $this->consigneeAddress,
            'consigneeZIPCode' => $this->consigneeZIPCode,
            'consigneeCity' => $this->consigneeCity,
            'consigneeProvinceAbbreviation' => $this->consigneeProvinceAbbreviation,
            'consigneeCountryAbbreviationBRT' => $this->consigneeCountryAbbreviationBRT,
            'numberOfParcels' => $this->numberOfParcels,
            'weightKG' => $this->weightKG,
            'volumeM3' => $this->volumeM3,
            'numericSenderReference' => $this->numericSenderReference,
            'alphanumericSenderReference' => $this->alphanumericSenderReference,
            'senderCompanyName' => $this->senderCompanyName,
            'senderProvinceAbbreviation' => $this->senderProvinceAbbreviation,
            'year' => $this->year,
            'labels' => $this->labels,
            'currentTimeUTC' => $this->currentTimeUTC,
            'executionMessage' => !is_array($this->executionMessage) ? json_decode($this->executionMessage, true) : $this->executionMessage,
            'disclaimer' => $this->disclaimer,
            'borderoNumber' => $this->borderoNumber,
            'borderoDate' => $this->borderoDate,
            'printed' => (int) $this->printed,
            'date_add' => $this->date_add,
            'date_upd' => $this->date_upd,
        ];
    }

    public static function install()
    {
        $pfx = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;

        $QUERY = "
            CREATE TABLE IF NOT EXISTS `{$pfx}brt_labels_response` (
                `id_brt_labels_response` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `arrivalTerminal` varchar(64) DEFAULT NULL,
                `arrivalDepot` varchar(64) DEFAULT NULL,
                `deliveryZone` varchar(64) DEFAULT NULL,
                `parcelNumberFrom` varchar(64) DEFAULT NULL,
                `parcelNumberTo` varchar(64) DEFAULT NULL,
                `departureDepot` varchar(64) DEFAULT NULL,
                `seriesNumber` varchar(64) DEFAULT NULL,
                `serviceType` varchar(64) DEFAULT NULL,
                `consigneeCompanyName` varchar(255) DEFAULT NULL,
                `consigneeAddress` varchar(255) DEFAULT NULL,
                `consigneeZIPCode` varchar(32) DEFAULT NULL,
                `consigneeCity` varchar(128) DEFAULT NULL,
                `consigneeProvinceAbbreviation` varchar(8) DEFAULT NULL,
                `consigneeCountryAbbreviationBRT` varchar(8) DEFAULT NULL,
                `numberOfParcels` int(11) DEFAULT 0,
                `weightKG` decimal(5,1) DEFAULT 0.0,
                `volumeM3` decimal(5,3) DEFAULT 0.000,
                `numericSenderReference` varchar(15) DEFAULT NULL,
                `alphanumericSenderReference` varchar(15) DEFAULT NULL,
                `senderCompanyName` varchar(255) DEFAULT NULL,
                `senderProvinceAbbreviation` varchar(8) DEFAULT NULL,
                `year` int(11) DEFAULT NULL,
                `labels` json DEFAULT NULL,
                `currentTimeUTC` DATETIME DEFAULT NULL,
                `executionMessage` json DEFAULT NULL,
                `disclaimer` varchar(255) DEFAULT NULL,
                `borderoNumber` int(11) DEFAULT NULL,
                `borderoDate` datetime DEFAULT NULL,
                `printed` tinyint(1) DEFAULT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NULL DEFAULT NULL,
                PRIMARY KEY (`id_brt_labels_response`),
                KEY `idx_numeric_sender_reference` (`numericSenderReference`),
                KEY `idx_alphanumeric_sender_reference` (`alphanumericSenderReference`)
            ) ENGINE={$engine}
        ";

        return \Db::getInstance()->execute($QUERY);
    }

    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id, $idLang);

        if ($id) {
            $this->labels = json_decode($this->labels, true);
            $this->executionMessage = json_decode($this->executionMessage, true);
        }
    }

    public function add($autoDate = true, $nullValues = false)
    {
        if (is_array($this->labels)) {
            $this->labels = json_encode($this->labels);
        }
        if (is_array($this->executionMessage)) {
            $this->executionMessage = json_encode($this->executionMessage);
        }
        return parent::add($autoDate, $nullValues);
    }

    public function update($nullValues = false)
    {
        if (is_array($this->labels)) {
            $this->labels = json_encode($this->labels);
        }
        if (is_array($this->executionMessage)) {
            $this->executionMessage = json_encode($this->executionMessage);
        }
        return parent::update($nullValues);
    }

    public static function exists($numericSenderReference)
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select(self::$definition['primary'])
            ->from(self::$definition['table'])
            ->where('numericSenderReference = ' . (int) $numericSenderReference);

        return (bool) $db->getValue($query);
    }

    public static function getByNumericSenderReference(int $numericSenderReference, int $year = 0): ModelBrtLabelsResponse
    {
        if (!$year) {
            $year = date('Y');
        }
        $db = \Db::getInstance();
        $sql = new \DbQuery();
        $sql
            ->select(self::$definition['primary'])
            ->from(self::$definition['table'])
            ->where('numericSenderReference = ' . (int) $numericSenderReference)
            ->where('year = ' . (int) $year);

        $result = (int) $db->getValue($sql);

        if ($result) {
            return new self($result);
        }

        return new self();
    }

    public static function deleteByNumericSenderReference($numericSenderReference): bool
    {
        $db = \Db::getInstance();
        $sql = new \DbQuery();
        $sql
            ->select(self::$definition['primary'])
            ->from(self::$definition['table'])
            ->where("numericSenderReference = '" . pSQL($numericSenderReference) . "'");

        $result = (int) $db->getValue($sql);

        if ($result) {
            $model = new self($result);
            return $model->delete();
        }

        return false;
    }
}
