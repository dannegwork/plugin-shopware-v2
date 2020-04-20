<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Content;

use _HumbugBox069fea7e7fc7\Nette\DI\Config\Adapters\PhpAdapter;
use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\AccessorInterface;
use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\AccessorModelInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Class CollectionDataProvider
 *
 * Item refers to any data model/logic that is desired to be rendered/displayed
 * The integrator can decide to either use all data as provided by the Narrative API,
 * or to design custom data layers to represent the fetched content
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Content
 */
class CollectionDataProvider implements AccessorModelInterface
{
    /**
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SalesChannelContext
     */
    protected $salesChannelContext;

    /**
     * @var null | EntitySearchResult
     */
    protected $collection = null;

    /**
     * @var array
     */
    protected $hitIds;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        SalesChannelRepositoryInterface $productRepository,
        LoggerInterface $logger
    ){
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    /**
     * Accessing collection of products based on the hits
     *
     * @return EntitySearchResult
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    public function getCollection() : EntitySearchResult
    {
        if(is_null($this->collection))
        {
            $criteria = new Criteria($this->getHitIds());
            $this->collection = $this->productRepository->search(
                $criteria,
                $this->getSalesChannelContext()
            );
        }

        return $this->collection;
    }

    /**
     * @return array
     */
    public function getHitIds() : array
    {
        return $this->hitIds;
    }

    /**
     * @param \ArrayIterator $blocks
     * @param string $hitAccessor
     * @param string $idField
     */
    public function setHitIds(\ArrayIterator $blocks, string $hitAccessor, string $idField = "id")
    {
        $ids = array_map(function(AccessorInterface $block) use ($hitAccessor, $idField) {
            if(property_exists($block, $hitAccessor))
            {
                return $block->get($hitAccessor)->get($idField);
            }
        }, $blocks->getArrayCopy());

        $this->hitIds = $ids;
    }

    /**
     * @return SalesChannelContext
     */
    public function getSalesChannelContext() : SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    /**
     * @param string $id
     */
    public function getItem(string $id)
    {
        if(is_null($this->collection))
        {
            $this->getCollection();
        }

        if(in_array($id, $this->collection->getIds()))
        {
            return $this->collection->get($id);
        }

        return false;
    }

    /**
     * @param null | AccessorInterface $context
     * @return AccessorModelInterface
     */
    public function addAccessorContext(?AccessorInterface $context = null): AccessorModelInterface
    {
        $this->setHitIds($context->getBlocks(),
            $context->getAccessorHandler()->getAccessorSetter('bx-hit'),
            $context->getAccessorHandler()->getHitIdFieldName('bx-hit')
        );
        $this->setSalesChannelContext($context->getAccessorHandler()->getSalesChannelContext());

        return $this;
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @return self
     */
    public function setSalesChannelContext(SalesChannelContext $salesChannelContext): self
    {
        $this->salesChannelContext = $salesChannelContext;
        return $this;
    }

}
