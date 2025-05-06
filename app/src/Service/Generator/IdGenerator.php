<?php

declare(strict_types=1);

namespace App\Service\Generator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;

class IdGenerator extends AbstractIdGenerator
{
        /**
     * {@inheritdoc}
     */
    public function generateId(EntityManagerInterface $em, $entity): mixed
    {
        $id = substr(md5(time()), 1, 8); //strtoupper(substr(uniqid(), 0, 8));

        if (null !== $em->getRepository(get_class($entity))->findOneBy(['id' => $id])) {
           // $id = $this->generate($em, $entity);
        }
 
        return $id;
    }
}