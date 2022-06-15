<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\StockStatus;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
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
            $query = $entityManager->getRepository(Product::class)
                ->createQueryBuilder('products')
                ->leftJoin('products.category', 'categories');
            if (isset($values->name)) {
                $query->where('products.name LIKE CONCAT(\'%\', :name, \'%\')')
                    ->setParameter('name', $values->name);
            }
            if (isset($values->productCode)) {
                $query->andWhere('products.id = :productCode')
                    ->setParameter('productCode', $values->productCode);
            }
            if (isset($values->categoryId)) {
                $query->andWhere('categories.id = :categoryId')
                    ->setParameter('categoryId', $values->categoryId);
            }
            if (isset($values->id)) {
                $query->andWhere('products.id = :id')
                    ->setParameter('id', $values->id);
            }
            $query->orderBy('products.name', 'ASC');
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
            //@todo: transaction olması gerekiyor
            $values = json_decode($request->getContent());
            if (!isset($values->name)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "name" is not valid.'], Response::HTTP_BAD_REQUEST);
            }
            if (!isset($values->categoryId)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "category" is not valid.'], Response::HTTP_BAD_REQUEST);
            }
            if (!isset($values->productCode)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "productCode" is not valid.'], Response::HTTP_BAD_REQUEST);
            }
            if (!isset($values->price)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "price" is not valid.'], Response::HTTP_BAD_REQUEST);
            }
            if (!isset($values->profit)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "profit" is not valid.'], Response::HTTP_BAD_REQUEST);
            }

            $entityManagerCategory = $this->doctrine->getManager();
            $category = $entityManagerCategory->getRepository(Category::class)->find($values->categoryId);
            if (!$category) {
                return new JsonResponse(['process' => false, 'messages' => 'No category found for id ' . $values->categoryId], Response::HTTP_NOT_FOUND);
            }

            $product = new Product();
            $product->setName($values->name);
            $product->setCategory($category);
            $product->setProductCode($values->productCode);
            $product->setPrice($values->price);
            $product->setProfit($values->profit);
            $product->setContent($values->content);

            $errors = $validator->validate($product);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return new JsonResponse(['process' => false, 'messages' => $messages], Response::HTTP_BAD_REQUEST);
            }

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($product);
            $entityManager->flush();

            $entityManager = $this->doctrine->getManager();
            $stockStatus = new StockStatus();
            $stockStatus->setProduct($product);
            $stockStatus->setTotal(0);
            $entityManager->persist($stockStatus);
            $entityManager->flush();

            return new JsonResponse(['process' => true, 'messages' => 'created', 'data' => $product], Response::HTTP_OK);

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
            $product = $entityManager->getRepository(Product::class)->find($id);
            if (!$product) {
                return new JsonResponse(['process' => false, 'messages' => 'No product found for id ' . $id], Response::HTTP_NOT_FOUND);
            }
            $values = json_decode($request->getContent());
            if (isset($values->name)) {
                $product->setName($values->name);
            }
            if (isset($values->categoryId)) {
                $entityManagerCategory = $this->doctrine->getManager();
                $category = $entityManagerCategory->getRepository(Category::class)->find($values->categoryId);
                if (!$category) {
                    return new JsonResponse(['process' => false, 'messages' => 'No category found for id ' . $values->categoryId], Response::HTTP_NOT_FOUND);
                }
                $product->setCategory($category);;
            }
            if (isset($values->productCode)) {
                $product->setName($values->productCode);
            }
            if (isset($values->price)) {
                $product->setPrice($values->price);
            }
            if (isset($values->profit)) {
                $product->setProfit($values->profit);
            }
            if (isset($values->content)) {
                $product->setContent($values->content);
            }
            $errors = $validator->validate($product);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return new JsonResponse(['process' => false, 'messages' => $messages], Response::HTTP_BAD_REQUEST);
            }
            $entityManager->flush();

            return new JsonResponse(['process' => true, 'messages' => 'updated', 'data' => $product], Response::HTTP_OK);
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
