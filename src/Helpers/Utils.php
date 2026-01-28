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

class Utils
{
    /**
     * Restituisce elenco impiegati per multiselect.
     */
    public static function getEmployees()
    {
        $emps = [];
        foreach (\Employee::getEmployees() as $e) {
            $emps[] = [
                'id_employee' => $e['id_employee'],
                'name' => $e['firstname'] . ' ' . $e['lastname'],
            ];
        }

        return $emps;
    }

    /**
     * Restituisce elenco stati ordine.
     */
    public static function getOrderStates()
    {
        $states = [];
        foreach (\OrderState::getOrderStates((int) \Configuration::get('PS_LANG_DEFAULT')) as $s) {
            $states[] = [
                'id_order_state' => $s['id_order_state'],
                'name' => $s['name'],
            ];
        }

        return $states;
    }

    /**
     * Restituisce elenco moduli pagamento installati.
     */
    public static function getAvailablePaymentModules()
    {
        $modules = [];
        foreach (\PaymentModule::getInstalledPaymentModules() as $m) {
            $module = \Module::getInstanceByName($m['name']);
            $modules[] = [
                'name' => $m['name'],
                'displayName' => $module ? $module->displayName : $m['name'],
            ];
        }

        return $modules;
    }
}
