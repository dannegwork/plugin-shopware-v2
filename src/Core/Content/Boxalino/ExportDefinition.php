<?php declare(strict_types=1);

namespace Boxalino\IntelligenceFramework\Core\Content\Boxalino;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ExportDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'boxalino_export';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ExportEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ExportCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('account', 'account'))->addFlags(new Required()),
            (new StringField('type', 'type'))->addFlags(new Required()),
            (new DateField('export_date', 'exportDate'))->addFlags(new Required()),
            (new StringField('status', 'status'))->addFlags(new Required()),
            (new UpdatedAtField())
        ]);
    }
}