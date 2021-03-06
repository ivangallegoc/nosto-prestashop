<?php
/**
 * 2013-2017 Nosto Solutions Ltd
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@nosto.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Nosto Solutions Ltd <contact@nosto.com>
 * @copyright 2013-2017 Nosto Solutions Ltd
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use Nosto\NostoException as NostoSDKException;
use Nosto\Object\Format as NostoSDKCurrencyFormat;

/**
 * Helper class for currency related tasks.
 */
class NostoHelperCurrency
{
    const CURRENCY_SYMBOL_MARKER = '¤';
    const CURRENCY_GROUP_LENGTH = 3;
    const CURRENCY_PRECISION = 2;

    const PS_CURRENCY_DEFAULT = 'PS_CURRENCY_DEFAULT';

    /**
     * @param $id
     * @return Currency
     * @suppress PhanTypeMismatchArgument
     */
    private static function loadCurrency($id)
    {
        return new Currency((string)$id);
    }

    /**
     * Fetches the base currency from the context.
     *
     * @return Currency
     * @throws NostoSDKException
     */
    public static function getBaseCurrency()
    {
        $baseCurrencyId = (int)Configuration::get(
            self::PS_CURRENCY_DEFAULT,
            NostoHelperContext::getLanguageId(),
            NostoHelperContext::getShopGroupId(),
            NostoHelperContext::getShopId()
        );
        if ($baseCurrencyId === 0) {
            $baseCurrencyId = (int)Configuration::get(
                self::PS_CURRENCY_DEFAULT,
                0,
                NostoHelperContext::getShopGroupId(),
                NostoHelperContext::getShopId()
            );
        }
        $baseCurrency = self::loadCurrency($baseCurrencyId);
        if (!Validate::isLoadedObject($baseCurrency)) {
            throw new NostoSDKException(
                sprintf(
                    'Failed to find base currency for shop #%s and lang #%s.',
                    NostoHelperContext::getShopId(),
                    NostoHelperContext::getLanguageId()
                )
            );
        }

        return $baseCurrency;
    }

    /**
     * Fetches all currencies defined in context.
     *
     * @param boolean $onlyActive if set to true, only active languages will be returned
     * @return array the found currencies.
     */
    public static function getCurrencies($onlyActive = false)
    {
        $allCurrencies = Currency::getCurrenciesByIdShop(NostoHelperContext::getShopId());
        if ($onlyActive === true) {
            $currencies = array();
            foreach ($allCurrencies as $currency) {
                if (self::currencyActive($currency)) {
                    $currencies[] = $currency;
                }
            }
        } else {
            $currencies = $allCurrencies;
        }

        return $currencies;
    }

    /**
     * Parses a PS currency into a Nosto currency.
     *
     * @param array $currency the PS currency data.
     * @return NostoSDKCurrencyFormat
     * @throws NostoSDKException
     */
    public static function getNostoCurrency(array $currency)
    {
        if (Context::getContext() instanceof Context
            && (_PS_VERSION_ >= '1.7')
        ) {
            // In Prestashop 1.7 we use the CLDR
            try {
                $nostoCurrency = self::createWithCldr($currency);
                return $nostoCurrency;
            } catch (Exception $e) {
                NostoHelperLogger::error($e);
            }
        }
        if (empty($currency['format'])) {
            $currency['format'] = 2;  //Fallback to format 2
        }
        switch ($currency['format']) {
            /* X 0,000.00 */
            case 1:
                $groupSymbol = ',';
                $decimalSymbol = '.';
                $symbolPosition = true;
                break;
            /* 0 000,00 X*/
            case 2:
                $groupSymbol = ' ';
                $decimalSymbol = ',';
                $symbolPosition = false;
                break;
            /* X 0.000,00 */
            case 3:
                $groupSymbol = '.';
                $decimalSymbol = ',';
                $symbolPosition = true;
                break;
            /* 0,000.00 X */
            case 4:
                $groupSymbol = ',';
                $decimalSymbol = '.';
                $symbolPosition = false;
                break;
            /* X 0'000.00 */
            case 5:
                $groupSymbol = '\'';
                $decimalSymbol = '.';
                $symbolPosition = true;
                break;

            default:
                throw new NostoSDKException(
                    sprintf(
                        'Unsupported PrestaShop currency format %d.',
                        $currency['format']
                    )
                );
        }
        return new NostoSDKCurrencyFormat(
            $symbolPosition,
            $groupSymbol,
            self::CURRENCY_GROUP_LENGTH,
            $decimalSymbol,
            self::CURRENCY_PRECISION
        );
    }

    /**
     * Creates Nosto currency object using CLDR that was introduced in Prestashop 1.7
     *
     * @see https://github.com/ICanBoogie/CLDR
     *
     * @param array $currency Prestashop currency array
     * @return NostoSDKCurrencyFormat
     * @suppress PhanTypeMismatchArgument
     */
    private static function createWithCldr(array $currency)
    {
        $cldr = Tools::getCldr(null, NostoHelperContext::getLanguage()->language_code);
        /** @noinspection PhpParamsInspection */
        $cldrCurrency = new \ICanBoogie\CLDR\Currency($cldr->getRepository(), $currency['iso_code']);
        $localizedCurrency = $cldrCurrency->localize($cldr->getCulture());
        $pattern = $localizedCurrency->locale->numbers->currency_formats['standard'];
        $symbols = $localizedCurrency->locale->numbers->symbols;
        $symbolPos = Tools::strpos($pattern, self::CURRENCY_SYMBOL_MARKER);

        // Check if the currency symbol is before or after the amount.
        $symbolPosition = $symbolPos === 0;
        $groupSymbol = isset($symbols['group']) ? $symbols['group'] : ',';
        $decimalSymbol = isset($symbols['decimal']) ? $symbols['decimal'] : ',';

        return new NostoSDKCurrencyFormat(
            $symbolPosition,
            $groupSymbol,
            self::CURRENCY_GROUP_LENGTH,
            $decimalSymbol,
            self::CURRENCY_PRECISION
        );
    }

    private static function currencyActive(array $currency)
    {
        $active = true;
        if (!$currency['active']) {
            $active = false;
        } else {
            if (isset($currency['deleted']) && $currency['deleted']) {
                $active = false;
            }
        }

        return $active;
    }
}
