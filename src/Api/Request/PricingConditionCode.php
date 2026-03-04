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

class PricingConditionCode
{
    protected $newtwok;
    protected $numberOfParcels;
    protected $weightKg;
    protected $volumeM3;
    protected $sandBox;

    public function __construct($network, $numberOfParcels, $weightKg, $volumeM3, $sandBox = false)
    {
        $this->newtwok = $network;
        $this->numberOfParcels = $numberOfParcels;
        $this->weightKg = $weightKg;
        $this->volumeM3 = $volumeM3;
        $this->sandBox = $sandBox;
    }

    public function getPricingConditionCode()
    {
        if ($this->newtwok == 'D') {
            if ($this->numberOfParcels == 1) {
                $pricingConditionCode = '390';
            } elseif ($this->numberOfParcels > 1 && $this->numberOfParcels < 6) {
                $pricingConditionCode = '395';
            } else {
                $pricingConditionCode = '';
            }
        } elseif ($this->newtwok == '' && $this->weightKg == 1 && $this->volumeM3 == 0.001) {
            $pricingConditionCode = '100';
        } else {
            $pricingConditionCode = '020';  // 010
        }

        if ($this->sandBox) {
            $pricingConditionCode = '';
        }

        return $pricingConditionCode;
    }
}
