<?php


namespace App\Service\Filter;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Stock;
use App\Repository\StockRepository;
use App\Repository\ProductRepository;
use App\Repository\SizeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class FilterService
{
    private $productRepository;
    private $em;
    private $paginator;

    public function __construct(
        StockRepository $stockRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $em,
        PaginatorInterface $paginator

    ) {
        $this->stockRepository = $stockRepository;
        $this->productRepository = $productRepository;
        $this->em = $em;
        $this->paginator = $paginator;
    }

    public function setNgetAllSizesByCategory(Category $category)
    {
        if ($sizes = $category->getSizes()) {
            foreach ($sizes as $size) {
                $category->removeSize($size);
            }
        }
        $products = $this->productRepository
            ->findAllByCategory($category->getId());
        foreach ($products as $product) {
            if ($stocks = $product->getStocks()) {
                foreach ($stocks as $stock) {
                    $category->addSize($stock->getSize());
                }
            }
        }
        $this->em->persist($category);
        $this->em->flush();
        return $category->getSizes();
    }

    public function paginate($id, $order = ['p.id', 'DESC'], Request $request)
    {


        switch ($order) {
            case 'last products':
                $order = ['p.id', 'DESC'];
                break;
            case 'discounts':
                $order = ['p.discountPrice', 'DESC'];
                break;
            case 'crescent price':
                $order = ['p.price', 'ASC'];
                break;
            case 'decrescent price':
                $order = ['p.price', 'DESC'];
                break;
            default:
                $order = ['p.id', 'DESC'];
                break;
        }

        return $this->paginator->paginate(
            $this->productRepository->findAllByCategoryQuery($id, $order), /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            12 /*limit per page*/
        );
    }
}
