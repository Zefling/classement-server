<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Repository\ClassementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ApiClassementController extends AbstractController
{

    public function __construct(
        private ClassementRepository $classementRepository
    ) {}

    #[Route(
        '/api/classement/add', 
        name: 'app_api_classement_add', 
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => Classement::class,
            '_api_item_operation_name' => 'post_publication',
        ],
    )]
    public function index(Classement $classement): Classement
    {
        
        $this->classementRepository->add($classement);
        
        return $classement;
    }
}
