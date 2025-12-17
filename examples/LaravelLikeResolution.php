<?php

/**
 * LaravelLikeResolution - More realistic Laravel-style container implementation
 *
 * This shows how Laravel's actual container resolution works, including:
 * - Multiple binding types
 * - Aliases
 * - Contextual bindings
 * - Better error handling
 */

class LaravelLikeContainer
{
    private array $bindings = [];
    private array $instances = [];
    private array $aliases = [];
    private array $contextual = [];
    private array $resolving = [];

    // Core binding methods
    public function bind(string $abstract, $concrete = null, bool $shared = false)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
    }

    public function singleton(string $abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, $instance)
    {
        $this->instances[$abstract] = $instance;
    }

    public function alias(string $abstract, string $alias)
    {
        $this->aliases[$alias] = $abstract;
    }

    // Contextual binding methods
    public function when($concrete)
    {
        return new ContextualBindingBuilder($this, $concrete);
    }

    public function addContextualBinding(string $concrete, string $abstract, $implementation)
    {
        $this->contextual[$concrete][$abstract] = $implementation;
    }

    // Resolution methods
    public function make(string $abstract)
    {
        // Handle aliases
        $abstract = $this->getAlias($abstract);

        // Check for circular dependencies
        if (isset($this->resolving[$abstract])) {
            throw new Exception("Circular dependency detected while resolving {$abstract}");
        }

        // Check if we already have an instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $this->resolving[$abstract] = true;

        try {
            $object = $this->resolve($abstract);

            // Store shared instances
            if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']) {
                $this->instances[$abstract] = $object;
            }

            return $object;

        } finally {
            unset($this->resolving[$abstract]);
        }
    }

    public function makeWith(string $abstract, array $parameters)
    {
        // Similar to make() but with specific parameters
        return $this->resolve($abstract, $parameters);
    }

    // Core resolution logic
    private function resolve(string $abstract, array $parameters = [])
    {
        // Get the concrete implementation
        $concrete = $this->getConcrete($abstract);

        // Check if we're in a contextual build
        $buildStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $context = $this->getContextualConcrete($abstract, $buildStack);

        if ($context) {
            $concrete = $context;
        }

        // If we have specific parameters, use them
        if (!empty($parameters)) {
            return $this->buildWithParameters($concrete, $parameters);
        }

        // Build the concrete
        if ($concrete === $abstract) {
            return $this->build($concrete);
        }

        if ($concrete instanceof Closure) {
            return $concrete($this, $this);
        }

        return $this->make($concrete);
    }

    private function build(string $concrete)
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            return $this->notInstantiable($concrete);
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->getDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    private function buildWithParameters(string $concrete, array $parameters)
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            return $this->notInstantiable($concrete);
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = [];
        $constructorParams = $constructor->getParameters();

        foreach ($constructorParams as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $parameters)) {
                $dependencies[] = $parameters[$name];
            } else {
                $dependencies[] = $this->make($param->getType()->getName());
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    private function getDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();

            if ($dependency === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Unresolvable dependency: {$parameter->getName()}");
                }
            } else {
                $dependencies[] = $this->make($dependency->getName());
            }
        }

        return $dependencies;
    }

    private function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    private function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    private function getContextualConcrete(string $abstract, array $buildStack)
    {
        foreach ($buildStack as $trace) {
            if (isset($trace['class']) && isset($this->contextual[$trace['class']][$abstract])) {
                return $this->contextual[$trace['class']][$abstract];
            }
        }

        return null;
    }

    private function notInstantiable(string $concrete)
    {
        if (!interface_exists($concrete)) {
            throw new Exception("Class {$concrete} does not exist");
        }

        throw new Exception("Target [{$concrete}] is not instantiable.");
    }
}

/**
 * Contextual Binding Builder - mimics Laravel's fluent interface
 */
class ContextualBindingBuilder
{
    private $container;
    private $concrete;

    public function __construct(LaravelLikeContainer $container, string $concrete)
    {
        $this->container = $container;
        $this->concrete = $concrete;
    }

    public function needs(string $abstract)
    {
        return new ContextualBindingNeeds($this->container, $this->concrete, $abstract);
    }
}

class ContextualBindingNeeds
{
    private $container;
    private $concrete;
    private $abstract;

    public function __construct(LaravelLikeContainer $container, string $concrete, string $abstract)
    {
        $this->container = $container;
        $this->concrete = $concrete;
        $this->abstract = $abstract;
    }

    public function give($implementation)
    {
        $this->container->addContextualBinding($this->concrete, $this->abstract, $implementation);
        return $this->container;
    }
}

// ===========================================
// DEMONSTRATION
// ===========================================

// Test interfaces and classes
interface CacheInterface
{
    public function get($key);
    public function set($key, $value);
}

interface PaymentInterface
{
    public function charge($amount);
}

class RedisCache implements CacheInterface
{
    public function get($key) { return "Redis: {$key}"; }
    public function set($key, $value) { echo "Redis SET {$key} = {$value}\n"; }
}

class ArrayCache implements CacheInterface
{
    private $data = [];
    public function get($key) { return $this->data[$key] ?? null; }
    public function set($key, $value) { $this->data[$key] = $value; }
}

class StripePayment implements PaymentInterface
{
    private $cache;
    public function __construct(CacheInterface $cache) { $this->cache = $cache; }
    public function charge($amount) { return "Stripe charged \${$amount}"; }
}

class PayPalPayment implements PaymentInterface
{
    private $cache;
    public function __construct(CacheInterface $cache) { $this->cache = $cache; }
    public function charge($amount) { return "PayPal charged \${$amount}"; }
}

class ProductionService
{
    private $payment;
    private $apiKey;
    public function __construct(PaymentInterface $payment, $apiKey)
    {
        $this->payment = $payment;
        $this->apiKey = $apiKey;
    }
    public function process($amount) { return $this->payment->charge($amount); }
}

class DevelopmentService
{
    private $payment;
    private $apiKey;
    public function __construct(PaymentInterface $payment, $apiKey)
    {
        $this->payment = $payment;
        $this->apiKey = $apiKey;
    }
    public function process($amount) { return $this->payment->charge($amount); }
}

// Demonstration
echo "=== Laravel-like Container Demonstration ===\n\n";

$container = new LaravelLikeContainer();

echo "1. Basic binding with interface to implementation:\n";
$container->bind(CacheInterface::class, RedisCache::class);
$container->bind(PaymentInterface::class, StripePayment::class);

$cache = $container->make(CacheInterface::class);
echo "Cache type: " . get_class($cache) . "\n";

echo "\n2. Singleton binding:\n";
$container->singleton('app.cache', RedisCache::class);

$cache1 = $container->make('app.cache');
$cache2 = $container->make('app.cache');
echo "Same instance? " . ($cache1 === $cache2 ? 'YES' : 'NO') . "\n";

echo "\n3. Contextual binding:\n";
$container->when(ProductionService::class)
          ->needs(CacheInterface::class)
          ->give(RedisCache::class);

$container->when(DevelopmentService::class)
          ->needs(CacheInterface::class)
          ->give(ArrayCache::class);

$container->when(ProductionService::class)
          ->needs('$apiKey')
          ->give('prod-key-123');

$container->when(DevelopmentService::class)
          ->needs('$apiKey')
          ->give('dev-key-456');

$prodService = $container->makeWith(ProductionService::class, []);
$devService = $container->makeWith(DevelopmentService::class, []);

echo "Production service cache type: " . get_class($prodService->payment) . "\n";
echo "Development service cache type: " . get_class($devService->payment) . "\n";

echo "\n4. Aliases:\n";
$container->alias(CacheInterface::class, 'cache');
$aliasedCache = $container->make('cache');
echo "Aliased cache type: " . get_class($aliasedCache) . "\n";

echo "\n5. makeWith with parameters:\n";
$customService = $container->makeWith(ProductionService::class, [
    'apiKey' => 'custom-key-789'
]);
echo "Custom service created with API key: " . $customService->apiKey . "\n";

echo "\n=== End of Demonstration ===\n";