<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Content;

use Boxalino\IntelligenceFramework\Service\Api\Response\Component\Block;
use Boxalino\IntelligenceFramework\Service\Api\Response\ResponseHydratorTrait;
use Psr\Log\LoggerInterface;

/**
 * Class BlocksDataProvider
 *
 * The Boxalino response is built of a list of blocks
 * A block matches a narrative layout segment
 * defined by each individual root sections of the narrative as designed in the Boxalino Intelligence Admin
 *
 * Depending on the context of the request and the request context handler, different JSON paths match
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Content
 */
class BlocksDataProvider implements \IteratorAggregate
{
    use ResponseHydratorTrait;

    protected $blocks;

    public function __construct(array $blocks)
    {
        $this->blocks = $blocks;
    }

    /**
     *
     * @return \Traversable|void
     */
    public function getIterator()
    {
        foreach($this->blocks as $block)
        {
            yield $block;
        }
    }

}
