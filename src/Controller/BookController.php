<?php

namespace App\Controller;

use DateTime;
use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

//#[Route('/book')]
class BookController extends AbstractController
{

    // Вывод информации о всех книгах
    // Входные данные
    // BookRepository, UserRepository - см документацию Symfony
    // Request - нужно для получения информации о залогинившемся пользователе
    // Выходные данные
    // Форма с информацией о всех книгах
    #[Route('/', name: 'app_book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository, Request $request, UserRepository $userRepository): Response
    {
        $session = $request->getSession();
        $email = $session->get(Security::LAST_USERNAME) ?? null;
        if ($email != NULL) {
            if (!$session->isStarted()) {
                $session->start();
            }
            $user = $userRepository->findOneBy(['email' => $email]);
            $user_id = $user->getId();
            $books = $bookRepository->getAllBooksById($user_id) ?? null;;
            return $this->render('book/index.html.twig', [
                'books' => $books,
                'user' => $user
            ]);
        } else {
            return $this->render('book/index.html.twig', [
                'books' => $bookRepository->findAll(),
            ]);
        }
    }

    // Создание книги
    // Входные данные
    // BookRepository, UserRepository, SluggerInterface - см документацию Symfony
    // Request - нужно для получения информации о залогинившемся пользователе
    // Выходные данные
    // Форма с информацией о конкретной книге
    #[Route('/new', name: 'app_book_new', methods: ['GET', 'POST'])]
    public function new(Request $request, BookRepository $bookRepository, UserRepository $userRepository, SluggerInterface $slugger): Response //создание новой книги
    {
        // получение данных о залогиненом пользователе
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
        $email = $session->get(Security::LAST_USERNAME) ?? null;
        $user = $userRepository->findOneByEmail($email);

        // создание книги
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $posterFile = $form->get('posterUrl')->getData();
            $bookFile = $form->get('pdfUrl')->getData();
            if($posterFile && $bookFile) {//постер книги и файл были загружены, добавляем данные о книге
                $posterFileName = pathinfo($posterFile->getClientOriginalName(), PATHINFO_FILENAME);
                $originalFilename = pathinfo($bookFile->getClientOriginalName(), PATHINFO_FILENAME);

                // подготавливаем данные к сохранению на сервере
                $bindedPosterFileName = $slugger->slug($posterFileName);
                $safeFilename = $slugger->slug($originalFilename);
                $cover_newFilename = $bindedPosterFileName.'-'.uniqid().'.'.$posterFile->guessExtension();
                $newFilename = $safeFilename.'-'.uniqid().'.'.$bookFile->guessExtension();

                try {
                    $posterFile->move(                     //сохраняем постер на сервере 
                        '..//public//files//posters',       
                        $cover_newFilename
                    );
                    $bookFile->move(
                        '..//public//files//books',       //сохраняем файл книги на сервере
                        $newFilename
                    );
                } catch (FileException $e) {
                    echo 'Error: '.$e->getMessage.'\n';
                }

                // добавляем оставшуюся информацию о книге
                $book->setAuthor($form->get('author')->getData());
                $book->setPoster('\\files\\posters\\'.$cover_newFilename);
                $book->setFile('\\files\\books\\'.$newFilename);
                $book->setLastReadDate(new DateTime());
                $book->setUserId($user);
                $bookRepository->add($book, true);
            }
        }
        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }

    // Просмотр информации о книге 
    // Входные данные
    // userRepository - см документацию Symfony
    // book - (объект) конкретная книга, информацию о которой нужно отобразить
    // request - нужно для получения информации о залогинившемся пользователе
    // Выходные данные
    // Форма с информацией о конкретной книге
    #[Route('/book/{id}', name: 'app_book_show', methods: ['GET'])]
    public function show(Request $request, Book $book, UserRepository $userRepository): Response //показать одну книгу
    {
        // Получение пользователя из сессии
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
        $email = $session->get(Security::LAST_USERNAME) ?? null;
        $user = $userRepository->findOneByEmail($email);
    
        // Отображение инфорамции о книге
        return $this->render('book/show.html.twig', [
            'book' => $book,
            'user' => $user
        ]);
        
    }

    // Изменение информации о книге 
    // Входные данные
    // userRepository,bookRepository - см документацию Symfony
    // book - (объект) конкретная книга, информацию о которой нужно изменить
    // request - нужно для получения информации о залогинившемся пользователе
    // Выходные данные
    // Форма с информацией о конкретной книге, если получилось добавить книгу, или информация о всех книгах пользователя
    #[Route('/book/{id}/edit', name: 'app_book_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Book $book, BookRepository $bookRepository, UserRepository $userRepository): Response //редактировать книгу
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
        $email = $session->get(Security::LAST_USERNAME) ?? null;
        $user = $userRepository->findOneByEmail($email);
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $posterFile = $form->get('posterUrl')->getData();
            $bookFile = $form->get('pdfUrl')->getData();
            if($posterFile && $bookFile) {//постер книги и файл были загружены, добавляем данные о книге
                $posterFileName = pathinfo($posterFile->getClientOriginalName(), PATHINFO_FILENAME);
                $originalFilename = pathinfo($bookFile->getClientOriginalName(), PATHINFO_FILENAME);
                // подготавливаем данные к сохранению на сервере
                $bindedPosterFileName = $slugger->slug($posterFileName);
                $safeFilename = $slugger->slug($originalFilename);
                $cover_newFilename = $bindedPosterFileName.'-'.uniqid().'.'.$posterFile->guessExtension();
                $newFilename = $safeFilename.'-'.uniqid().'.'.$bookFile->guessExtension();
                try {
                    $posterFile->move(                     //сохраняем постер на сервере 
                        '..//public//files//posters',       
                        $cover_newFilename
                    );
                    $bookFile->move(
                        '..//public//files//books',       //сохраняем файл книги на сервере
                        $newFilename
                    );
                } catch (FileException $e) {
                    echo 'Error: '.$e->getMessage.'\n';
                }
                // добавляем оставшуюся информацию о книге
                $book->setAuthor($form->get('author')->getData());
                $book->setPoster('\\files\\posters\\'.$cover_newFilename);
                $book->setFile('\\files\\books\\'.$newFilename);
                $book->setLastReadDate(new DateTime());
                $book->setUserId($user);
                $bookRepository->add($book, true);
            }
            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }
    }
    // Удаление книги
    // Входные данные
    // bookRepository - см документацию Symfony
    // book - (объект) конкретная книга, которую нужно удалить
    // request - нужно для получения информации о залогинившемся пользователе
    // Выходные данные
    // Редирект на app_book_index
    #[Route('/book/{id}', name: 'app_book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, BookRepository $bookRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $book->getId(), $request->request->get('_token'))) {
            $bookRepository->remove($book, true);
        }

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }
}
