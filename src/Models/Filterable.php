<?php

namespace Atom\Models;

use Atom\Models\ModelException;

trait Filterable {
    /**
     * Filter
     * @param  array  $params Params
     * @return $this
     */
    public function filter(array $params)
    {
        $conditions = $this->prepareFilterable($params);
        $this->where($conditions);
        return $this;
    }

    /**
     * Prepare filterable
     * @param  array  $params Params
     * @return array
     */
    public function prepareFilterable(array $params)
    {
        $conditions = [];
        foreach ($params as $key => $value) {
            if (empty($this->filterable)) {
                break;
            }
            if (!is_array($this->filterable)) {
                throw new ModelException(ModelException::ERR_MSG_INVALID_FILTERABLE);
            }
            if (in_array($key, $this->filterable)) {
                $conditions[] = [$key, $value];
                continue;
            }
            if (array_key_exists($key, $this->filterable)) {
                if (array_key_exists('LIKE', $this->filterable[$key])) {
                    $value = str_replace('{'.$key.'}', $value, $this->filterable[$key]['LIKE']);
                    $conditions[] = [$key, 'LIKE', $value];
                    continue;
                }
                $conditions[] = [$key, $value];
                continue;
            }
        }
        return $conditions;
    }
}
