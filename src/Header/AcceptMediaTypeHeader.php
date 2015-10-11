<?php

namespace BinSoul\Net\Http\Request\Header;

/**
 * Represent the "Accept" header.
 */
class AcceptMediaTypeHeader extends SortableValuesHeader
{
    protected function getDefault()
    {
        return ['*/*' => 1.0];
    }

    /**
     * Returns an array of acceptable media types sorted by preference.
     *
     * @return string[]
     */
    public function getMediaTypes()
    {
        return array_keys($this->values);
    }
}
