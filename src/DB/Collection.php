<?php

namespace DB;

use Entity\Ping;

class Collection {

    /**
     * @var string
     */
    protected $filename = null;

    /**
     * @var string
     */
    protected $entityClass = null;

    /**
     * @var array
     */
    protected $cache = array();

    /**
     * @var int
     */
    protected $lock = 0;

    /**
     * @var array
     */
    protected $queue = array();

    /**
     * @param string $path
     *
     * @return bool
     */
    protected static function createPath($path) {
        if (is_dir($path)) return true;
        $prev_path = substr($path, 0, strrpos($path, '/', -2) + 1);
        $return = self::createPath($prev_path);
        return ($return && is_writable($prev_path)) ? mkdir($path) : false;
    }

    protected static function fdCopy($source, $destination) {
        while (!feof($source)) {
            fwrite($destination, fread($source, 2048));
        }
    }

    /**
     * Collection constructor.
     *
     * @param        $filename
     * @param string $entityName
     *
     * @throws \Exception
     */
    public function __construct($filename, $entityClass = null) {

        // Verify the entity class
        if (is_null($entityClass)) $entityClass = Entity::class;
        if (!is_a($entityClass, Entity::class, true)) {
            throw new \Exception("'$entityClass' does not extend " . (Entity::class));
        }
        $this->entityClass = $entityClass;

        // Ensure the file exists
        self::createPath(dirname($filename));
        if (!file_exists($filename)) touch($filename);
        $this->filename = $filename;
    }

    /**
     * @param string $id
     *
     * @return Entity|null
     */
    public function fetch( $id ) {
        $fd = fopen($this->filename, 'c+');
        foreach ($this->cache as $key => $value)
            if($key<=$id) fseek($fd,$value);
        $pos = ftell($fd);
        while(($line=fgets($fd))!==false) {
            $line = trim($line);
            if(!strlen($line)) continue;
            if($line==='null') continue;
            $data = json_decode($line);
            if($data->_id === $id) {
                $this->cache[$data->_id] = $pos;
                ksort($this->cache);
                return new $this->entityClass($data);
            }
            $pos = ftell($fd);
        }
        return null;
    }

    /**
     * Writes an entity to the persistant storage
     *
     * @param Entity $entity
     */
    public function save($entity) {
        if($this->lock) {
            $this->queue[] = [[$this,'delete'],[new $this->entityClass($entity->__toString())]];
            return;
        }

        $store_fd = fopen($this->filename, 'c+');
        $temp_fd  = fopen('php://temp', 'c+');
        $written  = false;

        // Try to merge
        while(($line=fgets($store_fd))!==false) {
            $line = trim($line);
            if(!strlen($line)) continue;
            if($line==='null') continue;
            $data = json_decode($line,true);

            if($data['_id']===$entity->getId()) {
                $this->cache = array( $entity->getId() => ftell($temp_fd) );
                ksort($this->cache);
                fputs($temp_fd,$entity->__toString()."\n");
                $written = true;
                self::fdCopy($store_fd,$temp_fd);
                break;
            } else if ($data['_id']>$entity->getId()) {
                $this->cache = array( $entity->getId() => ftell($temp_fd) );
                fputs($temp_fd,$entity->__toString()."\n");
                $this->cache[$data['_id']] = ftell($temp_fd);
                ksort($this->cache);
                fputs($temp_fd,$line."\n");
                $written = true;
                self::fdCopy($store_fd,$temp_fd);
                break;
            }

            fputs($temp_fd,$line."\n");
        }

        // Append if needed
        if(!$written) {
            fputs($temp_fd,$entity->__toString()."\n");
        }

        // Copy everything back
        rewind($store_fd);
        rewind($temp_fd);
        self::fdCopy($temp_fd,$store_fd);
        fclose($store_fd);
        fclose($temp_fd);
    }

    /**
     * @param Callable(mixed,Entity) $callable
     * @param mixed $initialState
     *
     * @throws \Exception
     * @return mixed
     */
    public function reduce($callable, $initialState = null) {
        if (!is_callable($callable)) throw new \Exception("Given callback not callable");
        $this->lock++;
        $fd = fopen($this->filename, 'c+');
        $accumulator = $initialState;
        while (($line = fgets($fd)) !== false) {
            $line = trim($line);
            if(!strlen($line)) continue;
            if($line==='null') continue;
            $accumulator = call_user_func_array($callable, array($accumulator, new $this->entityClass(json_decode($line))));
        }
        fclose($fd);
        $this->lock--;

        if( ($this->lock === 0) && count($this->queue) ) {
            while($this->queue) {
                $task = array_shift($this->queue);
                call_user_func_array($task[0],$task[1]);
            }
        }

        return $accumulator;
    }

    /**
     * @param Entity $entity
     */
    public function delete(Entity $entity) {
        if($this->lock) {
            $this->queue[] = [[$this,'delete'],[new $this->entityClass($entity->__toString())]];
            return;
        }

        $store_fd = fopen($this->filename, 'c+');
        $temp_fd  = fopen('php://temp', 'c+');

        // Loop through
        while(($line=fgets($store_fd))!==false) {
            $line = trim($line);
            $data = json_decode($line,true);
            if($data['_id']===$entity->getId()) continue;
            fputs($temp_fd,$line."\n");
        }

        // Copy everything back
        rewind($store_fd);
        rewind($temp_fd);
        self::fdCopy($temp_fd,$store_fd);
        ftruncate($store_fd,ftell($store_fd));
        fclose($store_fd);
        fclose($temp_fd);

        // Clear the cache
        $this->cache = array();
    }
}
