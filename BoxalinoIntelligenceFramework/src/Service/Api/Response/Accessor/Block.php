<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Response\Accessor;

use Boxalino\IntelligenceFramework\Service\Api\Content\BlocksDataProvider;
use Monolog\Logger;

/**
 * Class Block
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Accessor
 */
class Block extends Accessor
    implements BlockInterface
{

    /**
     * @var string
     */
    protected $model;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var null | string
     */
    protected $accessor = null;

    /**
     * @var \ArrayIterator
     */
    protected $blocks;

    /**
     * @var int
     */
    protected $index = 0;

    /**
     * @return string
     */
    public function getModel() : string
    {
        return $this->model;
    }

    /**
     * @return string
     */
    public function getTemplate() : string
    {
        return $this->template;
    }

    /**
     * @return string|null
     */
    public function getAccessor() : ?string
    {
        return $this->accessor;
    }

    public function getBlocks() : \ArrayIterator
    {

    }

    /**
     * @return int
     */
    public function getIndex() : int
    {
        return $this->index;
    }


    /**
     * @param null | array $model
     * @return $this
     */
    public function setModel(array $model)
    {
        $this->model = $model[0] ?? null;
        return $this;
    }

    /**
     * @param array $template
     * @return $this
     */
    public function setTemplate(array $template)
    {
        $this->template = $template[0] ?? null;
        return $this;
    }

    /**
     * Accessor is identified as another widget request that provides content to the element
     * (ex: in the case of no search results matching the query, an automated request for "noresults" is done)
     *
     * @param array|null $accessor
     * @return $this
     */
    public function setAccessor(?array $accessor)
    {
        $this->accessor = $accessor[0] ?? null;
        return $this;
    }

    /**
     * @param array | null
     * @return $this
     */
    public function setBlocks(?array $blocks)
    {
        $this->blocks =[];
        foreach($blocks as $block)
        {
            $this->blocks[] = $this->toObject($block, $this->getAccessorHandler()->getAccessor("blocks"));
        }

        return $this;
    }

    /**
     * @param int|null $index
     * @return $this
     */
    public function setIndex(?int $index)
    {
        $this->index = $index ?? 0;
        return $this;
    }


}
