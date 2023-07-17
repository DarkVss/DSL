# Simple DSL library

## Requires 

* PHP 8.1+
* and nothing more (at stock-state)

## Some explanations

```ini
#SimpleCalculator
    plus(123) 
    minus(1)
#SimpleCalculator
    plus(2)
    pow(3)
```

`#SimpleCalculator` this is name of Entity, class extends by [\DSL\Entity class](src/DSL/Entity.php)<br>
`plus(123)` and `minus(1)` - method what will be called by chain for defined Entity<br>
All Entity must be used with some methods

### Custom Entity class

Custom Entity must ...:
1. ... be child of [\DSL\Entity class](src/DSL/Entity.php).
2. ... has some method what can be call by user from instruction
3. ... all user-calling method must be `public`
3. ... all user-calling method pass scalar types(if it has args) and return self
4. ... all user-calling method have prefix equal what set in [\DSL\EntityDescriptor\Method::METHOD_NAME_BEGIN](src/DSL/EntityDescriptor/Method.php)
5. ... all user-calling method have PHPDoc(comment and parameters description)

### How to

Run DSL processing(`DP` in next text)
```php
\DSL::init()
    ->useTypeCast(true) // set flag for casting types
    ->parseFile($instructionsFile) // file with instructions
    ->run() // return array, each element is result what was return by Entity object by `apply` methods, by order  
```

If u want to add custom Entity - before run DP execute next code
```php
\DSL\Entity\Pool::Instance()
    ->addEntity(\CustomEntityFirst::class)
    ->addEntity(\CustomEntitySecond::class);
```
and then run DP
