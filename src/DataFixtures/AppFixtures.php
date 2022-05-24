<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 21; $i++) {
            $user = new User();
            $user->setEmail('email'.$i.'@gmail.com');
            $user->setPassword(password_hash('password'.$i, PASSWORD_DEFAULT));
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);
            $random=rand(1, 20);
            for($j=0;$j<$random;$j++){
                $book = new Book();
                $book->setName('Увлекательная литература '.$j);
                $book->setAuthor('Очередной автор '.$j);
                $book->setPoster('..\server\img\pict.jpg');
                $book->setFile('..\server\files\CompSchemeMD.pdf');
                $book->setUserId($user);
                $manager->persist($book);
            }
        }
        $manager->flush();
    }
}
