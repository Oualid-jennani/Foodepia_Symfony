<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminType;
use App\Form\SuperAdminType;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use mysql_xdevapi\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SuperAdminController
 * @package App\Controller
 * @Route("/super/admin")
 */
class SuperAdminController extends AbstractController
{

    /**
     * @Route("/", name="dashboardSuperAdmin")
     */
    public function index(): Response
    {
        return $this->render('BackOffice/SuperAdmin/index.html.twig');
    }

    /**
     * @Route("/login", name="superAdminLogin")
     */
    public function login(): Response
    {
        return $this->render('backOffice/SuperAdmin/login.html.twig');
    }

    /**
     * @Route("/register", name="superAdminRegister")
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function register(Request $request, EntityManagerInterface $manager,UserPasswordEncoderInterface $encoder): Response
    {
        $user = new User();
        $form = $this->createForm(SuperAdminType::class,$user);
        $form ->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()){
                $hash = $encoder->encodePassword($user,$user->getPassword());
                $user->setPassword($hash);
                $user->setRegistrationDate(new \DateTime('now'));
                $user->setRoles(['ROLE_SUPER_ADMIN']);
                $manager->persist($user);
                $manager->flush();
                //$this->addFlash('success','success');
                return $this->redirectToRoute('superAdminLogin');
            }
        } catch (Exception $ex){
            $this->addFlash('error','error');
        }
        catch (UniqueConstraintViolationException $ex ) {
            $this->addFlash('error', 'Full name or User name already exists you must choose another');
        }

        return $this->render('backOffice/SuperAdmin/signUp.html.twig',[
            'form'=>$form->createView()
        ]);
    }
    /**
     * @Route("/add-admin", name="add_admin")
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function addAdmin(Request $request, EntityManagerInterface $manager,UserPasswordEncoderInterface $encoder): Response
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

                return $this->redirectToRoute('dashboardSuperAdmin');
            }

        }catch (Exception $ex){
            $this->addFlash('error','error');
        }

        return $this->render('backOffice/admin/signUp.html.twig',[
            'form'=>$form->createView()
        ]);
    }

}
