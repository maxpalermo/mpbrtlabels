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

class SqlInstall
{
    public static function install($definition)
    {
        $table = _DB_PREFIX_ . $definition['table'];
        $primary = $definition['primary'];

        $fieldsSql = [];
        // Chiave primaria
        $fieldsSql[] = "`{$primary}` INT UNSIGNED NOT NULL AUTO_INCREMENT";

        $fieldLangSql = [];
        $fieldLangSql[] = "`{$primary}` INT UNSIGNED NOT NULL";
        $fieldLangSql[] = '`id_lang` INT UNSIGNED NOT NULL';

        // Altri campi
        foreach ($definition['fields'] as $fieldName => $fieldDef) {
            if ($fieldName === $primary) {
                continue;
            }

            $type = 'TEXT';
            $unsigned = '';
            $default = '';
            $nullable = 'NULL';

            if (!empty($fieldDef['required'])) {
                $nullable = 'NOT NULL';
            }

            switch ($fieldDef['type']) {
                case \ObjectModelCore::TYPE_INT:
                    $type = 'INT';
                    if (preg_match('/unsigned/i', $fieldDef['validate'])) {
                        $unsigned = 'UNSIGNED';
                    }
                    $default = isset($fieldDef['default']) ? "DEFAULT '" . pSQL($fieldDef['default']) . "'" : 'DEFAULT 0';
                    break;
                case \ObjectModelCore::TYPE_BOOL:
                    $type = 'TINYINT(1)';
                    $unsigned = 'UNSIGNED';
                    $default = isset($fieldDef['default']) ? "DEFAULT '" . (int) $fieldDef['default'] . "'" : 'DEFAULT 0';
                    break;
                case \ObjectModelCore::TYPE_DATE:
                    $type = 'DATETIME';
                    $default = isset($fieldDef['default']) ? "DEFAULT '" . pSQL($fieldDef['default']) . "'" : '';
                    break;
                case \ObjectModelCore::TYPE_FLOAT:
                    $type = 'DECIMAL(20,6)';
                    $default = isset($fieldDef['default']) ? "DEFAULT '" . pSQL($fieldDef['default']) . "'" : "DEFAULT '0.000000'";
                    break;
                case \ObjectModelCore::TYPE_STRING:
                default:
                    if (!empty($fieldDef['size'])) {
                        if ($fieldDef['size'] < 256) {
                            $type = 'VARCHAR(' . (int) $fieldDef['size'] . ')';
                        } elseif ($fieldDef['size'] == 1024 * 1024) {
                            $type = 'JSON';
                        } elseif ($fieldDef['size'] == 1024 * 1048) {
                            $type = 'TEXT';
                        } elseif ($fieldDef['size'] == 1024 * 1096) {
                            $type = 'MEDIUMTEXT';
                        } else {
                            $type = 'LONGTEXT';
                        }
                    } else {
                        $type = 'LONGTEXT';
                    }
                    $default = isset($fieldDef['default']) ? "DEFAULT '" . pSQL($fieldDef['default']) . "'" : '';
                    break;
            }

            $fieldSql = sprintf(
                '`%s` %s %s %s %s',
                bqSQL($fieldName),
                $type,
                $unsigned,
                $nullable,
                $default
            );

            if (isset($fieldDef['lang']) && $fieldDef['lang']) {
                $fieldLangSql[] = $fieldSql;
            } else {
                $fieldsSql[] = $fieldSql;
            }
        }

        $fieldsSql[] = "PRIMARY KEY (`{$primary}`)";

        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS `%s` (%s) ENGINE=%s DEFAULT CHARSET=utf8mb4;',
            bqSQL($table),
            implode(",\n", $fieldsSql),
            _MYSQL_ENGINE_
        );

        return \Db::getInstance()->execute($sql);
    }
}
