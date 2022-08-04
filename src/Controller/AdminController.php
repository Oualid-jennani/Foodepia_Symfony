<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartLine;
use App\Entity\City;
use App\Entity\Country;
use App\Entity\MenuCategory;
use App\Entity\Order;
use App\Entity\Package;
use App\Entity\Province;
use App\Entity\ServiceType;
use App\Entity\SponsoredRestaurant;
use App\Entity\StatusHistory;
use App\Entity\SubMenu;
use App\Entity\User;
use App\Entity\Variant;
use App\Form\AdminType;
use App\Form\ChoseDriverType;
use App\Form\CountryType;
use App\Form\CityType;
use App\Form\EditCityType;
use App\Form\EditSecondAdminInformationsType;
use App\Form\EditUserPasswordType;
use App\Form\MenuCategoryType;
use App\Form\Model\SearchByCity;
use App\Form\Model\SearchByCityOrCountry;
use App\Form\NewSponsoredRestaurantType;
use App\Form\ProvinceType;
use App\Form\SearchByCityOrCountryType;
use App\Form\SearchByCityType;
use App\Form\SecondAdminType;
use App\Form\ServiceFormType;
use App\Form\PackageType;
use App\Repository\OrderRepository;
use App\Repository\ProvinceRepository;
use App\Service\Statistics;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use mysql_xdevapi\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class AdminController
 * @package App\Controller
 * @Route("/admin")
 */

class AdminController extends AbstractController
{
    private $security;
    private $session;
    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * AdminController constructor.
     * @param Security              $security
     * @param SessionInterface      $session
     * @param EntityManagerInterface $manager
     */
    public function __construct(Security $security, SessionInterface $session, EntityManagerInterface $manager){
        $this->security = $security;
        $this->session = $session;
        $this->manager = $manager;
    }

    //<editor-fold desc="Code dashboardAdmin">

    /**
     * @Route("/", name="dashboardAdmin")
     * @param Statistics $statistics
     * @return Response
     */
    public function index(Statistics $statistics): Response
    {


        $stat = [
            'chefCount' => $statistics->getChefsCount(),
            'customerCount' => $statistics->getCustomersCount(),
            'driverCount' => $statistics->getDriversCount(),
            'carterCount' => $statistics->getCartersCount()
        ];

        return $this->render('backOffice/admin/index.html.twig',[
             'statistics'=>$stat
        ]);
    }
    //</editor-fold>

    //<editor-fold desc="Code register and login">
    /**
     * @Route("/login", name="adminLogin")
     */
    public function login(): Response
    {
        return $this->render('backOffice/admin/login.html.twig');
    }

    /**
     * @Route("/register", name="adminRegister")
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function register(Request $request, EntityManagerInterface $manager,UserPasswordEncoderInterface $encoder): Response
    {
        $user = new User();
        $form = $this->createForm(AdminType::class,$user);
        $form ->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()){
                $hash = $encoder->encodePassword($user,$user->getPassword());
                $user->setPassword($hash);
                $user->setRegistrationDate(new DateTime('now'));
                $user->setRoles(['ROLE_ADMIN']);
                $manager->persist($user);
                $manager->flush();

                return $this->redirectToRoute('adminLogin');
            }

        }catch (Exception $ex){
            $this->addFlash('error','error');
        }

        return $this->render('backOffice/admin/signUp.html.twig',[
            'form'=>$form->createView()
        ]);
    }
    //</editor-fold>

    //<editor-fold desc="Code country">
    /**
     * @Route("/countries", name="listCountries")
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function listCounties(Request $request, EntityManagerInterface $manager): Response
    {
        $country = new Country();
        $form = $this->createForm(CountryType::class,$country);
        $form ->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()){
                $manager->persist($country);
                $manager->flush();

                return $this->redirectToRoute("listCountries");
            }
        }catch (Exception $ex){
            $this->addFlash('error','error');
        }

        $Countries = $this->getDoctrine()->getRepository(Country::class)->findAll();

        return $this->render('backOffice/admin/country/listCountries.html.twig', [
            'Countries' => $Countries,
            'form'=>$form->createView(),
        ]);
    }


    /**
     * @Route("/countries/edit/{id}" , name="editCountry")
     * @param Country $country
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public  function editCountry(Country $country,Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(CountryType::class,new Country());
        $form ->handleRequest($request);
        $formEdit = $this->createForm(CountryType::class,$country);
        $formEdit ->handleRequest($request);

        try {
            if ($formEdit->isSubmitted() && $formEdit->isValid()){
                $entityManager->flush();
                return $this->redirectToRoute("listCountries");
            }
        }catch (Exception $ex){
            $this->addFlash('error','error');
        }
        $Countries = $this->getDoctrine()->getRepository(Country::class)->findAll();

        return $this->render('backOffice/admin/country/listCountries.html.twig', [
            'Countries' => $Countries,
            'form'=>$form->createView(),
            'formEdit'=>$formEdit->createView(),
        ]);
    }

    /**
     * @Route("/countries/delete/{id}" , name="deleteCountry")
     * @param Country $country
     * @return RedirectResponse
     */
    public  function deleteCountry(Country $country)
    {
        $entityManager = $this->getDoctrine()->getManager();
       // $country = $entityManager->getRepository(Country::class)->find($id);
        $entityManager->remove($country);
        $entityManager->flush();

        return $this->redirectToRoute("listCountries");
    }
    //</editor-fold>

    //<editor-fold desc="Code Province">
    /**
     * @Route("/provinces", name="admin_provinces")
     * @param Request                   $request
     * @param EntityManagerInterface    $manager
     * @param ProvinceRepository        $repository
     * @return Response
     */
    public function provinces(Request $request, EntityManagerInterface $manager,ProvinceRepository $repository): Response
    {
        $province = new Province();

        $form = $this->createForm(ProvinceType::class,$province);
        $form ->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()){
                $manager->persist($province);
                $manager->flush();

                $this->addFlash('success','Province Added');
            }
        }catch (Exception $ex){
            $this->addFlash('error','Exception Error');
        }

        $provinces = $repository->findAll();

        return $this->render('backOffice/admin/province/provinces.html.twig', [
            'provinces' => $provinces,
            'form'=>$form->createView(),
        ]);
    }

    /**
     * @Route("/provinces/edit/{id}" , name="admin_edit_province")
     * @param Province          $province
     * @param Request           $request
     * @return RedirectResponse|Response
     */
    public  function editProvince(Province $province,Request $request)
    {
        $form = $this->createForm(ProvinceType::class,$province);
        $form ->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()){
                $this->manager->flush();
                $this->addFlash('success','Province Edited');
            }
        }catch (Exception $ex){
            $this->addFlash('error','Exception Error');
        }

        return $this->render('backOffice/admin/province/edit_province.html.twig', [
            'form'=>$form->createView(),
        ]);
    }

    /**
     * @Route("/provinces/delete/{id}" , name="admin_delete_province")
     * @param Province $province
     * @return RedirectResponse
     */
    public  function deleteProvince(Province $province)
    {
        $this->manager->remove($province);
        $this->manager->flush();

        return $this->redirectToRoute("admin_provinces");
    }
    //</editor-fold>

    //<editor-fold desc="Code City">
    /**
     * @Route("/cities", name="listCities")
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function listCities(Request $request, EntityManagerInterface $manager): Response
    {
        $city = new City();
        $form = $this->createForm(CityType::class,$city);
        $form ->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()){
                $manager->persist($city);
                $manager->flush();
                return $this->redirectToRoute("listCities");
            }
        }catch (Exception $ex){
            $this->addFlash('error','error');
        }
        $cities = $this->getDoctrine()->getRepository(City::class)->findAll();

        return $this->render('backOffice/admin/cities/listCities.html.twig', [
            'Cities' => $cities,
            'form'=>$form->createView(),
        ]);
    }

    /**
     * @Route("/cities/edit/{id}" , name="editCity")
     * @param City $city
     * @param Request $request
     * @return Response
     */
    public  function editCity(City $city,Request $request): Response
    {
        $formEdit = $this->createForm(EditCityType::class,$city);
        $formEdit ->handleRequest($request);

        try {
            if ($formEdit->isSubmitted() && $formEdit->isValid()){
                $this->manager->flush();
                $this->addFlash('success','City Edited');
            }
        }catch (Exception $ex){
            $this->addFlash('error','error Excp');
        }

        $Cities = $this->getDoctrine()->getRepository(City::class)->findAll();

        return $this->render('backOffice/admin/cities/edit_city.html.twig', [
            'Cities' => $Cities,
            'form'=>$formEdit->createView(),
        ]);
    }

    /**
     * @Route("/city/delete/{id}" , name="deleteCity")
     * @param City $city
     * @return RedirectResponse
     */
    public  function deleteCity(city $city)
    {
        $entityManager = $this->getDoctrine()->getManager();
        // $country = $entityManager->getRepository($city::class)->find($id);
        $entityManager->remove($city);
        $entityManager->flush();

        return $this->redirectToRoute("listCities");
    }
    //</editor-fold>

    //<editor-fold desc="Code Package">
    /**
     * @Route("/package/list", name="listPackage")
     */
    public function listPackage(): Response
    {
        $packages = $this->getDoctrine()->getRepository(Package::class)->findAll();

        return $this->render('backOffice/admin/package/listPackage.html.twig',[
            'packages'=>$packages
        ]);
    }

    /**
     * @Route("/package/new", name="newPackage")
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function newPackage(Request $request, EntityManagerInterface $manager): Response
    {
        $package = new Package();
        $form = $this->createForm(PackageType::class,$package);
        $form ->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()){
                $manager->persist($package);
                $manager->flush();
                $this->addFlash('success','Package has been added');
            }
        }catch (Exception $ex){
            $this->addFlash('error','error');
        }

        return $this->render('backOffice/admin/package/newPackage.html.twig',[
            'form'=>$form->createView()
        ]);
    }

    /**
     * @Route("/package/edit/{id}", name="editPackage")
     * @param Request $request
     * @param Package $package
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function editPackage(Request $request,Package $package, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(PackageType::class,$package);
        $form ->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()){
                $manager->persist($package);
                $manager->flush();
                $this->addFlash('success','Package has been Edited');
                return $this->redirectToRoute('listPackage');
            }

        }catch (Exception $ex){
            $this->addFlash('error','error');
        }

        return $this->render('backOffice/admin/package/editPackage.html.twig',[
            'form'=>$form->createView()
        ]);
    }

    /**
     * @Route("/packages/delete/{id}" , name="deletePackage")
     * @param Package $package
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public  function deletePackage(Package  $package, EntityManagerInterface $entityManager)
    {
        try{
            $entityManager->remove($package);
            $entityManager->flush();
            $this->addFlash('success','Package Deleted');

        }catch (NotFoundHttpException $exception)
        {
            $this->addFlash('error','Package Not Deleted');
        }

        return $this->redirectToRoute("listPackage");
    }
    //</editor-fold>

    //<editor-fold desc="Code Service Type">
    /**
     * @Route("/serviceType", name="listServiceType")
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function listServicesType(Request $request, EntityManagerInterface $manager): Response
    {
        $serviceType = new ServiceType();
        $form = $this->createForm(ServiceFormType::class,$serviceType);
        $form ->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()){
                $manager->persist($serviceType);
                $manager->flush();

                return $this->redirectToRoute("listServiceType");
            }
        }catch (Exception $ex){
            $this->addFlash('error','error');
        }
        $servicesType = $this->getDoctrine()->getRepository(ServiceType::class)->findAll();

        return $this->render('backOffice/admin/serviceType/listServiceType.html.twig', [
            'ServicesType' => $servicesType,
            'form'=>$form->createView(),
        ]);
    }

    /**
     * @Route("/serviceType/edit/{id}" , name="editServiceType")
     * @param ServiceType $serviceType
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function editServiceType(ServiceType $serviceType,Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(ServiceFormType::class,new ServiceType());
        $form ->handleRequest($request);
        $formEdit = $this->createForm(ServiceFormType::class,$serviceType);
        $formEdit ->handleRequest($request);

        try {
            if ($formEdit->isSubmitted() && $formEdit->isValid()){
                $entityManager->flush();
                return $this->redirectToRoute("listServiceType");
            }
        }catch (Exception $ex){
            $this->addFlash('error','error');
        }
        $servicesType = $this->getDoctrine()->getRepository(ServiceType::class)->findAll();

        return $this->render('backOffice/admin/serviceType/listServiceType.html.twig', [
            'ServicesType' => $servicesType,
            'form'=>$form->createView(),
            'formEdit'=>$formEdit->createView(),
        ]);
    }

    /**
     * @Route("/serviceType/delete/{id}" , name="deleteServiceType")
     * @param ServiceType $serviceType
     * @return RedirectResponse
     */
    public function deleteServiceType(ServiceType $serviceType)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($serviceType);
        $entityManager->flush();

        return $this->redirectToRoute("listServiceType");
    }
    //</editor-fold>

    //<editor-fold desc="Code Menu Category">
    /**
     * @Route("/cuisinies/list", name="listMenuCategory")
     */
    public function listMenuCategory(): Response
    {
        $menusCategory = $this->getDoctrine()->getRepository(MenuCategory::class)->findAll();
        return $this->render('backOffice/admin/menuCategory/listMenuCategories.html.twig', [
            'MenusCategory' => $menusCategory,
        ]);
    }

    /**
     * @Route("/cuisinies/new", name="newMenuCategory")
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function newMenuCategory(Request $request, EntityManagerInterface $manager): Response
    {
        $menuCategory = new MenuCategory();
        $form = $this->createForm(MenuCategoryType::class,$menuCategory);
        $form ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            try {
                /** @var UploadedFile $brochureFile */
                $brochureFile = $form->get('brochure')->getData();
                if ($brochureFile) {
                    $newFilename = uniqid().'.'.$brochureFile->guessExtension();
                    try {
                        $brochureFile->move($this->getParameter('menu_category_directory'),$newFilename);
                    } catch (Exception $ex){
                        $this->addFlash('error','error');
                    }
                    $menuCategory->setImageUrl($newFilename);
                }
                $menuCategory->setCreatedDate(new DateTime('now'));
                $manager->persist($menuCategory);
                $manager->flush();
                $this->addFlash('success','Cuisine has been added');

            }catch (Exception $ex){
                $this->addFlash('error','error');
            }
        }

        return $this->render('backOffice/admin/menuCategory/newMenuCategory.html.twig', [
            'form'=>$form->createView(),
        ]);
    }

    /**
     * @Route("/cuisinies/edit/{id}" , name="editMenuCategory")
     * @param MenuCategory $menuCategory
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return RedirectResponse|Response
     */
    public  function editMenuCategory(MenuCategory $menuCategory,Request $request,EntityManagerInterface $manager){
        $form = $this->createForm(MenuCategoryType::class,$menuCategory);
        $form ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            try {
                /** @var UploadedFile $brochureFile */
                $brochureFile = $form->get('brochure')->getData();
                if ($brochureFile) {
                    $oldFileName = $menuCategory->getImageUrl();
                    $newFilename = uniqid().'.'.$brochureFile->guessExtension();
                    $brochureFile->move($this->getParameter('menu_category_directory'),$newFilename);
                    $file = new Filesystem();

                    if(true === $file->exists("images/menu_category/".$oldFileName))
                    {
                        $file->remove(['images/menu_category/'.$oldFileName]);
                    }

                    $menuCategory->setImageUrl($newFilename);
                }

                $manager->persist($menuCategory);
                $manager->flush();
                $this->addFlash('success','Cuisine has been Edited');

                return $this->redirectToRoute("listMenuCategory");

            }catch (Exception $ex){
                $this->addFlash('error','error');
            }
        }

        return $this->render('backOffice/admin/menuCategory/editMenuCategories.html.twig', [
            'form'=>$form->createView(),
        ]);
    }

    /**
     * @Route("/cuisinies/delete/{id}" , name="deleteMenuCategory")
     * @param MenuCategory $menuCategory
     * @return RedirectResponse
     */
    public function deleteMenuCategory(MenuCategory $menuCategory)
    {
        $oldFileName = $menuCategory->getImageUrl();
        //--------------------------------------------------------------------------
        $file = new Filesystem();
        if(true === $file->exists("images/menu_category/".$oldFileName))
        {
            $file->remove(['images/menu_category/'.$oldFileName]);
        }
        //--------------------------------------------------------------------------

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($menuCategory);
        $entityManager->flush();

        return $this->redirectToRoute("listMenuCategory");
    }
    //</editor-fold>

    //<editor-fold desc="Code Driver">
    /**
     * @Route("/drivers/" , name="dashAdminDrivers")
     * @param Request $request
     *
     * @return Response
     */
    public function drivers(Request $request)
    {
        $data = new SearchByCityOrCountry();
        $form = $this->createForm(SearchByCityOrCountryType::class,$data);
        $form->handleRequest($request);
        $drivers = [];
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    if (null != $data->getCity() && null!= $data->getCountry()){
                        $drivers = $this->getDoctrine()->getRepository(User::class)->findDriversByCity($data->getCity());
                    }elseif (null == $data->getCity() && null != $data->getCountry()){
                        $drivers = $this->getDoctrine()->getRepository(User::class)->findDriversByCountry($data->getCountry());
                    }else{
                        $drivers = $this->getDoctrine()->getRepository(User::class)->findAllDrivers();
                    }
                }catch (\Exception $ex ) {
                    $this->addFlash('error',$ex->getMessage());
                }
            }else{
                $drivers = $this->getDoctrine()->getRepository(User::class)->findAllDrivers();
            }

        }catch (\Exception $exception) {
            $this->addFlash('error',$exception->getMessage());
        }

        return $this->render('backOffice/admin/drivers/drivers.html.twig', [
            'drivers'=>$drivers,
            'form'=>$form->createView()
        ]);
    }
    //</editor-fold>

    //<editor-fold desc="Code Restaurant">
    /**
     * @Route("/restaurants/" , name="adminRestaurants")
     * @param Request $request
     *
     * @return Response
     */
    public function adminRestaurant(Request $request)
    {
        $data = new SearchByCityOrCountry();
        $form = $this->createForm(SearchByCityOrCountryType::class,$data);
        $form->handleRequest($request);
        $restaurants = [];
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    if (null != $data->getCity() && null!= $data->getCountry()){
                        $restaurants = $this->getDoctrine()->getRepository(User::class)->findRestaurantByCity($data->getCity());
                    }elseif (null == $data->getCity() && null != $data->getCountry()){
                        $restaurants = $this->getDoctrine()->getRepository(User::class)->findRestaurantByCountry($data->getCountry());
                    }else{
                        $restaurants = $this->getDoctrine()->getRepository(User::class)->findAllRestaurant();
                    }
                }catch (\Exception $ex ) {
                    $this->addFlash('error',$ex->getMessage());
                }
            }else{
                $restaurants = $this->getDoctrine()->getRepository(User::class)->findAllRestaurant();
            }

        }catch (\Exception $exception) {
            $this->addFlash('error',$exception->getMessage());
        }


        return $this->render('backOffice/admin/restaurant/restaurant.html.twig', [
            'restaurants'=>$restaurants,
            'form'=>$form->createView()
        ]);
    }
    //</editor-fold>

    //<editor-fold desc="Sponsored Restaurant">
    /**
     * @Route("/Sponsored-Restaurant",name="dashAdminSponsoredRestaurantList")
     */
    public function sponsoredRestaurant() {
        $sponsoredRestaurant = $this->getDoctrine()->getRepository(SponsoredRestaurant::class)->findAll();

        return $this->render('backOffice/admin/sponsoredMerchant/sponsoredRestaurant.html.twig',[
            'sponsoredRestaurant'=>$sponsoredRestaurant
        ]);
    }

    /**
     * @Route("/Sponsored-Restaurant/new",name="dashAdminNewSponsoredRestaurant")
     * @param Request $request
     * @param EntityManagerInterface $manager
     *
     * @return Response
     */
    public function newSponsoredRestaurant(Request $request,EntityManagerInterface $manager) {

        $sponsoredRestaurant = new SponsoredRestaurant();
        $form = $this->createForm(NewSponsoredRestaurantType::class,$sponsoredRestaurant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $manager->persist($sponsoredRestaurant);
                $manager->flush();
                $this->addFlash('success','New Sponsored Restaurant has been added');
            }catch (\Exception $ex) {
                $this->addFlash('error',$ex->getMessage());
            }

        }

        return $this->render('backOffice/admin/sponsoredMerchant/newSponsoredRestaurant.html.twig',[
            'form'=>$form->createView()
        ]);
    }
    //</editor-fold>



    //<editor-fold desc="Code orders">
    /**
     * @Route("/orders" , name="adminOrders")
     * @param Request $request
     *
     * @return Response
     */
    public function orders(Request $request)
    {
        $data = new SearchByCityOrCountry();
        $form = $this->createForm(SearchByCityOrCountryType::class,$data);
        $form->handleRequest($request);
        $orders = [];
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                if (null != $data->getCity() && null != $data->getCountry()){
                    $orders = $this->getDoctrine()->getRepository(Order::class)
                                ->findByCity($data->getCity());
                }elseif (null == $data->getCity() && null != $data->getCountry()){
                    $orders = $this->getDoctrine()->getRepository(Order::class)
                                ->findByCountry($data->getCountry());
                }else{
                    $orders = $this->getDoctrine()->getRepository(Order::class)->findAll();
                }
            }else{
                $orders = $this->getDoctrine()->getRepository(Order::class)->findAll();
            }

        }catch (\Exception $exception) {
            $this->addFlash('error',$exception->getMessage());
        }

        return $this->render('backOffice/admin/orders/listOrders.html.twig', [
            'orders'=>$orders,
            'form'=>$form->createView()
        ]);
    }

    /**
     * @Route("/orders/detail/{id}" , name="adminOrderDetail")
     * @param Order $order
     * @return Response
     */
    public function orderDetail(Order $order)
    {
        return $this->render('backOffice/admin/orders/orderDetail.html.twig', [
            'order'=>$order
        ]);
    }

    /**
     * @Route("/order/cancel/{id}" , name="cancelOrder")
     *
     * @param Order $order
     * @param EntityManagerInterface $manager
     * @return RedirectResponse
     */
    public function cancelOrder(Order $order,EntityManagerInterface $manager)
    {
        if($order->getDriver() != null){
            $order->setStatus(Order::STATUS_CANCELED);
            $manager->persist($order);
            $manager->flush();

            $statusHistory = new StatusHistory();
            $statusHistory->setStatus(Order::STATUS_CANCELED);
            $statusHistory->setStatusDate(new \DateTime());
            $statusHistory->setStatusOrder($order);
            $manager->persist($statusHistory);
            $manager->flush();

            $this->addFlash('success','Order has been Canceled');
        }
        else{
            throw new NotFoundHttpException();
        }

        return $this->redirectToRoute('adminOrders');
    }

    /**
     * @Route("/orders/{id}/driver" , name="adminChoseDriver")
     * @param Order $order
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return Response
     */
    public function orderChoseDriver(Order $order,EntityManagerInterface $manager, Request $request)
    {

        $form = $this->createForm(ChoseDriverType::class,$order,['city' => $order->getCity()]);
        $form->handleRequest($request);
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                if($order->getStatus() != "CANCELED"){
                    $order->setStatus(Order::STATUS_VALIDATED);
                    $manager->persist($order);

                    $driver = $order->getDriver();
                    $driver->setOccupied(true);
                    $manager->persist($driver);

                    $manager->flush();

                    $statusHistory = new StatusHistory();
                    $statusHistory->setStatus(Order::STATUS_VALIDATED);
                    $statusHistory->setStatusDate(new \DateTime());
                    $statusHistory->setStatusOrder($order);
                    $manager->persist($statusHistory);
                    $manager->flush();

                    $this->addFlash('success','Driver has been added');
                }else{
                    throw new NotFoundHttpException();
                }

                return $this->redirectToRoute('adminOrders');
            }

        }catch (\Exception $exception) {
            $this->addFlash('error','Exception Error');
        }

        return $this->render('backOffice/admin/orders/orderChoseDriver.html.twig', [
            'city'=>$order->getCity(),
            'form'=>$form->createView()
        ]);
    }
    //</editor-fold>

    /**
     * @Route("/customers",name="customers_list")
     * @param Request $request
     *
     * @return Response
     */
    public function customersList(Request $request) {
        $data = new SearchByCityOrCountry();
        $form = $this->createForm(SearchByCityOrCountryType::class,$data);
        $form->handleRequest($request);
        $customers = [];
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    if (null != $data->getCity() && null!= $data->getCountry()){
                        $customers = $this->getDoctrine()->getRepository(User::class)->findCustomerByCity($data->getCity());
                    }elseif (null == $data->getCity() && null != $data->getCountry()){
                        $customers = $this->getDoctrine()->getRepository(User::class)->findCustomerByCountry($data->getCountry());
                    }else{
                        $customers = $this->getDoctrine()->getRepository(User::class)->findAllCustomers();
                    }
                }catch (\Exception $ex ) {
                    $this->addFlash('error',$ex->getMessage());
                }
            }else{
                $customers = $this->getDoctrine()->getRepository(User::class)->findAllCustomers();
            }

        }catch (\Exception $exception) {
            $this->addFlash('error',$exception->getMessage());
        }


        return $this->render('backOffice/admin/customer/customers_list.html.twig', [
            'customers'=>$customers,
            'form'=>$form->createView()
        ]);
    }

    //<editor-fold desc="Code Partner">
    /**
     * @Route("/partner/chef/" , name="adminChef")
     * @param Request $request
     *
     * @return Response
     */
    public function adminChef(Request $request)
    {
        $data = new SearchByCityOrCountry();
        $form = $this->createForm(SearchByCityOrCountryType::class,$data);
        $form->handleRequest($request);
        $partners = [];
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    if (null != $data->getCity() && null!= $data->getCountry()){
                        $partners = $this->getDoctrine()->getRepository(User::class)->findChefByCity($data->getCity());
                    }elseif (null == $data->getCity() && null != $data->getCountry()){
                        $country = $data->getCountry();
                        $partners = $this->getDoctrine()->getRepository(User::class)->findChefByCountry($country);
                    }else{
                        $partners = $this->getDoctrine()->getRepository(User::class)->findAllChefs();
                    }
                }catch (\Exception $ex ) {
                    $this->addFlash('error',$ex->getMessage());
                }
            }else{
                $partners = $this->getDoctrine()->getRepository(User::class)->findAllChefs();
            }

        }catch (\Exception $exception) {
            $this->addFlash('error',$exception->getMessage());
        }

        return $this->render('backOffice/admin/chef/chef.html.twig', [
            'partners'=>$partners,
            'form'=>$form->createView()
        ]);
    }



    /**
     * @Route("/partner/caterer/" , name="adminCaterer")
     * @param Request $request
     *
     * @return Response
     */
    public function adminCaterer(Request $request)
    {
        $data = new SearchByCityOrCountry();
        $form = $this->createForm(SearchByCityOrCountryType::class,$data);
        $form->handleRequest($request);
        $partners = [];
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    if (null != $data->getCity() && null!= $data->getCountry()){
                        $partners = $this->getDoctrine()->getRepository(User::class)->findCartersByCity($data->getCity());
                    }elseif (null == $data->getCity() && null != $data->getCountry()){
                        $country = $data->getCountry();
                        $partners = $this->getDoctrine()->getRepository(User::class)->findCartersByCountry($country);
                    }else{
                        $partners = $this->getDoctrine()->getRepository(User::class)->findAllCarters();
                    }
                }catch (\Exception $ex ) {
                    $this->addFlash('error',$ex->getMessage());
                }
            }else{
                $partners = $this->getDoctrine()->getRepository(User::class)->findAllCarters();
            }

        }catch (\Exception $exception) {
            $this->addFlash('error',$exception->getMessage());
        }

        return $this->render('backOffice/admin/caterer/caterer.html.twig', [
            'partners'=>$partners,
            'form'=>$form->createView()
        ]);
    }
    //</editor-fold>

    //<editor-fold desc="Second Admin Part">
    /**
     * @Route("/new-second-admin",name="newSecondAdmin")
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     *
     * @return Response
     */
    public function newSecondAdmin(
        EntityManagerInterface $manager,
        Request $request,
        UserPasswordEncoderInterface $encoder ) {

        $secondAdmin  = new User();
        $form = $this->createForm(SecondAdminType::class,$secondAdmin);
        $form->handleRequest($request);
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                $hash = $encoder->encodePassword($secondAdmin,$secondAdmin->getPassword());
                $secondAdmin->setPassword($hash);
                $secondAdmin->setRegistrationDate(new DateTime('now'));
                $secondAdmin->setRoles(['ROLE_SECOND_ADMIN']);
                $manager->persist($secondAdmin);
                $manager->flush();
                $this->addFlash('success','Second Admin added');

                return $this->render('backOffice/admin/secondAdmin/newSecondAdmin.html.twig',[
                    'form'=>$form->createView()
                ]);
            }

        }catch (\Exception $exception) {
            $this->addFlash('error','Exception Error');
        }

        return $this->render('backOffice/admin/secondAdmin/newSecondAdmin.html.twig',[
            'form'=>$form->createView()
        ]);
    }

    /**
     * @Route("/list-second-admin",name="listSecondAdmin")
     * @param Request $request
     *
     * @return Response
     */
    public function secondAdminList(Request $request) {
        $data = new SearchByCityOrCountry();
        $form = $this->createForm(SearchByCityOrCountryType::class,$data);
        $form->handleRequest($request);
        $secondAdmins = [];
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    if (null != $data->getCity() && null!= $data->getCountry()){
                        $secondAdmins = $this->getDoctrine()->getRepository(User::class)
                            ->findSecondAdminByCity($data->getCity());
                    }elseif (null == $data->getCity() && null != $data->getCountry()){
                        $secondAdmins = $this->getDoctrine()->getRepository(Order::class)
                            ->findSecondAdminByCountry($data->getCountry());
                    }else{
                        $secondAdmins = $this->getDoctrine()->getRepository(User::class)
                            ->findUsersByRole("ROLE_SECOND_ADMIN");
                    }
                }catch (\Exception $ex ) {
                    $this->addFlash('error',$ex->getMessage());
                }
            }else{
                $secondAdmins = $this->getDoctrine()->getRepository(User::class)
                    ->findUsersByRole("ROLE_SECOND_ADMIN");
            }

        }catch (\Exception $exception) {
            $this->addFlash('error',$exception->getMessage());
        }

        return $this->render('backOffice/admin/secondAdmin/listSecondAdmin.html.twig',[
            'secondAdmins'=>$secondAdmins,
            'form'=>$form->createView()
        ]);
    }

    /**
     * @Route("/edit-second-admin/{id}",name="editSecondAdmin")
     * @param User $secondAdmin
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function editSecondAdminList(
        User $secondAdmin ,
        EntityManagerInterface $manager ,
        Request $request ,
        UserPasswordEncoderInterface $encoder) {

        $role = $secondAdmin->getRoles();
        if ($role[0]=="ROLE_SECOND_ADMIN") {

            $form = $this->createForm(EditSecondAdminInformationsType::class,$secondAdmin);
            $formPassword = $this->createForm(EditUserPasswordType::class,$secondAdmin);
            $form->handleRequest($request);
            $formPassword->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $manager->persist($secondAdmin);
                    $manager->flush();
                    $this->addFlash('success', "Information's Edited");
                }catch (\Exception $exception) {
                    $this->addFlash('error','Server Form Error');
                }
            }elseif ($formPassword->isSubmitted() && $formPassword->isValid()) {
                try {
                    $hash = $encoder->encodePassword($secondAdmin, $secondAdmin->getPassword());
                    $secondAdmin->setPassword($hash);
                    $manager->persist($secondAdmin);
                    $manager->flush();
                    $this->addFlash('success', 'Password Changed');

                } catch (\Exception $exception) {
                    $this->addFlash('error', 'Error');
                }
            }

            return $this->render('backOffice/admin/secondAdmin/editSecondAdmin.html.twig',[
                'form'=>$form->createView(),
                'formPass'=> $formPassword->createView()
            ]);
        }else {
            throw new NotFoundHttpException();
        }
    }
    //</editor-fold>

}
