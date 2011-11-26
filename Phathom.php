<?php
/*
* This file is part of Phathom.
*
* Copyright (c) 2011 Martin Schrodt
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is furnished
* to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*/
class Phathom {

    const EOI = -1;
    
    private static $wrappedFunctions = array();

    private static function wrap(&$arguments) {
        foreach($arguments as $i => $a) {
			if (is_string($a)) {
                $arguments[$i] = function(Context $c) use ($a) {
                	$c->logTryMatch("string", $a);
                	//$c->enter($a);
                	$result = $c->matchString($a);
                	$c->logMatchResult("string", $a, $result);
                	//$c->leave($a, $result);
                	return $result;
                };
            } else if ($a === self::EOI) {
                $arguments[$i] = function(Context $c) { return $c->atEnd(); };
            } else if ($a === null) {
            	throw new Exception("found unsuspected null value while wrapping.");
            }
        }
    }
    
    private static function wrapFunction($name) {
    	if (!isset(self::$wrappedFunctions[$name])) {
    		$that = get_called_class();
    		if (!method_exists($that, $name."Rule")) {
    			throw new Exception("No Rule function for '$name'");
    		}
    		self::$wrappedFunctions[$name] = function(Context $c) use ($that, $name) {
    			$c->logTryMatch("rule", $name);
    			$f = call_user_func(array($that, $name."Rule"));
    			$c->enter($name);
    			$result = $f($c);
    			$c->logMatchResult("rule", $name, $result);
    			$c->leave($name, $result);
    			return $result;
    		};
    	}
    	return self::$wrappedFunctions[$name];
    }
    
    public static function oneOf() {
        $functions = func_get_args();
        self::wrap($functions);
        return function(Context $c) use ($functions) {
            foreach($functions as $f)
                if ($f($c) === true) return true;
            return false;
        };
    }

    public static function sequence() {
        $functions = func_get_args();
        self::wrap($functions);
        return function(Context $c) use ($functions) {
            foreach($functions as $f) {
            	if ($f($c) === false) return false;
            }
            return true;
        };
    }

    public static function nToM() {
    	$args = func_get_args();
    	$n = array_shift($args);
    	$m = array_shift($args);
    	$f = call_user_func_array(array("self", "sequence"), $args);
    	return function(Context $c) use ($f, $n, $m) {
    		$result = true;
    		$count = 0;
    		while($result !== false) {
    			$c->enter("nToM");
    			$result = $f($c);
    			$c->leave("nToM", $result);
    			if ($result === true) {
    				$count++;
    			}
    		}
    		return ($n === null || $count >= $n) && ($m === null || $count <= $m);
    	};
    }
    
    public static function nOrMore() {
    	$args = func_get_args();
    	$n = array_shift($args);
    	array_unshift($args, null);
    	array_unshift($args, $n);
    	return call_user_func_array(array("self", "nToM"), $args);
    }

    public static function zeroOrMore() {
    	$args = func_get_args();
    	array_unshift($args, 0);
    	return call_user_func_array(array("self", "nOrMore"), $args);
    }

    public static function oneOrMore() {
    	$args = func_get_args();
    	array_unshift($args, 1);
    	return call_user_func_array(array("self", "nOrMore"), $args);
   	}

    public static function firstOf() {
        $functions = func_get_args();
        self::wrap($functions);
        return function(Context $c) use ($functions) {
        	foreach($functions as $f) {
        		$c->enter("firstOf");
        		$result = $f($c);
                $c->leave("firstOf", $result);
                if ($result === true) {
                	break;
                }
        	}
            return $result === true;
        };
    }    
    
    public static function optional() {
    	$args = func_get_args();
    	$f = call_user_func_array(array("self", "sequence"), $args);
    	return function(Context $c) use ($f) {
    		$c->enter("optional");
    		$result = $f($c);
    		$c->leave("optional", $result);
    		return true;
    	};
    }
    
    public static function permutation() {
        $functions = func_get_args();
        self::wrap($functions);
        return function(Context $c) use ($functions) {
        	$matched = true;
        	$matchCount = 0;
        	while ($matched) {
        		$matched = false;
        		foreach($functions as $key => $f) {
        			if ($f != null) {
        				$c->enter("permutation");
        				$result = $f($c);
        				$c->leave("permutation", $result);
        				if ($result === true) {
        					$functions[$key] = null;
        					$matched = true;
        					$matchCount++;
        					break;
        				}
        			}
        		}
        	}
            return $matchCount > 0;
        };
    }
        
    public static function regex($regex) {
        return function(Context $c) use ($regex) {
        	$c->logTryMatch("regex", $regex);
        	//$c->enter($regex);
            $result = $c->matchPreg($regex);
            $c->logMatchResult("regex", $regex, $result);
            //$c->leave($regex, $result);
            return $result;
        };
    }
    
    public static function __callStatic($name, $parameters) {
    	return self::wrapFunction($name);
    }
    
    public static function run($rule, Context $c) {
    	$f = call_user_func(array(get_called_class(), $rule));
    	return $f($c);
    }
    

}
