<?php

class Algolia_Algoliasearch_Helper_Image extends Mage_Catalog_Helper_Image
{
    /*
     * Subclass to be able to catch the error
     */
    public function toString()
    {
        $model = $this->_getModel();

        if ($this->getImageFile()) {
            $model->setBaseFile($this->getImageFile());
        } else {
            $model->setBaseFile($this->getProduct()->getData($model->getDestinationSubdir()));
        }

        if ($model->isCached()) {
            return $this->getUrl($model);
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

        return $this->getUrl($model->saveFile());
    }

    public function getUrl(Mage_Catalog_Model_Product_Image $model)
    {
        $baseDir = Mage::getBaseDir('media');
        $path = str_replace($baseDir . DS, '', $model->getNewFile());
        $url = str_replace(DS, '/', $path);

        return $url;
    }
}
