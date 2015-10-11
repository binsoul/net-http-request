<?php

namespace BinSoul\Net\Http\Request\Header;

/**
 * Represents the "Accept-Charset" header.
 */
class AcceptCharsetHeader extends SortableValuesHeader
{
    /**
     * Returns an array of acceptable charsets sorted by preference.
     *
     * @return string[]
     */
    public function getCharsets()
    {
        return array_keys($this->values);
    }
}
