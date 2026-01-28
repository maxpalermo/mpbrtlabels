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

use \ObjectModel;
use \Throwable;

class ModelBrtShipmentConfig extends ObjectModel
{
    public const TYPE_NETWORK = 'network';

    public $type;
    public $brt_code;
    public $value;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'brt_shipment_config',
        'primary' => 'id_brt_shipment_config',
        'fields' => [
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'brt_code' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'value' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true, 'size' => 999999999],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null, $id_shop_group = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $id_shop_group);
        try {
            $this->value = json_decode($this->value, true, true, JSON_THROW_ON_ERROR);
        } catch (Throwable $th) {
            $this->value = [];
        }
    }

    public function add($autodate = true, $null_values = true)
    {
        $this->value = json_encode($this->value, JSON_THROW_ON_ERROR);

        return parent::add($autodate, $null_values);
    }

    public function update($null_values = true)
    {
        $this->value = json_encode($this->value, JSON_THROW_ON_ERROR);

        return parent::update($null_values);
    }
}
