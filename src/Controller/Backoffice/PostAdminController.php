<?php

declare(strict_types=1);

namespace  App\Controller\Backoffice;

use App\Model\Entity\Post;
use App\Model\Repository\UserRepository;
use App\Service\Authorization;
use App\Service\CsrfToken;
use App\Service\FormValidator\EditPostValidator;
use App\Service\Http\RedirectResponse;
use App\Service\Http\Request;
use App\Service\Http\Response;
use App\Service\Http\Session\Session;
use App\Service\Paginator;
use App\View\View;
use App\Model\Repository\PostRepository;
use App\Model\Repository\CommentRepository;

final class PostAdminController
{
    private PostRepository $postRepository;
    private CommentRepository $commentRepository;
    private UserRepository $userRepository;
    private View $view;
    private Request $request;
    private Session $session;
    private EditPostValidator $validator;
    private CsrfToken $csrf;
    private RedirectResponse $redirect;
    private Paginator $paginator;

    public function __construct(
        View $view,
        Request $request,
        Session $session,
        CommentRepository $commentRepository,
        UserRepository $userRepository,
        PostRepository $postRepository
    ) {

        $this->postRepository = $postRepository;
        $this->commentRepository = $commentRepository;
        $this->userRepository = $userRepository;
        $this->view = $view;
        $this->request = $request;
        $this->session = $session;
        $this->validator = new EditPostValidator($this->session);
        $this->csrf = new CsrfToken($this->session, $this->request);
        $this->paginator = new Paginator($this->request, $this->view);
        $security = new Authorization($this->session, $this->request);
        $this->redirect = new RedirectResponse();


        if (!$security->isLogged() || $security->loggedAs('User')) {
            $this->redirect->redirect('forbidden');
        }
    }

    public function postsList():Response
    {

        if (!is_null($this->request->query()->get('delete'))) {
            $id = $this->request->query()->get('id');
            $post = $this->postRepository->findOneBy(['id_post' => $id]);

            if (!is_null($post)) {
                $this->postRepository->delete($post);
                $this->session->addFlashes('danger', 'Post supprimé avec succès !');
            }
        }

        // PAGINATION

        $tableRows = $this->postRepository->countAllPosts();

        $paginator = $this->paginator->paginate($tableRows, 10, 'postsAdmin');

        $posts = $this->postRepository->findBy([], ['createdAt' =>'desc'], $paginator['parPage'], $paginator['depart']);

        return new Response($this->view->render([
            'template' => 'posts',
            'type' => 'backoffice',
            'data' => [
                'posts' => $posts,
                'paginator' => $paginator['paginator']
            ],
        ]), 200);
    }

    public function editPost(int $id):Response
    {

        $post = $this->postRepository->findOneBy(['id_post' => $id]);
        $users = $this->userRepository->findAll();

        if ($this->request->getMethod() === 'POST' && $this->csrf->tokenCheck()) {

            /** @var array $data */
            $data = $this->request->request()->all();

            if ($this->validator->editPostValidator($data)) {
                $user = $this->userRepository->findOneBy(['id_utilisateur'=>(int)$data['author']]);

                if ($post) {

                    $post = new Post(
                        $post->getIdPost(),
                        $data['chapo'],
                        $data['title'],
                        $data['content'],
                        $post->getCreatedAt(),
                        new \DateTime(),
                        $user
                    );

                    $this->postRepository->update($post);

                    $this->session->addFlashes('update', 'Post modifié avec succès !');
                }
            }
        }

        return new Response($this->view->render([
            'template' => 'editPost',
            'type' => 'backoffice',
            'data' => [
                'users' => $users,
                'post' => $post,
                'token' => $this->csrf->newToken()
            ],
        ]), 200);
    }

    public function addPost():Response
    {

        if ($this->request->getMethod() === 'POST' && $this->csrf->tokenCheck()) {

            /** @var array $data */
            $data = $this->request->request()->all();

            if ($this->validator->editPostValidator($data)) {
                $user = $this->session->get('user');

                $post = new Post(
                    0,
                    $data['chapo'],
                    $data['title'],
                    $data['content'],
                    new \DateTime('now'),
                    null,
                    $user
                );

                $this->postRepository->create($post);

                $this->session->addFlashes('success', 'Post ajouté avec succès !');
            }
        }

        return new Response($this->view->render([
            'template' => 'addPost',
            'type' => 'backoffice',
            'data' => [
                'token' => $this->csrf->newToken()
            ]
        ]), 200);
    }
}
