<?php

/**
 * Laravel Service Container Knowledge Test
 *
 * This test checks your understanding of the Service Container concepts.
 * Run it with: php examples/ServiceContainerTest.php
 *
 * Answer the questions and see if you understand the core concepts!
 */

class ServiceContainerTest
{
    private int $score = 0;
    private int $totalQuestions = 0;

    public function __construct()
    {
        echo "=== Laravel Service Container Knowledge Test ===\n\n";
        echo "Test your understanding of the Service Container concepts!\n\n";
    }

    /**
     * Ask a question and check the answer
     */
    private function askQuestion(string $question, array $options, int $correctAnswer, string $explanation): void
    {
        $this->totalQuestions++;

        echo "Question {$this->totalQuestions}: {$question}\n";

        foreach ($options as $index => $option) {
            echo "  " . ($index + 1) . ". {$option}\n";
        }

        echo "\nYour answer (1-" . count($options) . "): ";

        // For automated testing, we'll simulate correct answers
        // In a real test, you would read user input here
        $answer = $correctAnswer; // Simulate correct answer

        if ($answer === $correctAnswer) {
            echo "✅ CORRECT!\n";
            $this->score++;
        } else {
            echo "❌ Incorrect. The correct answer was " . ($correctAnswer + 1) . ".\n";
        }

        echo "Explanation: {$explanation}\n\n";
    }

    /**
     * Ask a true/false question
     */
    private function askTrueFalse(string $question, bool $correctAnswer, string $explanation): void
    {
        $this->totalQuestions++;

        echo "Question {$this->totalQuestions}: {$question}\n";
        echo "  1. True\n";
        echo "  2. False\n";

        echo "\nYour answer (1 or 2): ";

        // Simulate correct answer
        $answer = $correctAnswer ? 1 : 2;

        if (($correctAnswer && $answer === 1) || (!$correctAnswer && $answer === 2)) {
            echo "✅ CORRECT!\n";
            $this->score++;
        } else {
            echo "❌ Incorrect.\n";
        }

        echo "Explanation: {$explanation}\n\n";
    }

    /**
     * Run all test questions
     */
    public function runTest(): void
    {
        // Question 1: What is a Service Container?
        $this->askQuestion(
            "What is a Laravel Service Container?",
            [
                "A database storage system",
                "A registry that manages object creation and dependencies",
                "A file caching mechanism",
                "A routing system"
            ],
            1,
            "A Service Container is a dependency injection container that manages object creation and their dependencies automatically."
        );

        // Question 2: Zero Configuration Resolution
        $this->askQuestion(
            "When does Laravel's zero-configuration resolution work?",
            [
                "Only with interfaces",
                "Only with primitive values",
                "With concrete classes that have resolvable dependencies",
                "Never - you always need bindings"
            ],
            2,
            "Zero-configuration resolution works when you have concrete classes with constructor dependencies that can be automatically resolved."
        );

        // Question 3: Where injection works automatically
        $this->askTrueFalse(
            "Method injection (type-hinting parameters in methods) works automatically in your custom classes.",
            false,
            "Method injection only works automatically in Controllers, Event Listeners, Middleware, and Queued Jobs. In your custom classes, only constructor injection works."
        );

        // Question 4: Interfaces without bindings
        $this->askQuestion(
            "What happens when you try to resolve an interface without a binding?",
            [
                "Laravel creates the interface automatically",
                "Laravel throws a BindingResolutionException",
                "Laravel creates a mock implementation",
                "Laravel ignores the interface and continues"
            ],
            1,
            "Interfaces cannot be instantiated directly. You must bind the interface to a concrete implementation first."
        );

        // Question 5: Singleton vs Bind
        $this->askQuestion(
            "What's the difference between bind() and singleton()?",
            [
                "bind() is faster than singleton()",
                "singleton() creates one instance and reuses it, bind() creates new instances",
                "bind() only works with classes, singleton() only works with interfaces",
                "There is no difference"
            ],
            1,
            "singleton() creates one shared instance that gets reused every time you resolve it. bind() creates a new instance each time."
        );

        // Question 6: Circular dependencies
        $this->askTrueFalse(
            "Laravel can automatically resolve circular dependencies (A needs B, B needs A).",
            false,
            "Circular dependencies create infinite loops. Laravel detects them and throws an exception. You need to refactor your code or use interfaces to break the cycle."
        );

        // Question 7: What the app() function does
        $this->askQuestion(
            "What does the app() function do in Laravel?",
            [
                "Creates a new application instance",
                "Returns the application instance (which is the service container)",
                "Starts the Laravel framework",
                "Ends the application"
            ],
            1,
            "app() returns the application instance, which IS the service container. You can use it to resolve dependencies: app(UserService::class)"
        );

        // Question 8: Constructor vs Method injection
        $this->askQuestion(
            "When should you use constructor injection vs method injection?",
            [
                "Always use method injection",
                "Constructor injection for dependencies needed in multiple methods, method injection for one-time dependencies",
                "Never use constructor injection, always use method injection",
                "It doesn't matter, they're the same"
            ],
            1,
            "Use constructor injection when the dependency is needed across multiple methods in the class. Use method injection when it's only needed for one specific method."
        );

        // Question 9: Reflection API
        $this->askTrueFalse(
            "Laravel uses PHP's Reflection API to automatically figure out class dependencies.",
            true,
            "Reflection allows Laravel to examine class constructors at runtime to see what dependencies they need, then automatically create and inject those dependencies."
        );

        // Question 10: When to NOT use the container
        $this->askQuestion(
            "When should you NOT use the service container?",
            [
                "Never - always use it",
                "For simple value objects without dependencies or performance-critical code",
                "Only for testing",
                "Only for interfaces"
            ],
            1,
            "For simple objects without dependencies, performance-critical code, or when you need specific parameters, it's often better to create objects directly with 'new'."
        );

        // Show results
        $this->showResults();
    }

    /**
     * Display the test results
     */
    private function showResults(): void
    {
        $percentage = round(($this->score / $this->totalQuestions) * 100, 0);

        echo "=== TEST RESULTS ===\n";
        echo "Score: {$this->score} / {$this->totalQuestions}\n";
        echo "Percentage: {$percentage}%\n\n";

        if ($percentage >= 90) {
            echo "🎉 EXCELLENT! You have a strong understanding of Laravel's Service Container!\n";
        } elseif ($percentage >= 70) {
            echo "👍 GOOD! You understand most concepts. Review the areas you missed.\n";
        } elseif ($percentage >= 50) {
            echo "📚 OKAY! You have some understanding. Consider reviewing the knowledge base.\n";
        } else {
            echo "💪 KEEP LEARNING! Spend more time with the examples and documentation.\n";
        }

        echo "\n=== RECOMMENDATIONS ===\n";

        if ($percentage < 100) {
            echo "Review these sections in the knowledge base:\n";
            echo "- The examples/ directory for working code\n";
            echo "- The 'What Actually Is a Service Container?' section\n";
            echo "- The 'How Laravel Does It Internals' section\n";
        }

        echo "\nKeep practicing with the example files:\n";
        echo "- SimpleContainer.php - Basic concepts\n";
        echo "- ZeroConfigDemo.php - Automatic resolution\n";
        echo "- LaravelLikeResolution.php - Advanced features\n";
        echo "- ControllerResolution.php - Controller magic\n";

        echo "\nHappy coding! 🚀\n";
    }
}

// ===========================================
// PRACTICAL CODING CHALLENGE
// ===========================================

echo "=== PRACTICAL CHALLENGE ===\n\n";
echo "Before the quiz, try this practical exercise:\n\n";

echo "Create a simple container that can:\n";
echo "1. Auto-resolve: class A needs class B, class B needs class C\n";
echo "2. Bind an interface to an implementation\n";
echo "3. Create a singleton\n\n";

echo "Example code structure:\n";
echo "
interface LoggerInterface { public function log(\$msg); }\n
class FileLogger implements LoggerInterface { public function log(\$msg) { echo \"Log: \$msg\\n\"; } }\n
\nclass Service {\n
    public function __construct(private LoggerInterface \$logger) {}\n
    public function doWork() { \$this->logger->log('Working!'); }\n
}\n
\nclass Container {\n
    private array \$bindings = [];\n
    \n
    public function bind(\$abstract, \$concrete) {\n
        \$this->bindings[\$abstract] = \$concrete;\n
    }\n
    \n
    public function make(\$class) {\n
        // Your implementation here!\n
    }\n
}\n
\n\$container = new Container();\n
\$container->bind(LoggerInterface::class, FileLogger::class);\n
\$service = \$container->make(Service::class);\n
\$service->doWork(); // Should output: Log: Working!\n
\n";

echo "Try implementing this yourself before running the test!\n\n";

echo "Press Enter to continue to the test...";
// In a real interactive test, you would wait for user input here

echo "\n";

// Run the test
$test = new ServiceContainerTest();
$test->runTest();