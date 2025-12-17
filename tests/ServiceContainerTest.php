<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests for Service Container Implementation
 *
 * This test validates that users have correctly implemented a working
 * service container that matches Laravel's basic functionality.
 *
 * When users submit a PR, GitHub Actions will run these tests to verify
 * their implementation actually works!
 */
class ServiceContainerTest extends TestCase
{
    private SimpleContainer $container;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../examples/BuildYourContainer.php';
        $this->container = new SimpleContainer();
    }

    /** @test */
    public function it_can_bind_and_resolve_simple_classes()
    {
        // Test basic binding
        $this->container->bind('test-class', TestService::class);

        $service = $this->container->make('test-class');

        $this->assertInstanceOf(TestService::class, $service);
    }

    /** @test */
    public function it_can_auto_resolve_dependencies()
    {
        // Test auto-resolution without explicit binding
        $service = $this->container->make(ServiceWithDependency::class);

        $this->assertInstanceOf(ServiceWithDependency::class, $service);
        $this->assertInstanceOf(TestService::class, $service->dependency);
    }

    /** @test */
    public function it_can_resolve_nested_dependencies()
    {
        // Test complex dependency graphs
        $service = $this->container->make(ComplexService::class);

        $this->assertInstanceOf(ComplexService::class, $service);
        $this->assertInstanceOf(ServiceWithDependency::class, $service->dependency);
        $this->assertInstanceOf(TestService::class, $service->dependency->dependency);
    }

    /** @test */
    public function it_can_create_singletons()
    {
        // Test singleton functionality
        $this->container->singleton('shared-service', TestService::class);

        $instance1 = $this->container->make('shared-service');
        $instance2 = $this->container->make('shared-service');

        $this->assertSame($instance1, $instance2, 'Singleton should return the same instance');
    }

    /** @test */
    public function it_can_execute_closure_bindings()
    {
        // Test closure-based bindings
        $this->container->bind('custom-service', function($container) {
            return new CustomTestService('custom-value');
        });

        $service = $this->container->make('custom-service');

        $this->assertInstanceOf(CustomTestService::class, $service);
        $this->assertEquals('custom-value', $service->value);
    }

    /** @test */
    public function it_detects_circular_dependencies()
    {
        // Test circular dependency detection
        $this->container->bind(CircularA::class, function($container) {
            return new CircularA($container->make(CircularB::class));
        });

        $this->container->bind(CircularB::class, function($container) {
            return new CircularB($container->make(CircularA::class));
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/circular/i');

        $this->container->make(CircularA::class);
    }

    /** @test */
    public function it_handles_interfaces_with_bindings()
    {
        // Test interface resolution
        $this->container->bind(TestInterface::class, TestImplementation::class);

        $service = $this->container->make(TestInterface::class);

        $this->assertInstanceOf(TestImplementation::class, $service);
        $this->assertInstanceOf(TestInterface::class, $service);
    }

    /** @test */
    public function it_creates_new_instances_for_non_singletons()
    {
        // Test that non-singleton bindings create new instances
        $this->container->bind('regular-service', TestService::class);

        $instance1 = $this->container->make('regular-service');
        $instance2 = $this->container->make('regular-service');

        $this->assertNotSame($instance1, $instance2, 'Non-singleton should create new instances');
    }

    /** @test */
    public function it_handles_classes_with_no_dependencies()
    {
        // Test classes with no constructor
        $service = $this->container->make(NoDependencyService::class);

        $this->assertInstanceOf(NoDependencyService::class, $service);
    }

    /** @test */
    public function it_can_pass_container_to_closures()
    {
        // Test that container is passed to closure bindings
        $this->container->bind('closure-test', function($container) {
            $this->assertInstanceOf(SimpleContainer::class, $container);
            return new TestService();
        });

        $service = $this->container->make('closure-test');
        $this->assertInstanceOf(TestService::class, $service);
    }
}

// ===========================================
// TEST HELPER CLASSES
// ===========================================

class TestService
{
    public $name = 'test-service';
}

class ServiceWithDependency
{
    public $dependency;

    public function __construct(TestService $dependency)
    {
        $this->dependency = $dependency;
    }
}

class ComplexService
{
    public $dependency;

    public function __construct(ServiceWithDependency $dependency)
    {
        $this->dependency = $dependency;
    }
}

class CustomTestService
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class NoDependencyService
{
    public function test()
    {
        return 'no dependencies';
    }
}

class CircularA
{
    public function __construct(CircularB $b) {}
}

class CircularB
{
    public function __construct(CircularA $a) {}
}

interface TestInterface
{
    public function test();
}

class TestImplementation implements TestInterface
{
    public function test()
    {
        return 'implemented';
    }
}