<?php

namespace Radius;

class Scanner
{
    protected
        $cursor,
        $position,
        $end,
        $template,
        $tagPrefix;

    const POSITION_DATA     = 0;
    const POSITION_OPEN     = 1;
    const POSITION_CLOSE    = 2;

    const REGEX_NAME_CHAR   = '[\-A-Za-z0-9._:?]';

    public function operate($tagPrefix, $template)
    {
        $this->template = str_replace(array("\r\n", "\r"), "\n", $template);
        $this->cursor = 0;
        $this->end = strlen($this->template);
        $this->position = self::POSITION_DATA;
        $this->tagPrefix = $tagPrefix;

        $tokens = array();
        while (false !== $token = $this->nextToken()) {
            $tokens[] = $token;
        }

        return $tokens;
    }

    protected function nextToken()
    {
        // have we reached the end of the template?
        if ($this->cursor >= $this->end) {
            return false;
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

        // it's not an array. must be a string or something.
        if (!is_array($tokens)) {
            return $tokens;
        }
        // it's a tag token.
        else if (isset($tokens['flavor'])) {
            return $tokens;
        }
        // it's an empty array. lets get the next.
        else if (empty($tokens)) {
            return $this->nextToken();
        }
        // what is it?
        else {
            return $tokens[0];
        }
    }

    protected function lexData()
    {
        $match = null;

        $pos1 = strpos($this->template, "<{$this->tagPrefix}:", $this->cursor);
        $pos2 = strpos($this->template, "</{$this->tagPrefix}:", $this->cursor);

        if (false === $pos1 && false === $pos2) {
            $rv = substr($this->template, $this->cursor);
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
            $text = substr($this->template, $this->cursor, $len);
            $this->moveCursor($text);
            $result[] = $text;
        }

        // is it a self-closing tag or a opening tag?
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
            $this->template, $match, null, $this->cursor))
        {
            $this->moveCursor($match[0]);
            $this->position = self::POSITION_DATA;

            return $this->createOpenOrSelfTag($match);
        }

        return $this->lexIncorrectTag();
    }

    protected function lexCloseTag()
    {
        if (preg_match('@</'.$this->tagPrefix.':('.self::REGEX_NAME_CHAR.'+)\s*>@A', $this->template, $match, null, $this->cursor))
        {
            $this->moveCursor($match[0]);
            $this->position = self::POSITION_DATA;

            return array('name' => $match[1], 'flavor' => 'close');
        }

        return $this->lexIncorrectTag();
    }

    protected function lexIncorrectTag()
    {
        if (preg_match('@</?('.$this->tagPrefix.')?:?@A', $this->template, $match, null, $this->cursor)) {
            $this->moveCursor($match[0]);
            $this->position = self::POSITION_DATA;

            return $match[0];
        }
        // I think we'll never get there, or am I wrong?
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
