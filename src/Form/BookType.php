<?php

namespace App\Form;

use App\Entity\Book;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Название'
            ])
            ->add('author', TextType::class, [
                'label' => 'Автор'
            ])
            ->add('posterUrl', FileType::class, [
                'label' => 'Постер (img file)',
                'attr' => array('class' => 'class="form-control'),
                // неотображенное означает, что это поле не ассоциировано ни с одним свойством сущности
                'mapped' => false,

                // сделайте его необязательным, чтобы вам не нужно было повторно загружать PDF-файл
                // каждый раз, когда будете редактировать детали Product
                'required' => false,

                // неотображенные полля не могут определять свою валидацию используя аннотации
                // в ассоциированной сущности, поэтому вы можете использовать органичительные классы PHP
                'constraints' => [
                    new File([
                        'maxSize' => '20024k',
                        'mimeTypes' => [
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid jpeg/jpg/png document',
                    ])
                ],
            ])
            ->add('pdfUrl', FileType::class, [
                'label' => 'Файл книги (требуется PDF)',
                'attr' => array('class' => 'class="form-control', 'id' => 'inputGroupFile02'),

                // неотображенное означает, что это поле не ассоциировано ни с одним свойством сущности
                'mapped' => false,

                // сделайте его необязательным, чтобы вам не нужно было повторно загружать PDF-файл
                // каждый раз, когда будете редактировать детали Product
                'required' => false,

                // неотображенные полля не могут определять свою валидацию используя аннотации
                // в ассоциированной сущности, поэтому вы можете использовать органичительные классы PHP
                'constraints' => [
                    new File([
                        'maxSize' => '100024k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF document',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
