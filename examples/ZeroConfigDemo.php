<?php

/**
 * ZeroConfigDemo - Demonstrates Laravel's zero-configuration resolution
 *
 * This shows how Laravel can automatically resolve classes without any manual bindings.
 * The magic happens through PHP's Reflection API.
 */

class ZeroConfigContainer
{
    private array $instances = [];
    private array $resolving = [];

    public function make(string $class)
    {
        // Check for circular dependencies
        if (isset($this->resolving[$class])) {
            throw new Exception("Circular dependency detected: {$class}");
        }

        $this->resolving[$class] = true;

        try {
            return $this->build($class);
        } finally {
            unset($this->resolving[$class]);
        }
    }

    private function build(string $class)
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new Exception("Class {$class} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            // No constructor = no dependencies
            echo "Creating {$class} (no dependencies)\n";
            return new $class();
        }

        echo "Analyzing {$class} constructor dependencies...\n";
        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependencyClass = $this->getParameterClass($parameter);
            if ($dependencyClass) {
                echo "  - Found dependency: {$dependencyClass}\n";
                $dependencies[] = $this->make($dependencyClass);
            } else {
                throw new Exception("Cannot resolve parameter: {$parameter->getName()}");
            }
        }

        echo "Creating {$class} with " . count($dependencies) . " dependencies\n";
        return new $class(...$dependencies);
    }

    private function getParameterClass(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if ($type === null) {
            return null;
        }

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName();
        }

        return null;
    }
}

// ===========================================
// EXAMPLE CLASSES
// ===========================================

class Logger
{
    public function __construct()
    {
        echo "Logger: Initialized\n";
    }

    public function log($message)
    {
        echo "[LOG] {$message}\n";
    }
}

class Database
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        echo "Database: Initialized with logger\n";
        $this->logger->log("Database connection established");
    }

    public function query($sql)
    {
        $this->logger->log("Executing: {$sql}");
        return "Results for: {$sql}";
    }
}

class Cache
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        echo "Cache: Initialized with logger\n";
        $this->logger->log("Cache system ready");
    }

    public function get($key)
    {
        $this->logger->log("Cache GET: {$key}");
        return "cached_value_for_{$key}";
    }

    public function set($key, $value)
    {
        $this->logger->log("Cache SET: {$key} = {$value}");
    }
}

class UserService
{
    private $database;
    private $cache;
    private $logger;

    public function __construct(Database $database, Cache $cache, Logger $logger)
    {
        $this->database = $database;
        $this->cache = $cache;
        $this->logger = $logger;
        echo "UserService: Initialized with database, cache, and logger\n";
        $this->logger->log("UserService ready");
    }

    public function getUser($id)
    {
        $this->logger->log("Getting user {$id}");

        // Try cache first
        $cacheKey = "user_{$id}";
        $cached = $this->cache->get($cacheKey);

        if ($cached) {
            return $cached;
        }

        // Query database
        $result = $this->database->query("SELECT * FROM users WHERE id = {$id}");
        $this->cache->set($cacheKey, $result);

        return $result;
    }
}

class UserRepository
{
    private $database;
    private $logger;

    public function __construct(Database $database, Logger $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
        echo "UserRepository: Initialized\n";
    }

    public function findAll()
    {
        $this->logger->log("Finding all users");
        return $this->database->query("SELECT * FROM users");
    }
}

class NotificationService
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        echo "NotificationService: Initialized\n";
    }

    public function sendEmail($to, $message)
    {
        $this->logger->log("Sending email to {$to}: {$message}");
        return "Email sent to {$to}";
    }
}

// ===========================================
// DEMONSTRATION
// ===========================================

echo "=== Zero-Configuration Resolution Demo ===\n\n";

$container = new ZeroConfigContainer();

echo "1. Simple class with no dependencies:\n";
$logger = $container->make(Logger::class);
echo "\n";

echo "2. Class with one dependency:\n";
$database = $container->make(Database::class);
echo "\n";

echo "3. Class with complex dependency graph:\n";
echo "   UserService needs: Database + Cache + Logger\n";
echo "   Database needs: Logger\n";
echo "   Cache needs: Logger\n";
echo "\n";

$userService = $container->make(UserService::class);
echo "\n";

echo "4. Testing the resolved service:\n";
$result = $userService->getUser(123);
echo "Result: {$result}\n\n";

echo "5. Another class with overlapping dependencies:\n";
echo "   UserRepository needs: Database + Logger\n";
echo "   (Database and Logger already exist in our mental model)\n";
$userRepository = $container->make(UserRepository::class);
echo "\n";

echo "6. Circular dependency detection:\n";
try {
    // Create classes that depend on each other
    class A {
        public function __construct(B $b) {}
    }
    class B {
        public function __construct(A $a) {}
    }

    $container->make(A::class);
} catch (Exception $e) {
    echo "Caught error: " . $e->getMessage() . "\n";
}

echo "\n7. What happens with primitives:\n";
try {
    class ConfigService {
        public function __construct($apiKey, Logger $logger) {}
    }

    $container->make(ConfigService::class);
} catch (Exception $e) {
    echo "Caught error: " . $e->getMessage() . "\n";
}

echo "\n8. What happens with interfaces:\n";
try {
    interface CacheInterface {}
    class CacheService implements CacheInterface {}

    class ServiceWithInterface {
        public function __construct(CacheInterface $cache) {}
    }

    $container->make(ServiceWithInterface::class);
} catch (Exception $e) {
    echo "Caught error: " . $e->getMessage() . "\n";
}

echo "\n=== Key Takeaways ===\n";
echo "- Laravel uses Reflection to inspect constructors\n";
echo "- Dependencies are resolved recursively\n";
echo "- No manual bindings needed for concrete classes\n";
echo "- Fails with primitives (no type hints)\n";
echo "- Fails with interfaces (can't instantiate)\n";
echo "- Detects circular dependencies\n";
echo "- Each dependency is created once per resolution tree\n";

echo "\n=== End Demo ===\n";