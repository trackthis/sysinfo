<?php

namespace DB;

class Entity {

    /**
     * @var string
     */
    protected $_id = null;

    /**
     * Entity constructor.
     *
     * @param object|string $data
     *
     * @throws \JsonMapper_Exception
     */
    public function __construct($data = null) {
        static $mapper = null;
        if (is_null($mapper)) $mapper = new \JsonMapper();
        if (is_array($data))  $data = json_encode($data);
        if (is_string($data)) $data = json_decode($data);
        if (!is_null($data))  $mapper->map($data, $this);
        if (!$this->_id) $this->_id = uniqid('',true);
    }

    /**
     * @return string
     */
    public function __toString() {
        $data = get_object_vars($this);
        unset($data['__collection']);
        return json_encode($data);
    }

    /**
     * @return string
     */
    public function getId(): string {
        return $this->_id;
    }

    /**
     * @param string $id
     *
     * @return Entity
     */
    public function setId(string $id): Entity {
        $this->_id = $id;
        return $this;
    }
}
