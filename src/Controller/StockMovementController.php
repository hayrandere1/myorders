<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\StockMovement;
use App\Entity\StockStatus;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StockMovementController extends AbstractController
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function index(Request $request, LoggerInterface $logger): JsonResponse
    {
        try {
            if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                return new JsonResponse(['process' => false, 'messages' => 'Only for admins'], Response::HTTP_UNAUTHORIZED);
            }

            $entityManager = $this->doctrine->getManager();
            $values = json_decode($request->getContent());
            $query = $entityManager->getRepository(StockMovement::class)
                ->createQueryBuilder('stock_movements')
                ->leftJoin('stock_movements.product', 'products')
                ->leftJoin('stock_movements.user', 'users');

            if (isset($values->productId)) {
                $query->andWhere('products.id = :productId')
                    ->setParameter('productId', $values->productId);
            }
            if (isset($values->userId)) {
                $query->andWhere('users.id = :userId')
                    ->setParameter('userId', $values->userId);
            }
            if (isset($values->id)) {
                $query->andWhere('stock_movements.id = :id')
                    ->setParameter('id', $values->id);
            }
            $query->orderBy('products.name', 'ASC');
            $stockMovements = $query->getQuery()->getArrayResult();

            return new JsonResponse(['process' => true, 'datas' => json_encode($stockMovements)], Response::HTTP_OK);
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

            if (!isset($values->productId)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "productId" is not valid.'], Response::HTTP_BAD_REQUEST);
            }
            if (!isset($values->value) || $values->value <= 0) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "value" is not valid.'], Response::HTTP_BAD_REQUEST);
            }

            $entityManagerCategory = $this->doctrine->getManager();
            $product = $entityManagerCategory->getRepository(Product::class)->find($values->productId);
            if (!$product) {
                return new JsonResponse(['process' => false, 'messages' => 'No product found for id ' . $values->productId], Response::HTTP_NOT_FOUND);
            }

            $stockMovement = new StockMovement();
            $stockMovement->setProduct($product);
            $stockMovement->setUser($this->getUser());
            $stockMovement->setValue($values->value);

            $errors = $validator->validate($stockMovement);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return new JsonResponse(['process' => false, 'messages' => $messages], Response::HTTP_BAD_REQUEST);
            }

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($stockMovement);
            $entityManager->flush();

            $entityManager = $this->doctrine->getManager();
            $stockStatus = $product->getStockStatus()[0];
            $stockStatus->setProduct($product);
            $stockStatus->setTotal($stockStatus->getTotal() + $values->value);
            $entityManager->persist($stockStatus);
            $entityManager->flush();

            return new JsonResponse(['process' => true, 'messages' => 'created', 'data' => $stockMovement], Response::HTTP_OK);

        } catch (\Throwable $throwable) {
            $logger->error($throwable->getMessage(), [
                "line" => $throwable->getLine(),
                "code" => $throwable->getCode(),
                "file" => $throwable->getFile()
            ]);
            return new JsonResponse(['process' => false, 'messages' => 'Unknown error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id,Request $request, ValidatorInterface $validator, LoggerInterface $logger): JsonResponse
    {
        try {
            if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                return new JsonResponse(['process' => false, 'messages' => 'Only for admins'], Response::HTTP_UNAUTHORIZED);
            }

            $entityManager = $this->doctrine->getManager();
            $stockMovement = $entityManager->getRepository(StockMovement::class)->find($id);
            if (!$stockMovement) {
                return new JsonResponse(['process' => false, 'messages' => 'No stock movement found for id ' . $id], Response::HTTP_NOT_FOUND);
            }
            //@todo: transaction olması gerekiyor
            $entityManagerProduct = $this->doctrine->getManager();
            $product = $entityManagerProduct->getRepository(Product::class)->find($stockMovement->getProduct());

            if (!$product) {
                return new JsonResponse(['process' => false, 'messages' => 'No product found for id ' . $stockMovement->productId], Response::HTTP_NOT_FOUND);
            }

            $errors = $validator->validate($stockMovement);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return new JsonResponse(['process' => false, 'messages' => $messages], Response::HTTP_BAD_REQUEST);
            }

            $value=$stockMovement->getValue();
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($stockMovement);
            $entityManager->flush();

            $entityManager = $this->doctrine->getManager();
            $stockStatus = $product->getStockStatus()[0];
            $stockStatus->setProduct($product);
            $stockStatus->setTotal($stockStatus->getTotal() - $value);
            $entityManager->persist($stockStatus);
            $entityManager->flush();

            return new JsonResponse(['process' => true, 'messages' => 'deleted', 'data' => $stockMovement], Response::HTTP_OK);

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
