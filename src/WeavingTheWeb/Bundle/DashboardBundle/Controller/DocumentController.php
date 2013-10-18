<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use WeavingTheWeb\Bundle\DashboardBundle\DBAL\Connection;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Class DocumentController
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package WeavingTheWeb\Bundle\DashboardBundle\Controller
 */
class DocumentController extends Controller
{
    /**
     * @Extra\Route("/navigation/{activeMenu}", name="weaving_the_web_dashboard_show_navigation")
     * @Extra\Method({"GET"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function showNavigationAction($activeMenu = 'github_repositories', $showSitemap = false)
    {
        $response = $this->render('::navigation.html.twig', [
            'active_menu_item' => $activeMenu,
            'show_sitemap' => $showSitemap
        ]);

        /**
         * @var \Symfony\Component\Security\Core\SecurityContext $securityContext
         */
        $securityContext = $this->get('security.context');
        $token = $securityContext->getToken();

        if ($token instanceof AnonymousToken) {
            $response->setPublic();
            $response->setSharedMaxAge(3600*2);
        } else {
            $response->setPrivate();
            $response->setSharedMaxAge(0);
        }

        return $response;
    }

    /**
     * @Extra\Route("/documents", name="weaving_the_web_dashboard_show_documents")
     * @Extra\Method({"GET", "POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     *
     * @Secure(roles="ROLE_USER")
     */
    public function showDocumentsAction()
    {
        /**
         * @var $request Request
         */
        $request = $this->get('request');
        $translator = $this->get('translator');

        /**
         * @var $connection Connection
         */
        $connection = $this->get('weaving_the_web_dashboard.dbal_connection');
        $defaultQuery = $connection->getDefaultQuery();
        $sql = 'SELECT 1';

        if ($request->request->has('query')) {
            $sql = $request->request->get('query');
        } elseif (is_null($defaultQuery->error)) {
            $sql = $defaultQuery->sql;
        }

        $query = $connection->executeQuery($sql);

        return $this->render(
            'WeavingTheWebDashboardBundle:Document:showDocuments.html.twig', array(
                'active_menu_item' => 'dashboard',
                'error' => $query->error,
                'default_query' => $query->sql,
                'records' => $query->records,
                'title' => $translator->trans('title_documents')));
    }

    /**
     * @Extra\Route("/sql", name="weaving_the_web_dashboard_save_sql", options={"expose"=true})
     * @Extra\Method({"POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function saveSqlAction()
    {
        $request = $this->get('request');
        $translator = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $type = 'error';

        if ($request->request->has('sql')) {
            $error = null;
            $sql = $request->request->get('sql');
        } else {
            $error = $translator->trans('save_query_failure', array(), 'messages');
            $sql = '';
        }

        /**
         * @var $perspectiveRepository \WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository
         */
        $perspectiveRepository = $entityManager->getRepository('WeavingTheWebDashboardBundle:Perspective');
        $result = $perspectiveRepository->findBy(['value' => $sql]);

        if (count($result) === 0) {
            try {
                $setters= $this->get('weaving_the_web_mapping.mapping');

                /**
                 * @var $perspective \WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective
                 */
                $perspective = $perspectiveRepository->savePerspective($sql, $setters);
                $entityManager->persist($perspective);
                $entityManager->flush();

                $result = $translator->trans('save_query_success', array('{{ sql }}' => $sql), 'messages');
                $type = 'success';
            } catch (\Exception $exception) {
                $result = $exception->getMessage();
            }
        } else {
            $result = $translator->trans('query_exists_already', array(), 'messages');
            $type = 'block';
        }

        return new Response(json_encode((object) array(
                'result' => is_null($error) ? $result : $error,
                'type' => $type
            )),
            201,
            array('Context-type' => 'application/json'));
    }
}
