<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Doctrine\Orm\NoResultException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective;

use phpseclib\Crypt\AES;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @Extra\Route("/perspective")
 */
class PerspectiveController extends ContainerAware
{
    /**
     * @Extra\Cache(expires="+2 hours", public="true")
     * @Extra\Route("/sitemap", name="weaving_the_web_dashboard_show_sitemap")
     * @Extra\Template("WeavingTheWebDashboardBundle:Perspective:showSitemap.html.twig")
     *
     * @return array
     */
    public function showSitemapAction()
    {
        /** @var \WeavingTheWeb\Bundle\DashboardBundle\Routing\Router $router */
        $router = $this->container->get('weaving_the_web_dashboard.router');

        /** @var \Symfony\Bundle\TwigBundle\TwigEngine $templateEngine */
        $templateEngine = $this->container->get('templating');
        $response = $templateEngine->renderResponse(
             'WeavingTheWebDashboardBundle:Perspective:showSitemap.html.twig', array(
                'perspectives' => $router->getPublicPerspectivesSitemap(),
                'title' => 'title_sitemap'
            )
        );
        $dateTime = new \DateTime();
        $dateInterval = \DateInterval::createFromDateString('2 hours');
        $expiresAt = $dateTime->add($dateInterval);
        $response->setExpires($expiresAt);

        return $response;
    }

    /**
     * @Extra\Route("/", name="weaving_the_web_dashboard_show_public_perspectives")
     */
    public function showPublicPerspectivesAction()
    {
        /**
         * @var \Symfony\Component\Routing\Router $router
         */
        $router = $this->container->get('router');
        $url = $router->generate('weaving_the_web_dashboard_show_sitemap');

        return new RedirectResponse($url);

    }

    /**
     * @param $hash
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Extra\Cache(expires="+2 hours", public="true")
     * @Extra\Route(
     *      "/{hash}",
     *      name="weaving_the_web_dashboard_show_perspective",
     *      options={"expose"=true}
     * )
     */
    public function showPerspectiveAction($hash)
    {
        /**
         * @var \Doctrine\Orm\EntityManager $entityManager
         */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        /**
         * @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository $perspectiveRepository
         */
        $perspectiveRepository = $entityManager->getRepository('WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective');

        try {
            /**
             * @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective $perspective
             */
            $perspective = $perspectiveRepository->findOneByPartialHash($hash);
        } catch (NoResultException $exception) {
            return $this->raiseHttpNotFoundException($exception);
        }

        /**
         * @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker
         */
        $authorizationChecker = $this->container->get('security.authorization_checker');
        if ($perspective->isPrivate() && !$authorizationChecker->isGranted('ROLE_ADMIN')) {
            $this->raiseHttpNotFoundException();
        }

        if ($perspective->isQueryPerspective() && $this->validPerspective($perspective)) {
            return $this->renderQueryPerspective($perspective);
        } elseif ($perspective->isJsonPerspective()) {
            return $this->renderJsonPerspective($perspective);
        } else {
            $this->raiseHttpNotFoundException();
        }
    }

    /**
     * @param $exception
     */
    protected function raiseHttpNotFoundException($exception = null)
    {
        throw new NotFoundHttpException('The requested query it not available', $exception);
    }

    /**
     * @param $perspective
     * @return bool
     */
    public function validPerspective($perspective)
    {
        /**
         * @var \Symfony\Component\Validator\Validator $validator
         */
        $validator = $this->container->get('validator');
        $constraintsViolationsList = $validator->validate($perspective, ['public_perspectives']);

        return count($constraintsViolationsList) === 0;
    }

    /**
     * @Extra\Route(
     *      "/{hash}/export",
     *      name="weaving_the_web_dashboard_export_perspective",
     *      options={"expose"=true}
     * )
     * @Extra\Method({"GET"})
     * @Extra\ParamConverter(
     *      "perspective",
     *      class="WeavingTheWebDashboardBundle:Perspective"
     * )
     *
     * @param Perspective $perspective
     * @return NotFoundHttpException
     */
    public function exportPerspectiveAction(Perspective $perspective)
    {
        /**
         * @var \Symfony\Component\Translation\Translator $translator
         */
        $translator = $this->container->get('translator');

        if (false === $this->container->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        if ($perspective->isExportable()) {
            $query = $this->executeQuery($perspective);

            if (is_null($query->error)) {
                $crypto = $this->container->get('weaving_the_web_dashboard.security.crypto');
                $jsonEncodedRecords = json_encode($query->records);
                $encryptionResult= $crypto->encrypt($jsonEncodedRecords);

                return new JsonResponse([
                    'result' => $encryptionResult['encrypted_message'],
                    'key' => $this->container->getParameter('aes_key'),
                    'iv' => $this->container->getParameter('aes_iv'),
                    'padding' => $encryptionResult['padding_length'],
                    'type' => 'success'
                ]);
            } else {
                return new JsonResponse([
                    'result' => $query->error,
                    'type' => 'error'
                ]);
            }
        } else {
            throw new NotFoundHttpException($translator->trans('perspective.not_found', [], 'perspective', 'en'));
        }
    }

    /**
     * @param Perspective $perspective
     * @return \stdClass
     */
    protected function executeQuery(Perspective $perspective)
    {
        /**
         * @var \WeavingTheWeb\Bundle\DashboardBundle\DBAL\Connection $connection
         */
        $connection = $this->container->get('weaving_the_web_dashboard.dbal_connection');

        return $connection->executeQuery($perspective->getValue());
    }

    /**
     * @param $perspective
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderQueryPerspective(Perspective $perspective)
    {
        $query = $this->executeQuery($perspective);

        $translator = $this->container->get('translator');

        /** @var \Symfony\Bundle\TwigBundle\TwigEngine $templateEngine */
        $templateEngine = $this->container->get('templating');

        if ($perspective->getName() !== null) {
            $perspectiveTitle = $perspective->getName();
        } else {
            $perspectiveTitle = $translator->trans('title_perspective');
        }

        /**
         * @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker $authorizationChecker
         */
        $authorizationChecker = $this->container->get('security.authorization_checker');
        $anonymousVisit = $perspective->isPublic() &&
            !$authorizationChecker->isGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY);

        $response = $templateEngine->renderResponse(
            'WeavingTheWebDashboardBundle:Perspective:showPerspective.html.twig',
            array(
                'anonymous_visit' => $anonymousVisit,
                'active_menu_item' => 'dashboard',
                'default_query' => $query->sql,
                'enabled_search' => true,
                'error' => $query->error,
                'records' => $query->records,
                'title' => $perspectiveTitle
            )
        );
        $dateTime = new \DateTime();
        $dateInterval = \DateInterval::createFromDateString('2 hours');
        $expiresAt = $dateTime->add($dateInterval);
        $response->setExpires($expiresAt);

        return $response;
    }

    /**
     * @param $perspective
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderJsonPerspective(Perspective $perspective)
    {
        $translator = $this->container->get('translator');

        /** @var \Symfony\Bundle\TwigBundle\TwigEngine $templateEngine */
        $templateEngine = $this->container->get('templating');

        if ($perspective->getName() !== null) {
            $perspectiveTitle = $perspective->getName();
        } else {
            $perspectiveTitle = $translator->trans('title_perspective');
        }

        $relativePath = $perspective->getValue();
        $projectRootDir = realpath($this->container->getParameter('kernel.root_dir') . '/..');
        $jsonFilePath = $projectRootDir . '/' . $relativePath;

        $error = null;
        if (file_exists($jsonFilePath)) {
            $records = json_decode(file_get_contents($jsonFilePath), $asAssocArray = true);
            $jsonLastError = json_last_error();
            if ($jsonLastError !== JSON_ERROR_NONE) {
                $error = $jsonLastError;
            }
        } else {
            $records = [];
        }
        $query = sprintf('SELECT * FROM weaving_perspective WHERE per_id = %d', $perspective->getId());

        $response = $templateEngine->renderResponse(
            'WeavingTheWebDashboardBundle:Perspective:showPerspective.html.twig',
            array(
                'active_menu_item' => 'dashboard',
                'anonymous_visit' => false,
                'error' => $error,
                'display_graph' => true,
                'enabled_search' => true,
                'default_query' => $query,
                'records' => $records,
                'title' => $perspectiveTitle
            )
        );
        $dateTime = new \DateTime();
        $dateInterval = \DateInterval::createFromDateString('2 hours');
        $expiresAt = $dateTime->add($dateInterval);
        $response->setExpires($expiresAt);

        return $response;
    }
} 
