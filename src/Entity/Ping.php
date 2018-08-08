<?php

namespace Entity;

use DB\Entity;

class Ping extends Entity {

    /**
     * @var string|null
     */
    protected $url = null;

    /**
     * Record creation time in ms from epoch
     *
     * @var integer|null
     */
    protected $iat = null;

    /**
     * Timestamp in ms from epoch of when this record expires
     *
     * @var integer|null
     */
    protected $exp = null;

    /**
     * RTT in ms
     * current request
     *
     * @var integer|null
     */
    protected $rtt = null;

    /**
     * @inheritdoc
     */
    public function __construct($data = null) {
        parent::__construct($data);
        if(is_null($this->iat)) $this->iat = round(microtime(true)*1000);
        if(is_null($this->exp)) $this->exp = round(microtime(true)*1000) + (3600*1000);
    }

    /**
     * @return null|string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @param null|string $url
     *
     * @return Ping
     */
    public function setUrl($url): Ping {
        $this->url = $url;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getIat() {
        return $this->iat;
    }

    /**
     * @param int|null $iat
     *
     * @return Ping
     */
    public function setIat($iat): Ping {
        $this->iat = $iat;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getExp() {
        return $this->exp;
    }

    /**
     * @param int|null $exp
     *
     * @return Ping
     */
    public function setExp($exp): Ping {
        $this->exp = $exp;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRtt() {
        return $this->rtt;
    }

    /**
     * @param int|null $rtt
     *
     * @return Ping
     */
    public function setRtt($rtt): Ping {
        $this->rtt = $rtt;
        return $this;
    }
}
