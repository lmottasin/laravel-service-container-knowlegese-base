# Contributing to Laravel Service Container Knowledge Base

## 🚀 How to Contribute

We welcome contributions! Here's how you can get started:

### 📝 Option 1: Improve Documentation

- Fix typos or improve explanations
- Add new examples or use cases
- Update outdated information
- Improve code comments

### 🛠️ Option 2: Complete the Service Container Exercise

1. **Read the Exercise**
   ```bash
   php examples/BuildYourContainer.php
   ```

2. **Implement the Container**
   - Open `examples/BuildYourContainer.php`
   - Implement all methods in the `SimpleContainer` class
   - Remove the `throw new Exception` placeholders

3. **Test Locally**
   ```bash
   composer install
   vendor/bin/phpunit tests/ServiceContainerTest.php
   ```

4. **Submit Pull Request**
   - Fork this repository
   - Create a feature branch
   - Commit your working implementation
   - Submit a Pull Request

## 🧪 Automatic Testing

When you submit a PR, GitHub Actions will automatically:

✅ **Run PHPUnit Tests** - 10 comprehensive tests validate your container implementation
✅ **Check Syntax** - Verify PHP syntax and structure
✅ **Validate Methods** - Ensure all required methods exist
✅ **Check Completeness** - Verify no placeholder exceptions remain

### Test Coverage

The tests validate:

- **Auto-resolution** - Automatic dependency injection
- **Bindings** - Manual binding and resolution
- **Singletons** - Shared instance management
- **Circular Dependencies** - Detection and error handling
- **Closures** - Custom factory functions
- **Interfaces** - Abstract type resolution
- **Error Handling** - Proper exception management

## 📋 Implementation Requirements

Your `SimpleContainer` class must implement these methods:

```php
class SimpleContainer
{
    // Store binding recipe
    public function bind($abstract, $concrete = null);

    // Mark as singleton (shared instance)
    public function singleton($abstract, $concrete = null);

    // Main resolution method
    public function make($abstract);

    // Auto-resolve dependencies (private)
    private function resolve($class);
}
```

## 🔧 Local Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/laravel-service-container-knowledge-base.git
   cd laravel-service-container-knowledge-base
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Run tests**
   ```bash
   vendor/bin/phpunit tests/ServiceContainerTest.php
   ```

4. **Test your implementation**
   ```bash
   php examples/BuildYourContainer.php
   ```

## 📝 Pull Request Guidelines

- Use clear, descriptive commit messages
- Include tests for new features
- Follow existing code style
- Update documentation as needed
- Ensure all tests pass

## 🐛 Reporting Issues

Found a bug or have a suggestion?

1. Check existing issues first
2. Create a new issue with clear description
3. Include steps to reproduce (if applicable)
4. Suggest possible solutions

## 💡 Tips for Success

- **Study First**: Read the existing documentation and examples
- **Reference Implementation**: Check `examples/SimpleContainer.php` for a working example
- **Test Locally**: Run tests before submitting PR
- **Start Simple**: Implement basic functionality first, then add advanced features
- **Ask Questions**: Use issues for clarification if needed

## 🎯 Learning Outcomes

By completing this exercise, you'll understand:

- How Laravel's Service Container works internally
- PHP Reflection API and dependency injection
- Object lifecycle management
- Circular dependency detection
- Factory patterns and closures
- SOLID principles in practice

This is excellent preparation for:
- Laravel developer interviews
- Advanced PHP development
- Understanding modern frameworks
- Building your own frameworks or libraries

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs/12.x/container)
- [PHP Reflection API](https://www.php.net/manual/en/book.reflection.php)
- [Dependency Injection Principles](https://en.wikipedia.org/wiki/Dependency_injection)

---

**Happy coding! 🎉**

Your contributions help make Laravel development more accessible to everyone!