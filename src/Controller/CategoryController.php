<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryController extends AbstractController
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function index(Request $request, LoggerInterface $logger): JsonResponse
    {
        try {
            $entityManager = $this->doctrine->getManager();
            $values = json_decode($request->getContent());
            $query = $entityManager->getRepository(Category::class)->createQueryBuilder('categories');
            if (isset($values->name)) {
                $query->where('categories.name LIKE CONCAT(\'%\', :name, \'%\')')
                    ->setParameter('name', $values->name);
            }
            if (isset($values->id)) {
                $query->andWhere('categories.id = :id')
                    ->setParameter('id', $values->id);
            }
            $query->orderBy('categories.name', 'ASC');
            $categories = $query->getQuery()->getArrayResult();
            return new JsonResponse(['process' => true, 'datas' => json_encode($categories)], Response::HTTP_OK);
        } catch (\Throwable $throwable) {
            $logger->error($throwable->getMessage(), [
                "line" => $throwable->getLine(),
                "code" => $throwable->getCode(),
                "file" => $throwable->getFile()
            ]);
            return new JsonResponse(['process' => false, 'messages' => 'Unknown error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request, ValidatorInterface $validator, LoggerInterface $logger): JsonResponse
    {
        try {
            if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                return new JsonResponse(['process' => false, 'messages' => 'Only for admins'], Response::HTTP_UNAUTHORIZED);
            }
            $values = json_decode($request->getContent());
            if (!isset($values->name)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "name" is not valid.'], Response::HTTP_BAD_REQUEST);
            }
            $category = new Category();
            $category->setName($values->name);

            $errors = $validator->validate($category);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return new JsonResponse(['process' => false, 'messages' => $messages], Response::HTTP_BAD_REQUEST);
            }
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($category);
            $entityManager->flush();
            return new JsonResponse(['process' => true, 'messages' => 'created', 'data' => $category], Response::HTTP_OK);

        } catch (\Throwable $throwable) {
            $logger->error($throwable->getMessage(), [
                "line" => $throwable->getLine(),
                "code" => $throwable->getCode(),
                "file" => $throwable->getFile()
            ]);
            return new JsonResponse(['process' => false, 'messages' => 'Unknown error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(int $id, Request $request, ValidatorInterface $validator, LoggerInterface $logger): JsonResponse
    {
        try {
            if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                return new JsonResponse(['process' => false, 'messages' => 'Only for admins'], Response::HTTP_UNAUTHORIZED);
            }
            $entityManager = $this->doctrine->getManager();
            $category = $entityManager->getRepository(Category::class)->find($id);
            if (!$category) {
                return new JsonResponse(['process' => false, 'messages' => 'No category found for id ' . $id], Response::HTTP_NOT_FOUND);
            }

            $values = json_decode($request->getContent());
            if (!isset($values->name)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "name" is not valid.'], Response::HTTP_BAD_REQUEST);
            }

            $category->setName($values->name);
            $errors = $validator->validate($category);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return new JsonResponse(['process' => false, 'messages' => $messages], Response::HTTP_BAD_REQUEST);
            }
            $entityManager->persist($category);
            $entityManager->flush();

            return new JsonResponse(['process' => true, 'messages' => 'updated', 'data' => $category], Response::HTTP_OK);
        } catch (\Throwable $throwable) {
            $logger->error($throwable->getMessage(), [
                "line" => $throwable->getLine(),
                "code" => $throwable->getCode(),
                "file" => $throwable->getFile()
            ]);
            return new JsonResponse(['process' => false, 'messages' => 'Unknown error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}