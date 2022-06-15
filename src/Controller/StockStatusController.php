<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\StockStatus;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StockStatusController extends AbstractController
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
            $query = $entityManager->getRepository(StockStatus::class)
                ->createQueryBuilder('StockStatus')
                ->leftJoin('StockStatus.product', 'product');
            if (isset($values->productId)) {
                $query->andWhere('product.id = :productId')
                    ->setParameter('productId', $values->productId);
            }
            if (isset($values->id)) {
                $query->andWhere('StockStatus.id = :id')
                    ->setParameter('id', $values->id);
            }
            $query->orderBy('StockStatus.id', 'ASC');
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
}
