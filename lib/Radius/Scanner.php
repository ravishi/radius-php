<?php

namespace Radius;

class Scanner
{
    protected
        $cursor,
        $position,
        $end,
        $code,
        $tagPrefix;

    const EOF               = -1;

    const POSITION_DATA     = 0;
    const POSITION_OPEN     = 1;
    const POSITION_CLOSE    = 2;

    const REGEX_NAME_CHAR   = '[\-A-Za-z0-9._:?]';

    public function operate($tagPrefix, $code)
    {
        $this->code = str_replace(array("\r\n", "\r"), "\n", $code);
        $this->cursor = 0;
        $this->end = strlen($this->code);
        $this->position = self::POSITION_DATA;
        $this->tagPrefix = $tagPrefix;

        $tokens = array();
        $end = false;
        while (!$end) {
            $token = $this->nextToken();
            $end = $token === self::EOF;
            $tokens[] = $token;
        }

        if (end($tokens) == self::EOF) {
            array_pop($tokens);
        }

        return $tokens;
    }

    protected function nextToken()
    {
        // have we reached the end of the code?
        if ($this->cursor >= $this->end) {
            return self::EOF;
        }

        switch ($this->position) {
        case self::POSITION_DATA:
            $tokens = $this->lexData();
            break;
        case self::POSITION_OPEN:
            $tokens = $this->lexOpenOrSelfTag();
            break;
        case self::POSITION_CLOSE:
            $tokens = $this->lexCloseTag();
            break;
        }

        // it not an array, lets return it
        if (!is_array($tokens)) {
            return $tokens;
        }
        // its a token, lets return it
        else if (isset($tokens['flavor'])) {
            return $tokens;
        }
        // if its an empty array, lets get the next
        else if (empty($tokens)) {
            return $this->nextToken();
        }
        else {
            return $tokens[0];
        }
    }

    protected function lexData()
    {
        $match = null;

        $pos1 = strpos($this->code, "<{$this->tagPrefix}:", $this->cursor);
        $pos2 = strpos($this->code, "</{$this->tagPrefix}:", $this->cursor);

        if (false === $pos1 && false === $pos2) {
            $rv = substr($this->code, $this->cursor);
            $this->cursor = $this->end;

            return $rv;
        }

        // min
        $pos = -log(0);
        if (false !== $pos1 && $pos1 < $pos) {
            $pos = $pos1;
            $token = "<{$this->tagPrefix}:";
        }
        if (false !== $pos2 && $pos2 < $pos) {
            $pos = $pos2;
            $token = "</{$this->tagPrefix}:";
        }

        $result = array();

        // if we have data
        if (0 < ($len = $pos - $this->cursor))
        {
            $text = substr($this->code, $this->cursor, $len);
            $this->moveCursor($text);
            $result[] = $text;
        }

        if ($token[1] == '/') {
            $this->position = self::POSITION_CLOSE;
        } else {
            $this->position = self::POSITION_OPEN;
        }

        return $result;
    }

    protected function lexOpenOrSelfTag()
    {
        if (preg_match('@<'.$this->tagPrefix.':('.self::REGEX_NAME_CHAR.'+)((?:\s+(?:'.self::REGEX_NAME_CHAR.'+)\s*=\s*(?:"(?:\\\"|[^"])*"|(?:\'(\\\\\'|[^\'])*\')))*)\s*/?>@A',
            $this->code, $match, null, $this->cursor))
        {
            $this->moveCursor($match[0]);
            $this->position = self::POSITION_DATA;

            return $this->createOpenOrSelfTag($match);
        }

        return $this->lexIncorrectTag();
    }

    protected function lexCloseTag()
    {
        if (preg_match('@</'.$this->tagPrefix.':('.self::REGEX_NAME_CHAR.'+)\s*>@A', $this->code, $match, null, $this->cursor))
        {
            $this->moveCursor($match[0]);
            $this->position = self::POSITION_DATA;

            return array('name' => $match[1], 'flavor' => 'close');
        }

        return $this->lexIncorrectTag();
    }

    protected function lexIncorrectTag()
    {
        if (preg_match('@</?('.$this->tagPrefix.')?:?@A', $this->code, $match, null, $this->cursor)) {
            $this->moveCursor($match[0]);
            $this->position = self::POSITION_DATA;

            return $match[0];
        }
        // @codeCoverageIgnoreStart
        else {
            throw new \Exception('this was not supposed to happen');
        }
        // @codeCoverageIgnoreEnd
    }

    protected function createOpenOrSelfTag($match)
    {
        $flavor = substr($match[0], -2) == '/>'
            ? 'self' 
            : 'open';

        // build attrs
        $attrs = array();
		preg_match_all('@('.self::REGEX_NAME_CHAR.'+?)\s*=\s*("(?:\\\"|[^"])*"|(?:\'(?:\\\\\'|[^\'])*\'))@', $match[2], $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $attrs[$m[1]] = substr($m[2], 1, -1);
        }

        // create tag
        return array('name' => $match[1], 'attrs' => $attrs, 'flavor' => $flavor);
    }

    protected function moveCursor($text)
    {
        $this->cursor += strlen($text);
    }
}
