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

namespace MpSoft\MpBrtLabels\Api\Request;

class CodPaymentType
{
    const CONTANTI = '';
    const ASSEGNO_BANCARIO_MITTENTE = 'BM';
    const ASSEGNO_CIRCOLARE_MITTENTE = 'CM';
    const ASSEGNO_BANCARIO_CORRIERE_MANLEVA = 'BB';
    const ASSEGNO_MITTENTE_ORIGINAL = 'OM';
    const ASSEGNO_CIRCOLARE_MITTENTE_ORIGINAL = 'OC';

    protected $codPayment;

    public function __construct($codPayment)
    {
        $this->codPayment = $codPayment;
    }

    public function getCodPaymentOptions()
    {
        return [
            [
                'id' => self::CONTANTI,
                'value' => 'CONTANTI',
            ],
            [
                'id' => self::ASSEGNO_BANCARIO_MITTENTE,
                'value' => 'ASSEGNO BANCARIO MITTENTE',
            ],
            [
                'id' => self::ASSEGNO_CIRCOLARE_MITTENTE,
                'value' => 'ASSEGNO CIRCOLARE MITTENTE',
            ],
            [
                'id' => self::ASSEGNO_BANCARIO_CORRIERE_MANLEVA,
                'value' => 'ASSEGNO BANCARIO CORRIERE (MANLEVA)',
            ],
            [
                'id' => self::ASSEGNO_MITTENTE_ORIGINAL,
                'value' => 'ASSEGNO MITTENTE (ORIGINALE)',
            ],
            [
                'id' => self::ASSEGNO_CIRCOLARE_MITTENTE_ORIGINAL,
                'value' => 'ASSEGNO CIRCOLARE MITTENTE (ORIGINALE)',
            ],
        ];
    }

    public function getCodPaymentArray()
    {
        $array = $this->getCodPaymentOptions();
        $output = [];
        foreach ($array as $item) {
            $output[$item['id']] = $item['value'];
        }

        return $output;
    }

    public function getCodPaymentAssociativeArray()
    {
        return $this->getCodPaymentOptions();
    }

    public function getCodPaymentSelectOptions()
    {
        $options = $this->getCodPaymentOptions();
        $output = [];

        foreach ($options as $item) {
            $option = "<option value=\"{$item['id']}\">{$item['value']}</option>";
            $output[] = $option;
        }

        return implode("\n", $output);
    }
}
