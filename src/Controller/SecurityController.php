<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\ForgotPasswordType;
use App\Repository\UserRepository;
use App\Service\Email\Mail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{

    /**
     * @var Mail
     */
    private $mail;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        Mail $mail,
        UserRepository $userRepository,
        Security $security,
        TranslatorInterface $translator
    )
    {
        $this->mail = $mail;
        $this->userRepository = $userRepository;
        $this->security = $security;
        $this->translator = $translator;
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('homepage');
         }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/forgot_password", name="forgot_password")
     */
    public function reset_password(LoginLinkHandlerInterface $loginLinkHandler, Request $request)
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('danger', $this->translator->trans('user_account.unknown_account'));

                return $this->redirectToRoute('forgot_password');
            }

            $loginLinkDetails = $loginLinkHandler->createLoginLink($user);

            $this->mail->sendEmail(
                $email,
                'forgot password',
                $this->translator->trans('forgot_password.send_email', ['%urlLink%'=> $loginLinkDetails->getUrl()])
            );

            return $this->render('forgot_password/success.html.twig');
        }

        return $this->render('forgot_password/forgot_password.html.twig', [
            'forgotPasswordForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/change_password", name="change_password")
     */
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordEncoder)
    {

        $user = $this->userRepository->findOneBy(['email' => $this->security->getUser()->getEmail()]);

        $form = $this->createForm(ChangePasswordType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordEncoder->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();

            $this->addFlash('notice', $this->translator->trans('change_password.success'));

            return $this->redirectToRoute('login');
        }

        return $this->render('security/change_password.html.twig', [
            'changePasswordForm'=> $form->createView()
        ]);
    }
}
