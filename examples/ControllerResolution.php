<?php

/**
 * ControllerResolution - Shows how Laravel handles controller injection
 *
 * This demonstrates why method injection works in controllers but not in regular classes.
 * Laravel has special handling for controllers that regular classes don't get.
 */

class LaravelLikeContainer
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function instance(string $abstract, $instance)
    {
        $this->instances[$abstract] = $instance;
    }

    public function make(string $class)
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        if (isset($this->bindings[$class])) {
            $concrete = $this->bindings[$class];
            if ($concrete instanceof Closure) {
                return $concrete($this);
            }
            return $this->make($concrete);
        }

        // Simple auto-resolution
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $class();
        }

        $dependencies = [];
        $parameters = $constructor->getParameters();

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
            } else {
                throw new Exception("Cannot resolve: {$parameter->getName()}");
            }
        }

        return new $class(...$dependencies);
    }

    /**
     * Special method injection for controllers (like Laravel does)
     */
    public function callController($controller, $method, $parameters = [])
    {
        // Create controller instance
        $controllerInstance = $this->make($controller);

        // Get method reflection
        $methodReflection = new ReflectionMethod($controllerInstance, $method);

        // Resolve method parameters (this is the magic!)
        $resolvedParameters = $this->resolveMethodParameters($methodReflection, $parameters);

        // Call the method with resolved parameters
        return $methodReflection->invokeArgs($controllerInstance, $resolvedParameters);
    }

    private function resolveMethodParameters(ReflectionMethod $method, array $provided = []): array
    {
        $parameters = $method->getParameters();
        $resolved = [];

        foreach ($parameters as $param) {
            $paramName = $param->getName();

            // Check if parameter is provided
            if (isset($provided[$paramName])) {
                $resolved[] = $provided[$paramName];
                continue;
            }

            // Check if parameter has type hint
            $type = $param->getType();

            if ($type && !$type->isBuiltin()) {
                // Resolve from container
                $resolved[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                // Use default value
                $resolved[] = $param->getDefaultValue();
            } else {
                throw new Exception("Cannot resolve parameter: {$paramName}");
            }
        }

        return $resolved;
    }
}

// ===========================================
// EXAMPLE SERVICES
// ===========================================

class Request
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function input($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function all()
    {
        return $this->data;
    }
}

class UserRepository
{
    public function create($data)
    {
        echo "Creating user with data: " . json_encode($data) . "\n";
        return ['id' => 123, 'name' => $data['name']];
    }

    public function find($id)
    {
        echo "Finding user: {$id}\n";
        return ['id' => $id, 'name' => 'John Doe'];
    }
}

class EmailService
{
    public function sendWelcomeEmail($user)
    {
        echo "Sending welcome email to: {$user['name']} ({$user['id']})\n";
        return true;
    }
}

class ValidationService
{
    public function validate($data, $rules)
    {
        echo "Validating data with rules: " . json_encode($rules) . "\n";
        if (empty($data['name'])) {
            throw new Exception("Name is required");
        }
        return $data;
    }
}

// ===========================================
// CONTROLLER EXAMPLES
// ===========================================

class UserController
{
    private $userRepository;
    private $emailService;

    // Constructor injection
    public function __construct(UserRepository $userRepository, EmailService $emailService)
    {
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
        echo "UserController: Constructor injection completed\n";
    }

    // Method injection - this works in Laravel!
    public function store(Request $request, ValidationService $validator)
    {
        echo "UserController: Method injection - Request and ValidationService\n";

        $data = $request->all();
        $validated = $validator->validate($data, [
            'name' => 'required'
        ]);

        $user = $this->userRepository->create($validated);
        $this->emailService->sendWelcomeEmail($user);

        return $user;
    }

    // Multiple method parameters
    public function update($id, Request $request, UserRepository $repository)
    {
        echo "UserController: Multiple parameters\n";
        echo "  - ID: {$id}\n";
        echo "  - Request data: " . json_encode($request->all()) . "\n";

        return $repository->find($id);
    }
}

class PostController
{
    // No constructor, just method injection
    public function index(Request $request)
    {
        echo "PostController: Only method injection\n";
        echo "  - Request data: " . json_encode($request->all()) . "\n";
        return ['posts' => ['Post 1', 'Post 2']];
    }

    // Method with primitive + object parameters
    public function show($id, Request $request, UserRepository $users)
    {
        echo "PostController: Mixed parameters\n";
        echo "  - Post ID: {$id}\n";
        echo "  - Request: " . get_class($request) . "\n";
        echo "  - UserRepository: " . get_class($users) . "\n";

        return ['post' => "Post {$id}"];
    }
}

// ===========================================
// CUSTOM CLASS (NOT CONTROLLER)
// ===========================================

class CustomService
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        echo "CustomService: Constructor injection works\n";
    }

    // This would NOT work in real Laravel for regular classes
    public function processData(Request $request, ValidationService $validator)
    {
        echo "CustomService: This method injection would fail in real Laravel!\n";
        return "processed";
    }

    // This is how it should be done in custom classes
    public function processWithConstructor()
    {
        echo "CustomService: Using dependencies from constructor\n";
        return "processed correctly";
    }
}

// ===========================================
// DEMONSTRATION
// ===========================================

echo "=== Controller Method Injection Demo ===\n\n";

$container = new LaravelLikeContainer();

// Bind some services
$container->instance(Request::class, new Request(['name' => 'John Doe']));
$container->bind(UserRepository::class, UserRepository::class);
$container->bind(EmailService::class, EmailService::class);
$container->bind(ValidationService::class, ValidationService::class);

echo "1. Controller with constructor + method injection:\n";
$result = $container->callController(UserController::class, 'store');
echo "Result: " . json_encode($result) . "\n\n";

echo "2. Controller with multiple method parameters:\n";
$result = $container->callController(UserController::class, 'update', [
    'id' => 123
]);
echo "Result: " . json_encode($result) . "\n\n";

echo "3. Controller with no constructor:\n";
$result = $container->callController(PostController::class, 'index');
echo "Result: " . json_encode($result) . "\n\n";

echo "4. Controller with mixed parameters:\n";
$result = $container->callController(PostController::class, 'show', [
    'id' => 456
]);
echo "Result: " . json_encode($result) . "\n\n";

echo "5. Custom class demonstration:\n";
echo "   - Constructor injection works (like always)\n";
echo "   - Method injection would FAIL in real Laravel\n";

$customService = $container->make(CustomService::class);
$result = $customService->processWithConstructor();
echo "Custom service result: {$result}\n\n";

echo "=== Why This Special Handling Exists ===\n\n";
echo "Laravel's routing system specifically:\n";
echo "1. Creates the controller instance (constructor injection)\n";
echo "2. Analyzes the method parameters using Reflection\n";
echo "3. Resolves each parameter from the container\n";
echo "4. Calls the method with resolved parameters\n";
echo "\n";
echo "This happens in Laravel's Router/ControllerDispatcher,\n";
echo "NOT in the basic container resolution.\n\n";

echo "Regular classes don't get this treatment because:\n";
echo "- Laravel doesn't automatically call your methods\n";
echo "- No automatic parameter resolution for arbitrary methods\n";
echo "- Only constructors get auto-injection for regular classes\n\n";

echo "=== Key Takeaway ===\n";
echo "Method injection works in Controllers because Laravel's\n";
echo "routing system has special logic to resolve method parameters.\n";
echo "Your custom classes only get constructor injection.\n\n";

echo "=== End Demo ===\n";