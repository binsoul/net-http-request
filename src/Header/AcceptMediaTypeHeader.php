<?php

declare (strict_types = 1);

namespace BinSoul\Net\Http\Request\Header;

/**
 * Represent the "Accept" header.
 */
class AcceptMediaTypeHeader extends SortableValuesHeader
{
    protected function getDefault(): array 
    {
        return ['*/*' => 1.0];
    }

    /**
     * Returns an array of acceptable media types sorted by preference.
     *
     * @return string[]
     */
    public function getMediaTypes(): array 
    {
        return array_keys($this->values);
    }
}
