<?php

namespace App\Controller;


use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\PurchaseRepository;
use App\Service\Cart\CartService;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class IndexController extends AbstractController
{
    /**
     * @Route("/", name="app_index")
     * @param ProductRepository $repository
     * @param PurchaseRepository $purchaseRepository
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @return Response
     */
    public function index(
        ProductRepository $repository,
        MailerService $mailerService,
        PaginatorInterface $paginator,
        CartService $cartService,
        Request $request
    ): Response {
        
        if($user = $this->getUser()){

            if( !($user->getConfirmedEmail()) ){               
                $authenticate = $this->get('security.csrf.token_manager')->getToken('authenticate');
                $mailerService->sendSignUpEmail($user, $authenticate);
                return $this->render('index/confirm_your_email.html.twig', [
                    'controller_name' => 'IndexController'        
                ]);
            }

           $cartService->getLastNotPaidPurchase($user);
        }
       
        $products = $paginator->paginate(
            $repository->findAllByRandomQuery(null), /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            12 /*limit per page*/
        );

        return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
            'products' => $products,

        ]);
    }

    /**
     * Undocumented function
     *
     * @param CategoryRepository $repository
     *
     * @return void
     */
    public function menu(CategoryRepository $repository)
    {
        $ancestorCategories  = $repository->findAncestorCategories();
        return $this->render(
            'index/menu.html.twig',
            [
                'ancestorCategories' => $ancestorCategories,
            ]
        );
    }
    
    public function user()
    {       
        return $this->render('index/user.html.twig');
    }

    public function whatsappLink()
    {
        $android = stripos($_SERVER['HTTP_USER_AGENT'], "android");
        $iphone = stripos($_SERVER['HTTP_USER_AGENT'], "iphone");
        $ipad = stripos($_SERVER['HTTP_USER_AGENT'], "ipad");

        $whatsappNumber = '+2348144337778';
        $whatsappLink = '';
        if($android !== false || $ipad !== false || $iphone !== false) {//For mobile
            $whatsappLink = 'https://api.whatsapp.com/send?phone='.$whatsappNumber;
        } else {//For desktop
            $whatsappLink = 'https://web.whatsapp.com/send?phone='.$whatsappNumber;
        }
    
    return $this->render('index/whatsapp-link.html.twig', [
        
        'whatsappLink' => $whatsappLink,

    ]);
}
}