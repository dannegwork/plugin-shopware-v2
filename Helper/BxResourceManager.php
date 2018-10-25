<?php
namespace Boxalino\Helper;

class BxResourceManager{

    /**
     * @var null
     */
    private static $instance = null;

    /**
     * @var array
     */
    protected $resource = array();

    /**
     * @var array
     */
    protected $types = array('collection', 'product', 'blog');

    /**
     * \Boxalino\Helper\BxResourceManager constructor.
     */
    private function __construct() {
        $this->initResource();
    }

    public static function instance() {
        if (self::$instance == null)
            self::$instance = new \Boxalino\Helper\BxResourceManager();
        return self::$instance;
    }

    protected function initResource() {
        foreach ($this->types as $type) {
            $this->resource[$type] = array();
        }
    }

    public function getResource($id, $type) {
        $resource = null;
        if(isset($this->resource[$type]) && isset($this->resource[$type][$id])) {
            $resource = $this->resource[$type][$id];
        }
        return $resource;
    }

    public function setResource($resource, $id, $type) {
        if(!isset($this->resource[$type])) {
            $this->resource[$type] = array();
        }
        $this->resource[$type][$id] = $resource;
    }
}