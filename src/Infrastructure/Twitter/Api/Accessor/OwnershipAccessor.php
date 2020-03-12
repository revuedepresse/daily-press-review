<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Api\AccessToken\TokenChangeInterface;
use App\Api\Entity\TokenInterface;
use App\Domain\Resource\MemberOwnerships;
use App\Domain\Resource\OwnershipCollection;
use App\Twitter\Api\ApiAccessorInterface;
use App\Twitter\Exception\OverCapacityException;
use App\Twitter\Exception\UnavailableResourceException;
use Psr\Log\LoggerInterface;

class OwnershipAccessor implements OwnershipAccessorInterface
{
    private ApiAccessorInterface $accessor;

    private TokenRepositoryInterface $tokenRepository;

    private TokenChangeInterface $tokenChange;

    private LoggerInterface $logger;

    public function __construct(
        ApiAccessorInterface $accessor,
        TokenRepositoryInterface $tokenRepository,
        TokenChangeInterface $tokenChange,
        LoggerInterface $logger
    )
    {
        $this->accessor = $accessor;
        $this->tokenRepository = $tokenRepository;
        $this->tokenChange = $tokenChange;
        $this->logger = $logger;
    }

    /**
     * @param string                $screenName
     * @param TokenInterface        $token
     *
     * @param MemberOwnerships|null $memberOwnership
     *
     * @return MemberOwnerships
     * @throws OverCapacityException
     */
    public function getOwnershipsForMemberHavingScreenNameAndToken(
        string $screenName,
        TokenInterface $token,
        MemberOwnerships $memberOwnership = null
    ): MemberOwnerships {
        $activeToken = $token;
        $totalUnfrozenToken = $this->tokenRepository->howManyUnfrozenTokenAreThere();
        $ownershipCollection = OwnershipCollection::fromArray([]);

        // Leave the loop as soon as there are some ownerships to process
        // or there is no token left to access the Twitter API
        while ($totalUnfrozenToken > 0 && $ownershipCollection->isEmpty()) {
            try {
                $nextPage = -1;
                if ($memberOwnership instanceof MemberOwnerships) {
                    $nextPage = $memberOwnership->ownershipCollection()->nextPage();
                }

                $ownershipCollection = $this->accessor->getMemberOwnerships(
                    $screenName,
                    $nextPage
                );
            } catch (UnavailableResourceException $exception) {
                $this->logger->info($exception->getMessage());
                if ($ownershipCollection->isEmpty()) {
                    $activeToken = $this->tokenChange->replaceAccessToken(
                        $token,
                        $this->accessor
                    );
                }

                $totalUnfrozenToken--;
            }
        }

        if ($ownershipCollection->isEmpty()) {
            throw new OverCapacityException(
                'Over capacity usage of all available tokens.'
            );
        }

        return MemberOwnerships::from($activeToken, $ownershipCollection);
    }
}