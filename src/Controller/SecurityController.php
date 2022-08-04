<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\City;
use App\Entity\Country;
use App\Entity\Gallery;
use App\Entity\Package;
use App\Entity\User;
use App\Form\PartnerRegistrationType;
use App\Form\CustomerRegistrationType;
use App\Form\DriverRegistrationType;
use App\Form\RestauRegistrationType;
use App\Repository\CityRepository;
use App\Repository\ProvinceRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Class SuperAdminController.
 */

class SecurityController extends AbstractController
{
    private $verifyEmailHelper;
    private $mailer;

    public function __construct(VerifyEmailHelperInterface $helper, MailerInterface $mailer)
    {
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
    }

    /**
     * @Route("/login", name="security_login")
     * @param Request             $request
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils
    ): Response {
        $error = $authenticationUtils->getLastAuthenticationError();
        if($error) {
            $this->addFlash('error', $error->getMessage());

            $ref = $request->headers->get('referer');
            if (!is_string($ref) || $ref) {
                return $this->redirect($ref);
            }
        }

        if ($request->request->has('_password') || $request->request->has('_username')) {
            $ref = $request->headers->get('referer');
            if (!is_string($ref) || $ref) {
                return $this->redirect($ref);
            }
        }

        return $this->render('frontOffice/security/login.html.twig');
    }

    /**
     * @Route("/logout" , name="security_logout")
     */
    public function logout(){
    }

    //<editor-fold desc="Country and City registration">
    /**
     * @param array $data A cities object
     *
     * @return array The list of cities converted to a simple array
     */
    private function convertToArray(array $data)
    {
        $output = [];
        foreach ($data as $item) {
            $output[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
            ];
        }

        return $output;
    }

    /**
     * @Route("/cities", name="cities", options={"expose"=true})
     *
     * @param Request        $request    The request instance
     * @param CityRepository $repository The city's repository instance
     *
     * @throws NotFoundHttpException When calling this route without ajax
     *
     * @return JsonResponse A response
     */
    public function retrieveCity(
        Request $request,
        CityRepository $repository
    ): JsonResponse {
        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod('post')) {
                $country = $request->request->get('country');
                $cities = $repository->findByCountry($country);

                return new JsonResponse($this->convertToArray($cities));
            }
        }

        // Simply throw a 404 error.
        throw $this->createNotFoundException('Page not found');
    }

    /**
     * @Route("/province-cities", name="province_cities", options={"expose"=true})
     *
     * @param Request        $request    The request instance
     * @param CityRepository $repository The city's repository instance
     *
     * @throws NotFoundHttpException When calling this route without ajax
     *
     * @return JsonResponse A response
     */
    public function retrieveProvinceCity(
        Request $request,
        CityRepository $repository
    ): JsonResponse {
        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod('post')) {
                $province = $request->request->get('province');
                $cities = $repository->findByProvince($province);

                return new JsonResponse($this->convertToArray($cities));
            }
        }

        // Simply throw a 404 error.
        throw $this->createNotFoundException('Page not found');
    }

    /**
     * @Route("/provinces", name="retrieve_provinces", options={"expose"=true})
     *
     * @param Request        $request    The request instance
     * @param ProvinceRepository $repository The province's repository instance
     *
     * @throws NotFoundHttpException When calling this route without ajax
     *
     * @return JsonResponse A response
     */
    public function retrieveProvinces(
        Request $request,
        ProvinceRepository $repository
    ): JsonResponse {
        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod('post')) {
                $country = $request->request->get('country');
                $provinces = $repository->findByCountry($country);

                return new JsonResponse($this->convertToArray($provinces));
            }
        }

        // Simply throw a 404 error.
        throw $this->createNotFoundException('Page not found');
    }
    //</editor-fold>


    //<editor-fold desc="Registration Customer">
    /**
     * @Route("/registration/customer", name="registrationCustomer")
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function registrationCustomer(
        Request $request,
        EntityManagerInterface $manager,
        UserPasswordEncoderInterface $encoder
    ): Response{
        $user = new User();
        $form = $this->createForm(CustomerRegistrationType::class,$user);
        $form ->handleRequest($request);
        try {
            if ($form->isSubmitted() && $form->isValid()){
                $hash = $encoder->encodePassword($user,$user->getPassword());
                $user->setPassword($hash);
                $user->setRegistrationDate(new \DateTime('now'));
                $user->setRoles(['ROLE_CUSTOMER']);
                $user->setStatus('notConfirmed');
                $manager->persist($user);
                $manager->flush();

                $signatureComponents = $this->verifyEmailHelper->generateSignature(
                    'registration_confirmation_route',
                    (string)$user->getId(),
                    $user->getEmail(),
                    ['id' => $user->getId()]);

                $email = new TemplatedEmail();
                $email->from('contact@foodepia.com');
                $email->to($user->getEmail());
                $email->subject('Email Verification');
                $email->htmlTemplate('frontOffice/security/emailVerification.html.twig');
                $email->context(['signedUrl' => $signatureComponents->getSignedUrl()]);
                $this->mailer->send($email);

                $this->addFlash('success','Thank you for your registration you need to confirm your Email please!');
                return $this->render('frontOffice/security/login.html.twig');
            }
        } catch (UniqueConstraintViolationException $ex ) {
            $this->addFlash(
                'error',
                'Full name or User name already exists you must choose another'
            );
        } catch (\Exception $ex) {
            $this->addFlash('error','error');
        }

        return $this->render('frontOffice/security/signUpCustomer.html.twig',[
            'form'=>$form->createView(),
        ]);
    }
    //</editor-fold>


    //<editor-fold desc="Registration Restaurant">
    /**
     * @Route("/registration/restaurant", name="registrationRestaurant")
     */
    public function registrationRestaurant(): Response
    {
        $packages = $this->getDoctrine()->getRepository(Package::class)->findAll();

        return $this->render('frontOffice/security/signUpPackageRestaurant.html.twig',[
            'Packages' => $packages,
        ]);
    }


    /**
     * @Route("/registration/restaurant/package/{id}", name="registrationRestaurantInfo")
     *
     * @param Request                      $request
     * @param Package                      $package
     * @param EntityManagerInterface       $manager
     * @param UserPasswordEncoderInterface $encoder
     *
     * @return Response
     */
    public function registrationRestaurantInfo(
        Request $request,
        Package $package,
        EntityManagerInterface $manager,
        UserPasswordEncoderInterface $encoder
    ): Response {
        $user = new User();
        $form = $this->createForm(RestauRegistrationType::class,$user);
        $form ->handleRequest($request);
        try {
            if ($form->isSubmitted() && $form->isValid()){
                $hash = $encoder->encodePassword($user,$user->getPassword());
                $user->setPassword($hash);
                $user->setRegistrationDate(new \DateTime('now'));
                $user->setPackage($package);
                $user->setRoles(['ROLE_RESTAURANT']);
                $user->setStatus('notConfirmed');
                $manager->persist($user);
                $manager->flush();

                $gallery = new Gallery();
                $gallery->setRestaurant($user);
                $manager->persist($gallery);
                $manager->flush();

                $signatureComponents = $this->verifyEmailHelper->generateSignature(
                    'registration_confirmation_route',
                    (string)$user->getId(),
                    $user->getEmail(),
                    ['id' => $user->getId()]);

                $email = new TemplatedEmail();
                $email->from('contact@foodepia.com');
                $email->subject('Email Verification');
                $email->to($user->getEmail());
                $email->htmlTemplate('frontOffice/security/emailVerification.html.twig');
                $email->context(['signedUrl' => $signatureComponents->getSignedUrl()]);
                $this->mailer->send($email);
                $this->addFlash('success','Thank you for your registration you need to confirm your Email!');

                return $this->redirectToRoute('security_login');
            }
        } catch (UniqueConstraintViolationException $ex ) {
            $this->addFlash('error','Email or Username already exist');
        }catch (\Exception $ex) {
            $this->addFlash(
                'error',
                $ex->getMessage()
            );
        }

        return $this->render('frontOffice/security/signUpRestaurant.html.twig',[
            'form'=>$form->createView(),
        ]);
    }
    //</editor-fold>


    //<editor-fold desc="Registration partner">
    /**
     * @Route("/registration/partner", name="registrationPartner")
     */
    public function registrationPartner(): Response
    {
        $packages = $this->getDoctrine()->getRepository(Package::class)->findAll();

        return $this->render('frontOffice/security/signUpPackagePartner.html.twig',[
            'Packages' => $packages,
        ]);
    }


    /**
     * @Route("/registration/partner/package/{id}", name="registrationChefInfo")
     *
     * @param Request $request
     * @param Package $package
     * @param EntityManagerInterface $manager
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function registrationChefInfo(
        Request $request,
        Package $package,
        EntityManagerInterface $manager,
        UserPasswordEncoderInterface $encoder
    ): Response {
        $user = new User();
        $form = $this->createForm(PartnerRegistrationType::class,$user);
        $form ->handleRequest($request);
        try {
            if ($form->isSubmitted() && $form->isValid()){
                $hash = $encoder->encodePassword($user,$user->getPassword());
                $user->setPassword($hash);
                $user->setRegistrationDate(new \DateTime('now'));
                $user->setPackage($package);
                if($form->get('role')->getData()=='ROLE_TRAITEUR'){
                    $user->setRoles(['ROLE_TRAITEUR']);
                }elseif ($form->get('role')->getData()=="ROLE_CHEF"){
                    $user->setRoles(['ROLE_CHEF']);
                }else{
                    throw  new NotFoundHttpException();
                }

                $user->setStatus('notConfirmed');
                $manager->persist($user);
                $manager->flush();

                $gallery = new Gallery();
                $gallery->setRestaurant($user);
                $manager->persist($gallery);
                $manager->flush();
                $signatureComponents = $this->verifyEmailHelper->generateSignature(
                    'registration_confirmation_route',
                    (string)$user->getId(),
                    $user->getEmail(),
                    ['id' => $user->getId()]);

                $email = new TemplatedEmail();
                $email->from('contact@foodepia.com');
                $email->to($user->getEmail());
                $email->subject('Email Verification');
                $email->htmlTemplate('frontOffice/security/emailVerification.html.twig');
                $email->context(['signedUrl' => $signatureComponents->getSignedUrl()]);
                $this->mailer->send($email);

                //$this->addFlash('success','success');
                return $this->redirectToRoute('security_login');
            }

        } catch (Exception $ex) {
            $this->addFlash('error','error');
        } catch (UniqueConstraintViolationException $ex ) {
            $this->addFlash(
                'error',
                'Full name or User name already exists you must choose another'
            );
        }

        return $this->render('frontOffice/security/signUpPartner.html.twig',[
            'form'=>$form->createView(),
        ]);
    }
    //</editor-fold>



    //<editor-fold desc="Registration Driver">
    /**
     * @Route("/registration/driver", name="registrationDriver")
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function registrationDriver(
        Request $request,
        EntityManagerInterface $manager,
        UserPasswordEncoderInterface $encoder
    ): Response {
        $user = new User();
        $form = $this->createForm(DriverRegistrationType::class,$user);
        $form ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            try {
                $hash = $encoder->encodePassword($user,$user->getPassword());
                $user->setPassword($hash);
                $user->setRegistrationDate(new \DateTime('now'));
                $user->setRoles(['ROLE_DRIVER']);
                $user->setStatus("pending");
                $user->setOccupied(false);
                $manager->persist($user);
                $manager->flush();

                $email = (new Email())
                    ->from('contact@foodepia.com')
                    ->to($user->getEmail())
                    ->replyTo('foodepia@gmail.com')
                    ->subject('Account Verification')
                    ->text('Your email is being verified by the administrators')
                    ->html('<p>Your account will be processed within 24 hours</p>');

                $this->mailer->send($email);

            }catch (UniqueConstraintViolationException $ex ){
                $this->addFlash('error','Username or Email already exist');
            }

            return $this->redirectToRoute('security_login');
        }

        return $this->render('frontOffice/security/signUpDriver.html.twig',[
            'form'=>$form->createView(),
        ]);
    }
    //</editor-fold>

    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function dashboard(): Response
    {
        if ($this->getUser()) {
            $role = $this->getUser()->getRoles();
            $routes = [
                'ROLE_SUPER_ADMIN' => 'dashboardSuperAdmin',
                'ROLE_ADMIN' => 'dashboardAdmin',
                'ROLE_RESTAURANT' => 'dashboardRestaurant',
                'ROLE_CHEF' => 'dashboardPartner',
                'ROLE_TRAITEUR' => 'dashboardPartner',
                'ROLE_DRIVER' => 'dashboardDriver',
                'ROLE_SECOND_ADMIN'=>'dashboardSecondAdmin',
                'ROLE_CUSTOMER'=>'index',
            ];

            if (true === \array_key_exists($role[0], $routes)) {
                /**
                 * @var User $user
                 */
                $user = $this->getUser();
                if($role[0]=="ROLE_DRIVER" && $user->getStatus()!='active' ){
                    $this->addFlash('error','Your account is unapproved by admin please contact-us');
                    return $this->redirectToRoute('security_logout');
                }elseif ($user->getStatus()=='notConfirmed'){
                    $this->addFlash('error','You need to confirm your email please!');
                    return $this->redirectToRoute('security_logout');
                }elseif ($user->getStatus()=='block'){
                    $this->addFlash('error',"Uou can not login now contact administrator for more information");
                    return $this->redirectToRoute('security_logout');
                }
                return $this->redirectToRoute($routes[$role[0]]);
            }

            if (true === \array_key_exists($role[0], $routes)) {
                return $this->redirectToRoute($routes[$role[0]]);
            }
        }

        return $this->redirectToRoute('index');
    }
    /**
     * @Route("/driver-localisation", name="driver_localisation", options={"expose"=true})
     *
     * @param Request        $request    The request instance
     * @param UserRepository $repository The city's repository instance
     *
     * @throws NotFoundHttpException When calling this route without ajax
     *
     * @return JsonResponse A response
     */
    public function retrieveDriverLocalisation(
        Request $request,
        UserRepository $repository
    ): JsonResponse {
        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod('post')) {
                $id = $request->request->get('id');
                $driver = $repository->findDriversById($id);
                $loc = $driver->getLocalisation();
                return new JsonResponse($loc,200);
            }
        }
        // Simply throw a 404 error.
        throw $this->createNotFoundException('Page not found');
    }


    /**
     * @Route("/verify", name="registration_confirmation_route")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function verifyUserEmail(Request $request ,UserRepository $userRepository,EntityManagerInterface $manager): Response
    {
        $id = $request->get('amp;id'); // retrieve the user id from the url
        // Verify the user id exists and is not null
        if (null === $id) {
            $this->addFlash('error','Unable to find this user');
            return $this->redirectToRoute('security_login');
        }

        $user = $userRepository->find($id);
        // Ensure the user exists in persistence
        if (null === $user) {
            $this->addFlash('error','Unable to find this user 2');
            return $this->redirectToRoute('security_login');
        }
        // Do not get the User's Id or Email Address from the Request object
        try {
            $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), (string)$user->getId(),$user->getEmail());
            $user->setStatus('active');
            $manager->flush();
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());

            return $this->redirectToRoute('security_login');
        }

        // Mark your user as verified. e.g. switch a User::verified property to true

        $this->addFlash('success', 'Your e-mail address has been verified.');

        return $this->redirectToRoute('security_login');
    }

    /**
     * @Route("/remove/{id}",name="remove_user")
     * @param User                      $user
     * @param EntityManagerInterface    $manager
     * @return Response
     */
    public function removeUser(User $user , EntityManagerInterface $manager) {
        $manager->remove($user);
        $manager->flush();
        return new Response('ok',200);
    }

}
