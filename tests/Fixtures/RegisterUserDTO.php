<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Fixtures;

use Arbor\Validator\Attributes as V;

final readonly class RegisterUserDTO
{
    public function __construct(
        #[V\Required, V\FullName]
        public string $name,

        #[V\Required, V\Email]
        public string $email,

        #[V\Required, V\MinLength(8)]
        public string $password,

        #[V\Required, V\SameAs('password', message: 'A confirmação de senha não confere')]
        public string $passwordConfirmation,

        #[V\Required, V\Cpf]
        public string $cpf,

        #[V\Required, V\Phone(format: 'BR')]
        public string $phone,

        #[V\Optional, V\Cnpj(allowAlphanumeric: true)]
        public ?string $cnpj = null,

        #[V\Optional, V\Date(format: 'Y-m-d')]
        public ?\DateTimeImmutable $birthDate = null,

        #[V\Optional, V\Time(format: 'H:i')]
        public ?string $preferredContactTime = null,

        #[V\Optional, V\Domain]
        public ?string $websiteDomain = null,

        #[V\Optional, V\Url]
        public ?string $profileUrl = null,

        #[V\Optional, V\Uuid]
        public ?string $affiliateUuid = null,

        #[V\Optional, V\NoHtml]
        public ?string $bio = null,

        /** @var array<string, mixed>|null */
        #[V\Optional, V\UploadedFile(
            maxSize: '5MB',
            extensions: ['jpg', 'jpeg', 'png', 'webp'],
            mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
            allowNonUploadedFiles: true,
        )]
        public ?array $avatar = null,
    ) {
    }
}
