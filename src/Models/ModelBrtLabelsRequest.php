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

use MpSoft\MpBrtLabels\Api\Rest\Delete;

class ModelBrtLabelsRequest extends \ObjectModel
{
    public $year;
    public $orderId;
    public $numericSenderReference;
    public $alphanumericSenderReference;
    public $isLabelRequired;
    public $accountJson;
    public $createDataJson;
    public $labelParametersJson;
    public $parcelsJson;
    public $isCODMandatory;
    public $cashOnDelivery;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'brt_labels_request',
        'primary' => 'id_brt_labels_request',
        'fields' => [
            'year' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'orderId' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
            'numericSenderReference' => ['type' => self::TYPE_STRING, 'size' => 15, 'validate' => 'isUnsignedInt', 'required' => true],
            'alphanumericSenderReference' => ['type' => self::TYPE_STRING, 'size' => 15, 'validate' => 'isAnything', 'required' => true],
            'isLabelRequired' => ['type' => self::TYPE_INT, 'validate' => 'isBool'],
            'accountJson' => ['type' => self::TYPE_HTML, 'validate' => 'isJson'],
            'createDataJson' => ['type' => self::TYPE_HTML, 'validate' => 'isJson'],
            'labelParametersJson' => ['type' => self::TYPE_HTML, 'validate' => 'isJson'],
            'parcelsJson' => ['type' => self::TYPE_HTML, 'validate' => 'isJson'],
            'isCODMandatory' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'cashOnDelivery' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id, $idLang);

        if ($id) {
            $this->accountJson = json_decode($this->accountJson, true);
            $this->createDataJson = json_decode($this->createDataJson, true);
            $this->labelParametersJson = json_decode($this->labelParametersJson, true);
            $this->parcelsJson = json_decode($this->parcelsJson, true);
        }
    }

    public function add($autoDate = true, $nullValues = false)
    {
        if (is_array($this->accountJson)) {
            $this->accountJson = json_encode($this->accountJson);
        }
        if (is_array($this->createDataJson)) {
            $this->createDataJson = json_encode($this->createDataJson);
        }
        if (is_array($this->labelParametersJson)) {
            $this->labelParametersJson = json_encode($this->labelParametersJson);
        }
        if (is_array($this->parcelsJson)) {
            $this->parcelsJson = json_encode($this->parcelsJson);
        }
        return parent::add($autoDate, $nullValues);
    }

    public function update($nullValues = false)
    {
        if (is_array($this->accountJson)) {
            $this->accountJson = json_encode($this->accountJson);
        }
        if (is_array($this->createDataJson)) {
            $this->createDataJson = json_encode($this->createDataJson);
        }
        if (is_array($this->labelParametersJson)) {
            $this->labelParametersJson = json_encode($this->labelParametersJson);
        }
        if (is_array($this->parcelsJson)) {
            $this->parcelsJson = json_encode($this->parcelsJson);
        }
        return parent::update($nullValues);
    }

    public function delete($force = false): array
    {
        $numericSenderReference = $this->numericSenderReference;
        $alphanumericSenderReference = $this->alphanumericSenderReference;

        $delete = Delete::sendRequest($numericSenderReference, $alphanumericSenderReference);
        if (isset($delete['deleteResponse']) && ($delete['deleteResponse']['executionMessage']['code'] == 0 || $force)) {
            parent::delete();
            $response = ModelBrtLabelsResponse::getByNumericSenderReference($numericSenderReference);
            if (\Validate::isLoadedObject($response)) {
                $response->delete();
            }
        }

        return $delete;
    }

    /**
     * @param int $orderId
     *
     * @return int
     */
    public static function getNumericSenderReferenceByOrderId($orderId, $year = null)
    {
        if (!$year) {
            $year = date('Y');
        }
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select('numericSenderReference')
            ->from(self::$definition['table'])
            ->where('orderId = ' . (int) $orderId)
            ->where('year = ' . (int) $year);

        return (int) $db->getValue($query);
    }

    public static function getAlphanumericSenderReferenceByOrderId($orderId, $year = null)
    {
        if (!$year) {
            $year = date('Y');
        }
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select('alphanumericSenderReference')
            ->from(self::$definition['table'])
            ->where('orderId = ' . (int) $orderId)
            ->where('year = ' . (int) $year);

        return (string) $db->getValue($query);
    }

    public static function getByNumericSenderReference($numericSenderReference, $year = null): self|null
    {
        if (!$year) {
            $year = date('Y');
        }
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select(self::$definition['primary'])
            ->from(self::$definition['table'])
            ->where('numericSenderReference = ' . (int) $numericSenderReference)
            ->where('year = ' . (int) $year);

        $result = $db->getValue($query);

        if ($result) {
            return new self($result);
        }

        return null;
    }

    public static function getByIdOrder($idOrder, $year = null): self
    {
        if (!$year) {
            $year = date('Y');
        }
        $numericSenderReference = self::getNumericSenderReferenceByOrderId($idOrder, $year);

        return self::getByNumericSenderReference($numericSenderReference, $year);
    }

    public static function deleteByNumericSenderReference($numericSenderReference, $year = null): bool
    {
        if (!$year) {
            $year = date('Y');
        }
        $model = self::getByNumericSenderReference($numericSenderReference, $year);
        if ($model) {
            return $model->delete();
        }

        return false;
    }

    public static function deleteByIdOrder($idOrder): bool
    {
        $numericSenderReference = self::getNumericSenderReferenceByOrderId($idOrder);

        return self::deleteByNumericSenderReference($numericSenderReference);
    }

    public static function install()
    {
        $pfx = _DB_PREFIX_;
        $QUERY = "
            CREATE TABLE IF NOT EXISTS `{$pfx}brt_labels_request` (
                `id_brt_labels_request` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `year` DECIMAL(4,0) UNSIGNED NOT NULL,
                `orderId` int UNSIGNED DEFAULT NULL,
                `numericSenderReference` char(15) NOT NULL,
                `alphanumericSenderReference` varchar(15) NOT NULL,
                `isLabelRequired` boolean NOT NULL DEFAULT 1,
                `accountJson` json NOT NULL,
                `createDataJson` json NOT NULL,
                `labelParametersJson` json NOT NULL,
                `parcelsJson` json NOT NULL,
                `isCODMandatory` boolean NOT NULL DEFAULT 0,
                `cashOnDelivery` decimal(20,6) NOT NULL DEFAULT 0.00,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
                PRIMARY KEY (`id_brt_labels_request`),
                KEY `idx_orderId` (`orderId`),
                KEY `idx_numericSenderReference` (`numericSenderReference`)
            ) ENGINE=InnoDB
        ";

        return \Db::getInstance()->execute($QUERY);
    }

    public function toArray()
    {
        return [
            'id' => (int) $this->id,
            'year' => (int) $this->year,
            'idOrder' => (int) $this->orderId,
            'numericSenderReference' => (int) $this->numericSenderReference,
            'alphanumericSenderReference' => (string) $this->alphanumericSenderReference,
            'accountJson' => !is_array($this->accountJson) ? json_decode($this->accountJson, true) : $this->accountJson,
            'createDataJson' => !is_array($this->createDataJson) ? json_decode($this->createDataJson, true) : $this->createDataJson,
            'isLabelRequired' => (int) $this->isLabelRequired,
            'labelParametersJson' => !is_array($this->labelParametersJson) ? json_decode($this->labelParametersJson, true) : $this->labelParametersJson,
            'parcelsJson' => !is_array($this->parcelsJson) ? json_decode($this->parcelsJson, true) : $this->parcelsJson,
            'isCODMandatory' => (int) $this->isCODMandatory,
            'cashOnDelivery' => (float) $this->cashOnDelivery,
            'date_add' => $this->date_add,
            'date_upd' => $this->date_upd,
        ];
    }
}
