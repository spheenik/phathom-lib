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

define("START", 0);
define("POS", 1);
define("VALUES", 2);

class Context {

	protected $tracingEnabled = false;
    protected $log = array();
    
    protected $len;
    
    protected $stack;
    public $state;
    
    public function __construct($len) {
    	$this->len = $len;
        $this->stack = $this->getInitialState();
        $this->state = &$this->stack[count($this->stack)-1];
    }
    
    public function getInitialState() {
    	return array(array(0, 0, array()));
    }
    
    public function setTracingEnabled($flag) {
    	$this->tracingEnabled = $flag;
    }
	
    public function getPos() {
        return $this->state[POS];
    }
    
    public function atEnd() {
    	return $this->state[POS] == $this->len;
    }

    public function enter($name) {
    	//echo "enter $name<br/>";
        $this->stack[] = $this->state;
        $this->state = &$this->stack[count($this->stack)-1];
        $this->state[START] = $this->state[POS];
    }

    public function leave($name, $matched) {
    	//echo "leave $name<br/>"; var_dump($matched);
    	$prevState = array_pop($this->stack);
        $this->state = &$this->stack[count($this->stack)-1];
        if ($matched) {
        	$this->state[POS] = $prevState[POS];
            $this->state[VALUES] = $prevState[VALUES];
        }
    }

    public function push($val) {
        $this->state[VALUES][] = $val; 
    }

    public function pop($idx = 0) {
        $ret = array_splice($this->state[VALUES], - $idx - 1, 1);
        return $ret[0];
    }

    public function peek($idx = 0) {
    	return $this->state[VALUES][count($this->state[VALUES]) - $idx - 1];
    }
    
    public function append($what, $idx = 0) {
    	return $this->state[VALUES][count($this->state[VALUES]) - $idx - 1][] = $what;
    }
    
    public function logTryMatch($type, $which) {
    	if (!$this->tracingEnabled) return;
    	$went = htmlentities($which);
    	$str = "trying to match $type <b>$went</b> at ".$this->state[POS];
    	$this->log[] = $str;
    }
    
    public function logMatchResult($type, $which, $result) {
    	if (!$this->tracingEnabled) return;
    	$went = htmlentities($which);
    	if ($result === true) {
    		$str = "successfully matched $type <b>$went</b> at ".$this->state[START]."-".$this->state[POS];
    	} else {
    		$str = "did not match $type <b>$went</b> at ".$this->state[POS];
    	}
    	$this->log[] = $str;
    }

    public function dumpLog() {
    	echo "<pre><ul>";
    	foreach($this->log as $entry) {
    		echo "<li>$entry</li>";
    	}
    	echo "</ul></pre>";
    }
    
}
