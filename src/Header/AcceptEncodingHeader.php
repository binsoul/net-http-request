<?php

namespace BinSoul\Net\Http\Request\Header;

/**
 * Represents the "Accept-Encoding" header.
 */
class AcceptEncodingHeader extends SortableValuesHeader
{
    /**
     * Returns an array of acceptable encodings sorted by preference.
     *
     * @return string[]
     */
    public function getEncodings()
    {
        return array_keys($this->values);
    }
}
