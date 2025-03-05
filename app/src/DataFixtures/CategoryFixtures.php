<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getCategoriesData() as [$name, $parent]) {
            $category = new Category();
            $category->setTitle($name);
            $parentCategory = $manager->getRepository(Category::class)->findOneBy(['title' => $parent]);
            $category->setParent($parentCategory);

            $manager->persist($category);
            $manager->flush();
        }
    }

    /**
     * @return array<array>
     */
    private function getCategoriesData(): array
    {
        return [
            ['Furniture', null],
            ['Beds', 'Furniture'],
            ['Chairs', 'Furniture'],
            ['Sports', null],
            ['Walker', 'Sports']
        ];
    }
}
