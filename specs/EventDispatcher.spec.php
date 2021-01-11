<?php

use Ekok\Web\Event;
use Ekok\Web\EventDispatcher;

describe('Ekok\Web\EventDispatcher', function() {
    it('should dispatch event', function() {
        $dispatcher = new EventDispatcher();
        $dispatcher->on('foo', function(Event $event) {
            $event->setData($event->getData() . ' first');
            $event->stopPropagation();
        });
        $dispatcher->on('foo', function(Event $event) {
            // this function will never be called
            $event->setData($event->getData() . ' second');
        }, -1);
        $dispatcher->on('foo', function (Event $event) {
            $event->setData($event->getData() . ' third');
        }, 1);
        $dispatcher->one('first_only', function(Event $event) {
            $event->setData('first set');
        });

        $dispatcher->dispatch('foo', $event = new Event());
        expect($event->getData())->to->be->equal(' third first');

        $dispatcher->dispatch('first_only', $event = new Event());
        expect($event->getData())->to->be->equal('first set');

        // second call no dispatcher applied
        $dispatcher->dispatch('first_only', $event = new Event());
        expect($event->getData())->to->be->equal(null);

        // remove event
        $dispatcher->off('foo');
        $dispatcher->dispatch('foo', $event = new Event());
        expect($event->getData())->to->be->equal(null);
    });
});
