<?php
/**
 * 2013-2014 Nosto Solutions Ltd
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
 *  @author Nosto Solutions Ltd <contact@nosto.com>
 *  @copyright  2013-2014 Nosto Solutions Ltd
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once(dirname(__FILE__).'/api.php');

/**
 * Front controller for gathering all existing orders from the shop and sending the meta-data to Nosto.
 *
 * This controller should only be invoked once, when the Nosto module has been installed.
 */
class NostoTaggingOrderModuleFrontController extends NostoTaggingApiModuleFrontController
{
	/**
	 * @inheritdoc
	 */
	public function initContent()
	{
		$nosto_orders = array();
		foreach ($this->getOrderIds() as $id_order)
		{
			$order = new Order($id_order);
			$nosto_order = $this->module->getOrderData($order);
			if (!empty($nosto_order))
				$nosto_orders[] = $nosto_order;
			$order = null;
		}

		$this->encryptOutput(Tools::jsonEncode($nosto_orders));
	}

	/**
	 * Returns a list of all order ids with limit and offset applied.
	 *
	 * @return array the order id list.
	 */
	protected function getOrderIds()
	{
		$order_ids = array();
		$sql = <<<EOT
			SELECT `id_order`
			FROM `ps_orders`
			LIMIT $this->limit
			OFFSET $this->offset
EOT;
		$rows = Db::getInstance()->executeS($sql);
		foreach ($rows as $row)
			$order_ids[] = (int)$row['id_order'];
		return $order_ids;
	}
}
