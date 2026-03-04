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

namespace MpSoft\MpBrtLabels\Helpers;

use MpSoft\MpBrtLabels\Models\ModelBrtLabelsResponse;

class Label
{
    public static function getLabels($items = [])
    {
        if (!$items) {
            return false;
        }

        $labels = [];
        foreach ($items as $item) {
            $labels[] = self::getLabelByNumericSenderreference($item['numericSenderReference'], $item['year']);
        }

        return $labels;
    }

    public static function getLabelsStream($items = [])
    {
        if (!$items) {
            return false;
        }

        $labels = [];
        foreach ($items as $item) {
            $label = self::getLabelByNumericSenderreference($item['numericSenderReference'], $item['year']);
            $streams = $label ? array_column($label, 'stream') : [];
            $labels = array_merge($labels, $streams);
        }

        return $labels;
    }

    public static function getLabelByNumericSenderreference($numericSenderReference, $year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $db = \Db::getInstance();
        $sql = new \DbQuery();
        $sql
            ->select('labels')
            ->from(ModelBrtLabelsResponse::$definition['table'])
            ->where('numericSenderReference = ' . (int) $numericSenderReference)
            ->where('year = ' . (int) $year);

        $row = $db->getRow($sql);

        if ($row) {
            $row = json_decode($row['labels'], true);
            return $row['label'] ?? [];
        }

        return [];
    }

    public static function getLabelParcelIDByNumericSenderreference($numericSenderReference, $year = null)
    {
        $parcels = [];
        $label = self::getLabelByNumericSenderreference($numericSenderReference, $year);
        foreach ($label as $parcel) {
            $parcels[] = $parcel['parcelID'];
        }

        return $parcels;
    }

    public static function getLabelTrackingByParcelIDByNumericSenderreference($numericSenderReference, $year = null)
    {
        $parcels = [];
        $label = self::getLabelByNumericSenderreference($numericSenderReference, $year);
        foreach ($label as $parcel) {
            $parcels[] = $parcel['trackingByParcelID'];
        }

        return $parcels;
    }
}
