<?php

namespace SoftDelete\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Datasource\EntityInterface;

trait SoftDeleteTrait
{
    public $enableSoftDelete = true;

    public function getSoftDeleteConfig($name = false)
    {
        $config = false;
        if (isset($this->softDeleteConfig)) {
            $config = $this->softDeleteConfig;
        }
        if (!$config) {
            $field = 'deleted';
            if (isset($this->softDeleteField)) {
                $field = $this->softDeleteField;
            }
            $config = [
                'values' => [$field => date('Y-m-d H:i:s')],
                'without' => [$field . ' is' => null],
                'in' => [$field . ' is not' => null]
            ];
        }
        if ($name) {
            return isset($config[$name]) ? $config[$name]: false;
        }
        return $config;
    }

    public function getSoftDeleteValues()
    {
        return $this->getSoftDeleteConfig('values');
    }

    public function getWithoutSoftDeleteConditions()
    {
        return $this->getSoftDeleteConfig('without');
    }

    public function getInSoftDeleteConditions()
    {
        return $this->getSoftDeleteConfig('in');
    }

    public function query()
    {
        return new SoftDeleteQuery($this->connection(), $this);
    }

    public function dispatchEvent($name, $data = null, $subject = null)
    {
        $event = parent::dispatchEvent($name, $data, $subject);
        if ($event->isStopped()) {
            return $event;
        }
        if ($this->enableSoftDelete
                && $name == 'Model.beforeDelete'
                && $data
                && isset($data['entity'])
                && isset($data['options'])) {
            $entity = $data['entity'];
            $options = $data['options'];

            $this->_associations->cascadeDelete(
                $entity,
                ['_primary' => false] + $options->getArrayCopy()
            );

            $primaryKey = (array)$this->primaryKey();
            $conditions = (array)$entity->extract($primaryKey);
            $event->result = $this->deleteAll($conditions);

            parent::dispatchEvent('Model.afterDelete', [
                'entity' => $entity,
                'options' => $options
            ]);
            $event->stopPropagation();
        }
        return $event;
    }

    public function deleteAll($conditions)
    {
        if ($this->enableSoftDelete) {
            $values = $this->getSoftDeleteValues();
            $statement = $this->query()->update()
                ->set($values)->where($conditions)->execute();
            $rowCount = $statement->rowCount();
            $statement->closeCursor();
            return $rowCount;
        } else {
            return parent::deleteAll($conditions);
        }
    }

    public function hardDelete(EntityInterface $entity, $options = [])
    {
        $org = $this->enableSoftDelete;
        $this->enableSoftDelete = false;
        $result = $this->delete($entity, $options);
        $this->enableSoftDelete = $org;
        return $result;
    }

    public function hardDeleteAll($conditions)
    {
        $org = $this->enableSoftDelete;
        $this->enableSoftDelete = false;
        $result = $this->deleteAll($conditions);
        $this->enableSoftDelete = $org;
        return $result;
    }
}
