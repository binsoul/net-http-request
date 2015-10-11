<?php

namespace BinSoul\Net\Http\Request\Header;

/**
 * Represents an header with sortable values.
 *
 * Sorted values are given in the following format:
 *
 * <code>value[;parameter][;q=weight][,]</code>
 */
abstract class SortableValuesHeader
{
    /** @var float[] */
    protected $values;

    /**
     * Constructs an instance of this class.
     *
     * @param string $header raw value of the header
     */
    public function __construct($header)
    {
        $this->values = $this->parseHeader($header);
        if (count($this->values) == 0) {
            $this->values = $this->getDefault();
        }
    }

    /**
     * Returns the default values if the raw header is invalid or empty.
     *
     * @return float[]
     */
    protected function getDefault()
    {
        return ['*' => 1.0];
    }

    /**
     * Parses a raw header string and returns an array of values sorted by preference.
     *
     * @param string $header
     *
     * @return float[]
     */
    private function parseHeader($header)
    {
        $result = [];
        if (trim($header) == '') {
            return $result;
        }

        $values = explode(',', preg_replace('/\s+/', '', $header));
        foreach ($values as $index => $value) {
            if (trim($value) == '') {
                continue;
            }

            $parameter = '';
            $quality = -$index;

            $parts = explode(';', $value);
            if (count($parts) > 2) {
                $parameter = ';'.$parts[1];
                if (preg_match('/q=([0-9\.]+)?/i', $parts[2], $matches) && isset($matches[1])) {
                    $quality = (float) $matches[1];
                }
            } elseif (count($parts) > 1) {
                if (!preg_match('/q=([0-9\.]+)?/i', $parts[1], $matches)) {
                    $parameter = ';'.$parts[1];
                    $quality = 1000 - $index;
                } elseif (isset($matches[1])) {
                    $quality = (float) $matches[1];
                }
            } else {
                $quality = 1000 - $index;
            }

            $result[$parts[0].$parameter] = $quality;
        }

        arsort($result);

        return $result;
    }
}
