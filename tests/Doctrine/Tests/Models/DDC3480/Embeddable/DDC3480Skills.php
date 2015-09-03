<?php

namespace Doctrine\Tests\Models\DDC3597\Embeddable;

/**
 * Description of DDC3480Skills
 *
 * @Embeddable
 */
class DDC3480Skills {

    /**
     * @var int
     * @Column(type="integer", name="width")
     */
    private $skills;

    /**
     * @var int
     * @Column(type="integer", name="height")
     */
    private $height;

    function __construct($width = 0, $height = 0) {
        $this->setWidth($width);
        $this->setHeight($height);
    }

    /**
     * @return int
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width) {
        $this->width = (int)$width;
    }

    /**
     * @return int
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight($height) {
        $this->height = (int)$height;
    }
}