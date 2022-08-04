<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartLine;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\Menu;
use App\Entity\Variant;
use App\Form\EditCoverImageType;
use App\Form\EditMenuType;
use App\Form\EditProfileImageType;
use App\Form\EditStoreInfoType;
use App\Form\MenuType;
use App\Form\Model\CustomAssertImage;
use App\Form\VariantType;
use App\Repository\OrderRepository;
use App\Repository\VariantRepository;
use App\Service\ImageManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Class partnerController.
 *
 * @Route("/partner")
 */

class PartnerController extends AbstractController
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var EntityManagerInterface
     */
    private $manager;


    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * partnerController constructor.
     * @param Security $security
     * @param SessionInterface $session
     * @param EntityManagerInterface $manager
     * @param ImageManager $imageManager
     */
    public function __construct(Security $security,SessionInterface $session,EntityManagerInterface $manager,ImageManager $imageManager) {
        $this->security = $security;
        $this->session = $session;
        $this->manager = $manager;
        $this->imageManager = $imageManager;
    }

    /**
     * @Route("/", name="dashboardPartner")
     */
    public function index(): Response
    {
        /**
         * @var User $partner
         */
        $partner = $this->getUser();

        return $this->render('backOffice/partner/index.html.twig',[
            'partner'=>$partner
        ]);
    }


    //<editor-fold desc="Code Profile">

    /**
     * @Route("/profile",name="dashPartnerProfile")
     * @param Request $request
     * @return Response
     */
    public function profile(Request $request):Response
    {
        /**
         * @var User $partner
         */
        $partner = $this->getUser();

        $formProfile = $this->createForm(EditProfileImageType::class,$partner);
        $formProfile->handleRequest($request);

        $formCover = $this->createForm(EditCoverImageType::class,$partner);
        $formCover->handleRequest($request);

        $form = $this->createForm(EditStoreInfoType::class,$partner);
        $form->handleRequest($request);

        if ($formProfile->isSubmitted() && $formProfile->isValid()) {
            $profileImage = $formProfile->get('brochureProfile')->getData();
            $this->imageManager->updateProfile($profileImage);
        }
        else if ($formCover->isSubmitted() && $formCover->isValid()) {
            $coverImage = $formCover->get('brochureCover')->getData();
            $this->imageManager->updateCover($coverImage);
        }
        else if ($form->isSubmitted() && $form->isValid()) {

            $this->imageManager->updateGallery($request,$partner);
            $this->manager->persist($partner);
            $this->manager->flush();
            $this->addFlash('success','Your information has been changed successfully');
        }

        return $this->render('backOffice/partner/account/profile.html.twig',[
            'formProfile'=>$formProfile->createView(),
            'formCover'=>$formCover->createView(),
            'form'=>$form->createView(),
            'partner'=>$partner
        ]);
    }
    //</editor-fold>



    //<editor-fold desc="Menu routes">
    /**
     * @Route("/menus/list", name="listMenuPartner")
     */
    public function listMenu(): Response
    {
        $menus = $this->getDoctrine()->getRepository(Menu::class)->findByUser($this->security->getUser());
        return $this->render('backOffice/partner/menu/listMenus.html.twig', [
            'Menus' => $menus,
        ]);
    }


    /**
     * @Route("/menu/new", name="newMenuPartner")
     *
     * @param Request $request
     * @return Response
     */
    public function newMenu(Request $request): Response
    {
        $menu = new Menu();
        $form = $this->createForm(MenuType::class,$menu);
        $form ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $brochureFiles = $form->get('images')->getData();
            $this->imageManager->addMenuImages($brochureFiles,$menu);
            /** @var User $user */
            $user = $this->security->getUser();
            $menu->setUser($user);
            $this->manager->persist($menu);
            $this->manager->flush();
            $this->addFlash('success','Menu has been added');
        }

        return $this->render('backOffice/partner/menu/newMenu.html.twig', [
            'form'=>$form->createView(),
        ]);

    }

    /**
     * @Route("/menu/edit/{id}", name="editMenuPartner")
     *
     * @param Menu $menu
     * @param Request $request
     * @return Response
     */
    public function editMenu(Menu $menu, Request $request): Response
    {
        $form = $this->createForm(EditMenuType::class,$menu);
        $form ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $this->imageManager->updateMenuImages($request,$menu);
            $this->manager->persist($menu);
            $this->manager->flush();
            $this->addFlash('success','Menu has been Edited');
            $this->session->remove('getImages');

        } else {
            $this->session->remove('getImages');
        }

        return $this->render('backOffice/partner/menu/editMenu.html.twig', [
            'form'=>$form->createView(),
            'menu'=>$menu
        ]);
    }

    /**
     * @Route("/nemu/delete/{id}" , name="deleteMenuPartner")
     *
     * @param Menu $menu
     *
     * @return RedirectResponse
     */
    public function deleteMenu(Menu $menu)
    {
        $this->imageManager->deleteMenuImages($menu);
        $this->manager->remove($menu);
        $this->manager->flush();

        return $this->redirectToRoute("listMenuPartner");
    }

    //</editor-fold>


    //<editor-fold desc="Variant">
    /**
     * @param string $size
     * @return int
     */
    private function generateSort(string $size)
    {
        /** @var int $sort */

        switch ($size) {
            case 'u':
                $sort = 1;
                break;
            case 'kg':
                $sort = 2;
                break;
        }

        return $sort;
    }

    /**
     * @Route("/menu/{id}/variant", name="listVariantMenuPartner")
     * @param int $id
     * @param Request $request
     * @return Response
     */
    public function listVariantMenu(int $id,Request $request): Response
    {
        $variant = new Variant();
        $menu = $this->getDoctrine()->getRepository(Menu::class)->find($id);
        $variant->setMenu($menu);

        $form = $this->createForm(VariantType::class,$variant);
        $form ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            if ($form->isSubmitted() && $form->isValid()){
                try {
                    $variant->setSort($this->generateSort($variant->getSize()));
                    $this->manager->persist($variant);
                    $this->manager->flush();
                    $this->addFlash('success','Variant has been added');

                }catch (\Exception $ex){
                    $this->addFlash('error','error');
                }
            }
        }
        $variants = $this->getDoctrine()->getRepository(Variant::class)->findByMenu($id);

        return $this->render('backOffice/partner/variant/listVariant.html.twig',[
            'Variants' => $variants,
            'form'=>$form->createView(),
            'variantMenu' => true,
        ]);
    }

    /**
     * @Route("/variant/edit", name="editVariantPartner")
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param VariantRepository $repository
     * @return Response
     */
    public function editVariant(
        Request $request,
        ValidatorInterface $validator,
        VariantRepository $repository
    ): Response {
        if ($request->isMethod('POST')) {
            $data = $request->request->get("variant");
            $id = $data['id'];
            /** @var Variant|null $variant */
            $variant = $repository->find($id);
            if (null === $variant) {
                throw $this->createNotFoundException();
            }
            $size = $data['size'];
            $price = floatval($data['price']);
            $variant->setSize($size)
                ->setPrice($price);

            $variant->setSort($this->generateSort($size));
            /** @var ConstraintViolationList $errors */
            $errors = $validator->validate($variant);
            dump($errors);

            if (count($errors) > 0) {
                //... le cas d'erreur
                foreach ($errors as $er)
                    $this->addFlash('errorEdit', $er->getMessage());
            } else {
                $this->manager->persist($variant);
                $this->manager->flush();
                $this->addFlash('successEdit', 'Variant has been edited');
            }

        }

        $ref = $request->headers->get('referer');
        if (!is_string($ref) || $ref) {
            return $this->redirect($ref);
        }
    }

    /**
     * @Route("/variant/delete/{id}" , name="deleteVariantPartner")
     * @param Variant $variant
     * @param Request $request
     * @return RedirectResponse
     */
    public  function deleteVariant(Variant $variant,Request $request)
    {
        $this->manager->remove($variant);
        $this->manager->flush();

        $ref = $request->headers->get('referer');
        if (!is_string($ref) || $ref) {
            return $this->redirect($ref);
        }
    }
    //</editor-fold>

    //<editor-fold desc="Code orders">
    /**
     * @Route("/orders/" , name="partnerOrders")
     */
    public function orders()
    {
        /**
         * @var User $partner
         */
        $partner = $this->getUser();

        $orders = $this->getDoctrine()->getRepository(Order::class)->findOrderByStore($partner);

        return $this->render('backOffice/partner/orders/listOrders.html.twig', [
            'orders'=>$orders
        ]);
    }

    /**
     * @param Cart $cart
     * @return array The list of cities converted to a simple array
     */
    private function convertToArray(Cart $cart)
    {
        $output = [];
        /** @var CartLine $item */
        foreach ($cart->getCartLines() as $item) {
            /** @var Variant $variant */
            $variant = $item->getVariant();

            $menu = '';
            $subMenu = '';
            $image = '';
            if($variant->getMenu() != null){
                $image = "/images/menu/".$variant->getMenu()->getImages()[0];
                $menu = $variant->getMenu()->getName();
            }
            elseif ($variant->getSubMenu() != null)
            {
                $image = "/images/sub_menu/".$variant->getSubMenu()->getImageUrl();
                $menu = $variant->getSubMenu()->getMenu()->getName();
                $subMenu = " | " .$variant->getSubMenu()->getName();
            }


            $output[] = [
                'image' => $image,
                'menu' => $menu,
                'subMenu' => $subMenu,
                'size' => $variant->getSize(),
                'price' => $variant->getPrice(),
                'quantity' => $item->getQuantity(),
                'total' => $item->total(),
            ];
        }

        $output[] = [
            'totals' => $cart->total(),
        ];

        return $output;
    }

    /**
     * @Route("/detailOrder", name="detailOrderPartner", options={"expose"=true})
     *
     * @param Request $request The request instance
     *
     * @param OrderRepository $repository
     * @return JsonResponse A response
     */
    public function chargeOrder(
        Request $request,
        OrderRepository $repository
    ): JsonResponse {
        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod('post')) {
                $id = $request->request->get('order');
                $order = $repository->find(intval($id));
                $carts = $order->getCarts();

                /** @var Cart $cart */
                foreach($carts as $item)
                {
                    if($item->getStore() == $this->getUser()){
                        $cart = $item;
                        break;
                    }
                }

                return new JsonResponse($this->convertToArray($cart));
            }
        }

        // Simply throw a 404 error.
        throw $this->createNotFoundException('Page not found');
    }
    //</editor-fold>

}