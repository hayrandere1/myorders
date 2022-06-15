<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\StockMovement;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderController extends AbstractController
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
            $query = $entityManager->getRepository(Order::class)
                ->createQueryBuilder('orders')
                ->leftJoin('orders.product', 'products');
            if (isset($values->oderCode)) {
                $query->where('orders.oderCode = :oderCode')
                    ->setParameter('oderCode', $values->oderCode);
            }
            if (isset($values->productId)) {
                $query->andWhere('products.id = :productId')
                    ->setParameter('productId', $values->productId);
            }
            if (isset($values->id)) {
                $query->andWhere('orders.id = :id')
                    ->setParameter('id', $values->id);
            }
            $query->orderBy('orders.id', 'ASC');
            $orders = $query->getQuery()->getArrayResult();

            return new JsonResponse(['process' => true, 'datas' => json_encode($orders)], Response::HTTP_OK);
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
        if (!in_array('ROLE_USER', $this->getUser()->getRoles())) {
            return new JsonResponse(['process' => false, 'messages' => 'Only for users'], Response::HTTP_UNAUTHORIZED);
        }
        //@todo: transaction olması gerekiyor
        try {
            $values = json_decode($request->getContent());
            if (!isset($values->productId)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "product" is not valid.'], Response::HTTP_BAD_REQUEST);
            }
            if (!isset($values->orderCode)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "orderCode" is not valid.'], Response::HTTP_BAD_REQUEST);
            }
            if (!isset($values->orderStatus)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "orderStatus" is not valid.'], Response::HTTP_BAD_REQUEST);
            }
            if (!isset($values->quantity) || $values->quantity <= 0) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "quantity" is not valid.'], Response::HTTP_BAD_REQUEST);
            }
            if (!isset($values->address)) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "address" is not valid.'], Response::HTTP_BAD_REQUEST);
            }

            if (!in_array($values->orderStatus, ['waiting', 'preparing', 'shipped', 'delivered', 'canceled'])) {
                return new JsonResponse(['process' => false, 'messages' => 'Unknown "orderStatus".'], Response::HTTP_BAD_REQUEST);
            }
            $entityManagerCategory = $this->doctrine->getManager();
            $product = $entityManagerCategory->getRepository(Product::class)->find($values->productId);
            if (!$product) {
                return new JsonResponse(['process' => false, 'messages' => 'No category found for id ' . $values->categoryId], Response::HTTP_NOT_FOUND);
            }

            $stockStatus = $product->getStockStatus()[0];
            if (!isset($stockStatus) || $stockStatus->getTotal() < $values->quantity) {
                return new JsonResponse(['process' => false, 'messages' => 'Out of stock.'], Response::HTTP_BAD_REQUEST);
            }

            $order = new Order();
            $order->setUser($this->getUser());
            $order->setProduct($product);
            $order->setOrderCode($values->orderCode);
            $order->setOrderStatus($values->orderStatus);
            $order->setQuantity($values->quantity);
            $order->setAddress($values->address);

            $errors = $validator->validate($order);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return new JsonResponse(['process' => false, 'messages' => $messages], Response::HTTP_BAD_REQUEST);
            }

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($order);
            $entityManager->flush();

            $entityManager = $this->doctrine->getManager();
            $stockStatus->setTotal($stockStatus->getTotal() - $values->quantity);
            $entityManager->persist($stockStatus);
            $entityManager->flush();

            $entityManager = $this->doctrine->getManager();
            $stockMovement = new StockMovement();
            $stockMovement->setProduct($product);
            $stockMovement->setUser($this->getUser());
            $stockMovement->setValue(-$values->quantity);
            $entityManager->persist($stockMovement);
            $entityManager->flush();

            return new JsonResponse(['process' => true, 'messages' => 'created', 'data' => $order], Response::HTTP_OK);

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
            $entityManager = $this->doctrine->getManager();
            $order = $entityManager->getRepository(Order::class)->find($id);
            if (!$order) {
                return new JsonResponse(['process' => false, 'messages' => 'No order found for id ' . $id], Response::HTTP_NOT_FOUND);
            }
            if (!empty($order->getShippingDate())) {
                return new JsonResponse(['process' => false, 'messages' => 'Cannot be updated.'], Response::HTTP_BAD_REQUEST);
            }
            $oldQuantity = $order->getQuantity();
            $values = json_decode($request->getContent());
            if (isset($values->quantity) && $values->quantity <= 0) {
                return new JsonResponse(['process' => false, 'messages' => 'The value "quantity" is not valid.'], Response::HTTP_BAD_REQUEST);
            }
            if (isset($values->productId)) {
                $entityManagerProduct = $this->doctrine->getManager();
                $product = $entityManagerProduct->getRepository(Product::class)->find($values->productId);
                if (!$product) {
                    return new JsonResponse(['process' => false, 'messages' => 'No product found for id ' . $values->productId], Response::HTTP_NOT_FOUND);
                }
                $order->setProduct($product);;
            }
            $stockStatus = $order->getProduct()->getStockStatus();

            if (isset($values->quantity) && (!isset($stockStatus) || $stockStatus->getTotal() < ($values->quantity - $oldQuantity))) {
                return new JsonResponse(['process' => false, 'messages' => 'Out of stock.'], Response::HTTP_BAD_REQUEST);
            }

            if (isset($values->orderCode)) {
                $order->setOrderCode($values->orderCode);
            }
            if (isset($values->orderStatus)) {
                $order->setOrderStatus($values->orderStatus);
                if ($values->orderStatus == 'delivered') {
                    $order->setShippingDate(new \DateTime('@' . strtotime('now')));
                }
            }
            if (isset($values->quantity)) {
                $order->setQuantity($values->quantity);
            }
            if (isset($values->address)) {
                $order->setAddress($values->address);
            }
            if (isset($values->shippingDate)) {
                $order->setShippingDate($values->shippingDate);
            }

            $errors = $validator->validate($order);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return new JsonResponse(['process' => false, 'messages' => $messages], Response::HTTP_BAD_REQUEST);
            }
            $entityManager->flush();

            if (isset($values->quantity)) {
                $entityManager = $this->doctrine->getManager();
                $stockStatus->setTotal($stockStatus->getTotal() - ($values->quantity - $oldQuantity));
                $entityManager->persist($stockStatus);
                $entityManager->flush();

                $entityManager = $this->doctrine->getManager();
                $stockMovement = new StockMovement();
                $stockMovement->setProduct($product);
                $stockMovement->setUser($this->getUser());
                $stockMovement->setValue(($oldQuantity - $values->quantity));
                $entityManager->persist($stockMovement);
                $entityManager->flush();
            }

            return new JsonResponse(['process' => true, 'messages' => 'updated', 'data' => $order], Response::HTTP_OK);
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
