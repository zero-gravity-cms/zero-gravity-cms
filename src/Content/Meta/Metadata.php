<?php

namespace ZeroGravity\Cms\Content\Meta;

use ArrayAccess;

/**
 * This class represents metadata for a content file.
 */
final class Metadata implements ArrayAccess
{
    private array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function getAll(): array
    {
        return $this->values;
    }

    public function setAll(array $values): array
    {
        return $this->values = $values;
    }

    /**
     * @param mixed|null $default
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

    public function setValue(string $key, $value): self
    {
        $this->values[$key] = $value;

        return $this;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->values);
    }

    public function offsetGet($offset)
    {
        return $this->getValue($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->setValue($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->values[$offset]);
    }
}
