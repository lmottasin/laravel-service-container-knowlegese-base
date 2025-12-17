<?php

/**
 * SimpleContainer - A simplified version of Laravel's Service Container
 *
 * This demonstrates the basic concept of a dependency injection container.
 * It's much simpler than Laravel's actual container but shows the core ideas.
 */

class SimpleContainer
{
    private array $bindings = [];
    private array $instances = [];
    private array $resolving = []; // Track for circular dependencies

    /**
     * Bind an abstract to a concrete implementation
     *
     * @param string $abstract An abstract identifier (interface name or alias)
     * @param mixed $concrete The actual implementation (class name or function)
     */
    public function bind(string $abstract, $concrete = null)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        // Store the recipe for creating this object later
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Bind a singleton (shared instance)
     * Singleton = create only once, reuse the same object every time
     */
    public function singleton(string $abstract, $concrete = null)
    {
        // First, do normal binding
        $this->bind($abstract, $concrete);

        // Mark as singleton (we'll handle this in make())
        // Create placeholder so container knows this should be shared
        if (!isset($this->instances[$abstract])) {
            $this->instances[$abstract] = null; // Placeholder for singleton
        }
    }

    /**
     * Resolve an instance from the container
     * Resolve = create and return the requested object with all its dependencies
     */
    public function make(string $abstract)
    {
        // Check for circular dependencies (A needs B, B needs A = infinite loop)
        if (isset($this->resolving[$abstract])) {
            throw new Exception("Circular dependency detected while resolving {$abstract}");
        }

        // Mark that we're currently building this to prevent circular dependencies
        $this->resolving[$abstract] = true;

        try {
            // 1. Check if we already have a singleton instance (shared object)
            if (array_key_exists($abstract, $this->instances) && $this->instances[$abstract] !== null) {
                return $this->instances[$abstract];
            }

            // 2. Check if we have a recipe (binding) for this object
            if (isset($this->bindings[$abstract])) {
                $concrete = $this->bindings[$abstract];

                // If it's a closure (anonymous function), execute it to create the object
                if ($concrete instanceof Closure) {
                    $instance = $concrete($this);
                } else {
                    // If it's a class name, resolve it (create it with dependencies)
                    $instance = $this->resolve($concrete);
                }
            } else {
                // 3. Try to resolve the class directly (auto-resolution)
                // Auto-resolution = automatically figure out dependencies using PHP reflection
                $instance = $this->resolve($abstract);
            }

            // 4. Store as singleton if needed (save for reuse)
            if (array_key_exists($abstract, $this->instances)) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;

        } finally {
            // Clean up: we're done building this object
            unset($this->resolving[$abstract]);
        }
    }

    /**
     * Resolve a class using reflection
     * Reflection = PHP's ability to examine code structure at runtime
     */
    private function resolve(string $class)
    {
        // Use reflection to see what this class needs in its constructor
        $reflection = new ReflectionClass($class);

        // Check if this class can be created (not an interface or abstract class)
        if (!$reflection->isInstantiable()) {
            throw new Exception("Class {$class} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            // No constructor = no dependencies needed
            return new $class();
        }

        // Get the list of parameters the constructor requires
        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $this->resolveDependency($parameter);
            $dependencies[] = $dependency;
        }

        // Create the instance, passing in all resolved dependencies
        return new $class(...$dependencies);
    }

    /**
     * Resolve a single dependency
     */
    private function resolveDependency(ReflectionParameter $parameter)
    {
        // Get the type hint (what class this parameter expects)
        $type = $parameter->getType();

        if ($type === null) {
            // No type hint - can't figure out what to create
            throw new Exception("Unresolvable dependency: {$parameter->name} has no type hint");
        }

        if ($type instanceof ReflectionUnionType) {
            throw new Exception("Union types are not supported: {$parameter->name}");
        }

        $dependencyName = $type->getName();

        // Recursively resolve the dependency (this builds the entire dependency tree)
        return $this->make($dependencyName);
    }
}

// ===========================================
// DEMONSTRATION
// ===========================================

// Example classes for demonstration
class Logger
{
    public function log($message)
    {
        echo "LOG: {$message}\n";
    }
}

class Database
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->logger->log("Database connection established");
    }

    public function query($sql)
    {
        $this->logger->log("Executing query: {$sql}");
        return "Query result for: {$sql}";
    }
}

class UserService
{
    private $database;
    private $logger;

    public function __construct(Database $database, Logger $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
        $this->logger->log("UserService initialized");
    }

    public function getUser($id)
    {
        $this->logger->log("Getting user {$id}");
        return $this->database->query("SELECT * FROM users WHERE id = {$id}");
    }
}

// Demonstration
echo "=== Simple Container Demonstration ===\n\n";

$container = new SimpleContainer();

echo "1. Auto-resolution (no bindings needed):\n";
// Auto-resolution = automatically figure out dependencies using PHP reflection
$userService = $container->make(UserService::class);
echo "Result: " . $userService->getUser(1) . "\n\n";

echo "2. With explicit binding:\n";
// Binding = telling the container how to create something
$container->bind('custom-db', function($container) {
    echo "Creating custom database instance via closure\n";
    // Closure = anonymous function that creates the object
    return new Database($container->make(Logger::class));
});

$customDb = $container->make('custom-db');
echo "Result: " . $customDb->query("SELECT * FROM posts") . "\n\n";

echo "3. Singleton binding:\n";
// Singleton = create only once, reuse the same object every time
$container->singleton('shared-logger', Logger::class);

$logger1 = $container->make('shared-logger');
$logger2 = $container->make('shared-logger');

// spl_object_hash() gives us a unique ID for each object instance
echo "Logger 1 identity: " . spl_object_hash($logger1) . "\n";
echo "Logger 2 identity: " . spl_object_hash($logger2) . "\n";
echo "Same instance? " . ($logger1 === $logger2 ? 'YES' : 'NO') . "\n\n";

echo "4. Circular dependency detection:\n";
// Circular dependency = A needs B, B needs A = infinite loop
try {
    // This would cause a circular dependency in the container
    $container->bind(A::class, function($container) {
        return new A($container->make(B::class));
    });
    $container->bind(B::class, function($container) {
        return new B($container->make(A::class)); // Circular!
    });

    $container->make(A::class);
} catch (Exception $e) {
    echo "Caught circular dependency: " . $e->getMessage() . "\n";
}

echo "\n=== End of Demonstration ===\n";

// Helper classes for circular dependency demo
class A { public function __construct(B $b) {} }
class B { public function __construct(A $a) {} }