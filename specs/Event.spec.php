<?php

use Ekok\Web\Event;

describe('Ekok\Web\Event', function() {
    it('should be usable', function() {
        $event = new Event();
        $event->setData('foo');

        expect($event->getData())->to->be->equal('foo');
        expect($event->isPropagationStopped())->to->be->false();

        $event->stopPropagation();
        expect($event->isPropagationStopped())->to->be->true();
    });
});
