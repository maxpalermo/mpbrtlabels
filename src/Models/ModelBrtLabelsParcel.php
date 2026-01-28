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

namespace MpSoft\MpBrtLabels\Models;

use MpSoft\MpBrtLabels\Helpers\SqlInstall;

class ModelBrtLabelsParcel extends \ObjectModel
{
    public $PECOD;
    public $PPESO;
    public $PVOLU;
    public $X;
    public $Y;
    public $Z;
    public $ID_FISCALE;
    public $PFLAG;
    public $PTIMP;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'brt_labels_parcel',
        'primary' => 'id_brt_labels_parcel',
        'fields' => [
            'PECOD' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 64,
                'default' => '',
            ],
            'PPESO' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isFloat',
                'required' => true,
                'default' => 0,
            ],
            'PVOLU' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isFloat',
                'required' => true,
                'default' => 0,
            ],
            'X' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'default' => 0,
            ],
            'Y' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'default' => 0,
            ],
            'Z' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'default' => 0,
            ],
            'ID_FISCALE' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => false,
                'default' => null,
            ],
            'PFLAG' => [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => false,
                'default' => null,
            ],
            'PTIMP' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDateFormat',
                'required' => false,
                'default' => null,
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDateFormat',
                'required' => true,
                'default' => null,
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDateFormat',
                'required' => false,
                'default' => null,
            ],
        ],
    ];

    public static function install()
    {
        return SqlInstall::install(self::$definition);
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'PECOD' => $this->PECOD,
            'PPESO' => $this->PPESO,
            'PVOLU' => $this->PVOLU,
            'X' => $this->X,
            'Y' => $this->Y,
            'Z' => $this->Z,
            'ID_FISCALE' => $this->ID_FISCALE,
            'PFLAG' => $this->PFLAG,
            'PTIMP' => $this->PTIMP,
            'date_add' => $this->date_add,
            'date_upd' => $this->date_upd,
        ];
    }

    public function getAjaxParams()
    {
        return [
            'parcelId' => $this->id,
            'x' => $this->X,
            'y' => $this->Y,
            'z' => $this->Z,
            'weight' => $this->PPESO,
            'volume' => $this->PVOLU,
            'idFiscale' => $this->ID_FISCALE,
            'pflag' => $this->PFLAG,
            'ptimp' => $this->PTIMP,
            'date_add' => $this->date_add,
            'date_upd' => $this->date_upd,
        ];
    }

    public static function getByParcelId($parcelId)
    {
        return self::getBy('id_brt_labels_parcel', $parcelId);
    }

    public static function getByParcelCode($parcelCode)
    {
        return self::getBy('PECOD', $parcelCode);
    }

    public static function getBy($field, $value)
    {
        $value = pSQL($value);
        $db = \Db::getInstance();
        $sql = new \DbQuery();
        $sql
            ->select(self::$definition['primary'])
            ->from(self::$definition['table'])
            ->where("$field = '$value'");

        $id = (int) $db->getValue($sql);

        return new self($id);
    }
}
