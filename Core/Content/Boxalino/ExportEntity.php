<?php declare(strict_types=1);

namespace Boxalino\IntelligenceFramework\Core\Content\Boxalino;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ExportEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $account;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $exportDate;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function setAccount(string $account): void
    {
        $this->account = $account;
    }

    public function getExportDate(string $datetime) : string
    {
        return $this->exportDate;
    }

    public function setExportDate(string $exportDate): void
    {
        $this->exportDate = $exportDate;
    }

    public function getStatus(string $status) : string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}