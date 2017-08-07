<?php
/**
 * 2013-2016 Nosto Solutions Ltd
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
 * @copyright 2013-2016 Nosto Solutions Ltd
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Model for tagging customers.
 */
class NostoCustomer extends \Nosto\Object\User
{
    private $customer_reference;

    /**
     * Loads the customer data from supplied context and customer objects.
     *
     * @param Customer $customer the customer object.
     * @return NostoCustomer
     */
    public static function loadData(Customer $customer)
    {
        $user = new NostoCustomer();
        $user->setFirstName($customer->firstname);
        $user->setLastName($customer->lastname);
        $user->setEmail($customer->email);
        try {
            $user->populateCustomerReference($customer);
        } catch (Exception $e) {
            NostoHelperLogger::error(
                __CLASS__ . '::' . __FUNCTION__ . ' - ' . $e->getMessage(),
                $e->getCode()
            );
        }

        return $user;
    }

    /**
     * Populates customer reference attribute. If customer doesn't yet have
     * customer reference saved in db a new will be generated and saved
     *
     * @param Customer $customer
     */
    private function populateCustomerReference(Customer $customer)
    {
        /* @var NostoTaggingHelperCustomer $customer_helper */
        $customer_helper = Nosto::helper('nosto_tagging/customer');
        $customer_reference = $customer_helper->getCustomerReference($customer);
        if (!empty($customer_reference)) {
            $this->customer_reference = $customer_reference;
        } else {
            $customer_reference = $customer_helper->generateCustomerReference($customer);
            $customer_helper->saveCustomerReference($customer, $customer_reference);
            $this->customer_reference = $customer_reference;
        }
    }
}
