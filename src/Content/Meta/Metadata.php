<?php

namespace ZeroGravity\Cms\Content\Meta;

use ArrayAccess;

/**
 * This class represents metadata for a content file.
 */
class Metadata implements ArrayAccess
{
    /**
     * @var array
     */
    private $values;

    /**
     * Metadata constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->values;
    }

    /**
     * @param array $values
     *
     * @return array
     */
    public function setAll(array $values): array
    {
        return $this->values = $values;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed|null
     */
    public function getValue(string $key, $default = null)
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        return $default;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return self
     */
    public function setValue(string $key, $value): self
    {
        $this->values[$key] = $value;

        return $this;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->values);
    }

    public function offsetGet($offset)
    {
        return $this->getValue($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setValue($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->values[$offset]);
    }
}
