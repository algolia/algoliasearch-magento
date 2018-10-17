<?php

class Algolia_Algoliasearch_Model_Source_JobStatuses
{
    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ERROR = 'error';
    const STATUS_COMPLETE = 'complete';

    protected $_statuses = array(
        self::STATUS_NEW => 'New',
        self::STATUS_ERROR => 'Error',
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_COMPLETE => 'Complete'
    );

    /**
     * @return array
     */
    public function getStatuses()
    {
        return $this->_statuses;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        foreach ($this->_methods as $method => $label) {
            $option[] = array(
                'value' => $method,
                'label' => $label,
            );
        }
        return $options;
    }
}
