<?php

namespace PhraseanetSDK\Repository;

use PhraseanetSDK\Exception\RuntimeException;
use Doctrine\Common\Collections\ArrayCollection;

class Cgus extends AbstractRepository
{

    /**
     * Find All the cgus for the choosen databox
     *
     * @param  integer          $databoxId The databox id
     * @return ArrayCollection
     * @throws RuntimeException
     */
    public function findByDatabox($databoxId)
    {
        $response = $this->query('GET', sprintf('/databoxes/%d/termsOfUse/', $databoxId));

        if (true !== $response->hasProperty('termsOfUse')) {
            throw new RuntimeException('Missing "termsOfuse" property in response content');
        }

        $metaCollection = new ArrayCollection();

        foreach ($response->getProperty('termsOfUse') as $metaDatas) {
            $metaCollection->add($this->em->hydrateEntity($this->em->getEntity('cgus'), $metaDatas));
        }

        return $metaCollection;
    }
}
