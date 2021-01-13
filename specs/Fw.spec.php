<?php

use Ekok\Web\Fw;

describe('Ekok\Web\Fw', function() {
    beforeEach(function() {
        $this->fw = new Fw();
    });

    it('should be able constructed from globals', function() {
        $fw = Fw::fromGlobals();
        $expected = array(
            'PATH' => '/',
            'METHOD' => 'GET',
            'IP' => false,
            'AJAX' => false,
        );
        $actual = array_intersect_key($expected, $fw->getValues());

        expect($actual)->to->be->equal($expected);
    });

    it('should resolve given parameters', function() {
        $server = array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_CLIENT_IP' => '168.1.1.1,168.1.1.2',
            'REQUEST_METHOD' => 'POST',
            'PATH_INFO' => '/foo',
        );
        $fw = new Fw(null, null, null, null, $server);
        $expected = array(
            'PATH' => '/foo',
            'METHOD' => 'POST',
            'IP' => '168.1.1.1',
            'AJAX' => true,
        );
        $actual = array_intersect_key($expected, $fw->getValues());

        expect($actual)->to->be->equal($expected);
    });

    it('should be able to retrieve path from request URI', function() {
        $fw = new Fw(null, null, null, null, array(
            'REQUEST_URI' => '/base/app/index.php/foo',
            'SCRIPT_NAME' => '/base/app/index.php',
        ));
        expect($fw['PATH'])->to->be->equal('/foo');

        $fw = new Fw(null, null, null, null, array(
            'REQUEST_URI' => '/base/app/foo',
            'SCRIPT_NAME' => '/base/app/index.php',
        ));
        expect($fw['PATH'])->to->be->equal('/foo');
    });

    it('should be able to act as array', function() {
        $this->fw['foo'] = 'bar';
        expect($this->fw['foo'])->to->be->equal('bar');
        unset($this->fw['foo']);
        expect(isset($this->fw['foo']))->to->be->false();
        expect($this->fw['foo'])->to->be->equal(null);
        expect(isset($this->fw['foo']))->to->be->true();
    });

    it('should be able to manipulate session', function() {
        $this->fw['SESSION']['foo'] = 'bar';

        expect($this->fw['SESSION'])->to->be->equal(array('foo' => 'bar'));

        // update session
        $this->fw['SESSION'] = null;
        expect($this->fw['SESSION'])->to->be->equal(null);

        // remove session
        unset($this->fw['SESSION']);
        expect($this->fw['SESSION'])->to->be->equal(null);
    });

    it('should be able to act as router', function() {
        $this->fw->route('GET /', 'home');
        $this->fw->route('GET home /home', 'home');
        $this->fw->route('GET home /home', 'second_home', array(
            'priority' => 1,
        ));
        $this->fw->route('GET data /data/@no', 'data');
        $this->fw->route('GET /parameter-eater/@data*', 'eater');
        $this->fw->route('GET /parameter-eater2/@data*/@extra1/@extra2', 'eater');
        $this->fw->route('GET /requirement/@id', 'requirement', array('requirements' => array('id' => '\d+')));

        $routes = $this->fw->getRoutes();
        $aliases = $this->fw->getAliases();

        expect($aliases)->to->be->include->keys(array('home', 'data'));
        expect($routes)->to->have->length(6);

        // register invalid routes
        expect(function() {
            $this->fw->route('GET', 'home');
        })->to->throw('LogicException', "Invalid route: 'GET'.");

        // get route
        expect($this->fw->findRoute('/'))->to->be->equal(array(
            'methods' => array('GET'),
            'controller' => 'home',
            'options' => null,
            'alias' => '',
            'path' => '/',
            'parameters' => array(),
        ));

        $this->fw['PATH'] = '/home';
        expect($this->fw->findRoute())->to->be->equal(array(
            'methods' => array('GET'),
            'controller' => 'second_home',
            'options' => array('priority' => 1),
            'alias' => 'home',
            'path' => '/home',
            'parameters' => array(),
        ));

        $this->fw['PATH'] = '/unknown';
        expect($this->fw->findRoute())->to->be->equal(null);

        // manual find routes
        expect($this->fw->findMatchedRoutes('/data/1'))->to->be->equal(array(
            array(
                'methods' => array('GET'),
                'controller' => 'data',
                'options' => null,
                'alias' => 'data',
                'path' => '/data/@no',
                'parameters' => array('no' => '1'),
            ),
        ));

        // unknown routes
        expect($this->fw->findMatchedRoutes('/unknown'))->to->be->equal(array());

        // with parameter eaters
        expect($this->fw->findMatchedRoutes('/parameter-eater/1/2/3/foo/bar'))->to->be->equal(array(
            array(
                'methods' => array('GET'),
                'controller' => 'eater',
                'options' => null,
                'alias' => '',
                'path' => '/parameter-eater/@data*',
                'parameters' => array('data' => '1/2/3/foo/bar'),
            ),
        ));
        expect($this->fw->findMatchedRoutes('/parameter-eater2/1/2/3/foo/bar'))->to->be->equal(array(
            array(
                'methods' => array('GET'),
                'controller' => 'eater',
                'options' => null,
                'alias' => '',
                'path' => '/parameter-eater2/@data*/@extra1/@extra2',
                'parameters' => array('data' => '1/2/3', 'extra1' => 'foo', 'extra2' => 'bar'),
            ),
        ));
    });

    it('should able to build route', function() {
        $this->fw['BASE_PATH'] = 'base';
        $this->fw['ENTRY_SCRIPT'] = true;
        $this->fw['ENTRY'] = 'test.php';

        $this->fw->route('GET foo /foo/@bar', 'none');
        $this->fw->route('GET bar /foo/@bar*', 'none');
        $this->fw->route('GET baz /foo/baz', 'none');

        expect($this->fw->baseUrl())->to->be->equal('http://localhost/base');
        expect($this->fw->baseUrl('/path'))->to->be->equal('http://localhost/base/path');
        expect($this->fw->baseUrl('path'))->to->be->equal('http://localhost/base/path');

        expect($this->fw->alias('foo', array('bar' => 'baz', 'rest' => 1)))->to->be->equal('/foo/baz?rest=1');
        expect($this->fw->alias('bar', array('bar' => array('baz', 'qux'))))->to->be->equal('/foo/baz/qux');
        expect($this->fw->alias('baz', array('bar' => 'baz')))->to->be->equal('/foo/baz?bar=baz');
        expect(function() {
            $this->fw->alias('unknown');
        })->to->throw('LogicException', "Route not found: unknown.");
        expect(function () {
            $this->fw->alias('foo');
        })->to->throw('InvalidArgumentException', "Route parameter is required: foo@bar.");

        expect($this->fw->path('foo', array('bar' => 'baz', 'rest' => 1)))->to->be->equal('/base/test.php/foo/baz?rest=1');
        expect($this->fw->path('baz', array('bar' => 'baz')))->to->be->equal('/base/test.php/foo/baz?bar=baz');
        expect($this->fw->path('unknown', array('bar' => 'baz')))->to->be->equal('/base/test.php/unknown?bar=baz');

        expect($this->fw->asset('asset'))->to->be->equal('/base/asset');
        expect($this->fw->asset('/asset'))->to->be->equal('/base/asset');
        expect(function () {
            $this->fw->asset('');
        })->to->throw('LogicException', "Empty path!");

        expect($this->fw->url('foo', array('bar' => 'baz', 'rest' => 1)))->to->be->equal('http://localhost/base/test.php/foo/baz?rest=1');
    });

    it('can be extended', function() {
        $this->fw->extend('foo', function() {
            return count(func_get_args());
        });
        $this->fw->extend('bar', function($fw) {
            return get_class($fw);
        }, true);

        expect($this->fw->foo(1, 2, 3))->to->be->equal(3);
        expect($this->fw->bar())->to->be->equal(Fw::class);
        expect(function () {
            $this->fw->unknown();
        })->to->throw('BadMethodCallException', "Extension method not exists: unknown.");
    });
});
