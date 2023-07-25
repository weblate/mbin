<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\DTO\EntryCommentResponseDto;
use App\DTO\EntryResponseDto;
use App\DTO\MagazineLogResponseDto;
use App\DTO\PostCommentResponseDto;
use App\DTO\PostResponseDto;
use App\DTO\ReportDto;
use App\DTO\ReportRequestDto;
use App\Entity\Client;
use App\Entity\Contracts\ContentInterface;
use App\Entity\Contracts\ReportInterface;
use App\Entity\Entry;
use App\Entity\EntryComment;
use App\Entity\Image;
use App\Entity\MagazineLog;
use App\Entity\OAuth2ClientAccess;
use App\Entity\Post;
use App\Entity\PostComment;
use App\Exception\SubjectHasBeenReportedException;
use App\Form\Constraint\ImageConstraint;
use App\Repository\Criteria;
use App\Repository\ImageRepository;
use App\Repository\OAuth2ClientAccessRepository;
use App\Schema\PaginationSchema;
use App\Service\IpResolver;
use App\Service\ReportManager;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseApi extends AbstractController
{
    public const MIN_PER_PAGE = 1;
    public const MAX_PER_PAGE = 100;
    public const DEPTH = 10;
    public const MIN_DEPTH = 0;
    public const MAX_DEPTH = 25;

    private static $constraint;

    public function __construct(
        protected readonly IpResolver $ipResolver,
        protected readonly LoggerInterface $logger,
        protected readonly SerializerInterface $serializer,
        protected readonly ValidatorInterface $validator,
        protected readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $request,
        private readonly ImageRepository $imageRepository,
        private readonly ReportManager $reportManager,
        private readonly OAuth2ClientAccessRepository $clientAccessRepository,
    ) {
    }

    /**
     * Rate limit an API request and return rate limit status headers.
     *
     * @param ?RateLimiterFactory $limiterFactory     A limiter factory to use when the user is authenticated
     * @param ?RateLimiterFactory $anonLimiterFactory A limiter factory to use when the user is anonymous
     *
     * @return array An array of headers describing the current rate limit status to the client
     *
     * @throws AccessDeniedHttpException    if the user is not authenticated and no anonymous rate limiter factory is provided, access to the resource will be denied
     * @throws TooManyRequestsHttpException If the limit is hit, rate limit the connection
     */
    protected function rateLimit(
        RateLimiterFactory $limiterFactory = null,
        RateLimiterFactory $anonLimiterFactory = null
    ): array {
        $this->logAccess();
        if (null === $limiterFactory && null === $anonLimiterFactory) {
            throw new \LogicException('No rate limiter factory provided!');
        }
        $limiter = null;
        if (
            $limiterFactory && $this->isGranted('ROLE_USER')
        ) {
            $limiter = $limiterFactory->create($this->getUserOrThrow()->getUserIdentifier());
        } elseif ($anonLimiterFactory) {
            $limiter = $anonLimiterFactory->create($this->ipResolver->resolve());
        } else {
            // non-API_USER without an anonymous rate limiter? Not allowed.
            throw new AccessDeniedHttpException();
        }
        $limit = $limiter->consume();

        $headers = [
            'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
            'X-RateLimit-Retry-After' => $limit->getRetryAfter()->getTimestamp(),
            'X-RateLimit-Limit' => $limit->getLimit(),
        ];

        if (false === $limit->isAccepted()) {
            throw new TooManyRequestsHttpException(headers: $headers);
        }

        return $headers;
    }

    /**
     * Logs timestamp, client, and route name of authenticated API access for admin
     * to track how API clients are being (ab)used and for stat creation.
     *
     * This might be better to have as a cache entry, with an aggregate in the database
     * created periodically
     */
    private function logAccess()
    {
        /** @var ?OAuth2Token $token */
        $token = $this->container->get('security.token_storage')->getToken();
        if (null !== $token && $token instanceof OAuth2Token) {
            $clientId = $token->getOAuthClientId();
            /** @var Client $client */
            $client = $this->entityManager->getReference(Client::class, $clientId);
            $access = new OAuth2ClientAccess();
            $access->setClient($client);
            $access->setCreatedAt(new \DateTimeImmutable());
            $access->setPath($this->request->getCurrentRequest()->get('_route'));
            $this->clientAccessRepository->save($access, flush: true);
        }
    }

    public function serializePaginated(array $serializedItems, Pagerfanta $pagerfanta): array
    {
        return [
            'items' => $serializedItems,
            'pagination' => (new PaginationSchema($pagerfanta))->jsonSerialize(),
        ];
    }

    public function serializeContentInterface(ContentInterface $content): mixed
    {
        $toReturn = null;
        $className = $this->entityManager->getClassMetadata(get_class($content))->rootEntityName;
        switch ($className) {
            case Entry::class:
                /**
                 * @var Entry $content
                 */
                $toReturn = (new EntryResponseDto($content))->jsonSerialize();
                $toReturn['type'] = 'entry';
                break;
            case EntryComment::class:
                /**
                 * @var EntryComment $content
                 */
                $toReturn = (new EntryCommentResponseDto($content))->jsonSerialize();
                $toReturn['type'] = 'entry_comment';
                break;
            case Post::class:
                /**
                 * @var Post $content
                 */
                $toReturn = (new PostResponseDto($content))->jsonSerialize();
                $toReturn['type'] = 'post';
                break;
            case PostComment::class:
                /**
                 * @var PostComment $content
                 */
                $toReturn = (new PostCommentResponseDto($content))->jsonSerialize();
                $toReturn['type'] = 'post_comment';
                break;
            default:
                throw new \LogicException('Invalid contentInterface classname "'.$className.'"');
        }

        return $toReturn;
    }

    /**
     * Serialize a single log item to JSON.
     *
     * @return array An associative array representation of the items's safe fields, to be used as JSON
     */
    protected function serializeLogItem(MagazineLog $log)
    {
        $response = new MagazineLogResponseDto($log);

        return $response->jsonSerialize();
    }

    public static function constrainPerPage(mixed $value, int $min = self::MIN_PER_PAGE, int $max = self::MAX_PER_PAGE): int
    {
        return min(max(intval($value), $min), $max);
    }

    /**
     * Alias for constrainPerPage with different defaults.
     */
    public static function constrainDepth(mixed $value, int $min = self::MIN_DEPTH, int $max = self::MAX_DEPTH): int
    {
        return self::constrainPerPage($value, $min, $max);
    }

    public function handleLanguageCriteria(Criteria $criteria): void
    {
        $usePreferred = filter_var($this->request->getCurrentRequest()->get('usePreferredLangs', false), FILTER_VALIDATE_BOOL);

        if ($usePreferred && null === $this->getUser()) {
            // Debating between AccessDenied and BadRequest exceptions for this
            throw new AccessDeniedHttpException('You must be logged in to use your preferred languages');
        }

        $languages = $usePreferred ? $this->getUserOrThrow()->preferredLanguages : $this->request->getCurrentRequest()->get('lang');
        if (null !== $languages) {
            if (is_string($languages)) {
                $languages = explode(',', $languages);
            }

            $criteria->languages = $languages;
        }
    }

    public function handleUploadedImage(): Image
    {
        try {
            /**
             * @var UploadedFile $uploaded
             */
            $uploaded = $this->request->getCurrentRequest()->files->get('uploadImage');

            if (null === self::$constraint) {
                self::$constraint = ImageConstraint::default();
            }

            if (null === $uploaded) {
                throw new BadRequestHttpException('Uploaded file not found!');
            }

            if (self::$constraint->maxSize < $uploaded->getSize()) {
                throw new BadRequestHttpException('File cannot exceed '.(string) self::$constraint->maxSize.' bytes');
            }

            if (false === array_search($uploaded->getMimeType(), self::$constraint->mimeTypes)) {
                throw new BadRequestHttpException('Mimetype of "'.$uploaded->getMimeType().'" not allowed!');
            }

            $image = $this->imageRepository->findOrCreateFromUpload($uploaded);

            if (null === $image) {
                throw new BadRequestHttpException('Failed to create file');
            }

            $image->altText = $this->request->getCurrentRequest()->get('alt', null);
        } catch (\Exception $e) {
            if (null !== $uploaded && file_exists($uploaded->getPathname())) {
                unlink($uploaded->getPathname());
            }
            throw $e;
        }

        return $image;
    }

    protected function reportContent(ReportInterface $reportable): void
    {
        /** @var ReportRequestDto $dto */
        $dto = $this->serializer->deserialize($this->request->getCurrentRequest()->getContent(), ReportRequestDto::class, 'json');

        $errors = $this->validator->validate($dto);
        if (0 < count($errors)) {
            throw new BadRequestHttpException((string) $errors);
        }

        $reportDto = (new ReportDto())->create($reportable, $dto->reason);

        try {
            $this->reportManager->report($reportDto, $this->getUserOrThrow());
        } catch (SubjectHasBeenReportedException $e) {
            // Do nothing
        }
    }
}
