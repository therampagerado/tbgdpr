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

namespace TbGdprModule;

use Adapter_Exception;
use Configuration;
use Error;
use Exception;
use Language;
use PrestaShopDatabaseException;
use PrestaShopException;
use ReflectionClass;
use ReflectionException;
use TbGdprModule\Exception\GdprException;
use TbGdprModule\PhpParser\NodeTraverser;
use TbGdprModule\PhpParser\ParserFactory;
use TbGdprModule\PhpParser\PrettyPrinter\Standard as StandardPrinter;
use TbGdprModule\Tools as GdprTools;
use TbGdprRequest;
use TbObjectRequest;
use TbRemoveRequest;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Trait Installation
 *
 * @package TbGdprModule
 */
trait Installation
{
    /**
     * @return bool
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     */
    public function install()
    {
        if (version_compare(phpversion(), '5.6.0', '<')) {
            $this->context->controller->errors[] = sprintf($this->l('Unable to install - your store is not GDPR compliant: %s'), $this->l('Your PHP version is end-of-life'));
        }

        // random_bytes should be secure and not throw an exception
        try {
            if (!function_exists('random_bytes')) {
                throw new Exception('random_bytes function does not exist');
            }
            random_bytes(64);
        } catch (Exception $e) {
            $this->context->controller->errors[] = sprintf($this->l('Unable to install - your store is not GDPR compliant: %s'), $e->getMessage());
            return false;
        }

        $this->installFrontControllerOverride();
        $this->installDefaultSettings();

        TbGdprRequest::createDatabase();
        TbObjectRequest::createDatabase();
        TbRemoveRequest::createDatabase();

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('displayTop') &&
            $this->registerHook('displayTopNav2') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayCustomerAccount') &&
            $this->registerHook('displayFooterAfter');
    }

    /**
     * @return bool
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     */
    public function uninstall()
    {
        $this->uninstallFrontControllerOverride();

        return parent::uninstall();
    }

    /**
     * @return bool
     * @throws ReflectionException
     * @throws PrestaShopException
     */
    protected function installFrontControllerOverride()
    {
        $reflection = new ReflectionClass('FrontController');
        $filename = $reflection->getFileName();
        if (strpos(realpath($filename), realpath(_PS_OVERRIDE_DIR_)) === false) {
            if (!file_exists(_PS_OVERRIDE_DIR_.'classes')) {
                if (defined('_TB_UMASK_')) {
                    $oldUmask = umask(_TB_UMASK_);
                }
                @mkdir(_PS_OVERRIDE_DIR_.'classes');
                if (isset($oldUmask)) {
                    umask($oldUmask);
                }
            }
            if (!file_exists(_PS_OVERRIDE_DIR_.'classes/controller')) {
                if (defined('_TB_UMASK_')) {
                    $oldUmask = umask(_TB_UMASK_);
                }
                @mkdir(_PS_OVERRIDE_DIR_.'classes/controller');
                if (isset($oldUmask)) {
                    umask($oldUmask);
                }
            }
            $filename = _PS_OVERRIDE_DIR_.'classes/controller/FrontController.php';
            if (defined('_TB_UMASK_')) {
                $oldUmask = umask(_TB_UMASK_);
            }
            file_put_contents($filename, implode("\n", [
                '<?php',
                '',
                "if (!defined('_PS_VERSION_')) {",
                "    exit;",
                "}",
                '',
                'class FrontController extends FrontControllerCore {}',
            ]));
            if (isset($oldUmask)) {
                umask($oldUmask);
            }
        }
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
        try {
            $stmts = $parser->parse(file_get_contents($filename));
            $prettyPrinter = new StandardPrinter();
            $overrideVisitor = new OverrideVisitor($this);
            $traverser = new NodeTraverser();
            $overrideVisitor->setOverrideToInstall('FrontController::__destruct');
            $traverser->addVisitor($overrideVisitor);
            $traverser->traverse($stmts);
            file_put_contents($reflection->getFileName(), $prettyPrinter->prettyPrintFile($stmts));
        } catch (\Error $e) {
            $this->context->controller->errors[] = $this->l('Unable to remove override', 'Installation').": Parse Error: {$e->getMessage()}";
            return false;
        }
        @unlink(_PS_CACHE_DIR_.'/class_index.php');

        return true;
    }

    /**
     * Install default settings from a local JSON file
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    protected function installDefaultSettings()
    {
        $defaults = GdprTools::defaultConsentSettings();
        $languages = Language::getLanguages(false);
        foreach ($defaults as $key => $default) {
            if (isset($default['en'])) {
                $value = [];
                foreach ($languages as $language) {
                    if (isset($default[strtolower($language['iso_code'])])) {
                        $value[$language['id_lang']] = $default[strtolower($language['iso_code'])];
                    } else {
                        $value[$language['id_lang']] = $default['en'];
                    }
                }
            } else {
                $value = $default;
            }
            Configuration::updateGlobalValue($key, $value);
        }
    }

    /**
     * Install email templates
     *
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    protected function installEmailTemplates()
    {
        if (!file_exists(_PS_THEME_DIR_."modules/{$this->name}/")) {
            @mkdir(_PS_THEME_DIR_."modules/{$this->name}/");
        }
        if (!file_exists(_PS_THEME_DIR_."modules/{$this->name}/mails/")) {
            @mkdir(_PS_THEME_DIR_."modules/{$this->name}/mails/");
        }
        foreach (Language::getLanguages(false) as $language) {
            $iso = strtolower($language['iso_code']);
            if (!file_exists(__DIR__."/../mails/{$iso}/")) {
                GdprTools::xcopy(__DIR__."/../mails/en/", __DIR__."/../mails/{$iso}/");
                if (!file_exists(_PS_THEME_DIR_."modules/{$this->name}/mails/{$iso}/")) {
                    GdprTools::xcopy(__DIR__."/../mails/en/", _PS_THEME_DIR_."modules/{$this->name}/mails/{$iso}/");
                }
            } else {
                if (!file_exists(_PS_THEME_DIR_."modules/{$this->name}/mails/{$iso}/")) {
                    GdprTools::xcopy(__DIR__."/../mails/{$iso}/", _PS_THEME_DIR_."modules/{$this->name}/mails/{$iso}/");
                }
            }
        }
    }

    /**
     * @return bool
     * @throws ReflectionException
     * @throws PrestaShopException
     */
    protected function uninstallFrontControllerOverride()
    {
        $reflection = new ReflectionClass('FrontController');
        $filename = $reflection->getFileName();
        if (strpos(realpath($filename), realpath(_PS_OVERRIDE_DIR_)) === false) {
            return true; // Override already gone
        }
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
        try {
            $stmts = $parser->parse(file_get_contents($filename));
            $traverser = new NodeTraverser();
            $prettyPrinter = new StandardPrinter();
            $overrideVisitor = new OverrideVisitor($this);
            $overrideVisitor->setOverrideToUninstall('FrontController::__destruct');
            $traverser->addVisitor($overrideVisitor);
            $traverser->traverse($stmts);
            file_put_contents($reflection->getFileName(), $prettyPrinter->prettyPrintFile($stmts));
        } catch (Error $e) {
            $this->context->controller->errors[] = $this->l('Unable to remove override', 'Installation').": Parse Error: {$e->getMessage()}";
            return false;
        }
        @unlink(_PS_CACHE_DIR_.'/class_index.php');

        return true;
    }
}
