<?php declare(strict_types=1);

namespace Boxalino\IntelligenceFramework\Core\Content\Boxalino;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class CronExportEntity extends Entity
{
    use EntityIdTrait;


    /**
     * @var string
     */
    protected $exportDate;


    public function getExportDate(string $datetime) : string
    {
        return $this->exportDate;
    }

    public function setExportDate(string $exportDate): void
    {
        $this->exportDate = $exportDate;
    }

}