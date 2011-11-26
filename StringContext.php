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
class StringContext extends Context {

    private $input;

    private $currentMatch;
    private $subgroups;

    public function __construct($input) {
        $this->input = $input;
    	parent::__construct(strlen($input));
	}

    public function matchString($which) {
        $l = strlen($which);
    	//$result = $l == 0 ? true : substr_compare($this->input, $which, $this->state[POS], $l) == 0;
        $result = substr($this->input, $this->state[POS], $l) === $which;
        if ($result) $this->state[POS] += $l;
        $this->currentMatch = $which;
        $this->subgroups = null;
        return $result;
    }

    public function matchPreg($which) {
    	$m = $which[0]."^".substr($which, 1);
    	$result = preg_match($m, substr($this->input, $this->state[POS]), $this->subgroups);
    	if ($result === 0) {
    		return false;
    	}
        $this->currentMatch = $this->subgroups[0];
        $this->state[POS] += strlen($this->currentMatch);
        return true;
    }

    public function currentMatch() {
        return $this->currentMatch;    
    }
    
    public function subgroup($n = 0) {
    	return $this->subgroups[$n];
    }
    
    public function logMatchResult($type, $which, $result) {
    	if (!$this->tracingEnabled) return;
    	$went = htmlentities($which);
    	if ($result === true) {
        		$str = "successfully matched $type <b>$went</b> at ".$this->state[START]."-".$this->state[POS].": <pre style='display:inline;background-color:fuchsia;'>".htmlentities(substr($this->input, $this->state[START], $this->state[POS] - $this->state[START]))."</pre>";
    		} else {
    		$str = "did not match $type <b>$went</b> at ".$this->state[POS];
    	}
    	$this->log[] = $str;
    }
    
    
}
