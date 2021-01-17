<?php

use Ekok\Web\Container;
use Ekok\Web\ContainerValueIterator;

describe('Ekok\Web\ContainerValueIterator', function() {
    it('should iterable', function() {
        $container = new Container();
        $container['foo'] = function() { return new DateTime(); };
        $container['bar'] = function() { return new DateTime(); };
        $container['baz'] = function() { return new DateTime(); };

        $retriveAll = array(
            'foo' => $container['foo'],
            'bar' => $container['bar'],
            'baz' => $container['baz'],
        );
        $iterator = new ContainerValueIterator($container, array('foo', 'bar', 'baz'));

        expect($retriveAll)->to->be->equal(iterator_to_array($iterator));
    });
});
