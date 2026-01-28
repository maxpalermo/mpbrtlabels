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

class Parcels
{
    protected array $parcels;
    protected int $totalParcels;
    protected float $totalWeightKg;
    protected float $totalVolumeM3;

    public function __construct(array $parcels)
    {
        $this->setParcels($parcels);

        $this->totalParcels = \count($this->parcels);
        $this->totalWeightKg = \array_sum(\array_column($this->parcels, 'weightKg'));
        $this->totalVolumeM3 = \array_sum(\array_column($this->parcels, 'volumeM3'));
    }

    protected function setParcels($parcels)
    {
        foreach ($parcels as $parcel) {
            $parcel = new Parcel($parcel);
            $this->parcels[] = $parcel;
        }
    }

    protected function getTotalWeightKg()
    {
        $weightKg = 0;
        foreach ($this->parcels as $parcel) {
            $weightKg += $parcel->getWeightKg();
        }

        return (float) $weightKg;
    }

    protected function getTotalVolumeM3()
    {
        $volumeM3 = 0;
        foreach ($this->parcels as $parcel) {
            $volumeM3 += $parcel->getVolumeM3();
        }

        return (float) $volumeM3;
    }
}
