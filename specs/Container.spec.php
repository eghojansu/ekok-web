<?php

use Ekok\Web\Container;

describe('Ekok\Web\Container', function() {
    beforeEach(function() {
        $this->container = new Container();
    });

    it('can be used as simple container', function() {
        expect($this->container->get('foo', 'default'))->to->be->equal('default');

        $this->container->set('foo', 'bar');
        expect($this->container->get('foo'))->to->be->equal('bar');

        $this->container->remove('foo');
        expect($this->container->has('foo'))->to->be->false();
    });

    it('can be used as service container', function() {
        $this->container->set('date', $dateService = function() {
            return new DateTime();
        });
        $this->container->set('date_holder', function(Container $container) {
            $object = new stdClass();
            $object->date = $container->get('date');

            return $object;
        });

        $date = $this->container->get('date');
        $dateHolder = $this->container->get('date_holder');

        expect($date)->to->be->instanceof('DateTime');
        expect($dateHolder)->to->be->instanceof('stdClass');
        expect($date)->to->be->equal($dateHolder->date);

        // raw value should be exists
        expect($this->container->raw('date'))->to->be->equal($dateService);

        // remove
        $this->container->remove('date');
        expect($this->container->has('date'))->to->be->false();
    });

    it('should throws when assign frozen service', function() {
        $this->container->set('date', function() {
            return new DateTime();
        });

        expect($this->container->get('date'))->to->be->instanceof('DateTime');
        expect(function() {
            $this->container->set('date', 'anothervalue');
        })->to->throw('LogicException', 'Service has been frozen: date.');
    });

    it('should returns fresh instance with factory', function() {
        $this->container->factory('date', function() {
            return new DateTime();
        });

        $date1 = $this->container->get('date');
        $date2 = $this->container->get('date');

        expect($date1)->to->be->not->equal($date2);

        // throw exception
        expect(function() {
            $this->container->factory('foo', 'bar');
        })->to->throw('InvalidArgumentException', 'Service is not a Closure or invokable object.');
    });

    it('should protect callable from being treated as service constructor', function() {
        $this->container->protect('date', $service = function() {
            return new DateTime();
        });

        expect($this->container->get('date'))->to->be->equal($service);

        // throw exception
        expect(function() {
            $this->container->protect('foo', 'bar');
        })->to->throw('InvalidArgumentException', 'Callable is not a Closure or invokable object.');
    });

    it('should be able to extend service constructor', function() {
        $this->container->set('object', function() {
            return new stdClass();
        });
        $this->container->extend('object', function(stdClass $object) {
            $object->changed = true;

            return $object;
        });
        $this->container->factory('factory', function() {
            return new stdClass();
        });
        $this->container->extend('factory', function(stdClass $factory) {
            $factory->changed = true;

            return $factory;
        });

        $object1 = $this->container->get('object');
        $object2 = $this->container->get('object');

        expect($object1)->to->be->equal($object2);
        expect($object1->changed)->to->be->true();

        $factory1 = $this->container->get('factory');
        $factory2 = $this->container->get('factory');

        expect($factory1)->to->be->not->equal($factory2);
        expect($factory1->changed)->to->be->true();
    });

    it('should throws exception if extended service is not defined', function() {
        expect(function() {
            $this->container->extend('foo', 'bar');
        })->to->throw('OutOfBoundsException', 'Service is not defined: foo.');
    });

    it('should throws exception if extended service is frozen', function() {
        $this->container->set('foo', function() {
            return new stdClass();
        });

        // retrive
        $this->container->get('foo');

        expect(function() {
            $this->container->extend('foo', 'bar');
        })->to->throw('RuntimeException', 'Service has been frozen: foo.');
    });

    it('should throws exception if extended service is not callable', function() {
        $this->container->set('foo', 'bar');

        expect(function() {
            $this->container->extend('foo', function() {
            return new stdClass();
        });
        })->to->throw('InvalidArgumentException', 'Service is not a Closure or invokable object: foo.');
    });

    it('should throws exception if extended service is protected', function() {
        $this->container->protect('foo', function() {
            return new stdClass();
        });

        expect(function() {
            $this->container->extend('foo', 'bar');
        })->to->throw('LogicException', 'Service has been protected: foo.');
    });

    it('should throws exception if extension is not callable', function() {
        $this->container->set('foo', function() {
            return new stdClass();
        });

        expect(function() {
            $this->container->extend('foo', 'bar');
        })->to->throw('InvalidArgumentException', 'Extension service is not a Closure or invokable object.');
    });

    it('should be able to load configuration files', function() {
        $this->container->loadFiles(__DIR__.'/fixtures/data.php');

        expect($this->container->get('foo'))->to->be->equal('bar');
    });

    it('can use batch command', function() {
        $this->container->setAll(array(
            'foo' => 'bar',
            'bar' => 'baz',
        ));
        expect($this->container->getAll(array('foo', 'foo2' => 'bar')))->to->be->equal(array('foo' => 'bar', 'foo2' => 'baz'));
        expect($this->container->hasAll('foo', 'bar'))->to->be->true();

        $this->container->removeAll('foo');
        expect($this->container->hasAll('foo', 'bar'))->to->be->false();
    });
});
