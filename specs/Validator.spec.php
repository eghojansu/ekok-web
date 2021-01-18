<?php

use Ekok\Web\Validator;

describe('Ekok\Web\Validator', function() {
    beforeEach(function() {
        $this->validator = new Validator();
    });

    it('should be customizable', function() {
        $this->validator->setMessages(array(
            'foo' => 'bar',
        ));
        $this->validator->setRules(array(
            'foo' => 'trim',
        ));
        $this->validator->addRule('bar', 'trim', 'baz');

        expect($this->validator->getMessages())->to->include->keys(array('foo', 'bar'));
        expect($this->validator->getRules())->to->be->equal(array('foo' => 'trim', 'bar' => 'trim'));
    });

    it('can validate successfully', function() {
        $base = array(
            'today' => date('Y-m-d', strtotime('today')),
            'today_object' => new \DateTime('today'),
            'yesterday' => date('Y-m-d', strtotime('yesterday')),
            'tomorrow' => date('Y-m-d', strtotime('tomorrow')),
            'tomorrow_after' => date('Y-m-d', strtotime('+2 day')),
        );
        $rules = array(
            'accepted' => 'accepted',
            'after' => 'after:today',
            'after2' => 'after:after',
            'after3' => array('after' => array($base['today_object'])),
            'after_or_equal' => 'after_or_equal:today',
            'alpha' => 'alpha',
            'alnum' => 'alnum',
            'array' => 'array',
            'before' => 'before:today',
            'before_or_equal' => 'before_or_equal:today',
            'between' => 'between:1,3',
            'between2' => 'between:1,3',
            'between3' => 'between:1,3',
            'boolean' => 'boolean',
            'confirmed' => 'confirmed',
            'confirmed2' => 'confirmed:accepted',
            'date' => 'date:false,Y-m-d',
            'date_equals' => 'date_equals:'.$base['today'],
            'date_format' => 'date_format:Y-m-d',
            'different' => 'different:confirmed',
            'digits' => 'digits',
            'digits_between' => 'digits_between:1,3',
            'distinct' => 'distinct:true',
            'email' => 'email',
            'ends_with' => 'ends_with:foo,bar',
            'exclude' => 'exclude',
            'exclude_if' => 'exclude_if:email,email@example.com',
            'exclude_if2' => array('exclude_if' => array(function() { return true; })),
            'exclude_unless' => 'exclude_unless:email,email@example.com',
            'exclude_unless2' => array('exclude_unless' => array(function() { return true; })),
            'exclude_unless3' => array('exclude_unless' => array(function() { return false; })),
            'gt' => 'gt:alpha',
            'gte' => 'gte:alpha',
            'in' => 'in:a,b,c',
            'in_array' => 'in_array:distinct',
            'integer' => 'integer',
            'ip' => 'ip',
            'ip4' => 'ip4',
            'ip6' => 'ip6',
            'json' => 'json:true,true',
            'lt' => 'lt:alpha',
            'lte' => 'lte:alpha',
            'match' => 'match:/^foo$/',
            'max' => 'max:1',
            'min' => 'min:1',
            'max_length' => 'max_length:1',
            'min_length' => 'min_length:1',
            'not_in' => 'not_in:a,b,c',
            'not_match' => 'not_match:/^foo$/',
            'numeric' => 'numeric',
            'optional' => 'optional',
            'required' => 'required',
            'required_if' => 'required_if:email,email@example.com',
            'required_if2' => array('required_if' => array(function() { return true; })),
            'required_unless' => 'required_unless:email,email@example.com',
            'required_unless2' => array('required_unless' => array(function() { return true; })),
            'same' => 'same:1,false',
            'size' => 'size:1',
            'starts_with' => 'starts_with:foo,bar',
            'string' => 'string',
            'trim' => 'trim',
            'url' => 'url',
        );
        $data = array(
            'accepted' => '1',
            'after' => $base['tomorrow'],
            'after2' => $base['tomorrow_after'],
            'after3' => $base['tomorrow'],
            'after_or_equal' => $base['tomorrow'],
            'alpha' => 'alpha',
            'alnum' => 'alnum1',
            'array' => array(),
            'before' => $base['yesterday'],
            'before_or_equal' => $base['yesterday'],
            'between' => array(1),
            'between2' => 'ab',
            'between3' => 123,
            'boolean' => 'true',
            'confirmed' => 'true',
            'confirmed_confirmation' => 'true',
            'confirmed2' => '1',
            'date' => $base['today'],
            'date_equals' => $base['today'],
            'date_format' => $base['today'],
            'different' => 'false',
            'digits' => '10',
            'digits_between' => '10',
            'distinct' => array('A', 'b', 'C'),
            'email' => 'email@example.com',
            'ends_with' => 'foobar',
            'exclude' => 'email@example.com',
            'exclude_if' => 'email@example.com',
            'exclude_if2' => 'email@example.com',
            'exclude_unless' => 'email@example.com',
            'exclude_unless2' => 'email@example.com',
            'gt' => 'alpha1',
            'gte' => 'alpha',
            'in' => 'a',
            'in_array' => 'A',
            'integer' => '11',
            'ip' => '189.43.5.56',
            'ip4' => '189.43.5.56',
            'ip6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'json' => '{"foo":"bar"}',
            'lt' => 'alph',
            'lte' => 'alpha',
            'match' => 'foo',
            'max' => 'a',
            'min' => 'ab',
            'max_length' => 'a',
            'min_length' => 'ab',
            'not_in' => 'd',
            'not_match' => 'bar',
            'numeric' => '123.45',
            // optional not given
            'required' => 'foo',
            'required_if' => 'foo',
            'required_if2' => 'foo',
            'required_unless' => 'foo',
            'required_unless2' => 'foo',
            'same' => '1',
            'size' => '1',
            'starts_with' => 'foobar',
            'string' => 'string',
            'trim' => ' foo ',
            'url' => 'http://example.com',
        );
        $options = array();
        $expected = array(
            'accepted' => '1',
            'after' => $base['tomorrow'],
            'after2' => $base['tomorrow_after'],
            'after3' => $base['tomorrow'],
            'after_or_equal' => $base['tomorrow'],
            'alpha' => 'alpha',
            'alnum' => 'alnum1',
            'array' => array(),
            'before' => $base['yesterday'],
            'before_or_equal' => $base['yesterday'],
            'between' => array(1),
            'between2' => 'ab',
            'between3' => 123,
            'boolean' => true,
            'confirmed' => 'true',
            'confirmed2' => '1',
            'date' => $base['today'],
            'date_equals' => $base['today'],
            'date_format' => $base['today'],
            'different' => 'false',
            'digits' => '10',
            'digits_between' => '10',
            'distinct' => array('A', 'b', 'C'),
            'email' => 'email@example.com',
            'ends_with' => 'foobar',
            // exclude never included
            'exclude_unless' => 'email@example.com',
            'exclude_unless2' => 'email@example.com',
            'gt' => 'alpha1',
            'gte' => 'alpha',
            'in' => 'a',
            'in_array' => 'A',
            'integer' => 11,
            'ip' => '189.43.5.56',
            'ip4' => '189.43.5.56',
            'ip6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'json' => array('foo' => 'bar'),
            'lt' => 'alph',
            'lte' => 'alpha',
            'match' => 'foo',
            'max' => 'a',
            'min' => 'ab',
            'max_length' => 'a',
            'min_length' => 'ab',
            'not_in' => 'd',
            'not_match' => 'bar',
            'numeric' => 123.45,
            // optional excluded
            'required' => 'foo',
            'required_if' => 'foo',
            'required_if2' => 'foo',
            'required_unless' => 'foo',
            'required_unless2' => 'foo',
            'same' => '1',
            'size' => '1',
            'starts_with' => 'foobar',
            'string' => 'string',
            'trim' => 'foo',
            'url' => 'http://example.com',
        );
        $result = $this->validator->validate($rules, $data, $options);

        expect($result['violations'])->to->be->equal(array());
        expect($result['data'])->to->be->equal($expected);
    });

    it('can show violations', function() {
        $this->validator->addRule('foo', function() {
            return false;
        }, '"{value}" is not an option.');

        $today = date('Y-m-d', strtotime('today'));
        $tomorrow = date('Y-m-d', strtotime('tomorrow'));

        $rules = array(
            'required' => 'required',
            'starts_with' => 'starts_with:foo,bar',
            'ends_with' => 'ends_with:foo,bar',
            'before' => 'before:'.$today,
            'foo' => 'foo'
        );
        $data = array(
            'in' => 'd',
            'starts_with' => 'bazqux',
            'ends_with' => 'bazqux',
            'before' => $tomorrow,
            'foo' => 'bar',
        );
        $expected = array(
            'required' => array('This value should not be blank.'),
            'starts_with' => array('This value should starts with [foo, bar].'),
            'ends_with' => array('This value should ends with [foo, bar].'),
            'before' => array("This value should be before {$today}."),
            'foo' => array('"bar" is not an option.'),
        );

        $result = $this->validator->validate($rules, $data);

        expect($result['violations'])->to->be->equal($expected);

        // enable options
        $options = array(
            'skipOnError' => true,
        );
        $expected = array(
            'required' => array('This value should not be blank.'),
        );
        $result = $this->validator->validate($rules, $data, $options);

        expect($result['violations'])->to->be->equal($expected);
    });

    it('throws exception if rule is not defined', function() {
        expect(function() {
            $this->validator->validate(array(
                'foo' => 'bar',
            ), array());
        })->to->throw('LogicException', 'Rule not exists: bar.');
    });

    it('can handle dot style validation', function() {
        $rules = array(
            'name' => 'required|string|min:5',
            'addresses.*.street' => 'required|string|min:3|ends_with:st',
            'tags.*' => 'required|string|min:3',
        );
        $data = array(
            'name' => 'whataname',
            'addresses' => array(
                array('street' => '1 street name st'),
                array('street' => '2 street name st'),
            ),
            'age' => 'integer|min:18',
            'tags' => array(
                'first',
                'second',
                'third',
            ),
        );
        $expected = array(
            'name' => 'whataname',
            'addresses' => array(
                array('street' => '1 street name st'),
                array('street' => '2 street name st'),
            ),
            'tags' => array(
                'first',
                'second',
                'third',
            ),
        );
        $result = $this->validator->validate($rules, $data);

        expect($result['violations'])->to->be->equal(array());
        expect($result['data'])->to->be->equal($expected);

        // with invalid data
        $data = array(
            'name' => 'whataname',
            'addresses' => array(
                array('street' => '1 street name st'),
                array('street' => '2 street name'),
            ),
        );
        $expected = array(
            'addresses' => array(
                1 => array('street' => array('This value should ends with [st].')),
            ),
            'tags' => array(
                0 => array('This value should not be blank.'),
            ),
        );
        $result = $this->validator->validate($rules, $data);

        expect($result['violations'])->to->be->equal($expected);
    });

    it('able to handle nested dataset', function() {
        $rules = array(
            'user.*.options.*.name' => 'required|min:3',
            'data.*.optional' => 'exclude_if:data,null|optional|min:3',
        );
        $data = array(
            'user' => array(
                array(
                    'options' => array(
                        array('name' => 'first'),
                        array('name' => 'second'),
                    ),
                ),
            ),
        );
        $expected = array(
            'user' => array(
                array(
                    'options' => array(
                        array('name' => 'first'),
                        array('name' => 'second'),
                    ),
                ),
            ),
            'data' => array(),
        );
        $result = $this->validator->validate($rules, $data);

        expect($result['data'])->to->be->equal($expected);

        $data = array(
            'data' => array(
                array('optional' => '1'),
            ),
        );
        $expected = array(
            'user' => array(
                array(
                    'options' => array(
                        array('name' => array('This value should not be blank.')),
                    ),
                ),
            ),
            'data' => array(
                array(
                    'optional' => array('This value should not less than 3.'),
                )
            )
        );
        $result = $this->validator->validate($rules, $data);

        expect($result['violations'])->to->be->equal($expected);
    });
});
