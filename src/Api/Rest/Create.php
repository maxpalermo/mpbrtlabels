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

class Create
{
    private $requestData;

    public function __construct($requestData)
    {
        $this->requestData = $requestData;
    }

    public function doPostRequest(): array
    {
        $url = 'https://api.brt.it/rest/v1/shipments/shipment';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->requestData));

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        file_put_contents(dirname(__FILE__) . '/response.dat', json_encode($result, JSON_PRETTY_PRINT));

        if (200 == $httpcode && $result) {
            $data = json_decode($result, true);

            return [
                'success' => true,
                'data' => $data,
            ];
        }

        if (is_array($result) || is_object($result) && empty($error)) {
            $error = json_encode($result);
        }
        if (empty($error) && is_string($result)) {
            $error = $result;
        }

        return [
            'success' => false,
            'error' => $error,
            'httpcode' => $httpcode,
            'data' => $result ? json_decode($result, true) : [],
        ];
    }

    public function parseResponse($response)
    {
        return json_decode($response, true);
    }

    /**
     * Decodifica lo stream etichetta (Base64).
     *
     * @param string $stream
     *
     * @return string|false
     */
    public static function decodeLabel($stream)
    {
        return base64_decode($stream);
    }

    protected static function extractError($response)
    {
        // 1. Cerca la sezione <div id="code">
        if (preg_match('/<div id="code">(.*?)<\/div>/is', $response, $matches)) {
            $codeBlock = strip_tags($matches[1]);
            // 2. Trova la prima riga significativa (Exception o Unrecognized)
            if (preg_match('/((Exception|Unrecognized).*?)(\n|$)/', $codeBlock, $errMatch)) {
                return trim($errMatch[1]);
            }

            // Se non trova, restituisci tutto il blocco code
            return trim($codeBlock);
        }
        // 3. Fallback: rimuovi html e cerca la riga con Exception
        $plain = strip_tags($response);
        if (preg_match('/((Exception|Unrecognized).*?)(\n|$)/', $plain, $errMatch)) {
            return trim($errMatch[1]);
        }

        // 4. Fallback generico
        return 'Errore non identificato';
    }
}
