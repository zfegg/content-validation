<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation\Entity;

/**
 * Foo
 *
 * @Table
 * @Entity
 *
 */
class Foo
{
    /**
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     *
     * @Column(name="`key`", type="string", length=255)
     */
    private string $key;

    /**
     * @var mixed
     * @Column(name="value", type="json", length=255)
     */
    private $value;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }
}
