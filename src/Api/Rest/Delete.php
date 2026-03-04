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

namespace MpSoft\MpBrtLabels\Api\Rest;

class Delete
{
    public static function sendRequest($numericSenderReference, $alphanumericSenderReference = ''): array
    {
        $data = self::getRequestData($numericSenderReference, $alphanumericSenderReference);
        $url = 'https://api.brt.it/rest/v1/shipments/delete';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);
        }

        if (200 == $httpCode && $data) {
            return $data;
        }

        if (is_array($response) || is_object($response) && empty($error)) {
            $error = json_encode($response);
        }
        if (empty($error) && is_string($response)) {
            $error = $response;
        }

        return [
            'deleteResponse' => [
                'currentTimeUTC' => date('Y-m-d H:i:s'),
                'executionMessage' => [
                    'code' => -$httpCode,
                    'severity' => 'ERROR',
                    'codeDesc' => 'Errore durante la chiamata API',
                    'message' => $error
                ]
            ],
        ];
    }

    public static function getRequestData($numericSenderReference, $alphanumericSenderReference = '')
    {
        $sandBox = (int) \Configuration::get('MPBRTLABELS_SANDBOX_ENABLED');
        if ($sandBox) {
            $account_id = \Configuration::get('MPBRTLABELS_SANDBOX_ID');
            $account_pwd = \Configuration::get('MPBRTLABELS_SANDBOX_PWD');
            $senderCustomerCode = \Configuration::get('MPBRTLABELS_SANDBOX_CUSTOMER_CODE');
        } else {
            $account_id = \Configuration::get('MPBRTLABELS_ACCOUNT_ID');
            $account_pwd = \Configuration::get('MPBRTLABELS_ACCOUNT_PWD');
            $senderCustomerCode = \Configuration::get('MPBRTLABELS_ACCOUNT_CUSTOMER_CODE');
        }

        $data = [
            'account' => [
                'userID' => $account_id,
                'password' => $account_pwd
            ],
            'deleteData' => [
                'senderCustomerCode' => $senderCustomerCode,
                'numericSenderReference' => $numericSenderReference,
            ]
        ];

        if ($alphanumericSenderReference) {
            $data['deleteData']['alphanumericSenderReference'] = $alphanumericSenderReference;
        }

        return $data;
    }
}
