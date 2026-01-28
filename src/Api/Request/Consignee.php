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

class Consignee
{
    protected $consigneeCompanyName;
    protected $consigneeAddress;
    protected $consigneeCountryAbbreviationISOAlpha2;
    protected $consigneeZIPCode;
    protected $consigneeCity;
    protected $consigneeProvinceAbbreviation;
    protected $consigneeClosingShift1_DayOfTheWeek;
    protected $consigneeClosingShift1_PeriodOfTheDay;
    protected $consigneeClosingShift2_DayOfTheWeek;
    protected $consigneeClosingShift2_PeriodOfTheDay;
    protected $consigneeContactName;
    protected $consigneeTelephone;
    protected $consigneeEMail;
    protected $consigneeMobilePhoneNumber;
    protected $consigneeVATNumber;
    protected $consigneeVATNumberCountryISOAlpha2;
    protected $consigneeItalianFiscalCode;

    public function __construct(array $consignee)
    {
        $this->consigneeCompanyName = (string) ($consignee['consigneeCompanyName'] ?? '');
        $this->consigneeAddress = (string) ($consignee['consigneeAddress'] ?? '');
        $this->consigneeZIPCode = (string) ($consignee['consigneeZIPCode'] ?? '');
        $this->consigneeCity = (string) ($consignee['consigneeCity'] ?? '');
        $this->consigneeProvinceAbbreviation = (string) ($consignee['consigneeProvinceAbbreviation'] ?? '');
        $this->consigneeCountryAbbreviationISOAlpha2 = (string) ($consignee['consigneeCountryAbbreviationISOAlpha2'] ?? '');

        $this->consigneeClosingShift1_DayOfTheWeek = (string) ($consignee['consigneeClosingShift1_DayOfTheWeek'] ?? '');
        $this->consigneeClosingShift1_PeriodOfTheDay = (string) ($consignee['consigneeClosingShift1_PeriodOfTheDay'] ?? '');
        $this->consigneeClosingShift2_DayOfTheWeek = (string) ($consignee['consigneeClosingShift2_DayOfTheWeek'] ?? '');
        $this->consigneeClosingShift2_PeriodOfTheDay = (string) ($consignee['consigneeClosingShift2_PeriodOfTheDay'] ?? '');

        $this->consigneeContactName = (string) ($consignee['consigneeContactName'] ?? '');
        $this->consigneeTelephone = (string) ($consignee['consigneeTelephone'] ?? '');
        $this->consigneeEMail = (string) ($consignee['consigneeEMail'] ?? '');
        $this->consigneeMobilePhoneNumber = (string) ($consignee['consigneeMobilePhoneNumber'] ?? '');

        $this->consigneeVATNumber = (string) ($consignee['consigneeVATNumber'] ?? '');
        $this->consigneeVATNumberCountryISOAlpha2 = (string) ($consignee['consigneeVATNumberCountryISOAlpha2'] ?? '');
        $this->consigneeItalianFiscalCode = (string) ($consignee['consigneeItalianFiscalCode'] ?? '');
    }

    public function toArray(): array
    {
        /*
         * 'consigneeVATNumber' => $this->consigneeVATNumber,
         * 'consigneeVATNumberCountryISOAlpha2' => $this->consigneeVATNumberCountryISOAlpha2,
         * 'consigneeItalianFiscalCode' => $this->consigneeItalianFiscalCode,
         * 'consigneeClosingShift1_DayOfTheWeek' => $this->consigneeClosingShift1_DayOfTheWeek,
         * 'consigneeClosingShift1_PeriodOfTheDay' => $this->consigneeClosingShift1_PeriodOfTheDay,
         * 'consigneeClosingShift2_DayOfTheWeek' => $this->consigneeClosingShift2_DayOfTheWeek,
         * 'consigneeClosingShift2_PeriodOfTheDay' => $this->consigneeClosingShift2_PeriodOfTheDay,
         */
        return [
            'consigneeCompanyName' => $this->consigneeCompanyName,
            'consigneeAddress' => $this->consigneeAddress,
            'consigneeZIPCode' => $this->consigneeZIPCode,
            'consigneeCity' => $this->consigneeCity,
            'consigneeProvinceAbbreviation' => $this->consigneeProvinceAbbreviation,
            'consigneeCountryAbbreviationISOAlpha2' => $this->consigneeCountryAbbreviationISOAlpha2,
            'consigneeContactName' => $this->consigneeContactName,
            'consigneeTelephone' => $this->consigneeTelephone,
            'consigneeEMail' => $this->consigneeEMail,
            'consigneeMobilePhoneNumber' => $this->consigneeMobilePhoneNumber,
        ];
    }
}
