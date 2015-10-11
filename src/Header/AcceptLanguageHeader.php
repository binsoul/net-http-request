<?php

namespace BinSoul\Net\Http\Request\Header;

/**
 * Represent the "Accept-Language" header.
 */
class AcceptLanguageHeader extends SortableValuesHeader
{
    /**
     * Returns an array of acceptable languages sorted by preference.
     *
     * @return string[]
     */
    public function getLanguages()
    {
        return array_keys($this->values);
    }

    /**
     * Returns an array of acceptable locales sorted by preference.
     *
     * @return string[]
     */
    public function getLocales()
    {
        $result = array_keys($this->values);
        foreach ($result as $key => $value) {
            $parts = explode('-', $value, 2);
            if (count($parts) > 1) {
                $result[$key] = $parts[0];
            }
        }

        return array_values(array_unique($result));
    }
}
