<?php

namespace Kavinsky\CloverMerge\Spec;

use Kavinsky\CloverMerge\Utilities;
use Kahlan\Plugin\Double;

/**
 * @phan-closure-scope \Kahlan\Scope
 * @phan-file-suppress PhanParamTooMany
 */
describe('Utilities', function () {
    describe('filesExist', function () {
        context('Receives a list of paths containing a non existant file first.', function () {
            beforeEach(function () {
                allow('is_file')->toBeCalled()->andReturn(false, true, true);
                $this->result = Utilities::filesExist(new \Ds\Set(['no', 'yes', 'also yes']));
            });

            it('returns false.', function () {
                expect($this->result)->toBe(false);
            });
        });

        context('Receives a list of paths containing a non existant file last.', function () {
            beforeEach(function () {
                allow('is_file')->toBeCalled()->andReturn(true, true, false);
                $this->result = Utilities::filesExist(new \Ds\Set(['yes', 'also yes', 'no']));
            });

            it('returns false.', function () {
                expect($this->result)->toBe(false);
            });
        });

        context('Receives a list of paths containing no missing files.', function () {
            beforeEach(function () {
                allow('is_file')->toBeCalled()->andReturn(true, true, true);
                $this->result = Utilities::filesExist(new \Ds\Set(['yes', 'also yes', 'yes again']));
            });

            it('returns true.', function () {
                expect($this->result)->toBe(true);
            });
        });
    });
    describe('xmlHasAttributes', function () {
        context('Receives an element with the desired attributes.', function () {
            beforeEach(function () {
                $element = new \SimpleXMLElement('<test foo="bar" barry="baz"/>');
                $this->result = Utilities::xmlHasAttributes($element, ["foo", "barry"]);
            });

            it('returns true.', function () {
                expect($this->result)->toBeTruthy();
            });
        });
        context('Receives an element with one attribute missing.', function () {
            beforeEach(function () {
                $element = new \SimpleXMLElement('<test foo="bar"/>');
                $this->result = Utilities::xmlHasAttributes($element, ["foo", "barry"]);
            });

            it('returns false.', function () {
                expect($this->result)->toBeFalsy();
            });
        });
        context('Receives an element the one desired attribute.', function () {
            beforeEach(function () {
                $element = new \SimpleXMLElement('<test foo="bar"/>');
                $this->result = Utilities::xmlHasAttributes($element, ["foo"]);
            });

            it('returns true.', function () {
                expect($this->result)->toBeTruthy();
            });
        });
        context('Receives an element which is itself an attribute.', function () {
            beforeEach(function () {
                // The PHP docs claim that ->attributes() of an attribute returns null,
                // but it doesn't seem to, so we'll mock it to force a null response
                // to cover that path.
                // http://php.net/manual/en/simplexmlelement.attributes.php
                $this->element = Double::instance([
                    'extends' => '\SimpleXMLElement',
                    'args' => ['<test foo="bar"/>'] // Unable to instantiate an attribute
                ]);
                allow($this->element)->toReceive('attributes')->andReturn(null);
                $this->result = Utilities::xmlHasAttributes($this->element, ["bar"]);
            });

            it('attemts to fetch attributes of the attribute.', function () {
                expect($this->element)->toReceive('attributes');
            });

            it('returns false.', function () {
                expect($this->result)->toBeFalsy();
            });
        });
    });
    describe('logInfo', function () {
        context('Receives a string to print.', function () {
            beforeEach(function () {
                $this->closure = function () {
                    Utilities::logInfo('test');
                };
            });

            it('prints an info log.', function () {
                expect($this->closure)->toEcho("INFO: test\n");
            });
        });
    });
    describe('logWarning', function () {
        context('Receives a string to print.', function () {
            beforeEach(function () {
                $this->closure = function () {
                    Utilities::logWarning('test');
                };
            });

            it('prints a warning log.', function () {
                expect($this->closure)->toEcho("WARNING: test\n");
            });
        });
    });
});
