<?php declare(strict_types=1);

namespace Boxalino\IntelligenceFramework\Core\Content\Boxalino;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class ExportCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ExportEntity::class;
    }
}