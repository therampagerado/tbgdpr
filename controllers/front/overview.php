<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class TbGdprOverviewModuleFrontController
 */
class TbGdprOverviewModuleFrontController extends ModuleFrontController
{
    /** @var bool $display_column_left */
    public $display_column_left = false;
    /** @var bool $display_column_right */
    public $display_column_right = false;

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'tbgdpr_page_top' => Configuration::get('TB_GDPR_TOP'),
            'tbgdpr_blocks'   => $this->getBlocks(),
            'tbgdpr_request'  => TbGdprRequest::getRequestsForGuest($this->context->cookie->id_guest),
        ]);

        $this->addCSS($this->module->getLocalPath().'views/css/front.css');
        $this->setTemplate('overview/main.tpl');
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function setMedia()
    {
        $result = parent::setMedia();

        $this->addJS(_PS_MODULE_DIR_."{$this->module->name}/views/js/matchheight.js");

        return $result;
    }

    /**
     * @return array
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function getBlocks()
    {
        $blocks = [
            'informed' => [
                'title'       => $this->module->l('Data Sharing', 'overview'),
                'description' => $this->module->l('On this page you can find the list of our data partners.', 'overview'),
                'controller'  => 'informed',
                'link'        => $this->context->link->getModuleLink($this->module->name, 'informed', [], true),
                'icon'        => 'shield',
            ],
            'correct' => [
                'title' => $this->module->l('Rectify information', 'overview'),
                'description' => $this->module->l('Update your information if your personal data is incomplete or incorrect.', 'overview'),
                'controller' => 'correct',
                'link'        => $this->context->link->getModuleLink($this->module->name, 'correct', [], true),
                'icon'        => 'shield',
            ],
            'anonymous' => [
                'title' => $this->module->l('Become anonymous', 'overview'),
                'description' => $this->module->l('Use this to anonymize your data. Be cautious, some options are irreversible.', 'overview'),
                'controller' => 'anonymous',
                'link'        => $this->context->link->getModuleLink($this->module->name, 'anonymous', [], true),
                'icon'        => 'shield',
            ],
            'restrict' => [
                'title' => $this->module->l('Restrict data processing', 'overview'),
                'description' => $this->module->l('You have the right to restrict processing of your data. Use this tool to control what data is processed.', 'overview'),
                'controller' => 'restrict',
                'link'        => $this->context->link->getModuleLink($this->module->name, 'restrict', [], true),
                'icon'        => 'shield',
            ],
            'object' => [
                'title' => $this->module->l('Unsubscribe', 'overview'),
                'description' => $this->module->l('Unsubscribe from one or more services.', 'overview'),
                'controller' => 'object',
                'link'        => $this->context->link->getModuleLink($this->module->name, 'object', [], true),
                'icon'        => 'shield',
            ],
            'notification' => [
                'title' => $this->module->l('Be notified', 'overview'),
                'description' => $this->module->l('You have the right to be notified in case of a data breach which compromises your personal data. Leave your contact details and we\'ll keep you posted!', 'overview'),
                'controller' => 'notification',
                'link'        => $this->context->link->getModuleLink($this->module->name, 'notification', [], true),
                'icon'        => 'shield',
            ],
            'dataportability' => [
                'title' => $this->module->l('Data portability', 'overview'),
                'description' => $this->module->l('Export your data with this tool.', 'overview'),
                'controller' => 'dataportability',
                'link'        => $this->context->link->getModuleLink($this->module->name, 'dataportability', [], true),
                'icon'        => 'shield',
            ],
            'removedata' => [
                'title' => $this->module->l('Right to be forgotten', 'overview'),
                'description' => $this->module->l('You have the right to be forgotten. Delete your account and information from the shop. Warning! Your account information, access to the orders you placed, and other important information will be deleted.', 'overview'),
                'controller' => 'removedata',
                'link'        => $this->context->link->getModuleLink($this->module->name, 'removedata', [], true),
                'icon'        => 'shield',
            ],
        ];

        $enabledBlocks = [
            'informed'        => Configuration::get(TbGdpr::INFORMED_ENABLED) && trim(Configuration::getInt(TbGdpr::INFORMED_TEXT)[$this->context->language->id]),
            'correct'         => Configuration::get(TbGdpr::CORRECTED_ENABLED) && trim(Configuration::getInt(TbGdpr::CORRECTED_TEXT)[$this->context->language->id]),
            'anonymous'       => Configuration::get(TbGdpr::ANONYMOUS_ENABLED) && trim(Configuration::getInt(TbGdpr::ANONYMOUS_TEXT)[$this->context->language->id]),
            'restrict'        => Configuration::get(TbGdpr::RESTRICT_ENABLED) && trim(Configuration::getInt(TbGdpr::RESTRICT_TEXT)[$this->context->language->id]),
            'object'          => Configuration::get(TbGdpr::OBJECT_ENABLED),
            'notification'    => Configuration::get(TbGdpr::NOTIFICATION_ENABLED) && trim(Configuration::getInt(TbGdpr::NOTIFICATION_TEXT)[$this->context->language->id]),
            'dataportability' => Configuration::get(TbGdpr::DATAPORTABILITY_ENABLED) && trim(Configuration::getInt(TbGdpr::DATAPORTABILITY_TEXT)[$this->context->language->id]),
            'removedata'      => Configuration::get(TbGdpr::FORGOTTEN_ENABLED),
        ];

        foreach ($blocks as $index => $block) {
            if (!$enabledBlocks[$index]) {
                unset($blocks[$index]);
            }
        }

        return $blocks;
    }
}
