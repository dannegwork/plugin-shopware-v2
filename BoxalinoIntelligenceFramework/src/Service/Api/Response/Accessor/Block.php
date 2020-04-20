<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Response\Accessor;

use Boxalino\IntelligenceFramework\Service\Api\Util\AccessorHandlerInterface;
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
     * The load of the model is done on model request to ensure all other properties
     * (blocks, etc) have been set on the context which is passed via "$this"
     *
     * @return string|null
     */
    public function getModel() :?AccessorModelInterface
    {
        if(is_string($this->model))
        {
            $this->model = $this->getAccessorHandler()->getModel($this->model, $this);
        }

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

    /**
     * @return \ArrayIterator
     */
    public function getBlocks() : \ArrayIterator
    {
        return $this->blocks;
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
    public function setAccessor($accessor)
    {
        $this->accessor = $accessor;
        if(is_array($accessor))
        {
            $this->accessor = $accessor[0];
        }

        return $this;
    }

    /**
     * @param array | null
     * @return $this
     */
    public function setBlocks(?array $blocks)
    {
        $this->blocks = new \ArrayIterator();
        foreach($blocks as $block)
        {
            $this->blocks->append($this->toObject($block, $this->getAccessorHandler()->getAccessor("blocks")));
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
