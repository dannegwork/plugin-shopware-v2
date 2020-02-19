<?php declare(strict_types=1);

namespace Boxalino\IntelligenceFramework\Core\Content\Boxalino;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CronExportDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'boxalino_cron_export';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CronExportEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new DateField('export_date', 'exportDate'))->addFlags(new Required())
        ]);
    }
}