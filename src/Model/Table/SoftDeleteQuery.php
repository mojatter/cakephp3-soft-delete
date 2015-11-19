<?php

namespace SoftDelete\Model\Table;

use Cake\ORM\Query;

class SoftDeleteQuery extends Query
{
    public function triggerBeforeFind()
    {
        if (!$this->_beforeFindFired && $this->_type === 'select') {
            parent::triggerBeforeFind();
            $repository = $this->repository();
            $options = $this->getOptions();
            if (!is_array($options)
                    || !(in_array('withDeleted', $options, true)
                            || (isset($options['withDeleted']) && $options['withDeleted']))) {
                $conditions = $repository->getWithoutSoftDeleteConditions();
                foreach ($conditions as $k => $v) {
                    $a = $repository->aliasField($k);
                    $this->andWhere([$a => $v]);
                }
            }
            if (is_array($options)
                    && (in_array('inDeleted', $options, true)
                            || (isset($options['inDeleted']) && $options['inDeleted']))) {
                $conditions = $repository->getInSoftDeleteConditions();
                foreach ($conditions as $k => $v) {
                    $a = $repository->aliasField($k);
                    $this->andWhere([$a => $v]);
                }
            }
        }
    }
}
