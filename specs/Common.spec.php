<?php

use Ekok\Web\Common;

describe('Ekok\Web\Common', function() {
    it('should be able to stringify value', function () {
        expect(Common::stringify('data'))->to->be->equal('data');
        expect(Common::stringify(null))->to->be->equal('NULL');
        expect(Common::stringify(true))->to->be->equal('true');
        expect(Common::stringify(array('foo', 'bar', 1, null, true)))->to->be->equal('[foo, bar, 1, NULL, true]');
        expect(Common::stringify(array('foo' => 'bar')))->to->be->equal('[foo => bar]');
    });

    it('should be able to cast value', function () {
        expect(Common::castData('null'))->to->be->equal(null);
        expect(Common::castData('0b0001'))->to->be->equal(1);
        expect(Common::castData('0x1f'))->to->be->equal(31);
        expect(Common::castData('20'))->to->be->equal(20);
        expect(Common::castData('20.00'))->to->be->equal(20.00);
        expect(Common::castData(' foo '))->to->be->equal(' foo ');
    });

    it('should be able to get reference', function () {
        $data = array();
        $expected = array(
            'foo' => array(
                'bar' => 'baz',
            ),
        );

        $ref = &Common::getDataRef($data, 'foo.bar');
        $ref = 'baz';

        expect($data)->to->be->equal($expected);
    });

    it('should be able to get data value', function () {
        $data = array(
            'foo' => array(
                'bar' => 'baz',
            ),
            'bar' => 'baz',
        );

        expect(Common::getDataValue($data, 'foo.bar'))->to->be->equal('baz');
        expect(Common::getDataValue($data, 'bar'))->to->be->equal('baz');
        expect(Common::getDataValue($data, 'unknown'))->to->be->equal(null);
        expect(Common::getDataValue($data, 'foo.bar.baz'))->to->be->equal(null);
    });

    it('can parse expression', function() {
        $expression = 'foo:bar,true,10|bar:qux,20.32|qux|';
        $expected = array(
            'foo' => array('bar', true, 10),
            'bar' => array('qux', 20.32),
            'qux' => array(),
        );

        expect(Common::parseExpression($expression))->to->be->equal($expected);
    });

    it('can fix slashes', function() {
        expect(Common::fixSlashes('\\foo\\bar\\'))->to->be->equal('/foo/bar');
        expect(Common::fixSlashes('\\foo\\bar', true))->to->be->equal('/foo/bar/');
    });
});
