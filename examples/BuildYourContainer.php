<?php

/**
 * 🚀 Build Your Own Service Container - Hands-on Exercise
 *
 * This file provides step-by-step instructions to build a service container
 * from scratch. Follow along to understand exactly how Laravel's container works!
 *
 * Run this file after you complete the exercises below to test your implementation.
 */

echo "=== 🚀 Build Your Own Service Container ===\n\n";

echo "📚 INSTRUCTIONS:\n";
echo "1. Read the step-by-step guide below\n";
echo "2. Implement each part of the SimpleContainer class\n";
echo "3. Test your implementation by uncommenting the test code\n";
echo "4. Run: php examples/BuildYourContainer.php\n\n";

echo "💡 TIP: Don't copy-paste! Type it yourself to learn better!\n\n";

// ===========================================
// STEP-BY-STEP BUILDING GUIDE
// ===========================================

echo "
🔧 STEP 1: CREATE THE BASIC CONTAINER STRUCTURE
===============================================

First, create a SimpleContainer class with these properties:

class SimpleContainer {
    private array \$bindings = [];      // Stores our recipes for creating objects
    private array \$instances = [];      // Stores shared (singleton) objects
    private array \$resolving = [];      // Tracks what we're currently building (prevents circular dependencies)

    public function bind(\$abstract, \$concrete) {
        // TODO: Store the binding recipe
        // \$this->bindings[\$abstract] = \$concrete;
    }

    public function singleton(\$abstract, \$concrete) {
        // TODO: Store as singleton (shared instance)
        // First bind it normally, then mark as singleton
    }

    public function make(\$abstract) {
        // TODO: Create and return the requested object
        // This is the main method that does all the magic!
    }
}

🎯 STEP 2: IMPLEMENT THE BIND() METHOD
==========================================

public function bind(\$abstract, \$concrete = null) {
    if (\$concrete === null) {
        \$concrete = \$abstract;  // If no concrete given, use the abstract name
    }

    \$this->bindings[\$abstract] = \$concrete;
}

🎯 STEP 3: IMPLEMENT THE SINGLETON() METHOD
==============================================

public function singleton(\$abstract, \$concrete = null) {
    \$this->bind(\$abstract, \$concrete);

    // Mark as singleton by setting a placeholder
    if (!isset(\$this->instances[\$abstract])) {
        \$this->instances[\$abstract] = null;
    }
}

🎯 STEP 4: IMPLEMENT THE MAIN MAKE() METHOD
==============================================

public function make(\$abstract) {
    // 1. Check for circular dependencies (A needs B, B needs A)
    if (isset(\$this->resolving[\$abstract])) {
        throw new Exception(\"Circular dependency detected while resolving {\$abstract}\");
    }

    // 2. Mark that we're currently building this
    \$this->resolving[\$abstract] = true;

    try {
        // 3. Check if we already have a singleton instance
        if (array_key_exists(\$abstract, \$this->instances) && \$this->instances[\$abstract] !== null) {
            return \$this->instances[\$abstract];
        }

        // 4. Check if we have a binding for this
        if (isset(\$this->bindings[\$abstract])) {
            \$concrete = \$this->bindings[\$abstract];

            if (\$concrete instanceof Closure) {
                // If it's a closure, execute it
                \$instance = \$concrete(\$this);
            } else {
                // If it's a class name, resolve it
                \$instance = \$this->resolve(\$concrete);
            }
        } else {
            // 5. Try to auto-resolve the class
            \$instance = \$this->resolve(\$abstract);
        }

        // 6. Store as singleton if needed
        if (array_key_exists(\$abstract, \$this->instances)) {
            \$this->instances[\$abstract] = \$instance;
        }

        return \$instance;

    } finally {
        // 7. Clean up - we're done building this
        unset(\$this->resolving[\$abstract]);
    }
}

🎯 STEP 5: IMPLEMENT THE RESOLVE() METHOD (THE MAGIC!)
========================================================

private function resolve(\$class) {
    // Use reflection to inspect the class
    \$reflection = new ReflectionClass(\$class);

    if (!\$reflection->isInstantiable()) {
        throw new Exception(\"Class {\$class} is not instantiable\");
    }

    \$constructor = \$reflection->getConstructor();

    if (!\$constructor) {
        // No constructor = no dependencies needed
        return new \$class();
    }

    // Get constructor parameters (what this class needs)
    \$parameters = \$constructor->getParameters();
    \$dependencies = [];

    foreach (\$parameters as \$parameter) {
        // For each parameter, get the type hint
        \$type = \$parameter->getType();

        if (\$type === null) {
            throw new Exception(\"Cannot resolve parameter: {\$parameter->getName()} has no type hint\");
        }

        // Recursively resolve each dependency
        \$dependencyName = \$type->getName();
        \$dependencies[] = \$this->make(\$dependencyName);
    }

    // Create the instance with all resolved dependencies
    return new \$class(...\$dependencies);
}

🎯 STEP 6: TEST YOUR IMPLEMENTATION!
=======================================

After implementing all methods above, test your container:

class Logger {
    public function log(\$msg) { echo \"LOG: {\$msg}\\n\"; }
}

class Database {
    public function __construct(Logger \$logger) {
        \$this->logger = \$logger;
        \$this->logger->log(\"Database connected\");
    }
}

class UserService {
    public function __construct(Database \$db, Logger \$logger) {
        \$this->db = \$db;
        \$this->logger = \$logger;
        \$this->logger->log(\"UserService ready\");
    }
}

\$container = new SimpleContainer();

// Test auto-resolution
\$service = \$container->make(UserService::class);

// Test singleton
\$container->singleton('shared-logger', Logger::class);
\$logger1 = \$container->make('shared-logger');
\$logger2 = \$container->make('shared-logger');
echo \"Same instance? \" . (\$logger1 === \$logger2 ? 'YES' : 'NO') . \"\\n\";

";

// ===========================================
// EXAMPLE CLASSES FOR TESTING
// ===========================================

class Logger {
    public function log($msg) {
        echo "LOG: {$msg}\n";
    }
}

class Database {
    private $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->logger->log("Database connected");
    }

    public function query($sql) {
        $this->logger->log("Query: {$sql}");
        return "Results for: {$sql}";
    }
}

class UserService {
    private $db;
    private $logger;

    public function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
        $this->logger->log("UserService ready");
    }

    public function getUser($id) {
        $this->logger->log("Getting user {$id}");
        return $this->db->query("SELECT * FROM users WHERE id = {$id}");
    }
}

// ===========================================
// EMPTY CONTAINER CLASS FOR YOU TO IMPLEMENT
// ===========================================

class SimpleContainer
{
    private array $bindings = [];      // Stores our recipes for creating objects
    private array $instances = [];      // Stores shared (singleton) objects
    private array $resolving = [];      // Tracks what we're currently building

    // TODO: Implement this method based on STEP 3
    public function bind($abstract, $concrete = null)
    {
        // YOUR CODE HERE
    }

    // TODO: Implement this method based on STEP 4
    public function singleton($abstract, $concrete = null)
    {
        // YOUR CODE HERE
    }

    // TODO: Implement this method based on STEP 5
    public function make($abstract)
    {
        // YOUR CODE HERE
    }

    // TODO: Implement this method based on STEP 6
    private function resolve($class)
    {
        // YOUR CODE HERE
    }
}

// ===========================================
// TEST CODE (UNCOMMENT WHEN READY)
// ===========================================

echo "\n" . str_repeat("=", 50) . "\n";
echo "🧪 TESTING YOUR IMPLEMENTATION\n";
echo str_repeat("=", 50) . "\n\n";

// TODO: Uncomment this code after implementing the container
/*
echo "1. Testing auto-resolution:\n";
$container = new SimpleContainer();
$service = $container->make(UserService::class);
echo "✅ Auto-resolution working!\n\n";

echo "2. Testing singleton:\n";
$container->singleton('shared-logger', Logger::class);
$logger1 = $container->make('shared-logger');
$logger2 = $container->make('shared-logger');
echo "Same instance? " . ($logger1 === $logger2 ? 'YES' : 'NO') . "\n";
echo "✅ Singleton working!\n\n";

echo "3. Testing custom binding:\n";
$container->bind('custom-db', function($container) {
    return new Database($container->make(Logger::class));
});
$db = $container->make('custom-db');
echo "✅ Custom binding working!\n\n";

echo "🎉 All tests passed! Your container is working!\n";
*/

echo "\n💡 NEXT STEPS:\n";
echo "1. Implement all the TODO methods above\n";
echo "2. Uncomment the test code\n";
echo "3. Run: php examples/BuildYourContainer.php\n";
echo "4. Try adding more features (contextual bindings, primitive injection, etc.)\n\n";

echo "📚 Once you're done, check out SimpleContainer.php to see a working implementation!\n";