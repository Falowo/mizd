<?php


namespace App\Service\Cart;

use App\Entity\Country;
use App\Entity\DeliveryFees;
use App\Entity\Product;
use App\Entity\Purchase;
use App\Entity\PurchaseLine;
use App\Entity\Continent;
use App\Entity\Image;
use App\Entity\User;
use App\Repository\ContinentRepository;
use App\Repository\DeliveryFeesRepository;
use App\Repository\ImageRepository;
use App\Repository\NgCityRepository;
use App\Repository\NgStateRepository;
use App\Repository\PurchaseRepository;
use App\Repository\StockRepository;
use App\Repository\TransportRepository;
use App\Service\Locale\LocaleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    
    private $session;
    private $purchaseRepository;
    private $deliveryFeesRepository;
    private $localeService;
    private $transportRepository;
    private $stockRepository;
    private $imageRepository;
    private $ngCityRepository;
    private $ngStateRepository;
    private $continentRepository;
    private $em;

    public function __construct(
        SessionInterface $session,
        PurchaseRepository $purchaseRepository,
        LocaleService $localeService,
        DeliveryFeesRepository $deliveryFeesRepository,
        TransportRepository $transportRepository,
        StockRepository $stockRepository,
        ImageRepository $imageRepository,
        NgCityRepository $ngCityRepository,
        NgStateRepository $ngStateRepository,
        ContinentRepository $continentRepository,
        EntityManagerInterface $em

    ) {
        $this->session = $session;
        $this->purchaseRepository = $purchaseRepository;
        $this->localeService = $localeService;
        $this->deliveryFeesRepository = $deliveryFeesRepository;
        $this->transportRepository = $transportRepository;
        $this->stockRepository = $stockRepository;
        $this->imageRepository = $imageRepository;
        $this->ngCityRepository = $ngCityRepository;
        $this->ngStateRepository = $ngStateRepository;
        $this->continentRepository = $continentRepository;
        $this->em = $em;
    }


    public function add(PurchaseLine $purchaseLine)
    {
        $stock = $this->stockRepository->findOneBy([
            'product' => $purchaseLine->getProduct(),
            'size' => $purchaseLine->getSize(),
            'tint' => $purchaseLine->getTint()
        ]);
        $purchaseLine->setStock($stock);
        $purchase = $this->getPurchaseOrCreate();

        $purchase->addPurchaseLine($purchaseLine);
        $this->em->persist($purchase);
        $this->em->flush();

    }



    public function getTotalPurchaseLines(Purchase $purchase): float
    {
        $total = 0;

        foreach ($purchase->getPurchaseLines() as $purchaseLine) {
            /**
             * @var Product $product
             */
            $product = $purchaseLine->getProduct();
            if (!empty($product->getDiscountPrice())) {
                $total += $product->getDiscountPrice() * $purchaseLine->getQuantity();
            } else {
                $total += $product->getPrice() * $purchaseLine->getQuantity();
            }
        }
        return $total;
    }

    public function getTotalPurchase(Purchase $purchase): int
    {
            return $this->getTotalPurchaseLines($purchase) + $this->getDeliveryPrice($purchase->getDeliveryFees(), $purchase);
    }




    /**
     * @return Purchase
     */
    public function getPurchaseOrCreate(): Purchase
    {
        $purchaseId = $this->session->get('purchaseId', []);
        if ($purchaseId === []) {
            $purchase = new Purchase();
            $this->em->persist($purchase);
            $this->em->flush();
            $this->session->set('purchaseId', $purchase->getId());
        } else {
            $purchase = $this->purchaseRepository->find($purchaseId);
            if (is_null($purchase)) {
                $purchase = new Purchase();
                $this->em->persist($purchase);
                $this->em->flush();
                $this->session->set('purchaseId', $purchase->getId());
            }
        }
        return $purchase;
    }

    /**
     * Undocumented function
     *
     * @param User $user
     * @return void
     */
    public function getLastNotPaidPurchase(User $user)
    {
        if(!($purchase = $this->getPurchase())){

            $purchases = $user->getPurchases();
            if(count($purchases) > 0){
                $notPaidPurchases = [];
                foreach($purchases as $purchase){
                    
                    if (!($purchase->getPaid())){
                            $notPaidPurchases[] = $purchase;                            
                        }                        
                    }

                    foreach($notPaidPurchases as $notPaidPurchase){
                        
                        if($notPaidPurchase === end($notPaidPurchases)){
                            $this->setPurchaseInSession($notPaidPurchase);
                        }else{
                            $this->em -> remove($notPaidPurchase);
                            $this->em->flush();
                        }
                    }
            }
        }
    }

    /**
     *
     * @param Purchase $purchase
     * @return Purchase
     */
    public function setPurchaseInSession(Purchase $purchase): Purchase
    {
        $this->session->set('purchaseId', $purchase->getId());
        return $purchase;
    }



    /**
     * @return Purchase|null
     */
    public function getPurchase(): ?Purchase
    {
        if ($id = $this->session->get('purchaseId', [])) {
            return $this->purchaseRepository->find($id);
        } else {
            return null;
        }
    }


    public function getDeliveryFeess(Purchase $purchase)
    {
        /**
         * @var Country $country
         */
        $country = $purchase->getAddress()->getCountry();
       
        if ($country && $country->getCode() === 'NG') {
            $ngCityName = $purchase->getAddress()->getCityName();
            $ngCityNames = array_column(LocaleService::getArrayNgCities(), 'city');
            $ngCityJsonId = array_search($ngCityName, $ngCityNames);

            $ngCity = $this->ngCityRepository->findOneBy(['jsonId' => $ngCityJsonId]);



            if ($deliveryFeess = $this->deliveryFeesRepository->findBy(['ngCity' => $ngCity->getId()])) {
                return $deliveryFeess;
            } else {

                $ngStateName = $ngCity->getNgCityItem()['admin'];

                $ngStateJsonId = array_search($ngStateName, LocaleService::getNgStateConst());

                $ngState = $this->ngStateRepository->findOneBy(['jsonId' => $ngStateJsonId]);

                if ($deliveryFeess = $this->deliveryFeesRepository->findBy(['ngState' => $ngState->getId()])) {
                    return $deliveryFeess;
                } else {
                    $deliveryFeess = $this->getCountryDeliveryFeess($country);
                }
            }
        } else
            $deliveryFeess = $this->getCountryDeliveryFeess($country);
        return $deliveryFeess;
    }


    public function getCountryDeliveryFeess(Country $country): array
    {
        if ($deliveryFeess = $this->deliveryFeesRepository->findBy(['country' => $country->getId()])) {
            return $deliveryFeess;
        } else {
            $continentCode = $country->getCountryItem()['continent'];
            $continent = $this->continentRepository->findOneBy(['code' => $continentCode]);
            if ($deliveryFeess = $this->deliveryFeesRepository->findBy(['continent' => $continent->getId()])) {
               return $deliveryFeess;
            } else {
                $deliveryFeess = $this->getContinentDeliveryFeess($country);
            }
        }
        return $deliveryFeess;
    }

    public function getContinentDeliveryFeess(Country $country): ?array
    {
        $continentCode = $country->getCountryItem()['continent'];
        /**
         * @var Continent $continent
         */
        $continent = $this->continentRepository->findOneBy(['code' => $continentCode]);
        if ($deliveryFeess = $this->deliveryFeesRepository->findBy(['continent' => $continent->getId()])) {
            return $deliveryFeess;
        } else {
            $deliveryFeess = [];
            $transports =  $this->transportRepository->findAll();

            foreach ($transports as $transport) {
                $deliveryFees = new DeliveryFees;
                $deliveryFees
                    ->setTransport($transport)
                    ->setContinent($continent)
                    ->setAmountByKm($transport->getDefaultAmountByKm());

                $this->em->persist($deliveryFees);
            }
            $this->em->flush();
        }
        return $deliveryFeess;
    }


    /**
     * return the price of the delivery
     *
     * @param DeliveryFees $deliveryFees
     * @param Purchase $purchase
     * @param CartService $cartService
     * @return integer
     */
    public function getDeliveryPrice(
        ?DeliveryFees $deliveryFees,
        Purchase $purchase
    ): int
    {
        $price = 0;
        if($deliveryFees){

            if ($freeForMoreThan = $deliveryFees->getFreeForMoreThan()) {
                if ($this->getTotalPurchaseLines($purchase) > $freeForMoreThan) {
                    return 0;
                }
            }
            if ($fixedAmount = $deliveryFees->getFixedAmount()) {
                $price += $fixedAmount;
            }
            if ($amountByKm = $deliveryFees->getAmountByKm()) {
                $price += $amountByKm * $this->localeService->getDistanceFromIlobuInKm($purchase->getAddress());
            }
            if ($percentOfRawPrice = $deliveryFees->getPercentOfRawPrice()) {
                $price += $percentOfRawPrice * $this->getTotalPurchaseLines($purchase) / 100;
            }
        }
        return $price;
    }


    public function setImages(Purchase $purchase)
    {
        foreach ($purchase->getPurchaseLines() as $purchaseLine) {
            if(!($purchaseLine->getImage())){
                if ($image = $this->imageRepository->findOneBy([
                    'product' => $purchaseLine->getProduct(),
                    'tint' => $purchaseLine->getTint()
                ])) {
                   
                } 
                elseif($name= $purchaseLine->getProduct()->getMainImage()){
                    if($image = $this->imageRepository->findOneBy([
                        'name'=>$name,
                        'product'=>$purchaseLine->getProduct()
                        ])
                    ){}
                    else{
                        $image = new Image();
                        $image
                        ->setProduct($purchaseLine->getProduct())
                        ->setName($name)
                        ;
                    }
                }
                
                
                elseif (
                    $image = $this->imageRepository->findOneBy([
                        'product' => $purchaseLine->getProduct()
                    ]))
                {}

                else{
                    $image = null;
                }

                
                $purchaseLine->setImage($image);
                $this->em->persist($purchaseLine);
                $this->em->flush();
            }
        }
    }

    public function getMaxDays(Purchase $purchase)
    {
        if($deliveryFees = $purchase->getDeliveryFees()){
            if($maxDays = $deliveryFees->getMaxDays()){
                return $maxDays;
            }
            else {
                return $deliveryFees->getTransport()->getMaxDaysByKm() * $this->localeService->getDistanceFromIlobuInKm($purchase->getAddress());

            } 
        }else{
            return null;
        }
    }

    public function getPurchaseLinePrice(PurchaseLine $purchaseLine)
    {
        $product = $purchaseLine->getProduct();
        if (!empty($product->getDiscountPrice())) {
            $price = $product->getDiscountPrice() * $purchaseLine->getQuantity();
        } else {
            $price = $product->getPrice() * $purchaseLine->getQuantity();
        }
    return $price;
    }
}
