<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Response\Accessor;

use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\AccessorInterface;

/**
 * Class BlocksDefinition
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Accessor
 */
interface BlockInterface extends AccessorInterface
{

    /**
     * @return AccessorModelInterface|null
     */
    public function getModel() : ?AccessorModelInterface;

    /**
     * @return string
     */
    public function getTemplate() : string;

    /**
     * @return string|null
     */
    public function getAccessor() : ?string;

    /**
     * @return \ArrayIterator
     */
    public function getBlocks() : \ArrayIterator;

    /**
     * @return int
     */
    public function getIndex() : int;

    /**
     * @param array $model
     * @return $this
     */
    public function setModel(array $model);

    /**
     * @param array $template
     * @return $this
     */
    public function setTemplate(array $template);

    /**
     * Accessor is identified as another widget request that provides content to the element
     * (ex: in the case of no search results matching the query, an automated request for "noresults" is done)
     *
     * @param array|null $accessor
     * @return $this
     */
    public function setAccessor($accessor);

    /**
     * @param null | array $blocks
     * @return $this
     */
    public function setBlocks(?array $blocks);

    /**
     * @param int|null $index
     * @return $this
     */
    public function setIndex(?int $index);

}
