<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Resolver;

use Zfegg\ContentValidation\Opis\Helper;

trait RegisterTrait
{

    /** @var object[][] */
    protected array $services = [];

    /** @var ResolverInterface[]|self[] */
    protected array $ns = [];

    protected string $separator;

    public function __construct(string $separator = '::')
    {
        $this->separator = $separator;
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $name, string $type)
    {
        [$ns, $name] = $this->parseName($name);

        if (! $ns && isset($this->services[$name])) {
            return $this->services[$name][$type] ?? null;
        }

        if (isset($this->ns[$ns])) {
            return $this->ns[$ns]->resolve($name, $type);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function resolveAll(string $name): ?array
    {
        [$ns, $name] = $this->parseName($name);

        if (! $ns && isset($this->services[$name])) {
            return $this->services[$name];
        }

        if (isset($this->ns[$ns])) {
            return $this->ns[$ns]->resolveAll($name);
        }

        return null;
    }

    /**
     * @param object|callable $service
     */
    public function register(string $type, string $name, $service): self
    {
        [$ns, $name] = $this->parseName($name);

        if (isset($this->ns[$ns])) {
            return $this->ns[$ns]->register($type, $name, $service);
        }

        $this->services[$name][$type] = $service;

        return $this;
    }

    public function unregister(string $name, ?string $type = null): bool
    {
        [$ns, $name] = $this->parseName($name);

        if (isset($this->ns[$ns])) {
            return $this->ns[$ns]->unregister($name, $type);
        }

        if (! isset($this->services[$name])) {
            return false;
        }

        if ($type === null) {
            unset($this->services[$name]);

            return true;
        }

        if (isset($this->services[$name][$type])) {
            unset($this->services[$name][$type]);

            return true;
        }

        return false;
    }

    /**
     * @param callable|object $service
     */
    public function registerMultipleTypes(string $name, $service, ?array $types = null): self
    {
        [$ns, $name] = $this->parseName($name);

        if (isset($this->ns[$ns])) {
            return $this->ns[$ns]->registerMultipleTypes($name, $service, $types);
        }

        $types = $types ?? Helper::JSON_ALL_TYPES;

        foreach ($types as $type) {
            $this->services[$name][$type] = $service;
        }

        return $this;
    }

    public function registerNS(string $ns, ResolverInterface $resolver): self
    {
        $this->ns[$ns] = $resolver;

        return $this;
    }

    public function unregisterNS(string $ns): bool
    {
        if (isset($this->ns[$ns])) {
            unset($this->ns[$ns]);

            return true;
        }

        return false;
    }

    protected function parseName(string $name): array
    {
        $name = strtolower($name);

        if (strpos($name, $this->separator) === false) {
            return [null, $name];
        }

        return explode($this->separator, $name, 2);
    }
}
