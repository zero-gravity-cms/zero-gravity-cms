<?php

namespace ZeroGravity\Cms\Content\Meta;

use ArrayAccess;

/**
 * This class represents metadata for a content file.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Metadata implements ArrayAccess
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(private array $values)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->values;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function setAll(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        return $default;
    }

    public function setValue(string $key, mixed $value): self
    {
        $this->values[$key] = $value;

        return $this;
    }

    /**
     * @param string $offset
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->values);
    }

    /**
     * @param string $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->getValue($offset);
    }

    /**
     * @param string $offset
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->setValue($offset, $value);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->values[$offset]);
    }
}
