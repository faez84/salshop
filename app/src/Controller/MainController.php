<?php

declare(strict_types=1);

namespace App\Controller;

use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route(path: '/', name: 'app_main')]
    public function index(): Response
    {
        $arr = [
        ['id' => 1, 'name' => 'John'],
        ['id' => 5, 'name' => 'Jane'],
        ['id' => 8, 'name' => 'Doe'], 
        ['id' => 7, 'name' => 'Smith'],
        ['id' => 3, 'name' => 'Emily'],
        ['id' => 10, 'name' => 'Michael'],
        ['id' => 9, 'name' => 'Sarah'],
        ['id' => 4, 'name' => 'David'],
        ['id' => 6, 'name' => 'Jessica'],
        ['id' => 2, 'name' => 'Daniel']
        ];
        $arr2 = [
            'b' => 'Emily',
            'h' => 'John',
            'v' => 'Jane',
            's' => 'Smith',
            'q' => 'Doe',
            'r' => 'Michael',
            'i' => 'Sarah',
            'k' => 'David',
            'y' => 'Jessica',
            'w' => 'Daniel'
        ];

 //       array_multisort(array_column($arr, 'name'), SORT_ASC, $arr);
/*
 usort($arr, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
});

uksort($arr2, function ($a, $b) use ($arr2) {
    return 10;
});
// krsort($arr2);
$product = Product::create('Product 1', 10.99, 5,'Description of Product 1', null, null,'P001', null);
 
*/
 

        return $this->render('index.html.twig', [
          'mesg' => 'Welcome!',
        ]);
    }

    #[Route('/cp/userHome', 'app_user_home')]
    public function homeLogin(): Response
    {
        return $this->render('indexUser.html.twig');
    }
}
