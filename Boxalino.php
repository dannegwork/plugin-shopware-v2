<?php
namespace Boxalino;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;

/**
 * Class Boxalino
 * This class handles most of the plugin logic.
 * It creates the needed tables, adds the custom-attributes, creates the menu-entry and creates the acl-rules.
 * Additionally it handles the update-method and the migration of older database-values.
 *
 * @package Boxalino
 */
class Boxalino extends Plugin
{

    public function activate(ActivateContext $context)
    {
        $context->scheduleClearCache(InstallContext::CACHE_LIST_DEFAULT);
    }

    public function install(InstallContext $context)
    {
        $this->createDatabase();
    }

    public function update(UpdateContext $context)
    {
        parent::update($context);
    }

    public function uninstall(UninstallContext $context)
    {
        if ($context->keepUserData()) {
            return;
        }
        $this->removeDatabase();
        parent::uninstall($context);
    }

    private function createDatabase()
    {
        $db = Shopware()->Db();
        $db->query(
            'CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier('exports') .
            ' ( ' . $db->quoteIdentifier('export_date') . ' DATETIME)'
        );
        $db->query(
            'CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier('cron_exports') .
            ' ( ' . $db->quoteIdentifier('export_date') . ' DATETIME)'
        );
    }

    private function removeDatabase()
    {
        $db = Shopware()->Db();
        $db->query(
            'DROP TABLE IF EXISTS ' . $db->quoteIdentifier('exports')
        );
        $db->query(
            'DROP TABLE IF EXISTS ' . $db->quoteIdentifier('cron_exports')
        );
    }
}