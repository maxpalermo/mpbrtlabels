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

class Parcel
{
    protected $widthCm;
    protected $heightCm;
    protected $depthCm;
    protected $volumeM3;
    protected $weightKg;

    public function __construct(array $parcel)
    {
        $this->widthCm = (float) ($parcel['x'] ?? 0);
        $this->heightCm = (float) ($parcel['y'] ?? 0);
        $this->depthCm = (float) ($parcel['z'] ?? 0);
        $this->volumeM3 = (float) ($parcel['volume'] ?? 0);
        $this->weightKg = (float) ($parcel['weight'] ?? 1);
    }

    public function getWidthCm()
    {
        return (float) $this->widthCm;
    }

    public function getHeightCm()
    {
        return (float) $this->heightCm;
    }

    public function getDepthCm()
    {
        return (float) $this->depthCm;
    }

    public function getVolumeM3()
    {
        $volumeCm3 = $this->heightCm * $this->widthCm * $this->depthCm;
        $volumeM3 = $volumeCm3 / (10 ^ 6);

        $this->volumeM3 = $volumeM3;

        return (float) $volumeM3;
    }

    public function getWeightKg()
    {
        return (float) $this->weightKg;
    }
}
