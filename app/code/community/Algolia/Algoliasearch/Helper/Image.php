<?php

/*
 * Subclass to be able to catch the error
 */
class Algolia_Algoliasearch_Helper_Image extends Mage_Catalog_Helper_Image
{
    public function toString()
    {
        $model = $this->_getModel();

        if ($this->getImageFile()) {
            $model->setBaseFile($this->getImageFile());
        } else {
            $model->setBaseFile($this->getProduct()->getData($model->getDestinationSubdir()));
        }

        if ($model->isCached()) {
            return $this->removeProtocol($model->getUrl());
        }

        if ($this->_scheduleRotate) {
            $model->rotate($this->getAngle());
        }

        if ($this->_scheduleResize) {
            $model->resize();
        }

        if ($this->getWatermark()) {
            $model->setWatermark($this->getWatermark());
        }

        return $this->removeProtocol($model->saveFile()->getUrl());
    }

    public function removeProtocol($url)
    {
        return str_replace(array('https://', 'http://'), '//', $url);
    }
}
