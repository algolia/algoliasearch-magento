<?php

class Algolia_Algoliasearch_Helper_Image extends Mage_Catalog_Helper_Image
{
    /*
     * Override to be able to catch the error
     */
    public function toString()
    {
        $model = $this->_getModel();

        if ($this->getImageFile())
            $model->setBaseFile($this->getImageFile());
        else
            $model->setBaseFile($this->getProduct()->getData($model->getDestinationSubdir()));

        if ($model->isCached())
            return $model->getUrl();
        else
        {
            if ($this->_scheduleRotate)
                $model->rotate($this->getAngle());

            if ($this->_scheduleResize)
                $model->resize();

            if ($this->getWatermark())
                $model->setWatermark($this->getWatermark());

            $url = $model->saveFile()->getUrl();
        }

        return $url;
    }
}
