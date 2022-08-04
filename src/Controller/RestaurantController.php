<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartLine;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\Menu;
use App\Entity\SubMenu;
use App\Entity\Variant;
use App\Form\EditCoverImageType;
use App\Form\EditMenuType;
use App\Form\EditProfileImageType;
use App\Form\EditStoreInfoType;
use App\Form\MenuType;
use App\Form\Model\CustomAssertImage;
use App\Form\SubMenuType;
use App\Form\VariantType;
use App\Repository\OrderRepository;
use App\Repository\VariantRepository;
use App\Service\ImageManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use mysql_xdevapi\Exception;
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
 * Class RestaurantController.
 *
 * @Route("/restaurant")
 */

class RestaurantController extends AbstractController
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
     * @var ArrayCollection
     */
    private $formArray;

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * RestaurantController constructor.
     * @param Security $security
     * @param SessionInterface $session
     * @param EntityManagerInterface $manager
     */
    public function __construct(Security $security,SessionInterface $session,EntityManagerInterface $manager,ImageManager $imageManager) {
        $this->security = $security;
        $this->session = $session;
        $this->manager = $manager;
        $this->imageManager = $imageManager;
        $this->formArray = new ArrayCollection();
    }


    /**
     * @Route("/", name="dashboardRestaurant")
     */
    public function index(): Response
    {
        /**
         * @var User $restaurant
         */
        $restaurant = $this->getUser();

        return $this->render('backOffice/restaurant/index.html.twig',[
            'restaurant'=>$restaurant
        ]);
    }


    //<editor-fold desc="Update Yassine 23/01/2021">

    /**
     * @Route("/profile",name="dashRestauProfile")
     * @param Request $request
     * @return Response
     */
    public function profile(Request $request):Response
    {
        /** @var User $partner */
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

        return $this->render('backOffice/restaurant/account/profile.html.twig',[
            'formProfile'=>$formProfile->createView(),
            'formCover'=>$formCover->createView(),
            'form'=>$form->createView(),
            'partner'=>$partner
        ]);
    }
    //</editor-fold>


    //<editor-fold desc="Menu routes">
    /**
     * @Route("/menus/list", name="listMenu")
     */
    public function listMenu(): Response
    {
        $menus = $this->getDoctrine()->getRepository(Menu::class)->findByUser($this->security->getUser());
        return $this->render('backOffice/restaurant/menu/listMenus.html.twig', [
            'Menus' => $menus,
        ]);
    }

    /**
     * @Route("/menu/new", name="newMenu")
     *
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function newMenu(Request $request,ValidatorInterface $validator): Response
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

        return $this->render('backOffice/restaurant/menu/newMenu.html.twig', [
            'form'=>$form->createView(),
        ]);
    }

    /**
     * @Route("/menu/edit/{id}", name="editMenu")
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

        return $this->render('backOffice/restaurant/menu/editMenu.html.twig', [
            'form'=>$form->createView(),
            'menu'=>$menu
        ]);
    }

    /**
     * @Route("/nemu/delete/{id}" , name="deleteMenu")
     *
     * @param Menu $menu
     *
     * @return RedirectResponse
     */
    public  function deleteMenu(Menu $menu)
    {
        $this->imageManager->deleteMenuImages($menu);
        $this->manager->remove($menu);
        $this->manager->flush();

        return $this->redirectToRoute("listMenu");
    }

    //</editor-fold>

    //<editor-fold desc="Sub Menu">
    /**
     * @Route("/sub_menu/list", name="listSubMenu")
     */
    public function listSubMenu(): Response
    {
        $menus = $this->getDoctrine()->getRepository(Menu::class)->findByUser($this->security->getUser());
        $subMenus = $this->getDoctrine()->getRepository(SubMenu::class)->findByMenu($menus);
        return $this->render('backOffice/restaurant/SubMenu/listSubMenus.html.twig', [
            'SubMenus' => $subMenus,
        ]);
    }

    /**
     * @Route("/sub_menu/new", name="newSubMenu")
     * @param Request $request
     * @return Response
     */
    public function newSubMenu(Request $request): Response
    {

        $subMenu = new SubMenu();
        $form = $this->createForm(SubMenuType::class,$subMenu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            try{
                $brochureFiles = $form->get('brochure')->getData();
                $this->imageManager->addSubMenuImage($brochureFiles,$subMenu);

                $this->manager->persist($subMenu);
                $this->manager->flush();
                $this->addFlash('success','Sub Menu has been added');
            } catch (\Exception $ex) {
                $this->addFlash('error','error');
            }
        }


        return $this->render('backOffice/restaurant/SubMenu/newSubMenu.html.twig', [
            'form'=>$form->createView(),
        ]);
    }

    /**
     * @Route("/sub_menu/edit/{id}", name="editSubMenu")
     * @param SubMenu $subMenu
     * @param Request $request
     * @return Response
     */
    public function editSubMenu(SubMenu $subMenu, Request $request): Response
    {
        $form = $this->createForm(SubMenuType::class,$subMenu);
        $form ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            try {
                $brochureFiles = $form->get('brochure')->getData();
                $this->imageManager->updateSubMenuImage($brochureFiles,$subMenu);

                $this->manager->persist($subMenu);
                $this->manager->flush();
                $this->addFlash('success','Sub Menu has been Edited');

                return  $this->redirectToRoute('listSubMenu');
            } catch (\Exception $ex) {
                $this->addFlash('error','error');
            }
        }

        return $this->render('backOffice/restaurant/SubMenu/editSubMenu.html.twig', [
            'form'=>$form->createView(),
        ]);
    }

    /**
     * @Route("/sub_menu/delete/{id}" , name="deleteSubMenu")
     **
     * @param SubMenu $subMenu
     * @return RedirectResponse
     */
    public  function deleteSubMenu(SubMenu $subMenu)
    {

        $this->imageManager->deleteSubMenuImage($subMenu);
        $this->manager->remove($subMenu);
        $this->manager->flush();

        return $this->redirectToRoute("listSubMenu");
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
            case 'standard':
                $sort = 1;
                break;
            case 'sm':
                $sort = 2;
                break;
            case 'm':
                $sort = 3;
                break;
            case 'l':
                $sort = 4;
                break;
            case 'xl':
                $sort = 5;
                break;
        }

        return $sort;
    }

    /**
     * @Route("/menu/{id}/variant", name="listVariantMenu")
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

        return $this->render('backOffice/restaurant/variant/listVariant.html.twig',[
            'Variants' => $variants,
            'form'=>$form->createView(),
            'variantMenu' => true,
        ]);
    }


    /**
     * @Route("/sub_menu/{id}/variant", name="listVariantSubMenu")
     * @param int $id
     * @param Request $request
     * @return Response
     */
    public function listVariantSubMenu(int $id,Request $request): Response
    {
        $variant = new Variant();
        $subMenu = $this->getDoctrine()->getRepository(SubMenu::class)->find($id);
        $variant->setSubMenu($subMenu);

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
        $variants = $this->getDoctrine()->getRepository(Variant::class)->findBySubMenu($id);

        return $this->render('backOffice/restaurant/variant/listVariant.html.twig',[
            'Variants' => $variants,
            'form'=>$form->createView(),
            'variantSubMenu' => true,
        ]);
    }


    /**
     * @Route("/variant/edit", name="editVariant")
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
     * @Route("/variant/delete/{id}" , name="deleteVariant")
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
     * @Route("/orders/" , name="restaurantOrders")
     */
    public function orders()
    {
        /**
         * @var User $restaurant
         */
        $restaurant = $this->getUser();

        $orders = $this->getDoctrine()->getRepository(Order::class)->findOrderByStore($restaurant);

        return $this->render('backOffice/restaurant/orders/listOrders.html.twig', [
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
     * @Route("/detailOrder", name="detailOrderRestaueant", options={"expose"=true})
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