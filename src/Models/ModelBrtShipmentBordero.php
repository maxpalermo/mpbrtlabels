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

use MpSoft\MpBrtLabels\Helpers\GetByNumericReference;

class ModelBrtShipmentBordero extends \ObjectModel
{
    public const BORDERO_STATUS_PENDING = 0;
    public const BORDERO_STATUS_PRINTED = 1;

    public $id_brt_shipment_response;
    public $numeric_sender_reference;
    public $alphanumeric_sender_reference;
    public $bordero_number;
    public $bordero_date;
    public $bordero_status;
    public $printed;
    public $printed_date;
    public $id_employee;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'brt_shipment_bordero',
        'primary' => 'id_brt_shipment_bordero',
        'fields' => [
            'id_brt_shipment_response' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'numeric_sender_reference' => ['type' => self::TYPE_STRING, 'size' => '15', 'validate' => 'isUnsignedInt'],
            'alphanumeric_sender_reference' => ['type' => self::TYPE_STRING, 'size' => '15', 'validate' => 'isAnything'],
            'bordero_number' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'bordero_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'bordero_status' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'printed' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false],
            'printed_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public static function getUnprintedBorderoRows()
    {
        $db = \Db::getInstance();
        $subQuery = new \DbQuery();
        $subQuery
            ->select('id_brt_shipment_response')
            ->from(self::$definition['table'])
            ->where('bordero_status = ' . (int) self::BORDERO_STATUS_PENDING);
        $subQuery = $subQuery->build();
        $query = new \DbQuery();
        $query
            ->select('*')
            ->from(ModelBrtShipmentResponse::$definition['table'])
            ->where('id_brt_shipment_response IN (' . $subQuery . ')');

        $result = $db->executeS($query);
        if (!$result) {
            return [];
        }

        return $result;
    }

    public static function getBorderoByNumber($borderoNumber)
    {
        $db = \Db::getInstance();
        $subQuery = new \DbQuery();
        $subQuery
            ->select('*')
            ->from(self::$definition['table'])
            ->where('bordero_number = ' . (int) $borderoNumber)
            ->build();

        return $db->executeS($subQuery);
    }

    public static function getLastPrintedBorderoNumber()
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select('max(bordero_number)')
            ->from('brt_shipment_bordero')
            ->where('printed = 1');

        $result = (int) $db->getValue($query);

        return $result;
    }

    public static function getLatestBorderoNumber()
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select('max(bordero_number) as bordero_number')
            ->from('brt_shipment_bordero')
            ->where('bordero_status = ' . (int) self::BORDERO_STATUS_PENDING);

        $result = (int) $db->getValue($query);

        return $result;
    }

    public static function getFirstBorderoNumberAvailable()
    {
        $lastBorderoNumber = self::getLatestBorderoNumber();

        return $lastBorderoNumber + 1;
    }

    public static function getHistory()
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select('a.bordero_number')
            ->select('a.bordero_date')
            ->select('a.printed_date')
            ->select('count(a.bordero_number) as total_deliveries')
            ->select('sum(b.cash_on_delivery > 0) as count_cash_on_delivery')
            ->select('sum(b.cash_on_delivery) as total_cash_on_delivery')
            ->select('sum(b.number_of_parcels) as total_parcels')
            ->select('sum(b.weight_kg) as total_weight_kg')
            ->select('sum(b.volume_m3) as total_volume_m3')
            ->from('brt_shipment_bordero', 'a')
            ->innerJoin('brt_shipment_response', 'b', 'a.id_brt_shipment_response = b.id_brt_shipment_response')
            ->where('a.printed = 1')
            ->groupBy('a.bordero_number')
            ->orderBy('a.bordero_number DESC');

        $sql = $query->build();

        return $db->executeS($sql);
    }

    public static function compileBordero($params = null)
    {
        $numericSenderReference = $params['numericSenderReference'] ?? null;
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select('*')
            ->from('brt_shipment_bordero')
            ->where('bordero_status = ' . (int) self::BORDERO_STATUS_PENDING)
            ->orderBy(self::$definition['primary'] . ' ASC');

        $result = $db->executeS($query);
        $bordero = [];

        if ($result) {
            $bordero['numeric_sender_reference'] = $numericSenderReference;
            $bordero['number'] = self::getLatestBorderoNumber();
            $bordero['date'] = date('Y-m-d');
            $bordero['status'] = self::BORDERO_STATUS_PENDING;
            $bordero['id_employee'] = $params['id_employee'] ?? 0;
            $bordero['rows'] = [];
            $bordero['totals'] = [
                'total_deliveries' => 0,
                'total_parcels' => 0,
                'total_cash_on_delivery' => 0,
                'total_cash_on_delivery_amount' => 0,
                'total_weight_kg' => 0,
                'total_volume_m3' => 0,
            ];

            foreach ($result as $r) {
                $modelBrtResponse = new ModelBrtShipmentResponse($r['id_brt_shipment_response']);
                if (\Validate::isLoadedObject($modelBrtResponse)) {
                    $bordero['rows'][] = [
                        'id_brt_shipment_bordero' => $r['id_brt_shipment_bordero'],
                        'id_brt_shipment_response' => $r['id_brt_shipment_response'],
                        'consignee_company_name' => \Tools::strtoupper($modelBrtResponse->consignee_company_name),
                        'consignee_address' => \Tools::strtoupper($modelBrtResponse->consignee_address),
                        'consignee_zip_code' => \Tools::strtoupper($modelBrtResponse->consignee_zip_code),
                        'consignee_city' => \Tools::strtoupper($modelBrtResponse->consignee_city),
                        'consignee_province' => \Tools::strtoupper($modelBrtResponse->consignee_province_abbreviation),
                        'numeric_sender_reference' => $modelBrtResponse->numeric_sender_reference,
                        'alphanumeric_sender_reference' => \Tools::strtoupper($modelBrtResponse->alphanumeric_sender_reference),
                        'cash_on_delivery' => $modelBrtResponse->cash_on_delivery,
                        'number_of_parcels' => $modelBrtResponse->number_of_parcels,
                        'weight_kg' => $modelBrtResponse->weight_kg,
                        'volume_m3' => $modelBrtResponse->volume_m3,
                        'parcel_number_from' => $modelBrtResponse->parcel_number_from,
                        'parcel_number_to' => $modelBrtResponse->parcel_number_to,
                    ];
                    ++$bordero['totals']['total_deliveries'];
                    $bordero['totals']['total_parcels'] += $modelBrtResponse->number_of_parcels;
                    $bordero['totals']['total_weight_kg'] += $modelBrtResponse->weight_kg;
                    $bordero['totals']['total_volume_m3'] += $modelBrtResponse->volume_m3;
                    if ($modelBrtResponse->cash_on_delivery > 0) {
                        ++$bordero['totals']['total_cash_on_delivery'];
                        $bordero['totals']['total_cash_on_delivery_amount'] += $modelBrtResponse->cash_on_delivery;
                    }
                }
            }
        }

        return $bordero;
    }

    public static function getBorderoRowsId($bordero_number)
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select(self::$definition['primary'])
            ->from(self::$definition['table'])
            ->where('bordero_number = ' . (int) $bordero_number)
            ->orderBy(self::$definition['primary'] . ' ASC');

        return $db->executeS($query);
    }

    public static function getIdList($bordero_number = null)
    {
        if (!$bordero_number) {
            $bordero_number = self::getLatestBorderoNumber();
        }

        $ids = self::getBorderoRowsId($bordero_number);

        return array_map(function ($id) {
            return $id[self::$definition['primary']];
        }, $ids);
    }

    public static function getByNumericSenderReference($numericSenderReference): ModelBrtShipmentBordero
    {
        $result = (new GetByNumericReference($numericSenderReference, self::$definition['table'], self::$definition['primary']))->run(self::class);
        if ($result) {
            return $result[0];
        }

        return new self();
    }

    public static function updateBorderoStatus($bordero_number, $status = null)
    {
        if (null === $status) {
            $status = self::BORDERO_STATUS_PRINTED;
        }

        $db = \Db::getInstance();
        $res = $db->update(
            self::$definition['table'],
            [
                'bordero_status' => (int) $status,
                'printed' => 1,
                'printed_date' => date('Y-m-d H:i:s'),
            ],
            'bordero_number = ' . (int) $bordero_number
        );

        return $res;
    }

    public static function clean()
    {
        $db = \Db::getInstance();

        return $db->delete(
            self::$definition['table'],
            'numeric_sender_reference = "" OR alphanumeric_sender_reference = ""'
        );
    }

    public static function install()
    {
        $pfx = _DB_PREFIX_;
        $QUERY = "
            CREATE TABLE IF NOT EXISTS `{$pfx}brt_shipment_bordero` (
                `id_brt_shipment_bordero` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_brt_shipment_response` int(11) NOT NULL,
                `numeric_sender_reference` varchar(15) NOT NULL,
                `alphanumeric_sender_reference` varchar(15) NOT NULL,
                `bordero_number` int(11) NOT NULL,
                `bordero_date` datetime NOT NULL,
                `bordero_status` int(11) NOT NULL,
                `printed` tinyint(1) DEFAULT NULL,
                `printed_date` datetime DEFAULT NULL,
                `id_employee` int(11) NOT NULL,
                `date_add` datetime DEFAULT NULL,
                `date_upd` datetime DEFAULT NULL,
                PRIMARY KEY (`id_brt_shipment_bordero`)
            ) ENGINE=InnoDB
        ";

        return Db::getInstance()->execute($QUERY);
    }
}
