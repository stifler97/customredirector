<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Customredirector extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'customredirector';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Binshops';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Custom Redirector');
        $this->description = $this->l('This module redirects based on specific entity ids into other ones');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionFrontControllerInitBefore');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookActionFrontControllerInitBefore($params)
    {
        $controller = $params['controller'];
        if($controller->php_self !== 'category') {
            return;
        }

        if(!Tools::getIsset('id_category')) {
            return;
        }

        $id_category = (int) Tools::getValue('id_category');

        try {
            $categoryMapArray = $this->readCategoryMapFileAndReturnMapArray();
        } catch (Exception $e) {
            PrestaShopLogger::addLog("{$this->name} --- {$e->getMessage()}");
            return;
        }

        if(!array_key_exists($id_category, $categoryMapArray)) {
            return;
        }

        $linkToRedirect = $this->context->link->getCategoryLink(
            $categoryMapArray[$id_category],
            null,
            $this->context->language->id
        );

        Tools::redirect($linkToRedirect);
    }

    protected function readCategoryMapFileAndReturnMapArray($fileName = 'categoryMap')
    {
        $file = "{$this->local_path}{$fileName}.csv";

        if(!file_exists($file)) {
            throw new Exception("File does not exist: {$file}");
        }

        $open = fopen($file, "r");

        if(!$open) {
            throw new Exception("Failed to open file: {$file}");
        }

        while (($line = fgetcsv($open, 1000, ",")) !== FALSE) 
        {
            $id_category_from = $line[0];
            $id_category_to = $line[1];
            $categoryMapArray[$id_category_from] = $id_category_to;
        }

        fclose($open);

        return $categoryMapArray;
    }
}
