<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\EditCustomerType;
use App\Form\Model\ChangePassword;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


/**
 * Class CustomerController
 * @package App\Controller
 * @IsGranted("ROLE_CUSTOMER")
 */
class CustomerController extends AbstractController
{
    /**
     * @Route("/account-info", name="account-info")
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function profile(
        Request $request,
        EntityManagerInterface $manager,
        UserPasswordEncoderInterface $encoder
    ): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(EditCustomerType::class,$user);
        $form->handleRequest($request);
        $changePasswordModel = new ChangePassword();
        $formPassword = $this->createForm(ChangePasswordType::class,$changePasswordModel);
        $formPassword->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

           try {
                $manager->persist($user);
                $manager->flush();
                $this->addFlash('success', 'Information Edited');

            } catch (\Exception $exception) {
                $this->addFlash('error', 'Error');
            }

        } elseif ($formPassword->isSubmitted() && $formPassword->isValid()) {

            try {
                $hash = $encoder->encodePassword($user,$changePasswordModel->getNewPassword());
                $user->setPassword($hash);
                $manager->persist($user);
                $manager->flush();
                $this->addFlash('successPass', 'Password Changed');

            } catch (\Exception $exception) {
                $this->addFlash('errorPass', 'Error');
            }

        }

        return $this->render('frontOffice/customer/account-info.html.twig',[
            'form'=>$form->createView(),
            'formPassword'=>$formPassword->createView(),
        ]);
    }

    /**
     * @Route("/order-history", name="order_history")
     */
    public function orderHistory() {
        /**
         * @var User $customer
         */
        $customer = $this->getUser();
        $orders = $customer->getOrderCustomer();

        return $this->render('frontOffice/customer/order_history.html.twig',[
            'orders'=> $orders
        ]);

    }
    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY') and user === order.getCustomer()")
     * @Route("/order-Track/{id}", name="orderTrack")
     * @param Order $order
     * @return Response
     */
    public function orderTrack(Order $order) {

        return $this->render('frontOffice/customer/order_tracking.html.twig',[
            'order'=>$order,
        ]);
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_FULLY') and user === order.getCustomer()")
     * @Route("/new-order/{id}", name="order_again")
     * @param Order $order
     * @param SessionInterface $session
     * @return Response
     */
    public function NewOrder(Order $order,SessionInterface $session): Response
    {
        /** @var Cart|null $cart */
        $cart = new Cart();

        $session->set('COUNTRY',$order->getCity()->getCountry()->getName());

        foreach ($order->getCarts() as $orderCart){
            foreach ($orderCart->getCartLines() as $cartLine){
                $cart->addCartLine($cartLine);
                dump($cartLine->getVariant());
            }
        }
        $session->set('CART', $cart);

        return $this->redirectToRoute('checkout');
    }
}
