<?php

use Ekok\Web\ValidatorContext;

describe('Ekok\Web\ValidatorContext', function() {
    it('should act as validator context', function() {
        $context = new ValidatorContext('foo', 'bar', array('other' => 'data'), array('other2' => 'data2'));

        expect($context->getField())->to->be->equal('foo');
        expect($context->getValue())->to->be->equal('bar');
        expect($context->isValueSet())->to->be->true();
        expect($context->isValid())->to->be->true();
        expect($context->getPrefix())->to->be->equal(null);
        expect($context->getSuffix())->to->be->equal(null);
        expect($context->getPosition())->to->be->equal(null);
        expect($context->getPath())->to->be->equal('foo');
        expect($context->getMessage())->to->be->equal(null);
        expect($context->isSkipped())->to->be->false();
        expect($context->isExcluded())->to->be->false();
        expect($context->isNumeric())->to->be->false();
        expect($context->isPositional())->to->be->false();
        expect($context->getData())->to->be->equal(array('other' => 'data'));
        expect($context->getValidated())->to->be->equal(array('other2' => 'data2'));
        expect($context->checkOther('other'))->to->be->true();
        expect($context->checkOther('other2'))->to->be->true();
        expect($context->checkOther('other3'))->to->be->false();
        expect($context->getOther('other'))->to->be->equal('data');
        expect($context->getOther('other2'))->to->be->equal('data2');
        expect($context->getOther('other3'))->to->be->equal(null);

        $context->freeValueSet();
        expect($context->isValid())->to->be->false();

        $context->skip();
        $context->exclude();
        $context->valid();
        $context->setMessage('message');
        $context->setNumeric(true);
        expect($context->getMessage())->to->be->equal('message');
        expect($context->isValid())->to->be->true();
        expect($context->isSkipped())->to->be->true();
        expect($context->isExcluded())->to->be->true();
        expect($context->isNumeric())->to->be->true();

        $clone = $context->duplicate('bar', 'baz');
        expect($clone->getField())->to->be->equal('bar');
        expect($clone->getValue())->to->be->equal('baz');
    });

    it('handle dot path', function () {
        $context = new ValidatorContext('foo', 'bar', array(array('foo' => 'bar', 'other' => 'data')), array(array('foo' => 'bar', 'other2' => 'data2')), array(
            'prefix' => 'prefix',
            'position' => 0,
        ));

        expect($context->getPrefix())->to->be->equal('prefix');
        expect($context->getPosition())->to->be->equal(0);
        expect($context->getPath())->to->be->equal('prefix.0.foo');
        expect($context->checkOther('other'))->to->be->true();
        expect($context->checkOther('other2'))->to->be->true();
        expect($context->checkOther('other3'))->to->be->false();
        expect($context->getOther('other'))->to->be->equal('data');
        expect($context->getOther('other2'))->to->be->equal('data2');
        expect($context->getOther('other3'))->to->be->equal(null);
    });

    it('able to retrieve date value', function() {
        $format = 'Y-m-d';
        $today = date($format, strtotime('today'));
        $tomorrow = date($format, strtotime('tomorrow'));
        $yesterday = new \DateTime('yesterday');

        $context = new ValidatorContext('today', $today, array(
            'tomorrow' => $tomorrow,
            'yesterday' => $yesterday,
            'number' => '82',
            'array' => array(1, 2),
        ));

        expect($context->getDate()->format($format))->to->be->equal($today);
        expect($context->getDate('yesterday'))->to->be->equal($yesterday);
        expect($context->getDate('unknown'))->to->be->equal(null);

        expect($context->compareDate())->to->be->equal(-1);
        expect($context->compareDate('today'))->to->be->equal(0);
        expect(function() use ($context) {
            $context->compareDate('unknown');
        })->to->throw('LogicException', 'Both date should be valid date: today.');

        expect($context->getSize())->to->be->equal(10);
        expect($context->getSize('number'))->to->be->equal(2);
        expect($context->getSize('array'))->to->be->equal(2);

        // change type
        $context->setNumeric(true);

        expect($context->getSize('number'))->to->be->equal(82);
    });
});
