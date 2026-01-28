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
use setasign\Fpdi\Fpdi;
use \ObjectModel;
use \Throwable;

class ModelBrtShipmentResponseLabel extends ObjectModel
{
    public $id_brt_shipment_response;
    public $numeric_sender_reference;
    public $alphanumeric_sender_reference;
    public $number;
    public $measure_date;
    public $x;
    public $y;
    public $z;
    public $unit_measure;
    public $weight;
    public $volume;
    public $fiscal_id;
    public $p_flag;
    public $data_length;
    public $parcel_id;
    public $stream;
    public $stream_digital_label;
    public $parcel_number_geo_post;
    public $tracking_by_parcel_id;
    public $format;

    public static $definition = [
        'table' => 'brt_shipment_response_label',
        'primary' => 'id_brt_shipment_response_label',
        'fields' => [
            'id_brt_shipment_response' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'numeric_sender_reference' => ['type' => self::TYPE_STRING, 'size' => 15, 'validate' => 'isUnsignedInt', 'required' => true],
            'alphanumeric_sender_reference' => ['type' => self::TYPE_STRING, 'size' => 15, 'validate' => 'isAnything', 'required' => true, 'size' => 64],
            'number' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'measure_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'x' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'y' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'z' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'unit_measure' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 2, 'required' => true, 'default' => 'cm'],
            'weight' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true, 'default' => '1'],
            'volume' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true, 'default' => '1'],
            'fiscal_id' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => false, 'size' => 64],
            'p_flag' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false],
            'data_length' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'parcel_id' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true, 'size' => 64],
            'stream' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => false, 'size' => 999999999],
            'stream_digital_label' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => false, 'size' => 999999999],
            'parcel_number_geo_post' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => false, 'size' => 64],
            'tracking_by_parcel_id' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => false, 'size' => 64],
            'format' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => false, 'size' => 16],
        ],
    ];

    public function add($auto_date = true, $null_values = false)
    {
        $this->calcVolume();
        $this->formatWeight();

        return parent::add($auto_date, $null_values);
    }

    public function update($null_values = false)
    {
        $this->calcVolume();
        $this->formatWeight();

        return parent::update($null_values);
    }

    public function formatWeight()
    {
        $this->weight = number_format($this->weight, 1, '.', '');

        return $this->weight;
    }

    public function calcVolume()
    {
        $divisor = 1000000;
        if ($this->unit_measure == 'mm') {
            $divisor = 1000000000;
        }

        $this->volume = number_format(($this->x * $this->y * $this->z) / $divisor, 3, '.', '');

        return $this->volume;
    }

    public static function decodeBase64($stream)
    {
        return base64_decode($stream);
    }

    public function decodeStream()
    {
        return base64_decode($this->stream);
    }

    public function decodeStreamDigitalLabel()
    {
        return base64_decode($this->stream_digital_label);
    }

    public static function getByTrackingParcelId($trackingParcelId)
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select(self::$definition['primary'])
            ->from(self::$definition['table'])
            ->where('tracking_by_parcel_id = ' . (int) $trackingParcelId);

        $result = $db->executeS($query);
        $labels = [];
        if ($result) {
            foreach ($result as $r) {
                $labels[] = new self($r);
            }

            return $labels;
        }

        return $labels;
    }

    public static function getByNumericSenderReference($numericSenderReference): array
    {
        $result = (new GetByNumericReference($numericSenderReference, self::$definition['table'], self::$definition['primary']))->run(self::class);

        return $result;
    }

    public static function getByNumericSenderReferenceAndNumber($numericSenderReference, $number)
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select(self::$definition['primary'])
            ->from(self::$definition['table'])
            ->where('numeric_sender_reference = ' . (int) $numericSenderReference)
            ->where('number = ' . (int) $number);

        $result = (int) $db->getValue($query);

        return new self($result);
    }

    public static function deleteByNumericSenderReference($numericSenderReference): bool
    {
        return (new DeleteByNumericReference($numericSenderReference, self::$definition['table']))->run();
    }

    public static function createLabelPdf($idBrtShipmentResponse)
    {
        $db = \Db::getInstance();
        $sql = new \DbQuery();
        $sql
            ->select('stream')
            ->from(self::$definition['table'])
            ->where('id_brt_shipment_response = ' . (int) $idBrtShipmentResponse);

        $result = $db->executeS($sql);
        $streams = [];
        if ($result) {
            foreach ($result as $r) {
                $streams[] = base64_decode($r['stream']);
            }
        }

        return self::printMergedPDF($streams);
    }

    public static function printMergedPDF($streams)
    {
        $streamPDF = self::mergePdfStreams($streams);

        return $streamPDF;
    }

    public static function mergePdfStreams(array $streams)
    {
        // Specifica unità 'mm' direttamente
        $pdf = new Fpdi('P', 'mm');
        $brtLabel = [
            'width' => 100,
            'height' => 70,
        ];
        foreach ($streams as $stream) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'pdf');
            file_put_contents($tmpFile, $stream);

            $pageCount = $pdf->setSourceFile($tmpFile);
            for ($pageNo = 1; $pageNo <= $pageCount; ++$pageNo) {
                $tplIdx = $pdf->importPage($pageNo);
                $pdf->AddPage('L', [$brtLabel['width'], $brtLabel['height']]);
                $pdf->useTemplate($tplIdx);
            }

            unlink($tmpFile);
        }

        return $pdf->Output('S');
    }

    public static function hasLabels($numericSenderReference)
    {
        $sql = new \DbQuery();
        $sql
            ->select('COUNT(*)')
            ->from(self::$definition['table'])
            ->where('numeric_sender_reference = ' . (int) $numericSenderReference);

        return (bool) \Db::getInstance()->getValue($sql);
    }

    public static function install()
    {
        $pfx = _DB_PREFIX_;
        $QUERY = "
            CREATE TABLE IF NOT EXISTS `{$pfx}brt_shipment_response_label` (
                `id_brt_shipment_response_label` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_brt_shipment_response` int(11) UNSIGNED DEFAULT NULL,
                `numeric_sender_reference` varchar(15) DEFAULT NULL,
                `alphanumeric_sender_reference` varchar(15) DEFAULT NULL,
                `number` int(11) DEFAULT NULL,
                `measure_date` datetime DEFAULT NULL,
                `x` decimal(8,3) DEFAULT 0.000,
                `y` decimal(8,3) DEFAULT 0.000,
                `z` decimal(8,3) DEFAULT 0.000,
                `unit_measure` char(2) NOT NULL DEFAULT 'cm' COMMENT 'cm o mm',
                `weight` decimal(5,1) DEFAULT 1.0 COMMENT 'kg',
                `volume` decimal(5,3) DEFAULT 0.000 COMMENT 'm3',
                `fiscal_id` varchar(16) DEFAULT NULL,
                `p_flag` tinyint(1) DEFAULT NULL,
                `data_length` int(11) DEFAULT NULL,
                `parcel_id` varchar(64) DEFAULT NULL,
                `stream` longtext DEFAULT NULL,
                `stream_digital_label` longtext DEFAULT NULL,
                `parcel_number_geo_post` varchar(64) DEFAULT NULL,
                `tracking_by_parcel_id` varchar(64) DEFAULT NULL,
                `format` varchar(16) DEFAULT NULL,
                PRIMARY KEY (`id_brt_shipment_response_label`)
            ) ENGINE=InnoDB
        ";

        $db = \Db::getInstance();

        return $db->execute($QUERY);
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'idBrtShipmentResponse' => $this->id_brt_shipment_response,
            'numeric_sender_reference' => $this->numeric_sender_reference,
            'alphanumeric_sender_reference' => $this->alphanumeric_sender_reference,
            'number' => $this->number,
            'measure_date' => $this->measure_date,
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z,
            'weight' => $this->weight,
            'volume' => $this->volume,
            'fiscal_id' => $this->fiscal_id,
            'p_flag' => $this->p_flag,
            'data_length' => $this->data_length,
            'parcel_id' => $this->parcel_id,
            'stream' => $this->stream,
            'stream_digital_label' => $this->stream_digital_label,
            'parcel_number_geo_post' => $this->parcel_number_geo_post,
            'tracking_by_parcel_id' => $this->tracking_by_parcel_id,
            'format' => $this->format,
        ];
    }
}
