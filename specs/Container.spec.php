<?php

use Ekok\Web\Container;

describe('Ekok\Web\Container', function() {
    beforeEach(function() {
        $this->container = new Container();
    });

    it('can be used as simple container', function() {
        $this->container['foo'] = 'bar';

        expect($this->container->keys())->to->be->equal(array('foo'));
        expect($this->container->values())->to->be->equal(array('foo' => 'bar'));
        expect(isset($this->container['foo']))->to->be->true();
        expect($this->container['foo'])->to->be->equal('bar');

        unset($this->container['foo']);

        expect(function() {
            $foo = $this->container['foo'];
        })->to->throw('LogicException', 'Value not found: foo.');
    });

    it('can be used as service container', function() {
        $this->container['date'] = $dateService = function() {
            return new DateTime();
        };
        $this->container['date_holder'] = function(Container $container) {
            $object = new stdClass();
            $object->date = $container['date'];

            return $object;
        };

        $date = $this->container['date'];
        $dateHolder = $this->container['date_holder'];

        expect($date)->to->be->instanceof('DateTime');
        expect($dateHolder)->to->be->instanceof('stdClass');
        expect($date)->to->be->equal($dateHolder->date);

        // raw value should be exists
        expect($this->container->raw('date'))->to->be->equal($dateService);

        // remove
        unset($this->container['date']);
        expect(isset($this->container['date']))->to->be->false();
    });

    it('should throws when assign frozen service', function() {
        $this->container['date'] = function() {
            return new DateTime();
        };

        expect($this->container['date'])->to->be->instanceof('DateTime');
        expect(function() {
            $this->container['date'] = 'anothervalue';
        })->to->throw('LogicException', 'Service has been frozen: date.');
    });

    it('should returns fresh instance with factory', function() {
        $this->container['date'] = $this->container->factory(function() {
            return new DateTime();
        });

        $date1 = $this->container['date'];
        $date2 = $this->container['date'];

        expect($date1)->to->be->not->equal($date2);

        // throw exception
        expect(function() {
            $this->container->factory('foo');
        })->to->throw('InvalidArgumentException', 'Service is not a Closure or invokable object.');
    });

    it('should protect callable from being treated as service constructor', function() {
        $this->container['date'] = $this->container->protect($service = function() {
            return new DateTime();
        });

        expect($this->container['date'])->to->be->equal($service);

        // throw exception
        expect(function() {
            $this->container->protect('foo');
        })->to->throw('InvalidArgumentException', 'Callable is not a Closure or invokable object.');
    });

    it('should be able to extend service constructor', function() {
        $this->container['object'] = function() {
            return new stdClass();
        };
        $this->container['object'] = $this->container->extend('object', function(stdClass $object) {
            $object->changed = true;

            return $object;
        });
        $this->container['factory'] = $this->container->factory(function() {
            return new stdClass();
        });
        $this->container['factory'] = $this->container->extend('factory', function(stdClass $factory) {
            $factory->changed = true;

            return $factory;
        });

        $object1 = $this->container['object'];
        $object2 = $this->container['object'];

        expect($object1)->to->be->equal($object2);
        expect($object1->changed)->to->be->true();

        $factory1 = $this->container['factory'];
        $factory2 = $this->container['factory'];

        expect($factory1)->to->be->not->equal($factory2);
        expect($factory1->changed)->to->be->true();
    });

    it('should throws exception if extended service is not defined', function() {
        expect(function() {
            $this->container->extend('foo', 'bar');
        })->to->throw('OutOfBoundsException', 'Service is not defined: foo.');
    });

    it('should throws exception if extended service is frozen', function() {
        $this->container['foo'] = function() {
            return new stdClass();
        };

        // retrive
        $foo = $this->container['foo'];

        expect(function() {
            $this->container->extend('foo', 'bar');
        })->to->throw('RuntimeException', 'Service has been frozen: foo.');
    });

    it('should throws exception if extended service is not callable', function() {
        $this->container['foo'] = 'bar';

        expect(function() {
            $this->container->extend('foo', function() {
                return new stdClass();
            });
        })->to->throw('InvalidArgumentException', 'Service is not a Closure or invokable object: foo.');
    });

    it('should throws exception if extended service is protected', function() {
        $this->container['foo'] = $this->container->protect(function() {
            return new stdClass();
        });

        expect(function() {
            $this->container->extend('foo', 'bar');
        })->to->throw('LogicException', 'Service has been protected: foo.');
    });

    it('should throws exception if extension is not callable', function() {
        $this->container['foo'] = function() {
            return new stdClass();
        };

        expect(function() {
            $this->container['foo'] = $this->container->extend('foo', 'bar');
        })->to->throw('InvalidArgumentException', 'Extension service is not a Closure or invokable object.');
    });

    it('should be able to load configuration files', function() {
        $this->container->loadFiles(array(
            __DIR__.'/fixtures/data.php',
            'wrap' => __DIR__ . '/fixtures/data.php',
        ));

        expect($this->container['foo'])->to->be->equal('bar');
        expect($this->container['wrap']['foo'])->to->be->equal('bar');
    });

    it('should be able to use aliases', function() {
        $this->container['foo'] = function() {
            return new DateTime();
        };
        $this->container->service('bar', function() {
            return new DateTime();
        }, 'baz');
        $this->container->simple('std', 'stdClass');
        $this->container->alias('foo', 'foo_alias');

        $expectedAliases = array(
            'baz' => 'bar',
            'stdClass' => 'std',
            'foo_alias' => 'foo',
        );

        expect($this->container->aliases())->to->be->equal($expectedAliases);

        $foo = $this->container['foo'];
        $fooAlias = $this->container['foo_alias'];
        $bar = $this->container['bar'];
        $baz = $this->container['baz'];
        $std = $this->container['std'];
        $stdClass = $this->container['stdClass'];

        expect($foo)->to->be->equal($fooAlias);
        expect($bar)->to->be->equal($baz);
        expect($std)->to->be->instanceof('stdClass');
        expect($std)->to->be->equal($stdClass);

        expect(function() {
            $this->container->service('unknown', 'foo');
        })->to->throw('LogicException', 'Callable is not a Closure or invokable object.');
    });

    it('can be extended', function() {
        $this->container->method('foo', function () {
            return count(func_get_args());
        }, false);
        $this->container->method('bar', function ($container) {
            return get_class($container);
        });

        expect($this->container->methods())->to->have->length(2);
        expect($this->container->foo(1, 2, 3))->to->be->equal(3);
        expect($this->container->bar())->to->be->equal(Container::class);
        expect(function () {
            $this->container->unknown();
        })->to->throw('BadMethodCallException', "Method not registered: unknown.");
    });
});
