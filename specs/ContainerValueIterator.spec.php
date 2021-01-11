<?php

use Ekok\Web\Container;
use Ekok\Web\ContainerValueIterator;

describe('Ekok\Web\ContainerValueIterator', function() {
    it('should iterable', function() {
        $container = new Container();
        $container->setAll(array(
            'foo' => function() { return new DateTime(); },
            'bar' => function() { return new DateTime(); },
            'baz' => function() { return new DateTime(); },
        ));

        $retriveAll = $container->getAll(array('foo', 'bar', 'baz'));
        $iterator = new ContainerValueIterator($container, array('foo', 'bar', 'baz'));

        expect($retriveAll)->to->be->equal(iterator_to_array($iterator));
    });
});
